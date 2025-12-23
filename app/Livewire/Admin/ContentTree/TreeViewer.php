<?php

namespace App\Livewire\Admin\ContentTree;

use App\Models\ContentNode;
use Livewire\Component;

class TreeViewer extends Component
{
    public $expandedNodes = [];

    public function mount()
    {
        // Auto-expand root nodes
        $rootNodes = ContentNode::whereNull('parent_id')->pluck('id')->toArray();
        $this->expandedNodes = $rootNodes;
    }

    public function toggleNode($nodeId)
    {
        if (in_array($nodeId, $this->expandedNodes)) {
            $this->expandedNodes = array_diff($this->expandedNodes, [$nodeId]);
        } else {
            $this->expandedNodes[] = $nodeId;
        }
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

        session()->flash('success', 'Node deleted successfully!');
    }

    public function render()
    {
        $rootNodes = ContentNode::whereNull('parent_id')
            ->with(['template', 'children'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('livewire.admin.content-tree.tree-viewer', [
            'rootNodes' => $rootNodes,
        ])->layout('layouts.admin-clean');
    }
}
