<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class NewBuilderController extends Controller
{
    public function index(): View
    {
        return view('admin.new-builder.index');
    }
}
