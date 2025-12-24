<div>
    <!-- Floating Chat Button -->
    @if(!$isOpen)
        <button wire:click="toggleChat"
                class="fixed bottom-6 right-6 bg-blue-600 text-white p-4 rounded-full shadow-lg hover:bg-blue-700 transition z-50">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
        </button>
    @endif

    <!-- Chat Window -->
    @if($isOpen)
        <div class="fixed bottom-6 right-6 w-96 h-[600px] bg-white rounded-lg shadow-2xl flex flex-col z-50 border border-gray-200">
            <!-- Chat Header -->
            <div class="bg-blue-600 text-white p-4 rounded-t-lg flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold">AI Assistant</h3>
                        <p class="text-xs text-blue-100">Powered by Claude</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    @if(count($messages) > 0)
                        <button wire:click="clearHistory"
                                onclick="return confirm('Clear all chat history?')"
                                class="text-white hover:text-blue-100 transition"
                                title="Clear history">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    @endif
                    <button wire:click="toggleChat" class="text-white hover:text-blue-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
                @if(count($messages) === 0)
                    <div class="text-center text-gray-500 mt-8">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="font-medium">Start a conversation</p>
                        <p class="text-sm mt-2">Ask me to create content, build templates, or help with your CMS.</p>
                    </div>
                @else
                    @foreach($messages as $msg)
                        <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[80%]">
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
            <div class="border-t border-gray-200 p-4">
                <form wire:submit.prevent="sendMessage" class="flex space-x-2">
                    <input type="text"
                           wire:model="message"
                           placeholder="Type your message..."
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
                <p class="text-xs text-gray-400 mt-2">Try: “Generate an article about Artificial Intelligence or create a product template.”</p>
            </div>
        </div>
    @endif

    <!-- Auto-scroll to bottom script -->
    <script>
        // Update context with current URL
        function updateChatContext() {
            const currentUrl = window.location.pathname;
            @this.call('updateContext', currentUrl);
        }

        // Update context when chat opens
        document.addEventListener('livewire:init', () => {
            Livewire.on('chat-opened', () => {
                updateChatContext();
            });
        });

        // Function to scroll chat to bottom
        function scrollChatToBottom() {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Scroll on Livewire updates
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({ component }) => {
                // Small delay to ensure DOM is updated
                setTimeout(scrollChatToBottom, 50);
            });
        });

        // Scroll when component loads
        document.addEventListener('DOMContentLoaded', scrollChatToBottom);

        // Also scroll after any mutation in chat
        const observer = new MutationObserver(() => {
            scrollChatToBottom();
        });

        // Start observing when DOM is ready
        setTimeout(() => {
            const chatContainer = document.getElementById('chat-messages');
            if (chatContainer) {
                observer.observe(chatContainer, { childList: true, subtree: true });
            }
        }, 100);
    </script>
</div>
