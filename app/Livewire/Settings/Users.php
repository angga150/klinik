<?php

namespace App\Livewire\Settings;

use App\Livewire\Page;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;

class Users extends Page
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public array $form = [];

    #[Locked]
    public ?int $editing = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->allowed('users.manage');
        $this->editing = null;
        $this->form = ['active' => true, 'role' => 'Petugas Pendaftaran'];
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->allowed('users.manage');
        $u = User::where('clinic_id', $this->clinic())->findOrFail($id);
        $this->editing = $id;
        $this->form = $u->only(['name', 'email', 'active']);
        $this->form['role'] = $u->getRoleNames()->first();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->allowed('users.manage');
        $d = Validator::make($this->form, ['name' => 'required|string|max:150', 'email' => ['required', 'email', Rule::unique('users')->ignore($this->editing)], 'password' => ($this->editing ? 'nullable' : 'required').'|string|min:12|max:200', 'active' => 'required|boolean', 'role' => ['required', Rule::in(array_keys(config('clinic.roles')))]])->validate();
        if ($this->editing === auth()->id() && (! $d['active'] || $d['role'] !== 'Super Admin')) {
            $this->addError('user', 'Akun administrator yang sedang digunakan harus tetap aktif sebagai Super Admin.');

            return;
        }
        DB::transaction(function () use ($d) {
            $u = $this->editing ? User::where('clinic_id', $this->clinic())->findOrFail($this->editing) : new User;
            $fields = collect($d)->except(['role'])->all();
            if (empty($fields['password'])) {
                unset($fields['password']);
            }$u->fill($fields + ['clinic_id' => $this->clinic()])->save();
            $u->syncRoles([$d['role']]);
            if (! $u->active) {
                DB::table('sessions')->where('user_id', $u->id)->delete();
            }Audit::record(auth()->user(), 'user.saved', $u);
        });
        $this->showForm = false;
        $this->form = [];
        $this->success();
    }

    public function render()
    {
        $this->allowed('users.manage');

        return view('livewire.settings.users', ['rows' => User::where('clinic_id', $this->clinic())->with('roles')->where('name', 'like', '%'.$this->search.'%')->paginate(15), 'roles' => config('clinic.roles')])->title('Pengguna & hak akses');
    }
}
