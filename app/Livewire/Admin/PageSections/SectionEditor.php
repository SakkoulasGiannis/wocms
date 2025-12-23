<?php

namespace App\Livewire\Admin\PageSections;

use App\Models\PageSection;
use App\Models\AIChatMessage;
use App\Services\AI\AIManager;
use App\Services\AI\AIContentHandler;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class SectionEditor extends Component
{
    public $sectionId;
    public $section;
    public $availableSectionTypes = [];

    // AI Chat
    public $message = '';
    public $messages = [];
    public $isLoading = false;

    public function mount($sectionId)
    {
        $this->sectionId = $sectionId;
        $this->section = PageSection::findOrFail($sectionId);
        $this->availableSectionTypes = PageSection::getSectionTypes();

        // Load chat messages for this section
        $this->loadSectionMessages();
    }

    public function loadSectionMessages()
    {
        // Load chat messages that are related to this specific section
        $this->messages = AIChatMessage::where('user_id', Auth::id())
            ->where(function($query) {
                $query->where('metadata->section_id', $this->sectionId)
                    ->orWhere('intent', 'like', '%section%');
            })
            ->orderBy('created_at', 'asc')
            ->take(50)
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'message' => $msg->message,
                'intent' => $msg->intent,
                'created_at' => $msg->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function refreshSection()
    {
        $this->section = PageSection::findOrFail($this->sectionId);
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
            'metadata' => ['section_id' => $this->sectionId],
        ]);

        $this->messages[] = [
            'id' => $userMsg->id,
            'role' => 'user',
            'message' => $userMessage,
            'created_at' => 'Just now',
        ];

        try {
            // Build context with explicit section info
            $context = $this->buildSectionContext($userMessage);

            // Use content handler for section modification
            $contentHandler = new AIContentHandler();
            $result = $contentHandler->handlePageSectionModification($userMessage, $context);

            $message = $result['message'];
            if ($result['success']) {
                $message .= "\n\nΘέλεις να κάνω και άλλες αλλαγές;";

                // Refresh section data
                $this->refreshSection();
            }

            $aiMsg = AIChatMessage::create([
                'user_id' => Auth::id(),
                'role' => 'assistant',
                'message' => $message,
                'intent' => 'modify_page_section',
                'metadata' => array_merge($result, ['section_id' => $this->sectionId]),
            ]);

            $this->messages[] = [
                'id' => $aiMsg->id,
                'role' => 'assistant',
                'message' => $message,
                'intent' => 'modify_page_section',
                'created_at' => 'Just now',
            ];

        } catch (\Exception $e) {
            $this->messages[] = [
                'id' => null,
                'role' => 'assistant',
                'message' => '❌ Error: ' . $e->getMessage(),
                'created_at' => 'Just now',
            ];
        }

        $this->isLoading = false;
    }

    protected function buildSectionContext(string $userMessage): array
    {
        // Build explicit context about this section
        $sectionType = $this->availableSectionTypes[$this->section->section_type];

        return [
            'section_id' => $this->sectionId,
            'section_type' => $this->section->section_type,
            'section_name' => $this->section->name,
            'current_content' => $this->section->content,
            'current_settings' => $this->section->settings,
            'conversation_history' => array_map(fn($msg) => [
                'role' => $msg['role'],
                'message' => $msg['message']
            ], array_slice($this->messages, -5)),
            'explicit_instruction' => "You are modifying the '{$this->section->name}' section (ID: {$this->sectionId}, Type: {$sectionType['name']}). Current content: " . json_encode($this->section->content) . ". User request: {$userMessage}"
        ];
    }

    public function clearSectionHistory()
    {
        AIChatMessage::where('user_id', Auth::id())
            ->where('metadata->section_id', $this->sectionId)
            ->delete();
        $this->messages = [];
    }

    public function render()
    {
        return view('livewire.admin.page-sections.section-editor')
            ->layout('layouts.admin-clean');
    }
}
