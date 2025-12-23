@extends('layouts.admin-clean')

@section('title', 'Module Management')
@section('page-title', 'Module Management')

@section('content')
<div class="px-4 sm:px-0">

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg relative" role="alert">
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg relative">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Upload New Module Section -->
    <div class="mb-8 bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h2 class="text-xl font-semibold text-white flex items-center">
                <i class="fa fa-upload mr-2"></i>
                Install New Module
            </h2>
        </div>
        <div class="p-6">
            <form action="{{ route('admin.modules.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="module_zip" class="block text-sm font-medium text-gray-700 mb-2">
                        Upload Module ZIP File (Max 50MB)
                    </label>
                    <div class="flex items-center space-x-4">
                        <input type="file"
                               id="module_zip"
                               name="module_zip"
                               accept=".zip"
                               class="flex-1 text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                               required>
                        <button type="submit"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition duration-150 ease-in-out">
                            <i class="fa fa-cloud-upload-alt mr-2"></i>
                            Install Module
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        Upload a ZIP file containing a valid Laravel module with module.json file
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- Modules List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
            <h2 class="text-xl font-semibold text-white flex items-center">
                <i class="fa fa-cubes mr-2"></i>
                Installed Modules ({{ count($modules) }})
            </h2>
        </div>

        @if(count($modules) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Module
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Description
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Priority
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Menu
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($modules as $module)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">{{ substr($module['name'], 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $module['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $module['alias'] }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $module['description'] ?: 'No description' }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $module['path'] }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ $module['priority'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($module['enabled'])
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fa fa-check-circle mr-1"></i>
                                    Enabled
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    <i class="fa fa-times-circle mr-1"></i>
                                    Disabled
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($module['has_menu'])
                                <span class="text-green-600" title="Has menu configuration">
                                    <i class="fa fa-bars text-lg"></i>
                                </span>
                            @else
                                <span class="text-gray-300" title="No menu configuration">
                                    <i class="fa fa-minus text-lg"></i>
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                @if($module['enabled'])
                                    <form action="{{ route('admin.modules.disable', $module['name']) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded-md transition duration-150 ease-in-out"
                                                title="Disable module">
                                            <i class="fa fa-pause mr-1"></i>
                                            Disable
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.modules.enable', $module['name']) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-md transition duration-150 ease-in-out"
                                                title="Enable module">
                                            <i class="fa fa-play mr-1"></i>
                                            Enable
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('admin.modules.delete', $module['name']) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete the module \'{{ $module['name'] }}\'? This action cannot be undone!')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-md transition duration-150 ease-in-out"
                                            title="Delete module">
                                        <i class="fa fa-trash mr-1"></i>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-12 text-center">
            <i class="fa fa-cube text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No modules installed yet</p>
            <p class="text-gray-400 text-sm mt-2">Upload a module ZIP file to get started</p>
        </div>
        @endif
    </div>

    <!-- Documentation Link -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <i class="fa fa-info-circle text-blue-500 mt-1 mr-3"></i>
            <div>
                <h3 class="text-sm font-medium text-blue-900">Need help creating modules?</h3>
                <p class="mt-1 text-sm text-blue-700">
                    Check out the
                    <a href="{{ asset('docs/MODULE_MENU_GUIDE.md') }}" class="underline font-medium">Module Menu Guide</a>
                    and
                    <a href="{{ asset('MODULES_QUICKSTART.md') }}" class="underline font-medium">Quick Start Guide</a>
                    for detailed instructions.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
