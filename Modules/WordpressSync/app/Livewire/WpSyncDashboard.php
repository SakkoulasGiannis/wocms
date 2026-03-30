<?php

namespace Modules\WordpressSync\Livewire;

use App\Models\Blog;
use App\Models\ContentNode;
use App\Models\PageSection;
use App\Models\SectionTemplate;
use App\Models\Setting;
use App\Models\Template;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\WordpressSync\Services\WordPressApiClient;

class WpSyncDashboard extends Component
{
    // Settings
    public string $wpUrl = '';

    public string $wpUsername = '';

    public string $wpAppPassword = '';

    // Connection status
    public ?array $connectionStatus = null;

    // Sync state
    public string $activeTab = 'settings';

    public array $wpPosts = [];

    public array $wpPages = [];

    public int $wpPostsTotal = 0;

    public int $wpPagesTotal = 0;

    public bool $isSyncing = false;

    public array $syncLog = [];

    public array $selectedPosts = [];

    public array $selectedPages = [];

    public function mount(): void
    {
        $this->wpUrl = Setting::get('wp_sync_url', '');
        $this->wpUsername = Setting::get('wp_sync_username', '');
        $this->wpAppPassword = Setting::get('wp_sync_app_password', '');

        if ($this->wpUrl) {
            $this->connectionStatus = $this->getClient()->testConnection();
        }
    }

    public function saveSettings(): void
    {
        $this->validate([
            'wpUrl' => 'required|url',
            'wpUsername' => 'nullable|string|max:255',
            'wpAppPassword' => 'nullable|string|max:255',
        ]);

        Setting::set('wp_sync_url', $this->wpUrl, 'wordpress');
        Setting::set('wp_sync_username', $this->wpUsername, 'wordpress');
        Setting::set('wp_sync_app_password', $this->wpAppPassword, 'wordpress', true);

        $this->connectionStatus = $this->getClient()->testConnection();

        if ($this->connectionStatus['success']) {
            session()->flash('success', 'Connected to: '.($this->connectionStatus['name'] ?? $this->wpUrl));
        } else {
            session()->flash('error', 'Connection failed: '.($this->connectionStatus['error'] ?? 'Unknown error'));
        }
    }

    public function testConnection(): void
    {
        $this->connectionStatus = $this->getClient()->testConnection();
    }

    public function fetchPosts(): void
    {
        $this->activeTab = 'posts';
        $client = $this->getClient();
        $result = $client->getPosts(1, 50);

        if ($result['success']) {
            $this->wpPosts = $result['data'];
            $this->wpPostsTotal = $result['total'];
        } else {
            $this->addLog('error', 'Failed to fetch posts: '.($result['error'] ?? 'Unknown'));
        }
    }

    public function fetchPages(): void
    {
        $this->activeTab = 'pages';
        $client = $this->getClient();
        $result = $client->getPages(1, 50);

        if ($result['success']) {
            $this->wpPages = $result['data'];
            $this->wpPagesTotal = $result['total'];
        } else {
            $this->addLog('error', 'Failed to fetch pages: '.($result['error'] ?? 'Unknown'));
        }
    }

    public function togglePost(int $wpId): void
    {
        if (in_array($wpId, $this->selectedPosts)) {
            $this->selectedPosts = array_values(array_diff($this->selectedPosts, [$wpId]));
        } else {
            $this->selectedPosts[] = $wpId;
        }
    }

    public function togglePage(int $wpId): void
    {
        if (in_array($wpId, $this->selectedPages)) {
            $this->selectedPages = array_values(array_diff($this->selectedPages, [$wpId]));
        } else {
            $this->selectedPages[] = $wpId;
        }
    }

    public function selectAllPosts(): void
    {
        $this->selectedPosts = collect($this->wpPosts)->pluck('id')->toArray();
    }

    public function deselectAllPosts(): void
    {
        $this->selectedPosts = [];
    }

    public function selectAllPages(): void
    {
        $this->selectedPages = collect($this->wpPages)->pluck('id')->toArray();
    }

