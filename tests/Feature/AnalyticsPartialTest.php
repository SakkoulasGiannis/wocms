<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings\SettingsPage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Covers the analytics partial (resources/views/partials/analytics.blade.php)
 * and its end-to-end wiring with the admin Settings page:
 *
 *   1. Nothing renders while the settings are empty.
 *   2. A GA4 Measurement ID (G-…) renders the gtag.js snippet exactly once.
 *   3. Values that are not a G- id (e.g. old UA- ids) render nothing.
 *   4. GTM / Facebook Pixel snippets render only when their settings are set.
 *   5. Saving through SettingsPage::saveIntegrations stores under the exact
 *      key the partial reads AND invalidates the Setting cache, so the next
 *      render picks the new value up immediately.
 */
class AnalyticsPartialTest extends TestCase
{
    use RefreshDatabase;

    protected function renderPartial(): string
    {
        return view('partials.analytics')->render();
    }

    public function test_renders_nothing_when_settings_are_empty(): void
    {
        $html = $this->renderPartial();

        $this->assertStringNotContainsString('googletagmanager.com', $html);
        $this->assertStringNotContainsString('gtag', $html);
        $this->assertStringNotContainsString('fbq', $html);
        $this->assertSame('', trim($html));
    }

    public function test_renders_ga4_snippet_exactly_once_when_measurement_id_is_set(): void
    {
        Setting::set('google_analytics_id', 'G-TEST123456', 'integrations');

        $html = $this->renderPartial();

        $this->assertSame(1, substr_count($html, 'https://www.googletagmanager.com/gtag/js?id=G-TEST123456'));
        $this->assertSame(1, substr_count($html, "gtag('config', 'G-TEST123456')"));
        $this->assertStringContainsString('dns-prefetch', $html);
        // GTM / Pixel remain absent
        $this->assertStringNotContainsString('gtm.js', $html);
        $this->assertStringNotContainsString('fbq', $html);
    }

    public function test_renders_nothing_for_a_non_ga4_id(): void
    {
        Setting::set('google_analytics_id', 'UA-12345678-1', 'integrations');

        $this->assertSame('', trim($this->renderPartial()));
    }

    public function test_renders_gtm_snippet_when_container_id_is_set(): void
    {
        Setting::set('google_tag_manager_id', 'GTM-TEST123', 'integrations');

        $html = $this->renderPartial();

        $this->assertSame(1, substr_count($html, "'GTM-TEST123'"));
        $this->assertStringContainsString('googletagmanager.com/gtm.js', $html);
        $this->assertStringNotContainsString('gtag/js', $html);
    }

    public function test_renders_facebook_pixel_when_set(): void
    {
        Setting::set('facebook_pixel_id', '123456789012345', 'integrations');

        $html = $this->renderPartial();

        $this->assertSame(1, substr_count($html, "fbq('init','123456789012345')"));
        $this->assertStringContainsString('connect.facebook.net', $html);
        $this->assertStringNotContainsString('gtag/js', $html);
    }

    public function test_admin_settings_save_feeds_the_partial_and_busts_the_cache(): void
    {
        // Warm the Setting cache with the empty value first
        $this->assertSame('', trim($this->renderPartial()));

        Livewire::actingAs(User::factory()->create())
            ->test(SettingsPage::class)
            ->set('google_analytics_id', ' G-ADMIN99999 ')
            ->call('saveIntegrations')
            ->assertHasNoErrors();

        // Saved (trimmed) under the exact key the partial reads
        $this->assertSame('G-ADMIN99999', Setting::get('google_analytics_id'));
        $this->assertDatabaseHas('settings', [
            'key' => 'google_analytics_id',
            'value' => 'G-ADMIN99999',
            'group' => 'integrations',
        ]);

        // Cache was invalidated: the partial now renders the new id
        $html = $this->renderPartial();
        $this->assertSame(1, substr_count($html, 'gtag/js?id=G-ADMIN99999'));
    }

    public function test_admin_settings_rejects_malformed_ids(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(SettingsPage::class)
            ->set('google_analytics_id', 'not-a-ga4-id')
            ->call('saveIntegrations')
            ->assertHasErrors(['google_analytics_id' => 'regex']);
    }

    public function test_admin_login_page_does_not_contain_analytics(): void
    {
        Setting::set('google_analytics_id', 'G-TEST123456', 'integrations');

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('gtag/js', false)
            ->assertDontSee('googletagmanager', false);
    }
}
