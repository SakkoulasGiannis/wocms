@extends('layouts.admin-clean')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="px-4 sm:px-0">
    <!-- Quick Links -->
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('admin.templates.index') }}" class="block rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 p-6 shadow-lg hover:shadow-xl transition transform hover:scale-105">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-100">Templates</p>
                    <p class="text-2xl font-bold text-white">{{ $stats['total_templates'] }}</p>
                </div>
                <svg class="w-10 h-10 text-purple-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.content-tree') }}" class="block rounded-lg bg-gradient-to-br from-green-500 to-green-600 p-6 shadow-lg hover:shadow-xl transition transform hover:scale-105">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-100">Content Tree</p>
                    <p class="text-2xl font-bold text-white">Browse</p>
                </div>
                <svg class="w-10 h-10 text-green-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.settings') }}" class="block rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 p-6 shadow-lg hover:shadow-xl transition transform hover:scale-105">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-100">Settings</p>
                    <p class="text-2xl font-bold text-white">Configure</p>
                </div>
                <svg class="w-10 h-10 text-orange-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">Total Templates</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ $stats['total_templates'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">Active</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ $stats['active_templates'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">System</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ $stats['system_templates'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div class="rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Recent Templates</h3>
            </div>
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($stats['recent_templates'] as $template)
                    <li class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex min-w-0 flex-1 items-center">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900">{{ $template->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $template->slug }}</p>
                                </div>
                            </div>
                            <div class="ml-5 flex-shrink-0">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold leading-5
                                             {{ $template->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-gray-500">
                        No templates yet. <a href="{{ route('admin.templates.create') }}" class="text-blue-600 hover:text-blue-800">Create your first template</a>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