    public function deselectAllPages(): void
    {
        $this->selectedPages = [];
    }

    public function syncSelectedPosts(): void
    {
        if (empty($this->selectedPosts)) {
            $this->addLog('warning', 'No posts selected');

            return;
        }

        $this->isSyncing = true;
        $this->syncLog = [];
        $client = $this->getClient();

        // Ensure Blog template exists
        $template = Template::where('slug', 'blog')->first();
        if (! $template) {
            $this->addLog('error', 'Blog template not found. Create a Blog template first.');
            $this->isSyncing = false;

            return;
        }

        $synced = 0;
        $skipped = 0;

        foreach ($this->wpPosts as $wpPost) {
            if (! in_array($wpPost['id'], $this->selectedPosts)) {
                continue;
            }

            $title = html_entity_decode($wpPost['title']['rendered'] ?? '', ENT_QUOTES, 'UTF-8');
            $slug = $wpPost['slug'] ?? Str::slug($title);

            // Check if already exists
            $existing = Blog::where('slug', $slug)->first();
            if ($existing) {
                $this->addLog('skip', "Skipped (exists): {$title}");
                $skipped++;

                continue;
            }

            try {
                $body = $wpPost['content']['rendered'] ?? '';
                $excerpt = html_entity_decode(strip_tags($wpPost['excerpt']['rendered'] ?? ''), ENT_QUOTES, 'UTF-8');
                $tags = implode(', ', $client->getTagNames($wpPost));
                $categories = implode(', ', $client->getCategoryNames($wpPost));
                $publishedAt = $wpPost['date'] ?? now()->toDateTimeString();

                $blog = Blog::create([
                    'title' => $title,
                    'slug' => $slug,
                    'body' => $body,
                    'excerpt' => trim($excerpt),
                    'tags' => $tags ?: $categories,
                    'author' => $wpPost['_embedded']['author'][0]['name'] ?? '',
                    'render_mode' => 'body',
                    'status' => $wpPost['status'] === 'publish' ? 'published' : 'draft',
                    'published_at' => $publishedAt,
                ]);

                // Create content node
                $this->createContentNode($blog, $template, $title, $slug);

                // Download featured image
                $imageUrl = $client->getFeaturedImageUrl($wpPost);
                if ($imageUrl) {
                    $this->downloadAndAttachImage($blog, $imageUrl, 'featured_image');
                }

                $this->addLog('success', "Imported: {$title}");
                $synced++;
            } catch (\Exception $e) {
                $this->addLog('error', "Failed: {$title} - {$e->getMessage()}");
            }
        }

        $this->addLog('info', "Done! Synced {$synced} posts, skipped {$skipped}.");
        $this->isSyncing = false;
        $this->selectedPosts = [];
    }

    public function syncSelectedPages(): void
    {
        if (empty($this->selectedPages)) {
            $this->addLog('warning', 'No pages selected');

            return;
        }

        $this->isSyncing = true;
        $this->syncLog = [];
        $client = $this->getClient();

        $template = Template::where('slug', 'page')->orWhere('slug', 'pages')->first();
        if (! $template) {
            $this->addLog('error', 'Page template not found. Create a Page template first.');
            $this->isSyncing = false;

            return;
        }

        $modelClass = "App\\Models\\{$template->model_class}";
        if (! class_exists($modelClass)) {
            $this->addLog('error', "Model class {$modelClass} not found.");
            $this->isSyncing = false;

            return;
        }

        $synced = 0;
        $skipped = 0;

        foreach ($this->wpPages as $wpPage) {
            if (! in_array($wpPage['id'], $this->selectedPages)) {
                continue;
            }

            $title = html_entity_decode($wpPage['title']['rendered'] ?? '', ENT_QUOTES, 'UTF-8');
            $slug = $wpPage['slug'] ?? Str::slug($title);

            $existing = $modelClass::where('slug', $slug)->first();
            if ($existing) {
                $this->addLog('skip', "Skipped (exists): {$title}");
                $skipped++;

                continue;
            }

            try {
                $body = $wpPage['content']['rendered'] ?? '';

                $page = $modelClass::create([
                    'title' => $title,
                    'slug' => $slug,
                    'body' => $body,
                    'render_mode' => 'sections',
                    'status' => $wpPage['status'] === 'publish' ? 'active' : 'draft',
                ]);

                // When using sections mode, create an HTML section with the body content
                if ($body && method_exists($page, 'sections')) {
                    $this->createHtmlSection($page, $body);
                }

                $this->createContentNode($page, $template, $title, $slug);

                $imageUrl = $client->getFeaturedImageUrl($wpPage);
                if ($imageUrl) {
                    $this->downloadAndAttachImage($page, $imageUrl, 'featured_image');
                }

                $this->addLog('success', "Imported: {$title}");
                $synced++;
            } catch (\Exception $e) {
                $this->addLog('error', "Failed: {$title} - {$e->getMessage()}");
            }
        }

        $this->addLog('info', "Done! Synced {$synced} pages, skipped {$skipped}.");
        $this->isSyncing = false;
        $this->selectedPages = [];
    }

