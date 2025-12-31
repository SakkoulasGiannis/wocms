@section('title', 'Settings')
@section('page-title', 'Settings')

<div>
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button wire:click="$set('activeTab', 'general')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition {{ $activeTab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    General
                </button>

                <button wire:click="$set('activeTab', 'ai')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition {{ $activeTab === 'ai' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    AI Configuration
                </button>

                <button wire:click="$set('activeTab', 'prompts')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition {{ $activeTab === 'prompts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    AI Prompts
                </button>

                <button wire:click="$set('activeTab', 'grapejs')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition {{ $activeTab === 'grapejs' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    GrapeJS
                </button>

                <button wire:click="$set('activeTab', 'image-sizes')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition {{ $activeTab === 'image-sizes' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Image Sizes
                </button>

                <button wire:click="$set('activeTab', 'theme')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition {{ $activeTab === 'theme' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Theme
                </button>
            </nav>
        </div>
    </div>

    <!-- General Tab -->
    @if($activeTab === 'general')
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">General Settings</h2>

            <form wire:submit.prevent="saveGeneral">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Site Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               wire:model="site_name"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                               placeholder="My CMS">
                        @error('site_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Site Logo
                        </label>

                        <!-- Current Logo Preview -->
                        @if($site_logo && !$site_logo_upload)
                            <div class="mb-3">
                                <p class="text-xs text-gray-500 mb-2">Current Logo:</p>
                                <img src="{{ $site_logo }}" alt="Site Logo" class="max-w-xs max-h-24 rounded-lg shadow-sm border border-gray-200">
                            </div>
                        @endif

                        <!-- File Upload Input -->
                        <input type="file"
                               wire:model="site_logo_upload"
                               accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">

                        <!-- Loading Indicator -->
                        <div wire:loading wire:target="site_logo_upload" class="mt-2 text-sm text-blue-600">
                            Uploading...
                        </div>

                        <!-- New Upload Preview -->
                        @if($site_logo_upload)
                            <div class="mt-3">
                                <p class="text-xs text-gray-500 mb-2">New Logo Preview:</p>
                                <img src="{{ $site_logo_upload->temporaryUrl() }}" class="max-w-xs max-h-24 rounded-lg shadow-sm border border-gray-200">
                            </div>
                        @endif

                        @error('site_logo_upload')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <p class="mt-1 text-xs text-gray-500">
                            Upload a logo image (PNG, JPG, SVG). Max size: 2MB.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Favicon
                        </label>

                        <!-- Current Favicon Preview -->
                        @if($site_favicon && !$site_favicon_upload)
                            <div class="mb-3">
                                <p class="text-xs text-gray-500 mb-2">Current Favicon:</p>
                                <img src="{{ $site_favicon }}" alt="Favicon" class="w-8 h-8 rounded shadow-sm border border-gray-200">
                            </div>
                        @endif

                        <!-- File Upload Input -->
                        <input type="file"
                               wire:model="site_favicon_upload"
                               accept="image/png,image/x-icon,image/jpeg,image/jpg"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">

                        <!-- Loading Indicator -->
                        <div wire:loading wire:target="site_favicon_upload" class="mt-2 text-sm text-blue-600">
                            Uploading...
                        </div>

                        <!-- New Upload Preview -->
                        @if($site_favicon_upload)
                            <div class="mt-3">
                                <p class="text-xs text-gray-500 mb-2">New Favicon Preview:</p>
                                <img src="{{ $site_favicon_upload->temporaryUrl() }}" class="w-8 h-8 rounded shadow-sm border border-gray-200">
                            </div>
                        @endif

                        @error('site_favicon_upload')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <p class="mt-1 text-xs text-gray-500">
                            Upload a favicon image (PNG, ICO, JPG recommended 32x32 or 16x16). Max size: 1MB.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Site Description
                        </label>
                        <textarea wire:model="site_description"
                                  rows="3"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                                  placeholder="A brief description of your website..."></textarea>
                        @error('site_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="border-t pt-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox"
                                       wire:model="under_construction"
                                       id="under_construction"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                            </div>
                            <div class="ml-3">
                                <label for="under_construction" class="font-medium text-gray-700 cursor-pointer">
                                    🚧 Enable Under Construction Mode
                                </label>
                                <p class="text-sm text-gray-500 mt-1">
                                    When enabled, the frontend will display a "Coming Soon" page to visitors. Admin users will still have access to the admin panel.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save General Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- AI Tab -->
    @if($activeTab === 'ai')
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">AI Configuration</h2>

            <form wire:submit.prevent="saveAI">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            AI Provider <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="ai_provider"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                            <option value="claude">Claude (Anthropic)</option>
                            <option value="chatgpt">ChatGPT (OpenAI)</option>
                            <option value="ollama">Ollama (Local)</option>
                        </select>
                        @error('ai_provider')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($ai_provider === 'claude')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Claude API Key <span class="text-red-500">*</span>
                            </label>
                            <input type="password"
                                   wire:model="ai_claude_api_key"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 font-mono text-sm"
                                   placeholder="sk-ant-...">
                            @error('ai_claude_api_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Get your API key from <a href="https://console.anthropic.com/" target="_blank" class="text-blue-600 hover:underline">Anthropic Console</a>
                            </p>
                        </div>
                    @endif

                    @if($ai_provider === 'chatgpt')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ChatGPT API Key <span class="text-red-500">*</span>
                            </label>
                            <input type="password"
                                   wire:model="ai_chatgpt_api_key"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 font-mono text-sm"
                                   placeholder="sk-...">
                            @error('ai_chatgpt_api_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:underline">OpenAI Platform</a>
                            </p>
                        </div>
                    @endif

                    @if($ai_provider === 'ollama')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Ollama Server URL
                            </label>
                            <input type="text"
                                   wire:model="ai_ollama_url"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                                   placeholder="http://localhost:11434">
                            @error('ai_ollama_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Default Ollama server runs on http://localhost:11434
                            </p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Model <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               wire:model="ai_model"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                               placeholder="{{ $ai_provider === 'claude' ? 'claude-3-5-sonnet-20241022' : ($ai_provider === 'chatgpt' ? 'gpt-4-turbo-preview' : 'llama2') }}">
                        @error('ai_model')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            @if($ai_provider === 'claude')
                                Recommended: claude-3-5-sonnet-20241022, claude-3-opus-20240229
                            @elseif($ai_provider === 'chatgpt')
                                Recommended: gpt-4-turbo-preview, gpt-4, gpt-3.5-turbo
                            @else
                                Available models: llama2, codellama, mistral, etc.
                            @endif
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save AI Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- AI Prompts Tab -->
    @if($activeTab === 'prompts')
        <div class="bg-white rounded-lg shadow p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">AI Prompts</h2>
                <p class="text-sm text-gray-600">Customize the AI prompts used for content generation. These are the core instructions given to the AI.</p>
                <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs text-blue-800">
                        <strong>Note:</strong> Be careful when editing prompts. Changes may affect AI behavior and output quality.
                        Use the "Reset to Default" button if something goes wrong.
                    </p>
                </div>
            </div>

            <form wire:submit.prevent="savePrompts">
                <div class="space-y-8">
                    <!-- Structured HTML Generation Prompt -->
                    <div class="border border-gray-200 rounded-lg p-5 bg-gray-50">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="text-md font-semibold text-gray-900">🎨 Structured HTML Generation</h3>
                                <p class="text-xs text-gray-600 mt-1">Used when creating new page sections with AI-generated HTML structure and Tailwind CSS.</p>
                            </div>
                            <button type="button"
                                    wire:click="resetPrompt('structured_html')"
                                    class="text-xs text-blue-600 hover:text-blue-800">
                                Reset to Default
                            </button>
                        </div>
                        <textarea wire:model="prompt_structured_html"
                                  rows="12"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-3 font-mono text-xs"
                                  placeholder="System prompt for structured HTML generation..."></textarea>
                        @error('prompt_structured_html')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Content Generation Prompt -->
                    <div class="border border-gray-200 rounded-lg p-5 bg-gray-50">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="text-md font-semibold text-gray-900">📝 Content Generation</h3>
                                <p class="text-xs text-gray-600 mt-1">Used when AI creates blog posts, articles, or other template-based content.</p>
                            </div>
                            <button type="button"
                                    wire:click="resetPrompt('content_generation')"
                                    class="text-xs text-blue-600 hover:text-blue-800">
                                Reset to Default
                            </button>
                        </div>
                        <textarea wire:model="prompt_content_generation"
                                  rows="8"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-3 font-mono text-xs"
                                  placeholder="System prompt for content generation..."></textarea>
                        @error('prompt_content_generation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Template Generation Prompt -->
                    <div class="border border-gray-200 rounded-lg p-5 bg-gray-50">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="text-md font-semibold text-gray-900">🏗️ Template Generation</h3>
                                <p class="text-xs text-gray-600 mt-1">Used when AI creates new custom templates with fields and database structure.</p>
                            </div>
                            <button type="button"
                                    wire:click="resetPrompt('template_generation')"
                                    class="text-xs text-blue-600 hover:text-blue-800">
                                Reset to Default
                            </button>
                        </div>
                        <textarea wire:model="prompt_template_generation"
                                  rows="8"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-3 font-mono text-xs"
                                  placeholder="System prompt for template generation..."></textarea>
                        @error('prompt_template_generation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <button type="button"
                                wire:click="resetAllPrompts"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Reset All to Defaults
                        </button>

                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Prompts
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- Image Sizes Tab -->
    @if($activeTab === 'image-sizes')
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Image Sizes</h2>
                    <p class="text-sm text-gray-600 mt-1">Define custom image sizes for automatic generation on upload.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button wire:click="regenerateAllImages"
                            wire:confirm="This will regenerate all image conversions for all media. This may take a while. Continue?"
                            wire:loading.attr="disabled"
                            wire:target="regenerateAllImages"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg wire:loading.remove wire:target="regenerateAllImages" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg wire:loading wire:target="regenerateAllImages" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="regenerateAllImages">Regenerate All Images</span>
                        <span wire:loading wire:target="regenerateAllImages">Regenerating...</span>
                    </button>
                    <button wire:click="addImageSize"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Add Image Size</span>
                    </button>
                </div>
            </div>

            <!-- Add/Edit Form -->
            @if($showImageSizeForm)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <h3 class="text-md font-semibold text-gray-900 mb-4">
                        {{ $editingImageSize ? 'Edit Image Size' : 'Add New Image Size' }}
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Label</label>
                            <input type="text"
                                   wire:model="imageSizeForm.label"
                                   placeholder="e.g., Thumbnail"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 p-2">
                            @error('imageSizeForm.label') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mode</label>
                            <select wire:model="imageSizeForm.mode"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 p-2">
                                <option value="crop">Crop (exact dimensions)</option>
                                <option value="fit">Fit (maintain aspect ratio)</option>
                                <option value="resize">Resize (force dimensions)</option>
                            </select>
                            @error('imageSizeForm.mode') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Width (px)</label>
                            <input type="number"
                                   wire:model="imageSizeForm.width"
                                   placeholder="350"
                                   min="1"
                                   max="5000"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 p-2">
                            @error('imageSizeForm.width') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Height (px)</label>
                            <input type="number"
                                   wire:model="imageSizeForm.height"
                                   placeholder="350"
                                   min="1"
                                   max="5000"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 p-2">
                            @error('imageSizeForm.height') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-2 mt-4">
                        <button wire:click="cancelImageSize"
                                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button wire:click="saveImageSize"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            {{ $editingImageSize ? 'Update' : 'Save' }}
                        </button>
                    </div>
                </div>
            @endif

            <!-- Image Sizes List -->
            @if(count($imageSizes) > 0)
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Dimensions</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Mode</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($imageSizes as $size)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $size['label'] }}</div>
                                            <div class="text-xs text-gray-500 font-mono">{{ $size['name'] }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $size['width'] }} × {{ $size['height'] }} px
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                     {{ $size['mode'] === 'crop' ? 'bg-blue-100 text-blue-800' : ($size['mode'] === 'fit' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                                            {{ ucfirst($size['mode']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button wire:click="toggleImageSizeActive({{ $size['id'] }})"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition
                                                       {{ $size['is_active'] ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                            {{ $size['is_active'] ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm space-x-2">
                                        <button wire:click="editImageSize({{ $size['id'] }})"
                                                class="text-blue-600 hover:text-blue-800">
                                            Edit
                                        </button>
                                        <button wire:click="deleteImageSize({{ $size['id'] }})"
                                                onclick="return confirm('Are you sure you want to delete this image size?')"
                                                class="text-red-600 hover:text-red-800">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-600 font-medium">No image sizes defined yet</p>
                    <p class="text-sm text-gray-500 mt-1">Click "Add Image Size" to create your first one</p>
                </div>
            @endif

            <!-- Info Box -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">How it works:</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>When you upload an image, all active sizes will be generated automatically</li>
                            <li><strong>Crop:</strong> Resizes and crops to exact dimensions (best for thumbnails)</li>
                            <li><strong>Fit:</strong> Resizes while maintaining aspect ratio (best for responsive images)</li>
                            <li><strong>Resize:</strong> Forces exact dimensions without maintaining aspect ratio</li>
                            <li>Use the generated image name in your templates: <code class="bg-blue-100 px-1 rounded">thumbnail</code>, <code class="bg-blue-100 px-1 rounded">small_thumbnail</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- GrapeJS Tab -->
    @if($activeTab === 'grapejs')
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">GrapeJS Settings</h2>
            <p class="text-sm text-gray-600 mb-6">Configure how GrapeJS editor behaves when generating Blade templates.</p>

            <form wire:submit.prevent="saveGrapeJS">
                <div class="space-y-6">
                    <!-- Include CSS in Blade Template -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox"
                                   wire:model="grapejs_include_css_in_blade"
                                   id="grapejs_include_css_in_blade"
                                   class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </div>
                        <div class="ml-3">
                            <label for="grapejs_include_css_in_blade" class="font-medium text-gray-700">
                                Include CSS in Blade Templates
                            </label>
                            <p class="text-sm text-gray-500 mt-1">
                                When enabled, GrapeJS CSS styles will be included in generated Blade templates using <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ '@' }}push('styles')</code>.
                                Disable this if you want to manage styles separately or use only Tailwind CSS classes.
                            </p>

                            <!-- Example -->
                            <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                <p class="text-xs text-gray-600 font-medium mb-2">When enabled, generated Blade will include:</p>
                                <pre class="text-xs text-gray-700 font-mono">{{ '@' }}push('styles')
&lt;style&gt;
* { box-sizing: border-box; }
.container { max-width: 1200px; }
...
&lt;/style&gt;
{{ '@' }}endpush</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Save GrapeJS Settings
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Theme Tab -->
    @if($activeTab === 'theme')
        <div class="space-y-6">
            <!-- Asset Build Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Frontend Assets</h2>

                <div class="space-y-4">
                    <div>
                        <p class="text-gray-600 mb-4">
                            Build and compile frontend assets (CSS, JavaScript) using Vite.
                            This is required after making changes to frontend files.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button wire:click="buildAssets"
                                wire:loading.attr="disabled"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium rounded-lg shadow-sm transition flex items-center gap-2">
                            <svg wire:loading.remove wire:target="buildAssets" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <svg wire:loading wire:target="buildAssets" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="buildAssets">Build Assets</span>
                            <span wire:loading wire:target="buildAssets">Building...</span>
                        </button>

                        <div class="text-sm text-gray-500">
                            <p>This will run: <code class="bg-gray-100 px-2 py-1 rounded">npm run build</code></p>
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="text-sm font-semibold text-blue-900 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            When to build assets
                        </h3>
                        <ul class="text-sm text-blue-800 space-y-1 ml-6 list-disc">
                            <li>After modifying CSS or JavaScript files</li>
                            <li>After updating frontend dependencies</li>
                            <li>Before deploying to production</li>
                            <li>When styles or scripts are not updating</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Future Theme Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Theme Customization</h2>
                <p class="text-gray-600">Additional theme customization options will be available here soon.</p>
            </div>
        </div>
    @endif
</div>
