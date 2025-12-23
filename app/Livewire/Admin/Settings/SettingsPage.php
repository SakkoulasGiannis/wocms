<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Models\ImageSize;
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
    public $site_description = '';

    // AI Settings
    public $ai_provider = 'claude'; // claude, chatgpt, ollama
    public $ai_claude_api_key = '';
    public $ai_chatgpt_api_key = '';
    public $ai_ollama_url = '';
    public $ai_model = '';

    // AI Prompts
    public $prompt_structured_html = '';
    public $prompt_content_generation = '';
    public $prompt_template_generation = '';

    // GrapeJS Settings
    public $grapejs_include_css_in_blade = true;

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
        $this->site_description = Setting::get('site_description', '');

        // AI settings
        $this->ai_provider = Setting::get('ai_provider', 'claude');
        $this->ai_claude_api_key = Setting::get('ai_claude_api_key', '');
        $this->ai_chatgpt_api_key = Setting::get('ai_chatgpt_api_key', '');
        $this->ai_ollama_url = Setting::get('ai_ollama_url', 'http://localhost:11434');
        $this->ai_model = Setting::get('ai_model', $this->getDefaultModel());

        // AI Prompts
        $this->prompt_structured_html = Setting::get('prompt_structured_html', config('ai-prompts.structured_html'));
        $this->prompt_content_generation = Setting::get('prompt_content_generation', config('ai-prompts.content_generation'));
        $this->prompt_template_generation = Setting::get('prompt_template_generation', config('ai-prompts.template_generation'));

        // GrapeJS Settings
        $this->grapejs_include_css_in_blade = Setting::get('grapejs_include_css_in_blade', true);
    }

    protected function getDefaultModel(): string
    {
        return match($this->ai_provider) {
            'claude' => 'claude-3-5-sonnet-20241022',
            'chatgpt' => 'gpt-4-turbo-preview',
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
            'site_logo_upload' => 'nullable|image|max:2048', // 2MB max
            'site_description' => 'nullable|string|max:1000',
        ]);

        Setting::set('site_name', $this->site_name, 'general');

        // Handle logo upload
        if ($this->site_logo_upload) {
            // Store in public/storage/settings
            $path = $this->site_logo_upload->store('settings', 'public');
            $this->site_logo = '/storage/' . $path;
            Setting::set('site_logo', $this->site_logo, 'general');
            $this->site_logo_upload = null; // Reset upload field
        } elseif ($this->site_logo) {
            // Keep existing logo if no new upload
            Setting::set('site_logo', $this->site_logo, 'general');
        }

        Setting::set('site_description', $this->site_description, 'general');

        session()->flash('success', 'General settings saved successfully!');
    }

    public function saveAI()
    {
        $this->validate([
            'ai_provider' => 'required|in:claude,chatgpt,ollama',
            'ai_claude_api_key' => 'nullable|string',
            'ai_chatgpt_api_key' => 'nullable|string',
            'ai_ollama_url' => 'nullable|url',
            'ai_model' => 'required|string',
        ]);

        Setting::set('ai_provider', $this->ai_provider, 'ai');
        Setting::set('ai_claude_api_key', $this->ai_claude_api_key, 'ai', encrypt: true);
        Setting::set('ai_chatgpt_api_key', $this->ai_chatgpt_api_key, 'ai', encrypt: true);
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
        ]);

        Setting::set('prompt_structured_html', $this->prompt_structured_html, 'ai_prompts');
        Setting::set('prompt_content_generation', $this->prompt_content_generation, 'ai_prompts');
        Setting::set('prompt_template_generation', $this->prompt_template_generation, 'ai_prompts');

        session()->flash('success', 'AI prompts saved successfully!');
    }

    public function resetPrompt($promptName)
    {
        $configKey = 'ai-prompts.' . $promptName;
        $default = config($configKey);

        if ($default) {
            $this->{"prompt_{$promptName}"} = $default;
            session()->flash('success', ucfirst(str_replace('_', ' ', $promptName)) . ' reset to default!');
        }
    }

    public function resetAllPrompts()
    {
        $this->prompt_structured_html = config('ai-prompts.structured_html');
        $this->prompt_content_generation = config('ai-prompts.content_generation');
        $this->prompt_template_generation = config('ai-prompts.template_generation');

        session()->flash('success', 'All prompts reset to defaults!');
    }

    public function saveGrapeJS()
    {
        $this->validate([
            'grapejs_include_css_in_blade' => 'required|boolean',
        ]);

        Setting::set('grapejs_include_css_in_blade', $this->grapejs_include_css_in_blade, 'grapejs');

        session()->flash('success', 'GrapeJS settings saved successfully!');
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
            $imageSize->is_active = !$imageSize->is_active;
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

            session()->flash('success', 'Image regeneration started! Check the output: ' . $output);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to regenerate images: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.settings-page')->layout('layouts.admin-clean');
    }
}
