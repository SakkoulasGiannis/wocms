<?php

namespace App\Livewire\Admin\Forms;

use App\Models\Form;
use App\Models\FormSubmission;
use Livewire\Component;
use Livewire\WithPagination;

class SubmissionList extends Component
{
    use WithPagination;

    public $formId;
    public $form;
    public $search = '';
    public $filterStatus = 'all'; // all, read, unread, spam

    public function mount($formId)
    {
        $this->formId = $formId;
        $this->form = Form::findOrFail($formId);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function markAsRead($id)
    {
        $submission = FormSubmission::findOrFail($id);
        $submission->markAsRead();

        session()->flash('success', 'Submission marked as read.');
    }

    public function markAsUnread($id)
    {
        $submission = FormSubmission::findOrFail($id);
        $submission->update(['is_read' => false]);

        session()->flash('success', 'Submission marked as unread.');
    }

    public function markAsSpam($id)
    {
        $submission = FormSubmission::findOrFail($id);
        $submission->markAsSpam();

        session()->flash('success', 'Submission marked as spam.');
    }

    public function deleteSubmission($id)
    {
        $submission = FormSubmission::findOrFail($id);
        $submission->delete();

        session()->flash('success', 'Submission deleted successfully!');
    }

    public function exportToCsv()
    {
        $submissions = FormSubmission::where('form_id', $this->formId)
            ->when($this->filterStatus === 'read', fn($q) => $q->where('is_read', true))
            ->when($this->filterStatus === 'unread', fn($q) => $q->where('is_read', false))
            ->when($this->filterStatus === 'spam', fn($q) => $q->where('is_spam', true))
            ->get();

        if ($submissions->isEmpty()) {
            session()->flash('error', 'No submissions to export.');
            return;
        }

        // Get all field names from the first submission
        $headers = ['ID', 'Submitted At', 'IP Address', 'Status'];
        $firstSubmission = $submissions->first();
        if ($firstSubmission && $firstSubmission->data) {
            $headers = array_merge($headers, array_keys($firstSubmission->data));
        }

        // Create CSV content
        $csvContent = implode(',', array_map(function($header) {
            return '"' . str_replace('"', '""', $header) . '"';
        }, $headers)) . "\n";

        foreach ($submissions as $submission) {
            $row = [
                $submission->id,
                $submission->created_at->format('Y-m-d H:i:s'),
                $submission->ip_address ?? '',
                $submission->is_spam ? 'Spam' : ($submission->is_read ? 'Read' : 'Unread'),
            ];

            // Add data fields
            foreach (array_slice($headers, 4) as $fieldName) {
                $value = $submission->data[$fieldName] ?? '';
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $row[] = $value;
            }

            $csvContent .= implode(',', array_map(function($cell) {
                return '"' . str_replace('"', '""', $cell) . '"';
            }, $row)) . "\n";
        }

        // Return download response
        return response()->streamDownload(function() use ($csvContent) {
            echo $csvContent;
        }, 'submissions-' . $this->form->slug . '-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        $submissions = FormSubmission::where('form_id', $this->formId)
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('data', 'like', '%' . $this->search . '%')
                        ->orWhere('ip_address', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus === 'read', fn($q) => $q->where('is_read', true))
            ->when($this->filterStatus === 'unread', fn($q) => $q->where('is_read', false))
            ->when($this->filterStatus === 'spam', fn($q) => $q->where('is_spam', true))
            ->when($this->filterStatus === 'all', fn($q) => $q->where('is_spam', false))
            ->latest()
            ->paginate(15);

        $stats = [
            'total' => FormSubmission::where('form_id', $this->formId)->where('is_spam', false)->count(),
            'unread' => FormSubmission::where('form_id', $this->formId)->where('is_read', false)->where('is_spam', false)->count(),
            'spam' => FormSubmission::where('form_id', $this->formId)->where('is_spam', true)->count(),
        ];

        return view('livewire.admin.forms.submission-list', [
            'submissions' => $submissions,
            'stats' => $stats,
        ])->layout('layouts.admin-clean');
    }
}
