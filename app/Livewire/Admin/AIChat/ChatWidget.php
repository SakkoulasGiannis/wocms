<?php

namespace App\Livewire\Admin\AIChat;

use App\Models\AIChatMessage;
use App\Services\AI\AIContentHandler;
use App\Services\AI\AIManager;
use App\Services\AI\ToolExecutor;
use App\Services\AI\ToolRegistry;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatWidget extends Component
{
    public $isOpen = false;

    public $message = '';

    public $messages = [];

    public $isLoading = false;

    public $currentUrl = '';

    public $currentContext = [];

    /**
     * When a tool requires confirmation, we stash it here and show a modal.
     */
    public ?array $pendingToolCall = null;

    /**
     * In-progress agentic tool loop state: messages for the provider, iteration count.
     */
    public array $toolLoopState = [];

    /**
     * Feature flag — use tool-calling instead of legacy intent routing.
     */
    public bool $useTools = true;

    public function mount()
    {
        $this->loadMessages();
    }

    public function loadMessages()
    {
        $this->messages = AIChatMessage::where('user_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->take(50) // Last 50 messages
            ->get()
            ->map(fn ($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'message' => $msg->message,
                'intent' => $msg->intent,
                'created_at' => $msg->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function toggleChat()
    {
        $this->isOpen = ! $this->isOpen;

        if ($this->isOpen) {
            $this->dispatch('chat-opened');
        }
    }

    public function updateContext($url)
    {
        $this->currentUrl = $url;
        $this->currentContext = $this->parseUrlContext($url);
    }

    protected function parseUrlContext($url): array
    {
        $context = [
            'url' => $url,
            'type' => 'unknown',
        ];

        // Parse admin URLs to detect template/entry editing
        // Pattern: /admin/{template}/{id}/edit
        if (preg_match('#/admin/([^/]+)/(\d+)/edit#', $url, $matches)) {
            $templateSlug = $matches[1];
            $entryId = $matches[2];

            $context['type'] = 'entry_edit';
            $context['template_slug'] = $templateSlug;
            $context['entry_id'] = $entryId;

            // Try to load the entry
            try {
                $template = \App\Models\Template::where('slug', $templateSlug)->first();
                if ($template) {
                    $context['template_name'] = $template->name;

                    // Get the dynamic model
                    $modelClass = 'App\\Models\\'.str_replace(' ', '', ucwords(str_replace('-', ' ', $templateSlug)));
                    if (class_exists($modelClass)) {
                        $entry = $modelClass::find($entryId);
                        if ($entry) {
                            $context['entry_title'] = $entry->title ?? $entry->name ?? 'Entry #'.$entryId;
                            $context['entry_data'] = $entry->toArray();
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('AI Context Parse Error', ['error' => $e->getMessage()]);
            }
        }
        // Pattern: /admin/{template}/create
        elseif (preg_match('#/admin/([^/]+)/create#', $url, $matches)) {
            $context['type'] = 'entry_create';
            $context['template_slug'] = $matches[1];
        }
        // Pattern: /admin/{template}
        elseif (preg_match('#/admin/([^/]+)$#', $url, $matches)) {
            $context['type'] = 'template_list';
            $context['template_slug'] = $matches[1];
        }

        return $context;
    }

    public function sendMessage()
    {
        if (empty(trim($this->message))) {
            return;
        }

        $userMessage = trim($this->message);
        $this->message = '';
        $this->isLoading = true;

        // Save user message
        $userMsg = AIChatMessage::create([
            'user_id' => Auth::id(),
            'role' => 'user',
            'message' => $userMessage,
        ]);

        $this->messages[] = [
            'id' => $userMsg->id,
            'role' => 'user',
            'message' => $userMessage,
            'created_at' => 'Just now',
        ];

        try {
            // Get AI response
            $aiManager = new AIManager;

            // Detect intent
            $intent = $aiManager->detectIntent($userMessage);

            // Handle based on intent
            if ($intent === 'create_content') {
                // Use content handler for content generation
                $contentHandler = new AIContentHandler;
                $result = $contentHandler->handleContentGeneration($userMessage);

                if ($result['success']) {
                    // Check if this is a batch creation (multiple entries)
                    if (isset($result['entries']) && count($result['entries']) > 1) {
                        // Batch response - message is already formatted
                        $message = $result['message'];
                        $message .= "\nΘέλεις να κάνω αλλαγές ή να δημιουργήσω κάτι άλλο;";
                    } else {
                        // Single entry response
                        $message = $result['message']."\n\n";
                        $message .= '📝 **Τίτλος**: '.($result['data']['title'] ?? 'N/A')."\n";
                        $message .= '🔗 [Επεξεργασία του entry]('.$result['preview_url'].")\n";

                        if (! empty($result['frontend_url'])) {
                            $message .= '🌐 [Προβολή στο frontend]('.$result['frontend_url'].")\n";
                        }

                        $message .= "\nΘέλεις να κάνω αλλαγές ή να δημιουργήσω κάτι άλλο;";
                    }

                    $aiMsg = AIChatMessage::create([
                        'user_id' => Auth::id(),
                        'role' => 'assistant',
                        'message' => $message,
                        'intent' => $intent,
                        'metadata' => $result,
                    ]);

                    $this->messages[] = [
                        'id' => $aiMsg->id,
                        'role' => 'assistant',
                        'message' => $message,
                        'intent' => $intent,
                        'created_at' => 'Just now',
                        'preview_url' => $result['preview_url'] ?? null,
                    ];
                } else {
                    // Error in content generation
                    $this->messages[] = [
                        'id' => null,
                        'role' => 'assistant',
                        'message' => $result['message'],
                        'created_at' => 'Just now',
                    ];
                }
            } elseif ($intent === 'update_content') {
                // Use content handler for content updates
                $contentHandler = new AIContentHandler;
                $context = $this->buildContext();
                $result = $contentHandler->handleContentUpdate($userMessage, $context);

                if ($result['success']) {
                    // Update response
                    $message = $result['message']."\n\n";
                    $message .= '🔗 [Επεξεργασία του entry]('.$result['preview_url'].")\n";
                    $message .= "\nΘέλεις να κάνω άλλες αλλαγές;";

                    $aiMsg = AIChatMessage::create([
                        'user_id' => Auth::id(),
                        'role' => 'assistant',
                        'message' => $message,
                        'intent' => $intent,
                        'metadata' => $result,
                    ]);

                    $this->messages[] = [
                        'id' => $aiMsg->id,
                        'role' => 'assistant',
                        'message' => $message,
                        'intent' => $intent,
                        'created_at' => 'Just now',
                    ];
                } else {
                    // Error in update
                    $this->messages[] = [
                        'id' => null,
                        'role' => 'assistant',
                        'message' => $result['message'],
                        'created_at' => 'Just now',
                    ];
                }
            } elseif ($intent === 'create_template') {
                // Use content handler for template creation
                $contentHandler = new AIContentHandler;
                $result = $contentHandler->handleTemplateCreation($userMessage);

                $message = $result['message'];
                if (! $result['success']) {
                    $message .= "\n\nΘέλεις να δοκιμάσω ξανά με διαφορετική προσέγγιση;";
                } else {
                    $message .= "\n\nΜπορώ να σου δημιουργήσω και περιεχόμενο για αυτό το template;";
                }

                $aiMsg = AIChatMessage::create([
                    'user_id' => Auth::id(),
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'metadata' => $result,
                ]);

                $this->messages[] = [
                    'id' => $aiMsg->id,
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'created_at' => 'Just now',
                ];
            } elseif ($intent === 'modify_template') {
                // Use content handler for template modification
                $contentHandler = new AIContentHandler;
                $context = $this->buildContext();
                $result = $contentHandler->handleTemplateModification($userMessage, $context);

                $message = $result['message'];
                if (! $result['success']) {
                    $message .= "\n\nΘέλεις να δοκιμάσω με διαφορετικό τρόπο;";
                } else {
                    $message .= "\n\nΘέλεις να κάνω και άλλες αλλαγές;";
                }

                $aiMsg = AIChatMessage::create([
                    'user_id' => Auth::id(),
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'metadata' => $result,
                ]);

                $this->messages[] = [
                    'id' => $aiMsg->id,
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'created_at' => 'Just now',
                ];
            } elseif ($intent === 'modify_frontend') {
                // Use content handler for frontend modification
                $contentHandler = new AIContentHandler;
                $result = $contentHandler->handleFrontendModification($userMessage);

                $message = $result['message'];
                if (! $result['success']) {
                    $message .= "\n\nΘέλεις να δοκιμάσω με διαφορετικό τρόπο;";
                } else {
                    $message .= "\n\nΘέλεις να κάνω και άλλες αλλαγές;";
                }

                $aiMsg = AIChatMessage::create([
                    'user_id' => Auth::id(),
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'metadata' => $result,
                ]);

                $this->messages[] = [
                    'id' => $aiMsg->id,
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'created_at' => 'Just now',
                ];
            } elseif ($intent === 'create_page_section') {
                // Use content handler for page section creation
                $contentHandler = new AIContentHandler;
                $result = $contentHandler->handlePageSectionCreation($userMessage);

                $message = $result['message'];
                if ($result['success']) {
                    $sectionableRoute = str_replace('\\', '-', $result['sectionable_type'] ?? 'App\Models\Home');
                    $sectionableId = $result['sectionable_id'] ?? 1;
                    $message .= "\n\n🔗 [Δες όλα τα sections](/admin/page-sections/manage/{$sectionableRoute}/{$sectionableId})";
                    $message .= "\n\nΘέλεις να κάνω αλλαγές ή να προσθέσω κάτι άλλο;";
                }

                $aiMsg = AIChatMessage::create([
                    'user_id' => Auth::id(),
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'metadata' => $result,
                ]);

                $this->messages[] = [
                    'id' => $aiMsg->id,
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'created_at' => 'Just now',
                ];
            } elseif ($intent === 'modify_page_section') {
                // Use content handler for page section modification
                $contentHandler = new AIContentHandler;
                $context = $this->buildContext();
                $result = $contentHandler->handlePageSectionModification($userMessage, $context);

                $message = $result['message'];
                if ($result['success']) {
                    $message .= "\n\nΘέλεις να κάνω και άλλες αλλαγές;";
                }

                $aiMsg = AIChatMessage::create([
                    'user_id' => Auth::id(),
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'metadata' => $result,
                ]);

                $this->messages[] = [
                    'id' => $aiMsg->id,
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'created_at' => 'Just now',
                ];
            } elseif ($intent === 'reorder_page_section') {
                // Use content handler for page section reordering
                $contentHandler = new AIContentHandler;
                $result = $contentHandler->handlePageSectionReordering($userMessage);

                $message = $result['message'];
                if ($result['success']) {
                    $message .= "\n\n🔗 [Δες τη νέα σειρά](/admin/page-sections/home)";
                    $message .= "\n\nΘέλεις να κάνω και άλλες αλλαγές;";
                }

                $aiMsg = AIChatMessage::create([
                    'user_id' => Auth::id(),
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'metadata' => $result,
                ]);

                $this->messages[] = [
                    'id' => $aiMsg->id,
                    'role' => 'assistant',
                    'message' => $message,
                    'intent' => $intent,
                    'created_at' => 'Just now',
                ];
            } else {
                // Regular chat response
                $context = $this->buildContext();
                $response = $aiManager->chat($userMessage, $context);

                if ($response->success) {
                    $aiMsg = AIChatMessage::create([
                        'user_id' => Auth::id(),
                        'role' => 'assistant',
                        'message' => $response->content,
                        'intent' => $intent,
                        'metadata' => $response->data,
                    ]);

                    $this->messages[] = [
                        'id' => $aiMsg->id,
                        'role' => 'assistant',
                        'message' => $response->content,
                        'intent' => $intent,
                        'created_at' => 'Just now',
                    ];
                } else {
                    $this->messages[] = [
                        'id' => null,
                        'role' => 'assistant',
                        'message' => '❌ Error: '.$response->error,
                        'created_at' => 'Just now',
                    ];
                }
            }

        } catch (\Exception $e) {
            $this->messages[] = [
                'id' => null,
                'role' => 'assistant',
                'message' => '❌ Error: '.$e->getMessage(),
                'created_at' => 'Just now',
            ];
        }

        $this->isLoading = false;
    }

    public function clearHistory()
    {
        AIChatMessage::where('user_id', Auth::id())->delete();
        $this->messages = [];
    }

    /* ─────────────────────────────────────────────────────────────────
     * TOOL-CALLING FLOW (Phase 1 MVP)
     * ───────────────────────────────────────────────────────────────── */

    /**
     * Entry point for tool-based chat. Runs an agentic loop:
     *   user message → provider → tool_calls? → execute → back to provider → ... until text reply.
     */
    public function sendMessageWithTools(): void
    {
        if (empty(trim($this->message))) {
            return;
        }

        $userMessage = trim($this->message);
        $this->message = '';
        $this->isLoading = true;

        $userMsg = AIChatMessage::create([
            'user_id' => Auth::id(),
            'role' => 'user',
            'message' => $userMessage,
        ]);

        $this->messages[] = [
            'id' => $userMsg->id,
            'role' => 'user',
            'message' => $userMessage,
            'created_at' => 'Just now',
        ];

        // Initialize the conversation state
        $this->toolLoopState = [
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
            'iterations' => 0,
            'user_msg_id' => $userMsg->id,
        ];

        $this->runToolLoop();
    }

    /**
     * Agentic loop — call provider, execute any non-destructive tools, pause for confirmation on destructive ones.
     */
    protected function runToolLoop(): void
    {
        $registry = app(ToolRegistry::class);
        $executor = app(ToolExecutor::class);
        $manager = app(AIManager::class);
        $provider = \App\Models\Setting::get('ai_provider', 'claude');

        $tools = $provider === 'chatgpt'
            ? $registry->toOpenAISchema()
            : $registry->toClaudeSchema();

        $systemPrompt = $this->buildSystemPrompt();

        for ($i = $this->toolLoopState['iterations']; $i < 5; $i++) {
            $this->toolLoopState['iterations'] = $i + 1;

            try {
                $response = $manager->chatWithTools(
                    $this->toolLoopState['messages'],
                    $tools,
                    $systemPrompt
                );
            } catch (\Throwable $e) {
                $this->pushAssistantMessage('⚠️ Σφάλμα: '.$e->getMessage());
                $this->isLoading = false;

                return;
            }

            // No tool calls → final reply
            if (! $response->hasToolCalls()) {
                $text = trim($response->text) ?: 'OK.';
                $this->pushAssistantMessage($text);
                $this->isLoading = false;
                $this->toolLoopState = [];

                return;
            }

            // Add assistant's message (with tool_use blocks) to state
            $assistantContent = [];
            if ($response->text) {
                $assistantContent[] = ['type' => 'text', 'text' => $response->text];
            }
            foreach ($response->toolCalls as $call) {
                $assistantContent[] = [
                    'type' => 'tool_use',
                    'id' => $call['id'],
                    'name' => $call['name'],
                    'input' => $call['arguments'],
                ];
            }
            $this->toolLoopState['messages'][] = ['role' => 'assistant', 'content' => $assistantContent];

            // Execute each tool call
            foreach ($response->toolCalls as $call) {
                $result = $executor->execute($call['name'], $call['arguments'], [
                    'provider' => $provider,
                    'chat_message_id' => $this->toolLoopState['user_msg_id'] ?? null,
                    'confirmed' => false,
                ]);

                // If confirmation needed, stash and return — user will confirm via UI
                if (($result['requires_confirmation'] ?? false) === true) {
                    $this->pendingToolCall = [
                        'tool_call_id' => $call['id'],
                        'tool_name' => $call['name'],
                        'tool_label' => $result['tool_label'] ?? $call['name'],
                        'args' => $call['arguments'],
                        'preview' => $result['preview'] ?? '',
                        'audit_id' => $result['audit_id'] ?? null,
                    ];
                    $this->isLoading = false;
                    $this->pushAssistantMessage('⏳ Περιμένω επιβεβαίωση για: '.($result['preview'] ?? $call['name']));

                    return;
                }

                // Append tool result to conversation
                $this->toolLoopState['messages'][] = [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'tool_result',
                        'tool_use_id' => $call['id'],
                        'content' => json_encode([
                            'success' => $result['success'],
                            'message' => $result['message'],
                            'data' => $result['data'] ?? [],
                        ]),
                    ]],
                ];
            }
        }

        // Max iterations reached
        $this->pushAssistantMessage('⚠️ Έφτασα το μέγιστο όριο βημάτων (5). Διέκοψα.');
        $this->isLoading = false;
        $this->toolLoopState = [];
    }

    /**
     * User confirmed the pending tool — execute and continue the loop.
     */
    public function confirmPendingTool(): void
    {
        if (! $this->pendingToolCall) {
            return;
        }

        $executor = app(ToolExecutor::class);
        $provider = \App\Models\Setting::get('ai_provider', 'claude');

        $result = $executor->execute(
            $this->pendingToolCall['tool_name'],
            $this->pendingToolCall['args'],
            [
                'provider' => $provider,
                'chat_message_id' => $this->toolLoopState['user_msg_id'] ?? null,
                'confirmed' => true,
            ]
        );

        // Append tool result
        $this->toolLoopState['messages'][] = [
            'role' => 'user',
            'content' => [[
                'type' => 'tool_result',
                'tool_use_id' => $this->pendingToolCall['tool_call_id'],
                'content' => json_encode([
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'data' => $result['data'] ?? [],
                ]),
            ]],
        ];

        $icon = $result['success'] ? '✅' : '❌';
        $this->pushAssistantMessage("{$icon} ".$result['message']);

        $this->pendingToolCall = null;
        $this->isLoading = true;
        $this->runToolLoop();
    }

    public function cancelPendingTool(): void
    {
        if (! $this->pendingToolCall) {
            return;
        }

        // Append rejection to conversation
        $this->toolLoopState['messages'][] = [
            'role' => 'user',
            'content' => [[
                'type' => 'tool_result',
                'tool_use_id' => $this->pendingToolCall['tool_call_id'],
                'content' => json_encode([
                    'success' => false,
                    'message' => 'User cancelled this action.',
                ]),
            ]],
        ];

        $this->pushAssistantMessage('❌ Ακύρωσες την ενέργεια.');

        $this->pendingToolCall = null;
        $this->isLoading = true;
        $this->runToolLoop();
    }

    protected function buildSystemPrompt(): string
    {
        $context = $this->buildContext();
        $contextStr = $this->currentUrl ? "Current admin URL: {$this->currentUrl}\n" : '';
        if (! empty($this->currentContext)) {
            $contextStr .= 'Context: '.json_encode($this->currentContext, JSON_UNESCAPED_UNICODE)."\n";
        }

        return <<<PROMPT
You are the AI assistant for a Laravel CMS (Kreta Eiendom real estate). You help the admin manage content through TOOL CALLS.

IMPORTANT RULES:
1. When the user asks for an action (create, update, etc.), use the appropriate tool.
2. If the user is ambiguous, ask ONE clarifying question first — don't call tools blindly.
3. Tools that modify data are executed only after user confirmation — you don't need to ask "shall I proceed?" yourself, the system does that.
4. When you finish, give a short natural-language summary in Greek (unless user wrote in English).
5. If a tool fails, explain why and suggest a fix.
6. Do not guess IDs — if you need an ID (e.g. section_id), check the user's message or ask.

{$contextStr}
Current conversation context ready. Reply naturally and call tools when needed.
PROMPT;
    }

    protected function pushAssistantMessage(string $text): void
    {
        $msg = AIChatMessage::create([
            'user_id' => Auth::id(),
            'role' => 'assistant',
            'message' => $text,
        ]);
        $this->messages[] = [
            'id' => $msg->id,
            'role' => 'assistant',
            'message' => $text,
            'created_at' => 'Just now',
        ];
    }

    protected function buildContext(): array
    {
        // Get last 5 messages for context
        $recentMessages = array_slice($this->messages, -5);

        $context = [
            'conversation_history' => array_map(fn ($msg) => [
                'role' => $msg['role'],
                'message' => $msg['message'],
            ], $recentMessages),
        ];

        // Add page context if available
        if (! empty($this->currentContext)) {
            $context['page'] = $this->currentContext;
        }

        return $context;
    }

    public function render()
    {
        return view('livewire.admin.a-i-chat.chat-widget');
    }
}
