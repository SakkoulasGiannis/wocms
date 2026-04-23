<?php

namespace App\Livewire\Admin\Agents;

use App\Models\Agent;
use Livewire\Component;

class AgentList extends Component
{
    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatedSearch(): void
    {
        // No pagination reset needed (no pagination) — retained for future use.
    }

    public function toggleActive(int $id): void
    {
        $agent = Agent::findOrFail($id);
        $agent->toggleActive();
        session()->flash('success', "Agent '{$agent->name}' status updated.");
    }

    public function delete(int $id): void
    {
        $agent = Agent::findOrFail($id);
        $name = $agent->name;
        $agent->clearMediaCollection('photo');
        $agent->delete();
        session()->flash('success', "Agent '{$name}' deleted successfully.");
    }

    /**
     * Persist new order after drag-and-drop.
     *
     * @param  array<int, int>  $orderedIds  Array of agent IDs in new order
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            Agent::where('id', $id)->update(['order' => $position]);
        }
        session()->flash('success', 'Order updated.');
    }

    public function render()
    {
        $agents = Agent::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('role', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->ordered()
            ->get();

        return view('livewire.admin.agents.agent-list', [
            'agents' => $agents,
        ])
            ->layout('layouts.admin-clean')
            ->title('Our Staff');
    }
}
