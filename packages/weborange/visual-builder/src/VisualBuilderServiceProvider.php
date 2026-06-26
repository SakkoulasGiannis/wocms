<?php

namespace Weborange\VisualBuilder;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Weborange\VisualBuilder\Contracts\AiGenerator;
use Weborange\VisualBuilder\Contracts\BuilderPersistence;
use Weborange\VisualBuilder\Contracts\TokenSource;
use Weborange\VisualBuilder\Support\NullAiGenerator;
use Weborange\VisualBuilder\Support\NullPersistence;
use Weborange\VisualBuilder\Support\NullTokenSource;

class VisualBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/visual-builder.php', 'visual-builder');

        // Default no-op implementations; the host binds real ones to enable
        // saving and dynamic tokens. bindIf so the host always wins.
        $this->app->bindIf(BuilderPersistence::class, NullPersistence::class);
        $this->app->bindIf(TokenSource::class, NullTokenSource::class);
        $this->app->bindIf(AiGenerator::class, NullAiGenerator::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'visual-builder');

        if (config('visual-builder.register_routes', true)) {
            $this->registerRoutes();
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/visual-builder.php' => $this->app->configPath('visual-builder.php'),
            ], 'visual-builder-config');

            $this->publishes([
                __DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/visual-builder'),
            ], 'visual-builder-views');
        }
    }

    /**
     * Load the package's route definitions into the CURRENT route group. Call
     * this from inside a host route group (with the desired prefix + name) when
     * `register_routes` is disabled — e.g. to place the builder before a
     * catch-all wildcard.
     */
    public static function routes(): void
    {
        require __DIR__.'/../routes/web.php';
    }

    private function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('visual-builder.prefix', 'visual-builder'),
            'middleware' => config('visual-builder.middleware', ['web']),
            'as' => config('visual-builder.as', 'visual-builder.'),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }
}
