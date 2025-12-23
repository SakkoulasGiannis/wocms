@extends('layouts.admin-clean')

@section('title', 'Blog Posts')
@section('page-title', 'Blog Posts')

@section('content')
<div class="px-4 sm:px-0">

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-center h-64">
            <div class="text-center">
                <i class="fa fa-newspaper text-gray-300 text-6xl mb-4"></i>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Example Blog Module</h2>
                <p class="text-gray-500">This is a placeholder for the blog posts management page.</p>
                <p class="text-sm text-gray-400 mt-4">
                    This module demonstrates how modules can register menu items dynamically.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
