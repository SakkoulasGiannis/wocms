<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ImageSize;
use App\Models\Setting;
use App\Services\ThemeManager;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsPage extends Component
{
    use WithFileUploads;

    public $activeTab = 'general';

    // General Settings
    public $site_name = '';

    public $site_logo = '';

    public $site_logo_upload = null; // For file upload

    public $site_favicon = '';

    public $site_favicon_upload = null; // For favicon upload

    public $site_favicon_png = '';

    public $site_favicon_png_upload = null; // For PNG fallback favicon upload

    public $site_description = '';

    public $under_construction = false;

    public $active_theme = 'tailwind';

    public $availableThemes = [];

    // AI Settings
    public $ai_provider = 'claude'; // claude, chatgpt, gemini, ollama

    public $ai_claude_api_key = '';

    public $ai_chatgpt_api_key = '';

    public $ai_gemini_api_key = '';

    public $ai_gemini_model = 'gemini-flash-latest';

    public $ai_ollama_url = '';

    public $ai_model = '';

    // AI Prompts
    public $prompt_structured_html = '';

    public $prompt_content_generation = '';

    public $prompt_template_generation = '';

    /* ── Page Compiler prompts (new in 2026-06) ─────────────────────────
       page_compiler  → create-new-page workflow (AI gets template skeleton, fills it)
       page_editor    → edit-existing-page workflow (AI gets exported JSON, applies edit)
       section_writer → single-section content generation                                     */
    public $prompt_page_compiler = '';

    public $prompt_page_editor = '';

    public $prompt_section_writer = '';

    // Integrations Settings
    public $google_analytics_id = '';

    public $google_tag_manager_id = '';

    public $facebook_pixel_id = '';

    public $custom_head_scripts = '';

    public $site_custom_css = '';

    public $custom_body_scripts = '';

    public $google_maps_api_key = '';

    public $recaptcha_enabled = false;

    public $recaptcha_site_key = '';

    public $recaptcha_secret_key = '';

    public $recaptcha_min_score = '0.5';

    public $social_facebook = '';

    public $social_instagram = '';

    public $social_twitter = '';

    public $social_linkedin = '';

    public $social_youtube = '';

    public $social_tiktok = '';

    // Visual Editor Settings
    public bool $ve_tailwind_cdn = false;

    // Image Sizes
    public $imageSizes = [];

    public $editingImageSize = null;

    public $showImageSizeForm = false;

    public $imageSizeForm = [
        'label' => '',
        'width' => '',
        'height' => '',
        'mode' => 'crop',
    ];

    public function mount()
    {
        $this->loadSettings();
        $this->loadImageSizes();
    }

    public function loadSettings()
    {
        // General settings
        $this->site_name = Setting::get('site_name', 'My CMS');
        $this->site_logo = Setting::get('site_logo', '');
        $this->site_favicon = Setting::get('site_favicon', '');
        $this->site_favicon_png = Setting::get('site_favicon_png', '');
        $this->site_description = Setting::get('site_description', '');
        $this->under_construction = Setting::get('under_construction', false);
        $this->active_theme = Setting::get('active_theme', 'tailwind');

        // Load available themes
        $themeManager = app(ThemeManager::class);
        $this->availableThemes = $themeManager->getAvailableThemes();

        // AI settings
        $this->ai_provider = Setting::get('ai_provider', 'claude');
        $this->ai_claude_api_key = Setting::get('ai_claude_api_key', '');
        $this->ai_chatgpt_api_key = Setting::get('ai_chatgpt_api_key', '');
        $this->ai_gemini_api_key = Setting::get('ai_gemini_api_key', '');
        $this->ai_gemini_model = Setting::get('ai_gemini_model', 'gemini-flash-latest');
        $this->ai_ollama_url = Setting::get('ai_ollama_url', 'http://localhost:11434');
        $this->ai_model = Setting::get('ai_model', $this->getDefaultModel());

        // AI Prompts
        $this->prompt_structured_html = Setting::get('prompt_structured_html', config('ai-prompts.structured_html'));
        $this->prompt_content_generation = Setting::get('prompt_content_generation', config('ai-prompts.content_generation'));
        $this->prompt_template_generation = Setting::get('prompt_template_generation', config('ai-prompts.template_generation'));
        $this->prompt_page_compiler = Setting::get('prompt_page_compiler', config('ai-prompts.page_compiler', ''));
        $this->prompt_page_editor = Setting::get('prompt_page_editor', config('ai-prompts.page_editor', ''));
        $this->prompt_section_writer = Setting::get('prompt_section_writer', config('ai-prompts.section_writer', ''));

        // Visual Editor Settings
        $this->ve_tailwind_cdn = (bool) Setting::get('ve_tailwind_cdn', false);

        // Integrations settings
        $this->google_analytics_id = Setting::get('google_analytics_id', '');
        $this->google_tag_manager_id = Setting::get('google_tag_manager_id', '');
        $this->facebook_pixel_id = Setting::get('facebook_pixel_id', '');
        $this->custom_head_scripts = Setting::get('custom_head_scripts', '');
        $this->site_custom_css = Setting::get('site_custom_css', '');
        $this->custom_body_scripts = Setting::get('custom_body_scripts', '');
        $this->google_maps_api_key = Setting::get('google_maps_api_key', '');
        $this->recaptcha_site_key = Setting::get('recaptcha_site_key', '');
        $this->recaptcha_secret_key = Setting::get('recaptcha_secret_key', '');
        $this->recaptcha_enabled = (bool) Setting::get('recaptcha_enabled', false);
        $this->recaptcha_min_score = Setting::get('recaptcha_min_score', '0.5');
        $this->social_facebook = Setting::get('social_facebook', '');
        $this->social_instagram = Setting::get('social_instagram', '');
        $this->social_twitter = Setting::get('social_twitter', '');
        $this->social_linkedin = Setting::get('social_linkedin', '');
        $this->social_youtube = Setting::get('social_youtube', '');
        $this->social_tiktok = Setting::get('social_tiktok', '');
    }

    protected function getDefaultModel(): string
    {
        return match ($this->ai_provider) {
            'claude' => 'claude-3-5-sonnet-20241022',
            'chatgpt' => 'gpt-4-turbo-preview',
            'gemini' => 'gemini-flash-latest',
            'ollama' => 'llama2',
            default => 'claude-3-5-sonnet-20241022'
        };
    }

    public function updatedAiProvider()
    {
        // Update default model when provider changes
        $this->ai_model = $this->getDefaultModel();
    }

    public function saveGeneral()
    {
        $this->validate([
            'site_name' => 'required|string|max:255',
            'site_logo_upload' => 'nullable|image:allow_svg|max:2048', // 2MB max
            'site_favicon_upload' => 'nullable|image:allow_svg|mimes:png,ico,jpg,jpeg,svg|max:1024', // 1MB max
            'site_favicon_png_upload' => 'nullable|image|mimes:png|max:1024', // 1MB max, PNG fallback
            'site_description' => 'nullable|string|max:1000',
        ]);

        Setting::set('site_name', $this->site_name, 'general');

        // Handle logo upload
        if ($this->site_logo_upload) {
            // Store in public/storage/settings
            $path = $this->site_logo_upload->store('settings', 'public');
            $this->site_logo = '/storage/'.$path;
            Setting::set('site_logo', $this->site_logo, 'general');
            $this->site_logo_upload = null; // Reset upload field
        } elseif ($this->site_logo) {
            // Keep existing logo if no new upload
            Setting::set('site_logo', $this->site_logo, 'general');
        }

        // Handle favicon upload
        if ($this->site_favicon_upload) {
            // Store in public/storage/settings
            $path = $this->site_favicon_upload->store('settings', 'public');
            $this->site_favicon = '/storage/'.$path;
            Setting::set('site_favicon', $this->site_favicon, 'general');
            $this->site_favicon_upload = null; // Reset upload field
        } elseif ($this->site_favicon) {
            // Keep existing favicon if no new upload
            Setting::set('site_favicon', $this->site_favicon, 'general');
        }

        // Handle PNG fallback favicon upload (used alongside an SVG favicon for Safari/iOS)
        if ($this->site_favicon_png_upload) {
            $path = $this->site_favicon_png_upload->store('settings', 'public');
            $this->site_favicon_png = '/storage/'.$path;
            Setting::set('site_favicon_png', $this->site_favicon_png, 'general');
            $this->site_favicon_png_upload = null; // Reset upload field
        } elseif ($this->site_favicon_png) {
            // Keep existing PNG fallback if no new upload
            Setting::set('site_favicon_png', $this->site_favicon_png, 'general');
        }

        Setting::set('site_description', $this->site_description, 'general');
        Setting::set('under_construction', $this->under_construction, 'general');
        Setting::set('active_theme', $this->active_theme, 'general');

        // Clear theme cache
        $themeManager = app(ThemeManager::class);
        $themeManager->clearCache();

        session()->flash('success', 'General settings saved successfully!');
    }

    public function saveAI()
    {
        // Gemini uses `ai_gemini_model` exclusively; the generic `ai_model`
        // field is hidden in the UI for that provider, so make it nullable.
        $aiModelRule = $this->ai_provider === 'gemini' ? 'nullable|string' : 'required|string';

        $this->validate([
            'ai_provider' => 'required|in:claude,chatgpt,gemini,ollama',
            'ai_claude_api_key' => 'nullable|string',
            'ai_chatgpt_api_key' => 'nullable|string',
            'ai_gemini_api_key' => 'nullable|string',
            'ai_gemini_model' => 'nullable|string',
            'ai_ollama_url' => 'nullable|url',
            'ai_model' => $aiModelRule,
        ]);

        Setting::set('ai_provider', $this->ai_provider, 'ai');
        Setting::set('ai_claude_api_key', $this->ai_claude_api_key, 'ai', encrypt: true);
        Setting::set('ai_chatgpt_api_key', $this->ai_chatgpt_api_key, 'ai', encrypt: true);
        Setting::set('ai_gemini_api_key', $this->ai_gemini_api_key, 'ai', encrypt: true);
        Setting::set('ai_gemini_model', $this->ai_gemini_model, 'ai');
        Setting::set('ai_ollama_url', $this->ai_ollama_url, 'ai');
        Setting::set('ai_model', $this->ai_model, 'ai');

        session()->flash('success', 'AI settings saved successfully!');
    }

    public function savePrompts()
    {
        $this->validate([
            'prompt_structured_html' => 'required|string|min:10',
            'prompt_content_generation' => 'required|string|min:10',
            'prompt_template_generation' => 'required|string|min:10',
            'prompt_page_compiler' => 'nullable|string',
            'prompt_page_editor' => 'nullable|string',
            'prompt_section_writer' => 'nullable|string',
        ]);

        Setting::set('prompt_structured_html', $this->prompt_structured_html, 'ai_prompts');
        Setting::set('prompt_content_generation', $this->prompt_content_generation, 'ai_prompts');
        Setting::set('prompt_template_generation', $this->prompt_template_generation, 'ai_prompts');
        Setting::set('prompt_page_compiler', $this->prompt_page_compiler, 'ai_prompts');
        Setting::set('prompt_page_editor', $this->prompt_page_editor, 'ai_prompts');
        Setting::set('prompt_section_writer', $this->prompt_section_writer, 'ai_prompts');

        session()->flash('success', 'AI prompts saved successfully!');
    }

    public function resetPrompt($promptName)
    {
        $configKey = 'ai-prompts.'.$promptName;
        $default = config($configKey);

        if ($default) {
            $this->{"prompt_{$promptName}"} = $default;
            session()->flash('success', ucfirst(str_replace('_', ' ', $promptName)).' reset to default!');
        }
    }

    public function resetAllPrompts()
    {
        $this->prompt_structured_html = config('ai-prompts.structured_html');
        $this->prompt_content_generation = config('ai-prompts.content_generation');
        $this->prompt_template_generation = config('ai-prompts.template_generation');

        session()->flash('success', 'All prompts reset to defaults!');
    }

    public function saveVisualEditor(): void
    {
        Setting::set('ve_tailwind_cdn', $this->ve_tailwind_cdn, 'visual_editor');

        session()->flash('success', 'Visual Editor settings saved.');
    }

    // Image Sizes Methods
    public function loadImageSizes()
    {
        $this->imageSizes = ImageSize::orderBy('order')->orderBy('name')->get()->toArray();
    }

    public function addImageSize()
    {
        $this->editingImageSize = null;
        $this->showImageSizeForm = true;
        $this->imageSizeForm = [
            'label' => '',
            'width' => '',
            'height' => '',
            'mode' => 'crop',
        ];
    }

    public function editImageSize($id)
    {
        $imageSize = ImageSize::find($id);
        if ($imageSize) {
            $this->editingImageSize = $id;
            $this->showImageSizeForm = true;
            $this->imageSizeForm = [
                'label' => $imageSize->label,
                'width' => $imageSize->width,
                'height' => $imageSize->height,
                'mode' => $imageSize->mode,
            ];
        }
    }

    public function saveImageSize()
    {
        $this->validate([
            'imageSizeForm.label' => 'required|string|max:255',
            'imageSizeForm.width' => 'required|integer|min:1|max:5000',
            'imageSizeForm.height' => 'required|integer|min:1|max:5000',
            'imageSizeForm.mode' => 'required|in:crop,fit,resize',
        ]);

        if ($this->editingImageSize) {
            // Update existing
            $imageSize = ImageSize::find($this->editingImageSize);
            $imageSize->update($this->imageSizeForm);
            session()->flash('success', 'Image size updated successfully!');
        } else {
            // Create new
            ImageSize::create($this->imageSizeForm);
            session()->flash('success', 'Image size created successfully!');
        }

        $this->loadImageSizes();
        $this->cancelImageSize();
    }

    public function deleteImageSize($id)
    {
        ImageSize::find($id)?->delete();
        $this->loadImageSizes();
        session()->flash('success', 'Image size deleted successfully!');
    }

    public function toggleImageSizeActive($id)
    {
        $imageSize = ImageSize::find($id);
        if ($imageSize) {
            $imageSize->is_active = ! $imageSize->is_active;
            $imageSize->save();
            $this->loadImageSizes();
        }
    }

    public function cancelImageSize()
    {
        $this->editingImageSize = null;
        $this->showImageSizeForm = false;
        $this->imageSizeForm = [
            'label' => '',
            'width' => '',
            'height' => '',
            'mode' => 'crop',
        ];
    }

    public function regenerateAllImages()
    {
        try {
            // Use Spatie's built-in command to regenerate media
            \Artisan::call('media-library:regenerate');

            $output = \Artisan::output();

            session()->flash('success', 'Image regeneration started! Check the output: '.$output);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to regenerate images: '.$e->getMessage());
        }
    }

    public function buildAssets()
    {
        try {
            \Log::info('🔨 Starting npm build...');

            // PHP-FPM runs with a minimal environment — its PATH usually lacks
            // the directory holding node/npm, so `npm run build` fails with
            // "vite: not found" (vite's shebang can't locate `node`). Resolve an
            // absolute npm binary and pass an explicit PATH so the build works
            // the same as it does from an interactive shell.
            $npm = null;
            foreach (['/usr/local/bin/npm', '/usr/bin/npm', '/opt/homebrew/bin/npm'] as $candidate) {
                if (is_executable($candidate)) {
                    $npm = $candidate;
                    break;
                }
            }
            $npm = $npm ?: 'npm';
            $nodeBinDir = $npm !== 'npm' ? dirname($npm) : '/usr/bin';

            $env = [
                'PATH' => $nodeBinDir.':'.base_path().'/node_modules/.bin:/usr/local/bin:/usr/bin:/bin',
                'HOME' => base_path(), // npm needs a writable HOME for its cache
                'NODE_ENV' => 'production',
            ];

            $process = proc_open(
                escapeshellarg($npm).' run build 2>&1',
                [
                    0 => ['pipe', 'r'], // stdin
                    1 => ['pipe', 'w'], // stdout
                    2 => ['pipe', 'w'], // stderr
                ],
                $pipes,
                base_path(),
                $env
            );

            if (is_resource($process)) {
                // Read output
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);

                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                $returnCode = proc_close($process);

                if ($returnCode === 0) {
                    \Log::info('✅ npm build completed successfully', ['output' => $output]);
                    session()->flash('success', 'Assets built successfully! The frontend has been updated.');
                } else {
                    \Log::error('❌ npm build failed', ['error' => $error, 'output' => $output]);
                    session()->flash('error', 'Build failed: '.($error ?: $output));
                }
            } else {
                throw new \Exception('Failed to start build process');
            }
        } catch (\Exception $e) {
            \Log::error('❌ Build error: '.$e->getMessage());
            session()->flash('error', 'Failed to build assets: '.$e->getMessage());
        }
    }

    public function saveIntegrations(): void
    {
        $this->google_analytics_id = trim((string) $this->google_analytics_id);
        $this->google_tag_manager_id = trim((string) $this->google_tag_manager_id);
        $this->facebook_pixel_id = trim((string) $this->facebook_pixel_id);

        $this->validate([
            // Empty string allowed (Livewire props are '' not null, so `nullable` alone wouldn't skip the regex)
            'google_analytics_id' => ['nullable', 'string', 'max:50', 'regex:/^(G-[A-Z0-9]+)?$/i'],
            'google_tag_manager_id' => ['nullable', 'string', 'max:50', 'regex:/^(GTM-[A-Z0-9]+)?$/i'],
            'facebook_pixel_id' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]*$/'],
            'custom_head_scripts' => 'nullable|string',
            'custom_body_scripts' => 'nullable|string',
            'site_custom_css' => 'nullable|string',
            'google_maps_api_key' => 'nullable|string|max:255',
            'recaptcha_enabled' => 'boolean',
            'recaptcha_site_key' => 'nullable|string|max:255',
            'recaptcha_secret_key' => 'nullable|string|max:255',
            'recaptcha_min_score' => 'nullable|numeric|min:0|max:1',
            'social_facebook' => 'nullable|url|max:255',
            'social_instagram' => 'nullable|url|max:255',
            'social_twitter' => 'nullable|url|max:255',
            'social_linkedin' => 'nullable|url|max:255',
            'social_youtube' => 'nullable|url|max:255',
            'social_tiktok' => 'nullable|url|max:255',
        ]);

        Setting::set('google_analytics_id', $this->google_analytics_id, 'integrations');
        Setting::set('google_tag_manager_id', $this->google_tag_manager_id, 'integrations');
        Setting::set('facebook_pixel_id', $this->facebook_pixel_id, 'integrations');
        Setting::set('custom_head_scripts', $this->custom_head_scripts, 'integrations');
        Setting::set('custom_body_scripts', $this->custom_body_scripts, 'integrations');
        Setting::set('site_custom_css', $this->site_custom_css, 'integrations');
        Setting::set('google_maps_api_key', $this->google_maps_api_key, 'integrations', encrypt: true);
        Setting::set('recaptcha_site_key', $this->recaptcha_site_key, 'integrations');
        Setting::set('recaptcha_secret_key', $this->recaptcha_secret_key, 'integrations', encrypt: true);
        Setting::set('recaptcha_enabled', $this->recaptcha_enabled ? '1' : '0', 'integrations');
        Setting::set('recaptcha_min_score', $this->recaptcha_min_score !== '' && $this->recaptcha_min_score !== null ? (string) $this->recaptcha_min_score : '0.5', 'integrations');
        Setting::set('social_facebook', $this->social_facebook, 'integrations');
        Setting::set('social_instagram', $this->social_instagram, 'integrations');
        Setting::set('social_twitter', $this->social_twitter, 'integrations');
        Setting::set('social_linkedin', $this->social_linkedin, 'integrations');
        Setting::set('social_youtube', $this->social_youtube, 'integrations');
        Setting::set('social_tiktok', $this->social_tiktok, 'integrations');

        session()->flash('success', 'Integration settings saved successfully!');
    }

    public function render()
    {
        return view('livewire.admin.settings.settings-page')->layout('layouts.admin-clean');
    }
}
