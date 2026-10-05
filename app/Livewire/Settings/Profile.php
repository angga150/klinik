<?php

namespace App\Livewire\Settings;

use App\Livewire\Page;
use App\Support\Audit;
use Illuminate\Support\Facades\Validator;

class Profile extends Page
{
    public array $form = [];

    public array $clinicForm = [];

    public function mount(): void
    {
        $this->form = ['name' => auth()->user()->name];
        if (auth()->user()->can('settings.manage')) {
            $this->clinicForm = auth()->user()->clinic->only(['name', 'address', 'phone']);
        }
    }

    public function save(): void
    {
        $d = Validator::make($this->form, ['name' => 'required|string|max:150', 'current_password' => 'required|current_password', 'password' => 'nullable|string|min:12|confirmed'])->validate();
        $u = auth()->user();
        $u->name = $d['name'];
        if (filled($d['password'] ?? null)) {
            $u->password = $d['password'];
        }$u->save();
        $this->form = ['name' => $u->name];
        Audit::record($u, 'profile.updated', $u);
        $this->success();
    }

    public function saveClinic(): void
    {
        $this->allowed('settings.manage');
        $d = Validator::make($this->clinicForm, ['name' => 'required|string|max:150', 'address' => 'nullable|string|max:1000', 'phone' => 'nullable|string|max:30'])->validate();
        $c = auth()->user()->clinic;
        $c->update($d);
        Audit::record(auth()->user(), 'clinic.updated', $c);
        $this->success();
    }

    public function render()
    {
        return view('livewire.settings.profile')->title('Profil & klinik');
    }
}
