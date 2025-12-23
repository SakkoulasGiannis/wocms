@section('page-title', 'Forms')

<div>
    @if (session()->has('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <!-- Actions Bar -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex-1 max-w-md">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search forms..."
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
        </div>
        <a href="{{ route('admin.forms.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Form
        </a>
    </div>

    <!-- Forms Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($forms->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Slug
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Submissions
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Created
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($forms as $form)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $form->name }}</div>
                                @if($form->description)
                                    <div class="text-sm text-gray-500">{{ Str::limit($form->description, 60) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <code class="bg-gray-100 px-2 py-1 rounded">{{ $form->slug }}</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="toggleActive({{ $form->id }})"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $form->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $form->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($form->submissions_count > 0)
                                    <a href="{{ route('admin.forms.submissions', $form->id) }}"
                                       class="text-blue-600 hover:text-blue-900">
                                        {{ $form->submissions_count }} {{ Str::plural('submission', $form->submissions_count) }}
                                    </a>
                                @else
                                    <span class="text-gray-400">No submissions</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $form->created_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.forms.edit', $form->id) }}"
                                   class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>

                                @if($form->submissions_count > 0)
                                    <a href="{{ route('admin.forms.submissions', $form->id) }}"
                                       class="text-green-600 hover:text-green-900 mr-3">
                                        Submissions
                                    </a>
                                @endif

                                <button wire:click="duplicateForm({{ $form->id }})"
                                        class="text-purple-600 hover:text-purple-900 mr-3">
                                    Duplicate
                                </button>

                                <button wire:click="deleteForm({{ $form->id }})"
                                        wire:confirm="Are you sure you want to delete this form? This will also delete all fields and submissions."
                                        class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4 border-t border-gray-200">
                {{ $forms->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No forms</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new form.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.forms.create') }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Form
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
