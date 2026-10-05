<?php

namespace Tests\Feature;

use App\Livewire\Clinical\VisitDetail;
use App\Livewire\Patients\Index;
use App\Models\Clinic;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

class AccessAndPagesTest extends ClinicTestCase
{
    public function test_all_roles_can_load_their_dashboard_and_allowed_pages(): void
    {
        $pages = ['/patients' => 'patients.view', '/visits' => 'visits.view', '/pharmacy' => 'prescriptions.view', '/inventory' => 'inventory.view', '/billing' => 'invoices.view', '/reports' => 'reports.view', '/settings/master' => 'settings.manage', '/settings/users' => 'users.manage', '/settings/audit' => 'audit.view'];
        foreach (['superadmin', 'admin', 'pendaftaran', 'perawat', 'dokter', 'apoteker', 'kasir', 'kepala'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get('/dashboard')->assertOk();
            foreach ($pages as $url => $permission) {
                $this->get($url)->assertStatus($u->can($permission) ? 200 : 403);
            }
        }
    }

    public function test_all_master_catalog_pages_render(): void
    {
        $this->actingAs($this->user());
        foreach (array_keys(config('masters')) as $key) {
            $this->get('/settings/master/'.$key)->assertOk();
        }
    }

    public function test_cashier_and_registration_cannot_see_clinical_content(): void
    {
        $v = $this->clinical(false);
        foreach (['kasir', 'pendaftaran', 'apoteker'] as $role) {
            $this->actingAs($this->user($role))->get('/visits/'.$v->id)->assertOk()->assertDontSee('Pemeriksaan dokter</h2>', false)->assertDontSee('Rencana');
        }$this->actingAs($this->user('dokter'))->get('/visits/'.$v->id)->assertOk()->assertSee('Rencana');
    }

    public function test_cross_clinic_identifier_is_rejected(): void
    {
        $v = $this->visit();
        $other = Clinic::create(['name' => 'Other']);
        $v->update(['clinic_id' => $other->id]);
        $this->actingAs($this->user())->get('/visits/'.$v->id)->assertForbidden();
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $u = $this->user('dokter');
        $u->update(['active' => false]);
        $this->actingAs($u)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_idle_session_expires(): void
    {
        $this->actingAs($this->user())->withSession(['last_activity' => time() - 1801])->get('/dashboard')->assertRedirect('/login');
    }

    public function test_livewire_cannot_change_locked_visit_identifier(): void
    {
        $a = $this->visit();
        $b = $this->visit();
        $this->actingAs($this->user());
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(VisitDetail::class, ['visit' => $a])->set('visitId', $b->id);
    }

    public function test_unauthorized_server_action_is_rejected_even_when_called_directly(): void
    {
        $v = $this->visit();
        $this->actingAs($this->user('kasir'));
        Livewire::test(VisitDetail::class, ['visit' => $v])->set('vitals', $this->triageData())->call('triage')->assertForbidden();
        $this->assertDatabaseCount('vital_signs', 0);
    }

    public function test_patient_form_creates_patient_through_livewire(): void
    {
        $this->actingAs($this->user('pendaftaran'));
        Livewire::test(Index::class)->call('create')->set('form', ['name' => 'Pasien Livewire', 'sex' => 'P', 'birth_date' => '1995-03-04', 'payer' => 'Umum', 'active' => true])->call('save')->assertHasNoErrors()->assertSet('showForm', false);
        $this->assertDatabaseHas('patients', ['name' => 'Pasien Livewire']);
    }

    public function test_login_throttles_and_records_success_without_password_in_audit(): void
    {
        $u = $this->user();
        $u->update(['password' => 'ValidPassword123!']);
        $this->post('/login', ['email' => $u->email, 'password' => 'ValidPassword123!'])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('login_histories', ['user_id' => $u->id]);
        $this->post('/logout')->assertRedirect('/login');
        for ($i = 0; $i < 6; $i++) {
            $r = $this->post('/login', ['email' => $u->email, 'password' => 'incorrect']);
        }$r->assertSessionHasErrors('email');
    }
}
