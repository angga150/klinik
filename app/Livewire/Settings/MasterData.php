<?php

namespace App\Livewire\Settings;

use App\Actions\Identity\SaveMaster;
use App\Livewire\Page;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class MasterData extends Page
{
    use WithPagination;

    #[Locked]
    public string $resource;

    #[Locked]
    public ?int $editing = null;

    #[Url]
    public string $search = '';

    public array $form = [];

    public bool $showForm = false;

    public function mount(string $resource = 'polyclinics'): void
    {
        $this->allowed('settings.manage');
        abort_unless(config('masters.'.$resource), 404);
        $this->resource = $resource;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->allowed('settings.manage');
        $this->editing = null;
        $this->form = ['active' => true];
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        $this->allowed('settings.manage');
        $c = config('masters.'.$this->resource);
        $r = $c['model']::forClinic($this->clinic())->findOrFail($id);
        $this->editing = $id;
        $this->form = $r->only(array_merge(array_keys($c['fields']), ['active']));
        foreach (['starts_at', 'ends_at'] as $time) {
            if (isset($this->form[$time])) {
                $this->form[$time] = substr($this->form[$time], 0, 5);
            }
        }$this->showForm = true;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        app(SaveMaster::class)->execute(auth()->user(), $this->resource, $this->form, $this->editing);
        $this->showForm = false;
        $this->success();
    }

    public function render()
    {
        $this->allowed('settings.manage');
        $c = config('masters.'.$this->resource);
        $q = $c['model']::forClinic($this->clinic());
        if (isset($c['fields']['name'])) {
            $q->where('name', 'like', '%'.$this->search.'%');
        }
        $options = [];
        foreach ($c['fields'] as $key => $f) {
            $class = 'App\\Models\\'.$f['type'];
            if (class_exists($class)) {
                $options[$key] = $class::where('clinic_id', $this->clinic())->where('active', true)->orderBy('name')->get(['id', 'name']);
            }
        }

        return view('livewire.settings.master-data', ['catalog' => $c, 'rows' => $q->latest('id')->paginate(15), 'options' => $options])->title($c['title']);
    }
}
