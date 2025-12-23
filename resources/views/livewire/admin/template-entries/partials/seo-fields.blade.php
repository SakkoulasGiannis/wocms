<div x-show="activeTab === 'seo'" x-cloak class="space-y-6">
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>SEO Optimization:</strong> Fill in these fields to optimize your content for search engines and social media sharing.
                </p>
            </div>
        </div>
    </div>

    {{-- Basic SEO Section --}}
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            Basic SEO
        </h3>

        <div class="space-y-4">
            {{-- SEO Title --}}
            <div x-data="{ titleLength: 0 }">
                <label for="seo_title" class="block text-sm font-medium text-gray-700 mb-1">
                    Meta Title
                    <span class="text-gray-500 font-normal">(60-70 characters recommended)</span>
                </label>
                <input
                    type="text"
                    id="seo_title"
                    value="{{ $this->seoFields['seo_title'] ?? '' }}" onchange="@this.set('seoFields.seo_title', this.value)"
                    x-on:input="titleLength = $event.target.value.length"
                    maxlength="70"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter SEO title"
                >
                <p class="mt-1 text-sm text-gray-500">
                    Character count: <span x-text="titleLength">0</span>/70
                </p>
            </div>

            {{-- SEO Description --}}
            <div x-data="{ descLength: 0 }">
                <label for="seo_description" class="block text-sm font-medium text-gray-700 mb-1">
                    Meta Description
                    <span class="text-gray-500 font-normal">(155-160 characters recommended)</span>
                </label>
                <textarea
                    id="seo_description"
                    onchange="@this.set('seoFields.seo_description', this.value)"
                    x-on:input="descLength = $event.target.value.length"
                    maxlength="160"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter meta description"
                >{{ $this->seoFields['seo_description'] ?? '' }}</textarea>
                <p class="mt-1 text-sm text-gray-500">
                    Character count: <span x-text="descLength">0</span>/160
                </p>
            </div>

            {{-- SEO Keywords --}}
            <div>
                <label for="seo_keywords" class="block text-sm font-medium text-gray-700 mb-1">
                    Meta Keywords
                    <span class="text-gray-500 font-normal">(comma-separated)</span>
                </label>
                <input
                    type="text"
                    id="seo_keywords"
                    value="{{ $this->seoFields['seo_keywords'] ?? '' }}" onchange="@this.set('seoFields.seo_keywords', this.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="keyword1, keyword2, keyword3"
                >
            </div>

            {{-- Focus Keyword --}}
            <div>
                <label for="seo_focus_keyword" class="block text-sm font-medium text-gray-700 mb-1">
                    Focus Keyword
                </label>
                <input
                    type="text"
                    id="seo_focus_keyword"
                    value="{{ $this->seoFields['seo_focus_keyword'] ?? '' }}" onchange="@this.set('seoFields.seo_focus_keyword', this.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter primary keyword to target"
                >
            </div>

            {{-- Canonical URL --}}
            <div>
                <label for="seo_canonical_url" class="block text-sm font-medium text-gray-700 mb-1">
                    Canonical URL
                    <span class="text-gray-500 font-normal">(optional)</span>
                </label>
                <input
                    type="url"
                    id="seo_canonical_url"
                    value="{{ $this->seoFields['seo_canonical_url'] ?? '' }}" onchange="@this.set('seoFields.seo_canonical_url', this.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="https://example.com/canonical-page"
                >
            </div>

            {{-- Robots Settings --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="seo_robots_index" class="block text-sm font-medium text-gray-700 mb-1">
                        Robots Index
                    </label>
                    <select
                        id="seo_robots_index"
                        onchange="@this.set('seoFields.seo_robots_index', this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="index" {{ ($this->seoFields['seo_robots_index'] ?? 'index') === 'index' ? 'selected' : '' }}>Index</option>
                        <option value="noindex" {{ ($this->seoFields['seo_robots_index'] ?? 'index') === 'noindex' ? 'selected' : '' }}>No Index</option>
                    </select>
                </div>

                <div>
                    <label for="seo_robots_follow" class="block text-sm font-medium text-gray-700 mb-1">
                        Robots Follow
                    </label>
                    <select
                        id="seo_robots_follow"
                        onchange="@this.set('seoFields.seo_robots_follow', this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="follow" {{ ($this->seoFields['seo_robots_follow'] ?? 'follow') === 'follow' ? 'selected' : '' }}>Follow</option>
                        <option value="nofollow" {{ ($this->seoFields['seo_robots_follow'] ?? 'follow') === 'nofollow' ? 'selected' : '' }}>No Follow</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Open Graph Section --}}
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            Open Graph (Facebook)
        </h3>

        <div class="space-y-4">
            <div>
                <label for="seo_og_title" class="block text-sm font-medium text-gray-700 mb-1">
                    OG Title
                </label>
                <input
                    type="text"
                    id="seo_og_title"
                    value="{{ $this->seoFields['seo_og_title'] ?? '' }}" onchange="@this.set('seoFields.seo_og_title', this.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Open Graph title"
                >
            </div>

            <div>
                <label for="seo_og_description" class="block text-sm font-medium text-gray-700 mb-1">
                    OG Description
                </label>
                <textarea
                    id="seo_og_description"
                    onchange="@this.set('seoFields.seo_og_description', this.value)"
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Open Graph description"
                >{{ $this->seoFields['seo_og_description'] ?? '' }}</textarea>
            </div>

            <div>
                <label for="seo_og_image" class="block text-sm font-medium text-gray-700 mb-1">
                    OG Image URL
                </label>
                <input
                    type="url"
                    id="seo_og_image"
                    value="{{ $this->seoFields['seo_og_image'] ?? '' }}" onchange="@this.set('seoFields.seo_og_image', this.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="https://example.com/image.jpg"
                >
                <p class="mt-1 text-sm text-gray-500">Recommended: 1200x630px</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="seo_og_type" class="block text-sm font-medium text-gray-700 mb-1">
                        OG Type
                    </label>
                    <select
                        id="seo_og_type"
                        onchange="@this.set('seoFields.seo_og_type', this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="website" {{ ($this->seoFields['seo_og_type'] ?? 'website') === 'website' ? 'selected' : '' }}>Website</option>
                        <option value="article" {{ ($this->seoFields['seo_og_type'] ?? 'website') === 'article' ? 'selected' : '' }}>Article</option>
                        <option value="blog" {{ ($this->seoFields['seo_og_type'] ?? 'website') === 'blog' ? 'selected' : '' }}>Blog</option>
                        <option value="product" {{ ($this->seoFields['seo_og_type'] ?? 'website') === 'product' ? 'selected' : '' }}>Product</option>
                    </select>
                </div>

                <div>
                    <label for="seo_og_url" class="block text-sm font-medium text-gray-700 mb-1">
                        OG URL
                    </label>
                    <input
                        type="url"
                        id="seo_og_url"
                        value="{{ $this->seoFields['seo_og_url'] ?? '' }}" onchange="@this.set('seoFields.seo_og_url', this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="https://example.com/page"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Twitter Card Section --}}
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
            </svg>
            Twitter Card
        </h3>

        <div class="space-y-4">
            <div>
                <label for="seo_twitter_card" class="block text-sm font-medium text-gray-700 mb-1">
                    Card Type
                </label>
                <select
                    id="seo_twitter_card"
                    onchange="@this.set('seoFields.seo_twitter_card', this.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="summary">Summary</option>
                    <option value="summary_large_image">Summary Large Image</option>
                    <option value="app">App</option>
                    <option value="player">Player</option>
                </select>
            </div>

            <div>
                <label for="seo_twitter_title" class="block text-sm font-medium text-gray-700 mb-1">
                    Twitter Title
                </label>
                <input
                    type="text"
                    id="seo_twitter_title"
                    value="{{ $this->seoFields['seo_twitter_title'] ?? '' }}" onchange="@this.set('seoFields.seo_twitter_title', this.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Twitter card title"
                >
            </div>

            <div>
                <label for="seo_twitter_description" class="block text-sm font-medium text-gray-700 mb-1">
                    Twitter Description
                </label>
                <textarea
                    id="seo_twitter_description"
                    onchange="@this.set('seoFields.seo_twitter_description', this.value)"
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Twitter card description"
                >{{ $this->seoFields['seo_twitter_description'] ?? '' }}</textarea>
            </div>

            <div>
                <label for="seo_twitter_image" class="block text-sm font-medium text-gray-700 mb-1">
                    Twitter Image URL
                </label>
                <input
                    type="url"
                    id="seo_twitter_image"
                    value="{{ $this->seoFields['seo_twitter_image'] ?? '' }}" onchange="@this.set('seoFields.seo_twitter_image', this.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="https://example.com/image.jpg"
                >
                <p class="mt-1 text-sm text-gray-500">Recommended: 1200x628px</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="seo_twitter_site" class="block text-sm font-medium text-gray-700 mb-1">
                        Twitter Site
                        <span class="text-gray-500 font-normal">(@username)</span>
                    </label>
                    <input
                        type="text"
                        id="seo_twitter_site"
                        value="{{ $this->seoFields['seo_twitter_site'] ?? '' }}" onchange="@this.set('seoFields.seo_twitter_site', this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="@yoursite"
                    >
                </div>

                <div>
                    <label for="seo_twitter_creator" class="block text-sm font-medium text-gray-700 mb-1">
                        Twitter Creator
                        <span class="text-gray-500 font-normal">(@username)</span>
                    </label>
                    <input
                        type="text"
                        id="seo_twitter_creator"
                        value="{{ $this->seoFields['seo_twitter_creator'] ?? '' }}" onchange="@this.set('seoFields.seo_twitter_creator', this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="@author"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Schema.org / Structured Data Section --}}
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            Schema.org / Structured Data
        </h3>

        <div class="space-y-4">
            <div>
                <label for="seo_schema_type" class="block text-sm font-medium text-gray-700 mb-1">
                    Schema Type
                </label>
                <select
                    id="seo_schema_type"
                    onchange="@this.set('seoFields.seo_schema_type', this.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">None</option>
                    <option value="Article" {{ ($this->seoFields['seo_schema_type'] ?? '') === 'Article' ? 'selected' : '' }}>Article</option>
                    <option value="BlogPosting" {{ ($this->seoFields['seo_schema_type'] ?? '') === 'BlogPosting' ? 'selected' : '' }}>Blog Posting</option>
                    <option value="NewsArticle">News Article</option>
                    <option value="WebPage">Web Page</option>
                    <option value="Product">Product</option>
                    <option value="Event">Event</option>
                    <option value="Organization">Organization</option>
                    <option value="Person">Person</option>
                    <option value="FAQPage">FAQ Page</option>
                    <option value="HowTo">How To</option>
                </select>
            </div>

            <div>
                <label for="seo_schema_custom" class="block text-sm font-medium text-gray-700 mb-1">
                    Custom JSON-LD Schema
                    <span class="text-gray-500 font-normal">(advanced)</span>
                </label>
                <textarea
                    id="seo_schema_custom"
                    onchange="@this.set('seoFields.seo_schema_custom', this.value)"
                    rows="6"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                    placeholder='{"@@context": "https://schema.org", "@@type": "Article", ...}'
                >{{ $this->seoFields['seo_schema_custom'] ?? '' }}</textarea>
                <p class="mt-1 text-sm text-gray-500">Enter custom JSON-LD schema markup</p>
            </div>
        </div>
    </div>

    {{-- Advanced SEO Section --}}
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
            </svg>
            Advanced Settings
        </h3>

        <div class="space-y-4">
            {{-- Redirect --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label for="seo_redirect_url" class="block text-sm font-medium text-gray-700 mb-1">
                        Redirect URL
                    </label>
                    <input
                        type="url"
                        id="seo_redirect_url"
                        value="{{ $this->seoFields['seo_redirect_url'] ?? '' }}" onchange="@this.set('seoFields.seo_redirect_url', this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="https://example.com/redirect-to"
                    >
                </div>

                <div>
                    <label for="seo_redirect_type" class="block text-sm font-medium text-gray-700 mb-1">
                        Type
                    </label>
                    <select
                        id="seo_redirect_type"
                        onchange="@this.set('seoFields.seo_redirect_type', this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="301">301 (Permanent)</option>
                        <option value="302">302 (Temporary)</option>
                    </select>
                </div>
            </div>

            {{-- Sitemap Settings --}}
            <div class="border-t pt-4">
                <h4 class="font-medium text-gray-900 mb-3">XML Sitemap Settings</h4>

                <div class="space-y-3">
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="seo_sitemap_include"
                            {{ ($this->seoFields['seo_sitemap_include'] ?? true) ? 'checked' : '' }}
                            onchange="@this.set('seoFields.seo_sitemap_include', this.checked)"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="seo_sitemap_include" class="ml-2 block text-sm text-gray-700">
                            Include in XML sitemap
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="seo_sitemap_priority" class="block text-sm font-medium text-gray-700 mb-1">
                                Priority (0.0 - 1.0)
                            </label>
                            <input
                                type="text"
                                id="seo_sitemap_priority"
                                value="{{ $this->seoFields['seo_sitemap_priority'] ?? '' }}" onchange="@this.set('seoFields.seo_sitemap_priority', this.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                placeholder="0.5"
                            >
                        </div>

                        <div>
                            <label for="seo_sitemap_changefreq" class="block text-sm font-medium text-gray-700 mb-1">
                                Change Frequency
                            </label>
                            <select
                                id="seo_sitemap_changefreq"
                                onchange="@this.set('seoFields.seo_sitemap_changefreq', this.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="always" {{ ($this->seoFields['seo_sitemap_changefreq'] ?? 'weekly') === 'always' ? 'selected' : '' }}>Always</option>
                                <option value="hourly" {{ ($this->seoFields['seo_sitemap_changefreq'] ?? 'weekly') === 'hourly' ? 'selected' : '' }}>Hourly</option>
                                <option value="daily" {{ ($this->seoFields['seo_sitemap_changefreq'] ?? 'weekly') === 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ ($this->seoFields['seo_sitemap_changefreq'] ?? 'weekly') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ ($this->seoFields['seo_sitemap_changefreq'] ?? 'weekly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ ($this->seoFields['seo_sitemap_changefreq'] ?? 'weekly') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                <option value="never" {{ ($this->seoFields['seo_sitemap_changefreq'] ?? 'weekly') === 'never' ? 'selected' : '' }}>Never</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
