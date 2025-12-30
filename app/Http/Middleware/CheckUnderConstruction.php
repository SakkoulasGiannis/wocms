<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class CheckUnderConstruction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if under construction mode is enabled
        $underConstruction = Setting::get('under_construction', false);

        // Skip check for admin routes, login, livewire, and API endpoints
        if ($request->is('admin/*') ||
            $request->is('login') ||
            $request->is('logout') ||
            $request->is('livewire/*') ||
            $request->is('csrf-token')) {
            return $next($request);
        }

        // If under construction mode is enabled, show the maintenance page
        if ($underConstruction) {
            // Let admins bypass the under construction page
            if (auth()->check() && auth()->user()->hasRole('admin')) {
                return $next($request);
            }

            return response()->view('under-construction', [], 503);
        }

        return $next($request);
    }
}
