<?php

namespace App\Livewire\Admin\Agents;

use App\Models\Agent;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class AgentForm extends Component
{
    use WithFileUploads;

    public ?int $agentId = null;

    public string $name = '';

    public string $slug = '';

    public string $role = '';

    public string $email = '';

    public string $phone = '';

    public string $bio = '';

    public string $facebook = '';

    public string $instagram = '';

    public string $linkedin = '';

    public string $twitter = '';

    public bool $isActive = true;

    public int $order = 0;

    public $newPhoto = null;

    public ?string $photoUrl = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:agents,slug,'.$this->agentId,
            'role' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'bio' => 'nullable|string',
            'facebook' => 'nullable|string|max:500',
            'instagram' => 'nullable|string|max:500',
            'linkedin' => 'nullable|string|max:500',
            'twitter' => 'nullable|string|max:500',
            'isActive' => 'boolean',
            'order' => 'integer|min:0',
            'newPhoto' => 'nullable|image|max:10240',
        ];
    }

    public function mount(?int $agentId = null): void
    {
        $this->agentId = $agentId;

        if ($this->agentId) {
            $this->loadAgent();
        } else {
            // New agent: put at the end of the list
            $this->order = (int) (Agent::max('order') ?? 0) + 1;
        }
    }

    public function loadAgent(): void
    {
        $agent = Agent::findOrFail($this->agentId);

        $this->name = $agent->name;
        $this->slug = $agent->slug;
        $this->role = $agent->role ?? '';
        $this->email = $agent->email ?? '';
        $this->phone = $agent->phone ?? '';
        $this->bio = $agent->bio ?? '';
        $this->facebook = $agent->facebook ?? '';
        $this->instagram = $agent->instagram ?? '';
        $this->linkedin = $agent->linkedin ?? '';
        $this->twitter = $agent->twitter ?? '';
        $this->isActive = (bool) $agent->active;
        $this->order = (int) $agent->order;
        $this->photoUrl = $agent->getThumbUrl();
    }

    public function updatedName(): void
    {
        if (! $this->agentId && empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }
    }

    /**
     * Auto-save the photo immediately when uploaded (mirrors SliderForm behavior).
     */
    public function updatedNewPhoto(): void
    {
        if (! $this->newPhoto) {
            return;
        }

        $this->validate([
            'newPhoto' => 'image|max:10240',
        ]);

        // We can only save to Spatie Media Library if the agent already exists.
        // If not, the preview still updates — final save happens on save().
        if ($this->agentId) {
            $agent = Agent::find($this->agentId);
            if ($agent) {
                $agent->clearMediaCollection('photo');
                $agent->addMedia($this->newPhoto->getRealPath())
                    ->usingFileName($this->newPhoto->getClientOriginalName())
                    ->toMediaCollection('photo');

                $agent->refresh();
                $this->photoUrl = $agent->getThumbUrl();
                $this->newPhoto = null;

                session()->flash('success', 'Photo uploaded successfully.');
                $this->dispatch('notify', message: 'Photo saved!', type: 'success');

                return;
            }
        }

        // No agent yet — just update preview for the user using the temporary upload.
        try {
            $this->photoUrl = $this->newPhoto->temporaryUrl();
        } catch (\Throwable $e) {
            // Some storage drivers do not support temporary URLs; leave as-is.
        }
    }

    public function removePhoto(): void
    {
        if ($this->agentId) {
            $agent = Agent::find($this->agentId);
            if ($agent) {
                $agent->clearMediaCollection('photo');
            }
        }
        $this->newPhoto = null;
        $this->photoUrl = null;
        session()->flash('success', 'Photo removed.');
    }

    public function save(): void
    {
        $this->validate();

        // Ensure slug stays unique and filled.
        if (empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }

        $agent = Agent::updateOrCreate(
            ['id' => $this->agentId],
            [
                'name' => $this->name,
                'slug' => $this->slug,
                'role' => $this->role ?: null,
                'email' => $this->email ?: null,
                'phone' => $this->phone ?: null,
                'bio' => $this->bio ?: null,
                'facebook' => $this->facebook ?: null,
                'instagram' => $this->instagram ?: null,
                'linkedin' => $this->linkedin ?: null,
                'twitter' => $this->twitter ?: null,
                'active' => $this->isActive,
                'order' => $this->order,
            ]
        );

        $isNew = ! $this->agentId;
        $this->agentId = $agent->id;

        // Save photo if it was uploaded on a new agent (where auto-save couldn't run).
        if ($this->newPhoto) {
            $agent->clearMediaCollection('photo');
            $agent->addMedia($this->newPhoto->getRealPath())
                ->usingFileName($this->newPhoto->getClientOriginalName())
                ->toMediaCollection('photo');
            $this->newPhoto = null;
        }

        $this->loadAgent();

        session()->flash('success', $isNew ? 'Agent created successfully.' : 'Agent updated successfully.');

        if ($isNew) {
            $this->redirect(route('admin.agents.edit', $agent->id), navigate: false);
        }
    }

    public function render()
    {
        return view('livewire.admin.agents.agent-form')
            ->layout('layouts.admin-clean')
            ->title($this->agentId ? 'Edit Agent' : 'Add Agent');
    }
}
