<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaviconHeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_svg_favicon_renders_svg_link_with_png_fallback(): void
    {
        Setting::set('site_favicon', '/storage/settings/favicon.svg', 'general');
        Setting::set('site_favicon_png', '/storage/settings/favicon.png', 'general');

        $view = $this->view('partials.favicon');

        $view->assertSee('<link rel="icon" type="image/svg+xml" href="/storage/settings/favicon.svg">', false);
        $view->assertSee('<link rel="icon" type="image/png" href="/storage/settings/favicon.png">', false);
        $view->assertSee('<link rel="apple-touch-icon" href="/storage/settings/favicon.png">', false);
    }

    public function test_svg_favicon_without_png_fallback_renders_only_svg_link(): void
    {
        Setting::set('site_favicon', '/storage/settings/favicon.svg', 'general');

        $view = $this->view('partials.favicon');

        $view->assertSee('<link rel="icon" type="image/svg+xml" href="/storage/settings/favicon.svg">', false);
        $view->assertDontSee('image/png', false);
        $view->assertDontSee('apple-touch-icon', false);
    }

    public function test_png_favicon_keeps_current_behavior(): void
    {
        Setting::set('site_favicon', '/storage/settings/favicon.png', 'general');

        $view = $this->view('partials.favicon');

        $view->assertSee('<link rel="icon" type="image/x-icon" href="/storage/settings/favicon.png">', false);
        $view->assertDontSee('image/svg+xml', false);
    }

    public function test_png_only_fallback_setting_renders_png_links(): void
    {
        Setting::set('site_favicon_png', '/storage/settings/favicon.png', 'general');

        $view = $this->view('partials.favicon');

        $view->assertSee('<link rel="icon" type="image/png" href="/storage/settings/favicon.png">', false);
        $view->assertSee('<link rel="apple-touch-icon" href="/storage/settings/favicon.png">', false);
    }

    public function test_no_favicon_settings_render_no_links(): void
    {
        $view = $this->view('partials.favicon');

        $view->assertDontSee('<link', false);
    }
}
