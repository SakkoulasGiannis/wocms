<?php

namespace Tests\Feature;

use App\Models\ContentNode;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Page;
use App\Models\Template;
use App\Services\ContentShortcodeRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * SECURITY-CRITICAL: proves the `<x-form slug="..." />` embed token is
 * substituted safely.
 *
 * The bug this fixes: FrontendController::renderNodeContent() used to call
 * `Blade::render($rawHtml, $data)` on raw database content for the
 * `simple_content`/`full_page_grapejs` render modes — meaning any
 * `@php`/`{{ }}`/component tag an AI generator or a content editor ever
 * wrote would execute server-side (remote code execution). This test
 * proves the replacement (App\Services\ContentShortcodeRenderer, wired into
 * FrontendController and frontend.page's `$content->body` echo) renders a
 * real form from the one sanctioned token, while every kind of hostile
 * payload — including ones smuggled alongside a valid token — is left
 * completely inert, never compiled, never executed.
 */
class ContentShortcodeSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Template $pageTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('pages')) {
            Schema::create('pages', function ($table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('slug')->nullable();
                $table->longText('body')->nullable();
                $table->longText('body_css')->nullable();
                $table->string('featured_image')->nullable();
                $table->string('render_mode')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $this->pageTemplate = Template::firstOrCreate(
            ['slug' => 'page'],
            [
                'name' => 'Page',
                'model_class' => 'Page',
                'table_name' => 'pages',
                'is_active' => true,
                'is_public' => true,
                'has_physical_file' => true,
                'file_path' => 'frontend.page',
                'render_mode' => 'sections',
            ]
        );
    }

    protected function makeForm(string $slug = 'probe-form'): Form
    {
        $form = Form::create([
            'name' => 'Probe Form',
            'slug' => $slug,
            'is_active' => true,
            'store_submissions' => true,
            'send_email_notification' => false,
        ]);

        FormField::create([
            'form_id' => $form->id,
            'name' => 'email',
            'label' => 'PROBE_FORM_EMAIL_LABEL',
            'type' => 'email',
            'is_required' => true,
            'order' => 1,
        ]);

        return $form;
    }

    protected function makePage(string $slug, string $html): ContentNode
    {
        $page = Page::create([
            'title' => 'Test Page',
            'slug' => $slug,
            'body' => $html,
            'render_mode' => 'simple_content',
            'status' => 'active',
        ]);

        return ContentNode::create([
            'template_id' => $this->pageTemplate->id,
            'content_type' => Page::class,
            'content_id' => $page->id,
            'title' => 'Test Page',
            'slug' => $slug,
            'is_published' => true,
        ]);
    }

    /** @test */
    public function the_token_renders_a_real_working_form(): void
    {
        $this->makeForm('probe-form');
        $this->makePage('probe-page', '<section><x-form slug="probe-form" /></section>');

        $this->get('/probe-page')
            ->assertStatus(200)
            ->assertSee('PROBE_FORM_EMAIL_LABEL', false)
            ->assertSee('wire:model="formData.email"', false);
    }

    /** @test */
    public function a_hostile_payload_smuggled_after_a_valid_token_is_not_executed(): void
    {
        $this->makeForm('probe-form');
        $this->makePage('probe-page', '<x-form slug="probe-form"/>{{ system(\'id\') }}');

        $response = $this->get('/probe-page')->assertStatus(200);

        // The real form still renders...
        $response->assertSee('PROBE_FORM_EMAIL_LABEL', false);
        // ...and the trailing Blade-lookalike is emitted as INERT LITERAL
        // TEXT (content is never HTML-escaped either — that would break
        // legitimate rich HTML pages — it is simply never compiled/executed
        // as Blade/PHP), byte-for-byte, proving no code ran.
        $response->assertSee("{{ system('id') }}", false);
    }

    /** @test */
    public function php_directive_payload_is_never_executed(): void
    {
        $this->makePage('probe-page', '<p>before</p>@php file_put_contents(storage_path(\'app/PWNED.txt\'), \'x\'); @endphp<p>after</p>');

        $this->get('/probe-page')->assertStatus(200);

        $this->assertFileDoesNotExist(storage_path('app/PWNED.txt'));
    }

    /** @test */
    public function an_unrelated_component_tag_is_left_inert_not_substituted(): void
    {
        $this->makePage('probe-page', '<x-anything-else foo="bar" />');

        $response = $this->get('/probe-page')->assertStatus(200);

        // Left as literal text (escaped by the browser's HTML parser as an
        // unknown element) — never resolved to a real component, never a 500.
        $response->assertSee('x-anything-else', false);
    }

    /** @test */
    public function unknown_form_slug_degrades_to_a_tidy_placeholder_not_an_error(): void
    {
        $this->makePage('probe-page', '<x-form slug="does-not-exist" />');

        $response = $this->get('/probe-page')->assertStatus(200);

        $response->assertDontSee('<x-form', false);
        $response->assertSee('form not found: does-not-exist', false);
    }

    /** @test */
    public function inactive_form_is_treated_as_not_found(): void
    {
        $form = $this->makeForm('inactive-form');
        $form->update(['is_active' => false]);
        $this->makePage('probe-page', '<x-form slug="inactive-form" />');

        $response = $this->get('/probe-page')->assertStatus(200);

        $response->assertSee('form not found: inactive-form', false);
    }

    /** @test */
    public function renderer_service_leaves_non_matching_shapes_untouched(): void
    {
        $renderer = app(ContentShortcodeRenderer::class);

        // Extra attribute breaks the strict anchored shape -> untouched.
        $html = '<x-form slug="x" onclick="alert(1)" />';
        $this->assertSame($html, $renderer->render($html));

        // Not self-closed (has children) -> untouched.
        $html = '<x-form slug="x">{{ 1+1 }}</x-form>';
        $this->assertSame($html, $renderer->render($html));

        // Uppercase / invalid slug characters -> untouched.
        $html = '<x-form slug="Not_Valid!" />';
        $this->assertSame($html, $renderer->render($html));

        // Empty/null input never errors.
        $this->assertSame('', $renderer->render(''));
        $this->assertSame('', $renderer->render(null));
    }

    /** @test */
    public function renderer_never_calls_blade_render_on_the_whole_string(): void
    {
        $renderer = app(ContentShortcodeRenderer::class);

        // If this were ever passed to Blade::render() as the template
        // string, {{ }} would be evaluated and PHP functions could run.
        // Assert it comes back byte-for-byte, proving no Blade compilation
        // touched it.
        $payload = '{{ 7 * 6 }} @php echo "pwned"; @endphp <x-form-slug-lookalike />';
        $this->assertSame($payload, $renderer->render($payload));
    }
}
