<?php

namespace App\Livewire\Admin\ContentTree;

use App\Models\ContentNode;
use Livewire\Component;

class TreeViewer extends Component
{
    public $expandedNodes = [];

    public $loadedNodes = []; // Track which nodes have their children loaded

    public $selectedNodeId = null; // Currently selected node for section builder

    public function mount()
    {
        // Don't auto-expand root nodes anymore - let user expand on demand
        // But mark root nodes as loaded since we fetch them immediately
        $rootNodes = ContentNode::whereNull('parent_id')->pluck('id')->toArray();
        $this->loadedNodes = $rootNodes;
    }

    public function toggleNode($nodeId)
    {
        if (in_array($nodeId, $this->expandedNodes)) {
            // Collapse node
            $this->expandedNodes = array_diff($this->expandedNodes, [$nodeId]);
        } else {
            // Expand node
            $this->expandedNodes[] = $nodeId;

            // Mark children as loaded if not already
            // Support both template_X and regular node IDs
            if (! in_array($nodeId, $this->loadedNodes)) {
                $this->loadedNodes[] = $nodeId;
            }
        }
    }

    public function selectNode($nodeId)
    {
        $this->selectedNodeId = $nodeId;

        // Dispatch event to notify section builder
        $this->dispatch('node-selected', nodeId: $nodeId);
    }

    public function deleteNode($nodeId)
    {
        $node = ContentNode::findOrFail($nodeId);

        // Delete associated content if exists
        if ($node->content_type && $node->content_id) {
            $content = $node->getContentModel();
            if ($content) {
                $content->delete();
            }
        }

        // Delete the node (children will cascade)
        $node->delete();

        // Clear selection if deleted node was selected
        if ($this->selectedNodeId === $nodeId) {
            $this->selectedNodeId = null;
        }

        session()->flash('success', 'Node deleted successfully!');
    }

    public function getNodeChildren($nodeId)
    {
        // AJAX method to load children on demand
        $node = ContentNode::find($nodeId);

        if (! $node) {
            return [];
        }

        return $node->children()
            ->with('template')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    public function render()
    {
        // Load all active templates with custom ordering: Home first, Page second, rest alphabetically
        $templates = \App\Models\Template::where('is_active', true)
            ->get()
            ->sortBy(function ($template) {
                // Home = 0 (first)
                if (strtolower($template->slug) === 'home') {
                    return '0_home';
                }
                // Page = 1 (second)
                if (strtolower($template->slug) === 'page') {
                    return '1_page';
                }

                // Everything else alphabetically after
                return '2_'.strtolower($template->name);
            })
            ->values();

        return view('livewire.admin.content-tree.tree-viewer', [
            'templates' => $templates,
        ])->layout('layouts.admin-clean');
    }
}
