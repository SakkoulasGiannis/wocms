<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.page-sections', ['pageType' => $section->page_type]) }}"
                       class="text-gray-500 hover:text-gray-700 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $section->name }}</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $availableSectionTypes[$section->section_type]['name'] }}
                            <span class="mx-2">•</span>
                            Page: {{ ucfirst($section->page_type) }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $section->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $section->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex h-[calc(100vh-88px)]">
        <!-- Left Panel: Section Preview -->
        <div class="flex-1 overflow-y-auto bg-white border-r border-gray-200">
            <div class="p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Preview</h2>
                    <button wire:click="refreshSection" class="text-sm text-blue-600 hover:text-blue-800 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Refresh</span>
                    </button>
                </div>

                <!-- Render the section component -->
                <div class="border rounded-lg overflow-hidden shadow-sm bg-gray-50">
                    @php
                        $componentName = 'sections.' . str_replace('_', '-', $section->section_type);
                        $renderError = null;

                        try {
                            $componentView = view()->make('components.' . str_replace('.', '/', $componentName), [
                                'content' => $section->content,
                                'settings' => $section->settings
                            ]);
                            echo $componentView->render();
                        } catch (\Throwable $e) {
                            $renderError = $e->getMessage();
                        }
                    @endphp

                    @if($renderError)
                        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded text-sm">
                            <strong>Error rendering section:</strong> {{ $renderError }}
                            <div class="mt-2 text-xs">
                                Component: {{ $componentName }}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Section Details -->
                <div class="mt-6 space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Content JSON</h3>
                        <pre class="text-xs bg-white rounded p-3 overflow-x-auto border border-gray-200">{{ json_encode($section->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Settings JSON</h3>
                        <pre class="text-xs bg-white rounded p-3 overflow-x-auto border border-gray-200">{{ json_encode($section->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: AI Chat -->
        <div class="w-[450px] bg-white flex flex-col border-l border-gray-200">
            <!-- Chat Header -->
            <div class="bg-blue-600 text-white p-4 border-b border-blue-700">
                <div class="flex items-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold">AI Assistant</h3>
                        <p class="text-xs text-blue-100">Section-specific chat</p>
                    </div>
                </div>
                @if(count($messages) > 0)
                    <button wire:click="clearSectionHistory"
                            onclick="return confirm('Clear section chat history?')"
                            class="mt-2 text-xs text-blue-100 hover:text-white transition underline">
                        Clear history
                    </button>
                @endif
            </div>

            <!-- Context Info Banner -->
            <div class="bg-blue-50 border-b border-blue-200 p-3">
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-xs text-blue-800">
                        <p class="font-semibold">AI knows this section context</p>
                        <p class="mt-1">Ask it to modify content, change settings, or make adjustments.</p>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="section-chat-messages">
                @if(count($messages) === 0)
                    <div class="text-center text-gray-500 mt-8">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="font-medium">AI is ready to help</p>
                        <p class="text-sm mt-2">I already know about this section. Ask me to modify it!</p>
                        <div class="mt-4 text-left bg-gray-50 rounded-lg p-3 max-w-xs mx-auto">
                            <p class="text-xs font-semibold text-gray-700 mb-2">Try asking:</p>
                            <ul class="text-xs text-gray-600 space-y-1">
                                <li>• "Άλλαξε τον τίτλο σε..."</li>
                                <li>• "Βάλε το κείμενο..."</li>
                                <li>• "Κάνε το full screen"</li>
                                <li>• "Άλλαξε το χρώμα του overlay"</li>
                            </ul>
                        </div>
                    </div>
                @else
                    @foreach($messages as $msg)
                        <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%]">
                                <div class="{{ $msg['role'] === 'user' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800' }} rounded-lg p-3">
                                    @php
                                        // Convert markdown-style links to HTML
                                        $messageHtml = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="underline font-semibold hover:opacity-80" target="_blank">$1</a>', $msg['message']);
                                        // Convert **bold** to <strong>
                                        $messageHtml = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $messageHtml);
                                    @endphp
                                    <div class="text-sm whitespace-pre-wrap break-words">{!! nl2br($messageHtml) !!}</div>
                                </div>
                                <div class="flex items-center space-x-2 mt-1 px-1">
                                    <p class="text-xs text-gray-400">{{ $msg['created_at'] }}</p>
                                    @if(isset($msg['intent']) && $msg['intent'] !== 'chat')
                                        <span class="text-xs bg-purple-100 text-purple-600 px-2 py-0.5 rounded">
                                            {{ str_replace('_', ' ', $msg['intent']) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                <!-- Loading Indicator -->
                @if($isLoading)
                    <div class="flex justify-start">
                        <div class="bg-gray-100 rounded-lg p-3">
                            <div class="flex space-x-2">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Input Area -->
            <div class="border-t border-gray-200 p-4 bg-gray-50">
                <form wire:submit.prevent="sendMessage" class="flex space-x-2">
                    <input type="text"
                           wire:model="message"
                           placeholder="Ρώτα με για αλλαγές σε αυτό το section..."
                           class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 text-sm"
                           {{ $isLoading ? 'disabled' : '' }}>
                    <button type="submit"
                            class="bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ $isLoading ? 'disabled' : '' }}>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Auto-scroll script -->
    <script>
        // Function to scroll section chat to bottom
        function scrollSectionChatToBottom() {
            const chatMessages = document.getElementById('section-chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Scroll on Livewire updates
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({ component }) => {
                setTimeout(scrollSectionChatToBottom, 50);
            });
        });

        // Scroll when component loads
        document.addEventListener('DOMContentLoaded', scrollSectionChatToBottom);

        // Also scroll after any mutation in chat
        const observer = new MutationObserver(() => {
            scrollSectionChatToBottom();
        });

        setTimeout(() => {
            const chatContainer = document.getElementById('section-chat-messages');
            if (chatContainer) {
                observer.observe(chatContainer, { childList: true, subtree: true });
            }
        }, 100);
    </script>
</div>
