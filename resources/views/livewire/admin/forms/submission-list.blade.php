@section('page-title', 'Submissions: ' . $form->name)

<div>
    @if (session()->has('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    <!-- Back Link -->
    <div class="mb-6">
        <a href="{{ route('admin.forms.index') }}"
           class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Forms
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Submissions</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Unread</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['unread'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Spam</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['spam'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4 flex-1">
            <div class="flex-1 max-w-md">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search submissions..."
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
            </div>

            <select wire:model.live="filterStatus"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                <option value="all">All Submissions</option>
                <option value="unread">Unread Only</option>
                <option value="read">Read Only</option>
                <option value="spam">Spam</option>
            </select>
        </div>

        <button wire:click="exportToCsv"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export to CSV
        </button>
    </div>

    <!-- Submissions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($submissions->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Preview
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            IP Address
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Submitted
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($submissions as $submission)
                        <tr class="hover:bg-gray-50 {{ !$submission->is_read && !$submission->is_spam ? 'bg-blue-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                #{{ $submission->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($submission->is_spam)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Spam
                                    </span>
                                @elseif($submission->is_read)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Read
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Unread
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-md">
                                    @php
                                        $formattedData = $submission->getFormattedData();
                                        $preview = collect($formattedData)->take(2)->map(function($item) {
                                            return $item['label'] . ': ' . Str::limit($item['value'], 30);
                                        })->implode(' | ');
                                    @endphp
                                    <p class="truncate">{{ $preview }}</p>
                                    @if(count($formattedData) > 2)
                                        <p class="text-xs text-gray-500 mt-1">+ {{ count($formattedData) - 2 }} more fields</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $submission->ip_address ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ $submission->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $submission->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button type="button"
                                        onclick="openSubmissionModal({{ $submission->id }}, {{ json_encode($submission->getFormattedData()) }})"
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    View
                                </button>

                                @if(!$submission->is_read && !$submission->is_spam)
                                    <button wire:click="markAsRead({{ $submission->id }})"
                                            class="text-green-600 hover:text-green-900 mr-3">
                                        Mark Read
                                    </button>
                                @endif

                                @if($submission->is_read && !$submission->is_spam)
                                    <button wire:click="markAsUnread({{ $submission->id }})"
                                            class="text-orange-600 hover:text-orange-900 mr-3">
                                        Mark Unread
                                    </button>
                                @endif

                                @if(!$submission->is_spam)
                                    <button wire:click="markAsSpam({{ $submission->id }})"
                                            class="text-yellow-600 hover:text-yellow-900 mr-3">
                                        Spam
                                    </button>
                                @endif

                                <button wire:click="deleteSubmission({{ $submission->id }})"
                                        wire:confirm="Are you sure you want to delete this submission?"
                                        class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4 border-t border-gray-200">
                {{ $submissions->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No submissions</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if($filterStatus === 'all')
                        This form hasn't received any submissions yet.
                    @else
                        No {{ $filterStatus }} submissions found.
                    @endif
                </p>
            </div>
        @endif
    </div>

    <!-- Submission Detail Modal -->
    <div id="submissionModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeSubmissionModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button type="button" onclick="closeSubmissionModal()" class="bg-white rounded-md text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                            Submission Details
                        </h3>
                        <div id="submissionContent" class="mt-4 space-y-3">
                            <!-- Content will be dynamically inserted here -->
                        </div>
                    </div>
                </div>

                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="closeSubmissionModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openSubmissionModal(id, data) {
    const modal = document.getElementById('submissionModal');
    const content = document.getElementById('submissionContent');

    let html = '<div class="bg-gray-50 rounded-lg p-4">';
    data.forEach(item => {
        html += `
            <div class="py-3 border-b border-gray-200 last:border-0">
                <dt class="text-sm font-medium text-gray-500 mb-1">${item.label}</dt>
                <dd class="text-sm text-gray-900">${item.value || '<span class="text-gray-400">N/A</span>'}</dd>
            </div>
        `;
    });
    html += '</div>';

    content.innerHTML = html;
    modal.classList.remove('hidden');
}

function closeSubmissionModal() {
    const modal = document.getElementById('submissionModal');
    modal.classList.add('hidden');
}
</script>
@endpush
