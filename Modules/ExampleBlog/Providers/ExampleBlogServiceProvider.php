<?php

namespace Modules\ExampleBlog\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ExampleBlogServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerViews();
        $this->registerRoutes();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        //
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/exampleblog');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', 'exampleblog-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), 'exampleblog');
    }

    /**
     * Register routes.
     */
    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../Routes/web.php');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get publishable view paths.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path . '/modules/exampleblog')) {
                $paths[] = $path . '/modules/exampleblog';
            }
        }
        return $paths;
    }
}
