<?php

namespace Weborange\VisualBuilder\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Serves the builder's static JS straight from the package (no publish step),
 * so the package is drop-in for any host. Restricted to the bundled .js files.
 */
class AssetController extends Controller
{
    public function js(string $file): SymfonyResponse
    {
        // Allow only "<name>.js" and "new-builder/<name>.js" — no traversal.
        if (! preg_match('#^(new-builder/)?[A-Za-z0-9_.\-]+\.js$#', $file) || str_contains($file, '..')) {
            abort(404);
        }

        $path = realpath(__DIR__.'/../../../resources/js/'.$file);
        $base = realpath(__DIR__.'/../../../resources/js');

        if ($path === false || $base === false || ! str_starts_with($path, $base) || ! is_file($path)) {
            abort(404);
        }

        return new Response(file_get_contents($path), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
