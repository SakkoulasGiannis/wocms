<?php

namespace App\Providers;

use App\VisualBuilder\CmsBuilderPersistence;
use App\VisualBuilder\CmsTokenSource;
use Illuminate\Support\ServiceProvider;
use Weborange\VisualBuilder\Contracts\BuilderPersistence;
use Weborange\VisualBuilder\Contracts\TokenSource;

/**
 * Wires the framework-agnostic weborange/visual-builder package into this CMS:
 * binds the persistence + token-source contracts to CMS implementations and
 * points the builder at the admin layout / route prefix / media endpoint.
 */
class VisualBuilderHostServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Set before the package registers its routes (runs in register()).
        config([
            // The CMS registers the routes itself (in routes/web.php, inside the
            // admin group BEFORE the {templateSlug} wildcard), so disable the
            // package's own route registration.
            'visual-builder.register_routes' => false,
            'visual-builder.as' => 'admin.visual-builder.',
            'visual-builder.layout' => 'layouts.admin-clean',
            'visual-builder.content_section' => 'content',
            'visual-builder.title' => 'New Builder',
            'visual-builder.media_url' => url('admin/editorjs/media'),
            'visual-builder.upload_url' => url('admin/editorjs/upload-image'),
        ]);

        $this->app->bind(BuilderPersistence::class, CmsBuilderPersistence::class);
        $this->app->bind(TokenSource::class, CmsTokenSource::class);
    }
}
