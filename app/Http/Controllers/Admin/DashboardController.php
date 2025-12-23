<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Services\SchemaService;

class DashboardController extends Controller
{
    public function index(SchemaService $schemaService)
    {
        $stats = [
            'total_templates' => Template::count(),
            'active_templates' => Template::where('is_active', true)->count(),
            'system_templates' => Template::where('is_system', true)->count(),
            'recent_templates' => Template::latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
