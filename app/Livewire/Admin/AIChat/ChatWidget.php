<?php

namespace App\Livewire\Admin\AIChat;

use App\Models\AIChatMessage;
use App\Services\AI\AIContentHandler;
use App\Services\AI\AIManager;
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