    /**
     * Create an HTML section for imported pages using sections mode.
     */
    protected function createHtmlSection($page, string $htmlContent): void
    {
        // Prefer content-wysiwyg (has title + content fields), then custom-html (has html field)
        $sectionTemplate = SectionTemplate::where('slug', 'content-wysiwyg')->first()
            ?? SectionTemplate::where('slug', 'custom-html')->first()
            ?? SectionTemplate::where('slug', 'structured-html')->first();

        // Build content array matching the template's field names
        if ($sectionTemplate && $sectionTemplate->slug === 'content-wysiwyg') {
            $content = [
                'title' => $page->title ?? 'Content',
                'content' => $htmlContent,
            ];
        } elseif ($sectionTemplate && $sectionTemplate->slug === 'custom-html') {
            $content = [
                'html' => $htmlContent,
            ];
        } else {
            $content = [
                'title' => $page->title ?? 'Content',
                'content' => $htmlContent,
                'html' => $htmlContent,
            ];
        }

        PageSection::create([
            'sectionable_type' => get_class($page),
            'sectionable_id' => $page->id,
            'section_type' => $sectionTemplate ? $sectionTemplate->slug : 'content-wysiwyg',
            'section_template_id' => $sectionTemplate?->id,
            'name' => $page->title ?? 'Content',
            'content' => $content,
            'settings' => [],
            'order' => 0,
            'is_active' => true,
        ]);
    }

    protected function createContentNode($model, Template $template, string $title, string $slug): void
    {
        $existing = ContentNode::where('content_type', get_class($model))
            ->where('content_id', $model->id)
            ->first();

        if ($existing) {
            return;
        }

        if ($template->slug === 'blog') {
            $urlPath = '/blog/'.$slug;
        } elseif ($template->use_slug_prefix) {
            $urlPath = '/'.$template->slug.'/'.$slug;
        } else {
            $urlPath = '/'.$slug;
        }

        ContentNode::create([
            'template_id' => $template->id,
            'content_type' => get_class($model),
            'content_id' => $model->id,
            'title' => $title,
            'slug' => $slug,
            'url_path' => $urlPath,
            'is_published' => true,
            'sort_order' => 0,
        ]);
    }

    protected function downloadAndAttachImage($model, string $url, string $collection): void
    {
        if (! method_exists($model, 'addMediaFromUrl')) {
            return;
        }

        try {
            $model->addMediaFromUrl($url)->toMediaCollection($collection);
        } catch (\Exception $e) {
            $this->addLog('warning', 'Image download failed: '.basename($url));
        }
    }

    protected function addLog(string $type, string $message): void
    {
        $this->syncLog[] = [
            'type' => $type,
            'message' => $message,
            'time' => now()->format('H:i:s'),
        ];
    }

    protected function getClient(): WordPressApiClient
    {
        return new WordPressApiClient;
    }

    public function render()
    {
        return view('wordpresssync::livewire.wp-sync-dashboard')
            ->layout('layouts.admin-clean')
            ->title('WordPress Sync');
    }
}
