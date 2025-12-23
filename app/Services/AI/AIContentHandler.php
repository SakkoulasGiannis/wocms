<?php

namespace App\Services\AI;

use App\Models\Template;
use App\Models\ContentNode;
use App\Services\BladeCompiler;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AIContentHandler
{
    protected AIManager $aiManager;

    public function __construct()
    {
        $this->aiManager = new AIManager();
    }

    /**
     * Handle content generation request
     */
    public function handleContentGeneration(string $userMessage): array
    {
        // Detect which template to use
        $template = $this->detectTemplate($userMessage);

        if (!$template) {
            return [
                'success' => false,
                'message' => 'Δεν μπόρεσα να βρω κατάλληλο template. Διαθέσιμα templates: ' . $this->getAvailableTemplates(),
            ];
        }

        // Detect if user wants multiple entries
        $count = $this->detectContentCount($userMessage);

        // Get template fields
        $fields = $template->fields->map(fn($field) => [
            'name' => $field->name,
            'label' => $field->label,
            'type' => $field->type,
            'description' => $field->description,
            'is_required' => $field->is_required,
        ])->toArray();

        // Generate content using AI
        try {
            if ($count > 1) {
                return $this->handleBatchContentGeneration($template, $fields, $userMessage, $count);
            }

            $generatedData = $this->aiManager->generateContent(
                $template->name,
                $fields,
                $userMessage
            );

            if (empty($generatedData)) {
                return [
                    'success' => false,
                    'message' => 'Το AI δεν μπόρεσε να δημιουργήσει περιεχόμενο. Δοκίμασε ξανά με πιο συγκεκριμένες οδηγίες.',
                ];
            }

            // Auto-generate slug if not provided
            if (!isset($generatedData['slug']) && isset($generatedData['title'])) {
                $generatedData['slug'] = Str::slug($generatedData['title']);
            }

            // Create the entry
            $entry = $this->createEntry($template, $generatedData);

            if ($entry) {
                // Create ContentNode for frontend access
                $contentNode = $this->createContentNode($template, $entry, $generatedData);

                $message = "✅ Δημιούργησα το περιεχόμενο στο template '{$template->name}'!";

                return [
                    'success' => true,
                    'message' => $message,
                    'entry_id' => $entry->id,
                    'template_slug' => $template->slug,
                    'preview_url' => route('admin.template-entries.edit', [
                        'templateSlug' => $template->slug,
                        'entryId' => $entry->id
                    ]),
                    'frontend_url' => $contentNode ? url($contentNode->url_path) : null,
                    'data' => $generatedData,
                ];
            }

            return [
                'success' => false,
                'message' => 'Υπήρξε πρόβλημα στη δημιουργία του entry.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Detect how many content items to generate
     */
    protected function detectContentCount(string $message): int
    {
        $message = mb_strtolower($message);

        // Greek numbers
        $greekNumbers = [
            'ένα' => 1, 'μία' => 1, 'μια' => 1,
            'δύο' => 2, 'δυο' => 2,
            'τρία' => 3, 'τρια' => 3,
            'τέσσερα' => 4, 'τεσσερα' => 4,
            'πέντε' => 5, 'πεντε' => 5,
        ];

        // Check for Greek number words
        foreach ($greekNumbers as $word => $num) {
            if (str_contains($message, $word)) {
                return $num;
            }
        }

        // Check for digits
        if (preg_match('/(\d+)\s*(άρθρ|post|article|προϊόντ|product)/u', $message, $matches)) {
            return min((int)$matches[1], 5); // Max 5 items at once
        }

        return 1;
    }

    /**
     * Handle batch content generation (multiple entries)
     */
    protected function handleBatchContentGeneration(Template $template, array $fields, string $userMessage, int $count): array
    {
        \Log::info('Batch content generation requested', [
            'template' => $template->slug,
            'count' => $count,
            'message' => $userMessage
        ]);

        $createdEntries = [];
        $errors = [];

        // Generate each entry separately
        for ($i = 0; $i < $count; $i++) {
            try {
                $prompt = $this->buildBatchPrompt($userMessage, $i + 1, $count);

                $generatedData = $this->aiManager->generateContent(
                    $template->name,
                    $fields,
                    $prompt
                );

                if (empty($generatedData)) {
                    $errors[] = "Αποτυχία δημιουργίας entry " . ($i + 1);
                    continue;
                }

                // Auto-generate slug
                if (!isset($generatedData['slug']) && isset($generatedData['title'])) {
                    $generatedData['slug'] = Str::slug($generatedData['title']);
                }

                // Create the entry
                $entry = $this->createEntry($template, $generatedData);

                if ($entry) {
                    $contentNode = $this->createContentNode($template, $entry, $generatedData);

                    $createdEntries[] = [
                        'entry_id' => $entry->id,
                        'title' => $generatedData['title'] ?? 'Untitled',
                        'preview_url' => route('admin.template-entries.edit', [
                            'templateSlug' => $template->slug,
                            'entryId' => $entry->id
                        ]),
                        'frontend_url' => $contentNode ? url($contentNode->url_path) : null,
                    ];
                } else {
                    $errors[] = "Αποτυχία αποθήκευσης entry " . ($i + 1);
                }

            } catch (\Exception $e) {
                \Log::error("Batch entry creation failed", [
                    'index' => $i,
                    'error' => $e->getMessage()
                ]);
                $errors[] = "Entry " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        // Build response message
        $successCount = count($createdEntries);
        $message = "✅ Δημιούργησα {$successCount} από {$count} άρθρα!\n\n";

        foreach ($createdEntries as $index => $entry) {
            $message .= "**" . ($index + 1) . ". " . $entry['title'] . "**\n";
            $message .= "🔗 [Επεξεργασία](" . $entry['preview_url'] . ")";
            if ($entry['frontend_url']) {
                $message .= " | 🌐 [Frontend](" . $entry['frontend_url'] . ")";
            }
            $message .= "\n\n";
        }

        if (!empty($errors)) {
            $message .= "\n⚠️ Σφάλματα:\n" . implode("\n", $errors);
        }

        return [
            'success' => $successCount > 0,
            'message' => $message,
            'entries' => $createdEntries,
            'errors' => $errors,
        ];
    }

    /**
     * Build prompt for batch generation
     */
    protected function buildBatchPrompt(string $originalMessage, int $index, int $total): string
    {
        // Extract the topic/subject from the original message
        // For now, just add context about which item this is
        return $originalMessage . " (Δημιούργησε το άρθρο {$index} από {$total} - κάνε το μοναδικό και διαφορετικό από τα άλλα)";
    }

    /**
     * Detect which template to use based on the message
     */
    protected function detectTemplate(string $message): ?Template
    {
        $message = mb_strtolower($message);

        // Template keywords mapping (Greek & English)
        $keywords = [
            'blog' => ['άρθρο', 'article', 'blog', 'post', 'κείμενο'],
            'products' => ['προϊόν', 'product', 'item', 'αντικείμενο'],
            'services' => ['υπηρεσία', 'service'],
            'pages' => ['σελίδα', 'page'],
            'news' => ['νέα', 'news', 'ανακοίνωση'],
        ];

        // Try to find template by keywords
        foreach ($keywords as $slug => $terms) {
            foreach ($terms as $term) {
                if (str_contains($message, $term)) {
                    $template = Template::where('slug', $slug)
                        ->where('is_active', true)
                        ->first();

                    if ($template) {
                        return $template;
                    }

                    // Try plural form
                    $template = Template::where('slug', Str::plural($slug))
                        ->where('is_active', true)
                        ->first();

                    if ($template) {
                        return $template;
                    }
                }
            }
        }

        // If no specific match, try to find the first template that looks like a blog/article
        $commonNames = ['blog', 'posts', 'articles', 'news'];
        foreach ($commonNames as $name) {
            $template = Template::where('slug', 'like', "%{$name}%")
                ->where('is_active', true)
                ->first();

            if ($template) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Create entry in the template's table
     */
    protected function createEntry(Template $template, array $data): ?object
    {
        if (!$template->table_name) {
            return null;
        }

        // Clean data - remove null values and empty strings for optional fields
        $cleanData = [];
        foreach ($data as $key => $value) {
            // Skip null values
            if ($value === null) {
                continue;
            }

            // Skip empty strings, placeholder values, and common "not available" markers
            if ($value === '' || $value === '?' || $value === 'N/A' || $value === 'null') {
                continue;
            }

            // If value is already a JSON string (from array/object conversion), validate it
            if (is_string($value) && $this->isJson($value)) {
                // Double-check it's valid JSON before inserting
                $decoded = json_decode($value, true);
                if ($decoded !== null || $value === 'null') {
                    $cleanData[$key] = $value;
                } else {
                    \Log::warning("Invalid JSON detected for field: {$key}", ['value' => $value]);
                }
                continue;
            }

            // Convert arrays/objects to JSON strings (safety check)
            if (is_array($value) || is_object($value)) {
                $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($jsonValue !== false) {
                    $cleanData[$key] = $jsonValue;
                } else {
                    \Log::warning("Failed to encode array/object for field: {$key}", [
                        'value' => $value,
                        'error' => json_last_error_msg()
                    ]);
                }
            } else {
                $cleanData[$key] = $value;
            }
        }

        // Add metadata
        $cleanData['created_at'] = now();
        $cleanData['updated_at'] = now();

        // Insert into the template's table
        try {
            $id = DB::table($template->table_name)->insertGetId($cleanData);

            if ($id) {
                return (object) array_merge($cleanData, ['id' => $id]);
            }
        } catch (\Exception $e) {
            \Log::error('Entry creation failed', [
                'template' => $template->name,
                'error' => $e->getMessage(),
                'data' => $cleanData
            ]);
            throw $e;
        }

        return null;
    }

    /**
     * Get list of available templates
     */
    protected function getAvailableTemplates(): string
    {
        $templates = Template::where('is_active', true)
            ->where('requires_database', true)
            ->pluck('name')
            ->toArray();

        return implode(', ', $templates);
    }

    /**
     * Create ContentNode for frontend access
     */
    protected function createContentNode(Template $template, object $entry, array $data): ?ContentNode
    {
        try {
            \Log::info('Attempting to create ContentNode', [
                'template_slug' => $template->slug,
                'entry_id' => $entry->id,
                'data_keys' => array_keys($data)
            ]);

            // Get the content type (model class) for polymorphic relation
            $contentType = $this->getContentTypeClass($template);

            \Log::info('Content type determined', [
                'template_slug' => $template->slug,
                'content_type' => $contentType
            ]);

            if (!$contentType) {
                \Log::warning('Could not determine content type for template', [
                    'template' => $template->slug,
                    'available_slugs' => ['blog-post', 'product', 'service', 'page']
                ]);
                return null;
            }

            // Create ContentNode
            $node = ContentNode::create([
                'template_id' => $template->id,
                'parent_id' => null, // Root level for now
                'content_type' => $contentType,
                'content_id' => $entry->id,
                'title' => $data['title'] ?? 'Untitled',
                'slug' => $data['slug'] ?? Str::slug($data['title'] ?? 'untitled'),
                'is_published' => true,
                'sort_order' => 0,
            ]);

            \Log::info('ContentNode created', [
                'node_id' => $node->id,
                'url_path' => $node->url_path,
                'entry_id' => $entry->id
            ]);

            return $node;

        } catch (\Exception $e) {
            \Log::error('Failed to create ContentNode', [
                'template' => $template->slug,
                'entry_id' => $entry->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get the content type class name for the template
     */
    protected function getContentTypeClass(Template $template): ?string
    {
        // Map template slugs to model classes
        // You can extend this based on your needs
        $modelMap = [
            'blog' => \App\Models\Blog::class,
            'page' => \App\Models\Page::class,
            'home' => \App\Models\Home::class,
        ];

        return $modelMap[$template->slug] ?? null;
    }

    /**
     * Handle content update request
     */
    public function handleContentUpdate(string $userMessage, array $conversationContext = []): array
    {
        // Try to detect which entry to update from conversation context
        $entryInfo = $this->detectEntryFromContext($userMessage, $conversationContext);

        if (!$entryInfo) {
            return [
                'success' => false,
                'message' => '❌ Δεν μπόρεσα να καταλάβω ποιο entry θέλεις να ενημερώσεις. Μπορείς να μου πεις το ID ή τον τίτλο;',
            ];
        }

        $template = Template::find($entryInfo['template_id']);
        if (!$template || !$template->table_name) {
            return [
                'success' => false,
                'message' => '❌ Δεν βρέθηκε το template.',
            ];
        }

        // Get current entry data
        $currentEntry = DB::table($template->table_name)
            ->where('id', $entryInfo['entry_id'])
            ->first();

        if (!$currentEntry) {
            return [
                'success' => false,
                'message' => '❌ Δεν βρέθηκε το entry.',
            ];
        }

        // Get template fields
        $fields = $template->fields->map(fn($field) => [
            'name' => $field->name,
            'label' => $field->label,
            'type' => $field->type,
            'description' => $field->description,
            'is_required' => $field->is_required,
        ])->toArray();

        // Convert entry to array
        $currentData = (array) $currentEntry;

        try {
            // Get updates from AI
            $updates = $this->aiManager->updateContent(
                $template->name,
                $fields,
                $currentData,
                $userMessage
            );

            if (empty($updates)) {
                return [
                    'success' => false,
                    'message' => '❌ Δεν βρέθηκαν αλλαγές για εφαρμογή.',
                ];
            }

            // Update slug if title changed
            if (isset($updates['title']) && !isset($updates['slug'])) {
                $updates['slug'] = Str::slug($updates['title']);
            }

            // Apply updates
            DB::table($template->table_name)
                ->where('id', $entryInfo['entry_id'])
                ->update(array_merge($updates, ['updated_at' => now()]));

            // Update ContentNode if slug changed
            if (isset($updates['slug']) || isset($updates['title'])) {
                $this->updateContentNode($entryInfo['entry_id'], $template, $updates);
            }

            // Build response
            $changedFields = array_keys($updates);
            $message = "✅ Ενημέρωσα το entry!\n\n";
            $message .= "**Αλλαγές:**\n";
            foreach ($changedFields as $field) {
                $oldValue = $currentData[$field] ?? 'N/A';
                $newValue = $updates[$field];

                // Truncate long values
                if (is_string($oldValue) && strlen($oldValue) > 50) {
                    $oldValue = substr($oldValue, 0, 50) . '...';
                }
                if (is_string($newValue) && strlen($newValue) > 50) {
                    $newValue = substr($newValue, 0, 50) . '...';
                }

                $message .= "- **{$field}**: {$oldValue} → {$newValue}\n";
            }

            return [
                'success' => true,
                'message' => $message,
                'entry_id' => $entryInfo['entry_id'],
                'template_slug' => $template->slug,
                'preview_url' => route('admin.template-entries.edit', [
                    'templateSlug' => $template->slug,
                    'entryId' => $entryInfo['entry_id']
                ]),
                'updates' => $updates,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Detect which entry to update from context
     */
    protected function detectEntryFromContext(string $message, array $context): ?array
    {
        // Check conversation history for recently created/mentioned entries
        if (!empty($context['conversation_history'])) {
            foreach (array_reverse($context['conversation_history']) as $msg) {
                if ($msg['role'] === 'assistant' && isset($msg['metadata'])) {
                    $metadata = $msg['metadata'];
                    if (isset($metadata['entry_id']) && isset($metadata['template_slug'])) {
                        // Get template
                        $template = Template::where('slug', $metadata['template_slug'])->first();
                        if ($template) {
                            return [
                                'entry_id' => $metadata['entry_id'],
                                'template_id' => $template->id,
                            ];
                        }
                    }

                    // Check for batch entries
                    if (isset($metadata['entries']) && !empty($metadata['entries'])) {
                        $lastEntry = end($metadata['entries']);
                        if (isset($lastEntry['entry_id'])) {
                            $template = Template::where('slug', $metadata['template_slug'] ?? '')->first();
                            if (!$template) {
                                // Try to find from message
                                $template = $this->detectTemplate($message);
                            }
                            if ($template) {
                                return [
                                    'entry_id' => $lastEntry['entry_id'],
                                    'template_id' => $template->id,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Update ContentNode when entry data changes
     */
    protected function updateContentNode(int $entryId, Template $template, array $updates): void
    {
        $contentType = $this->getContentTypeClass($template);
        if (!$contentType) {
            return;
        }

        $node = ContentNode::where('content_type', $contentType)
            ->where('content_id', $entryId)
            ->first();

        if ($node) {
            $nodeUpdates = [];
            if (isset($updates['title'])) {
                $nodeUpdates['title'] = $updates['title'];
            }
            if (isset($updates['slug'])) {
                $nodeUpdates['slug'] = $updates['slug'];
            }

            if (!empty($nodeUpdates)) {
                $node->update($nodeUpdates);
            }
        }
    }

    /**
     * Handle template creation request
     */
    public function handleTemplateCreation(string $userMessage): array
    {
        try {
            // Generate template structure from AI
            $templateData = $this->aiManager->generateTemplate($userMessage);

            if (empty($templateData) || !isset($templateData['name'])) {
                return [
                    'success' => false,
                    'message' => '❌ Το AI δεν μπόρεσε να δημιουργήσει template structure. Δοκίμασε με πιο συγκεκριμένες οδηγίες.',
                ];
            }

            // Validate template data
            $validation = $this->validateTemplateData($templateData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => '❌ Validation error: ' . $validation['error'],
                ];
            }

            // Create the template
            $template = $this->createTemplate($templateData);

            if (!$template) {
                return [
                    'success' => false,
                    'message' => '❌ Αποτυχία δημιουργίας template στη βάση.',
                ];
            }

            // Create database table
            $tableCreated = $this->createTemplateTable($template);

            $message = "✅ Δημιούργησα το template **{$template->name}**!\n\n";
            $message .= "**Πεδία:** " . count($templateData['fields']) . "\n";
            $message .= "**Slug:** {$template->slug}\n";
            $message .= "**Table:** {$template->table_name}\n\n";

            if (!$tableCreated) {
                $message .= "⚠️ Το database table δεν δημιουργήθηκε. Θα χρειαστεί να το φτιάξεις manually.\n\n";
            }

            $message .= "🔗 [Επεξεργασία του template](/admin/templates/{$template->id}/edit)";

            return [
                'success' => true,
                'message' => $message,
                'template_id' => $template->id,
                'template_slug' => $template->slug,
                'template_data' => $templateData,
            ];

        } catch (\Exception $e) {
            \Log::error('Template creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate template data from AI
     */
    protected function validateTemplateData(array $data): array
    {
        // Check required fields
        if (empty($data['name'])) {
            return ['valid' => false, 'error' => 'Missing template name'];
        }

        if (empty($data['fields']) || !is_array($data['fields'])) {
            return ['valid' => false, 'error' => 'Missing or invalid fields array'];
        }

        // Validate each field
        foreach ($data['fields'] as $field) {
            if (empty($field['name']) || empty($field['type'])) {
                return ['valid' => false, 'error' => 'Each field must have name and type'];
            }
        }

        return ['valid' => true];
    }

    /**
     * Create template in database
     */
    protected function createTemplate(array $data): ?Template
    {
        // Generate slug if not provided
        $slug = $data['slug'] ?? Str::slug($data['name']);

        // Check if slug exists
        if (Template::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . time();
        }

        // Create template
        $template = Template::create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'icon' => $data['icon'] ?? '',
            'requires_database' => true,
            'table_name' => $slug,
            'is_active' => true,
            'is_public' => true,
            'show_in_admin_menu' => true,  // Εμφάνιση στο admin menu by default
            'allow_children' => false,
        ]);

        // Create fields
        foreach ($data['fields'] as $index => $fieldData) {
            $template->fields()->create([
                'name' => $fieldData['name'],
                'label' => $fieldData['label'] ?? ucfirst($fieldData['name']),
                'type' => $fieldData['type'],
                'description' => $fieldData['description'] ?? '',
                'is_required' => $fieldData['is_required'] ?? false,
                'show_in_table' => $fieldData['show_in_table'] ?? true,
                'position' => $index,
                'options' => $fieldData['options'] ?? null,
            ]);
        }

        return $template;
    }

    /**
     * Create database table for template
     */
    protected function createTemplateTable(Template $template): bool
    {
        $tableName = $template->table_name;

        if (!$tableName || DB::getSchemaBuilder()->hasTable($tableName)) {
            return false;
        }

        try {
            DB::getSchemaBuilder()->create($tableName, function ($table) use ($template) {
                $table->id();

                // Add fields based on template
                foreach ($template->fields as $field) {
                    $this->addFieldToTable($table, $field);
                }

                $table->timestamps();
                $table->softDeletes();
            });

            \Log::info('Template table created', ['table' => $tableName]);
            return true;

        } catch (\Exception $e) {
            \Log::error('Failed to create template table', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Add field to database table
     */
    protected function addFieldToTable($table, $field): void
    {
        $name = $field->name;
        $type = $field->type;
        $nullable = !$field->is_required;

        switch ($type) {
            case 'text':
            case 'email':
            case 'url':
                $column = $table->string($name)->nullable($nullable);
                break;

            case 'textarea':
            case 'wysiwyg':
            case 'grapejs':
                $column = $table->text($name)->nullable($nullable);
                break;

            case 'number':
            case 'integer':
                $column = $table->integer($name)->nullable($nullable);
                break;

            case 'decimal':
            case 'price':
                $column = $table->decimal($name, 10, 2)->nullable($nullable);
                break;

            case 'boolean':
            case 'checkbox':
                $column = $table->boolean($name)->default(false)->nullable($nullable);
                break;

            case 'date':
                $column = $table->date($name)->nullable($nullable);
                break;

            case 'datetime':
                $column = $table->dateTime($name)->nullable($nullable);
                break;

            case 'time':
                $column = $table->time($name)->nullable($nullable);
                break;

            case 'image':
            case 'file':
                $column = $table->text($name)->nullable($nullable);
                break;

            case 'repeater':
            case 'json':
                $column = $table->json($name)->nullable($nullable);
                break;

            case 'select':
            case 'radio':
                $column = $table->string($name)->nullable($nullable);
                break;

            default:
                $column = $table->text($name)->nullable($nullable);
        }
    }

    /**
     * Handle template modification request
     */
    public function handleTemplateModification(string $userMessage, array $conversationContext = []): array
    {
        try {
            // Detect which template to modify
            $template = $this->detectTemplateToModify($userMessage, $conversationContext);

            if (!$template) {
                return [
                    'success' => false,
                    'message' => '❌ Δεν μπόρεσα να καταλάβω ποιο template θέλεις να τροποποιήσεις. Μπορείς να το προσδιορίσεις;',
                ];
            }

            // Get modification instructions from AI
            $modifications = $this->aiManager->modifyTemplate($template, $userMessage);

            if (empty($modifications) || !isset($modifications['action'])) {
                return [
                    'success' => false,
                    'message' => '❌ Δεν κατάλαβα τι αλλαγές θέλεις να κάνω. Δοκίμασε πιο συγκεκριμένα.',
                ];
            }

            // Apply modifications
            $result = $this->applyTemplateModifications($template, $modifications);

            return $result;

        } catch (\Exception $e) {
            \Log::error('Template modification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Detect which template to modify
     */
    protected function detectTemplateToModify(string $message, array $context): ?Template
    {
        // Check if a template was just created
        if (!empty($context['conversation_history'])) {
            foreach (array_reverse($context['conversation_history']) as $msg) {
                if ($msg['role'] === 'assistant' && isset($msg['metadata']['template_slug'])) {
                    $template = Template::where('slug', $msg['metadata']['template_slug'])->first();
                    if ($template) {
                        return $template;
                    }
                }
            }
        }

        // Try to find template by name in message
        $message = mb_strtolower($message);
        $templates = Template::where('is_active', true)->get();

        foreach ($templates as $template) {
            $templateName = mb_strtolower($template->name);
            $templateSlug = mb_strtolower($template->slug);

            if (str_contains($message, $templateName) || str_contains($message, $templateSlug)) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Apply template modifications
     */
    protected function applyTemplateModifications(Template $template, array $modifications): array
    {
        $action = $modifications['action'];
        $changes = [];

        try {
            if ($action === 'add_fields' && !empty($modifications['fields_to_add'])) {
                $added = $this->addFieldsToTemplate($template, $modifications['fields_to_add']);
                $changes[] = "Προστέθηκαν {$added} νέα πεδία";
            }

            if ($action === 'remove_fields' && !empty($modifications['fields_to_remove'])) {
                $removed = $this->removeFieldsFromTemplate($template, $modifications['fields_to_remove']);
                $changes[] = "Αφαιρέθηκαν {$removed} πεδία";
            }

            if ($action === 'modify_fields' && !empty($modifications['fields_to_modify'])) {
                $modified = $this->modifyTemplateFields($template, $modifications['fields_to_modify']);
                $changes[] = "Τροποποιήθηκαν {$modified} πεδία";
            }

            $message = "✅ Ενημέρωσα το template **{$template->name}**!\n\n";
            $message .= "**Αλλαγές:**\n";
            foreach ($changes as $change) {
                $message .= "- {$change}\n";
            }

            if (!empty($modifications['reason'])) {
                $message .= "\n**Λόγος:** {$modifications['reason']}\n";
            }

            $message .= "\n🔗 [Επεξεργασία template](/admin/templates/{$template->id}/edit)";

            return [
                'success' => true,
                'message' => $message,
                'template_id' => $template->id,
                'template_slug' => $template->slug,
                'modifications' => $modifications,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '❌ Αποτυχία εφαρμογής αλλαγών: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Add fields to template
     */
    protected function addFieldsToTemplate(Template $template, array $fieldsToAdd): int
    {
        $added = 0;
        $maxPosition = $template->fields()->max('position') ?? 0;

        foreach ($fieldsToAdd as $fieldData) {
            // Create field in template_fields table
            $field = $template->fields()->create([
                'name' => $fieldData['name'],
                'label' => $fieldData['label'] ?? ucfirst($fieldData['name']),
                'type' => $fieldData['type'],
                'description' => $fieldData['description'] ?? '',
                'is_required' => $fieldData['is_required'] ?? false,
                'show_in_table' => $fieldData['show_in_table'] ?? true,
                'position' => ++$maxPosition,
                'options' => $fieldData['options'] ?? null,
            ]);

            // Add column to database table
            if ($template->table_name && DB::getSchemaBuilder()->hasTable($template->table_name)) {
                $this->addColumnToTable($template->table_name, $field);
                $added++;
            }
        }

        return $added;
    }

    /**
     * Remove fields from template
     */
    protected function removeFieldsFromTemplate(Template $template, array $fieldNamesToRemove): int
    {
        $removed = 0;

        foreach ($fieldNamesToRemove as $fieldName) {
            $field = $template->fields()->where('name', $fieldName)->first();

            if ($field) {
                // Remove column from database table
                if ($template->table_name && DB::getSchemaBuilder()->hasTable($template->table_name)) {
                    $this->removeColumnFromTable($template->table_name, $fieldName);
                }

                // Delete field from template_fields
                $field->delete();
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Modify existing template fields
     */
    protected function modifyTemplateFields(Template $template, array $fieldsToModify): int
    {
        $modified = 0;

        foreach ($fieldsToModify as $fieldData) {
            $field = $template->fields()->where('name', $fieldData['name'])->first();

            if ($field) {
                $updates = [];

                if (isset($fieldData['label'])) $updates['label'] = $fieldData['label'];
                if (isset($fieldData['description'])) $updates['description'] = $fieldData['description'];
                if (isset($fieldData['is_required'])) $updates['is_required'] = $fieldData['is_required'];
                if (isset($fieldData['show_in_table'])) $updates['show_in_table'] = $fieldData['show_in_table'];

                if (!empty($updates)) {
                    $field->update($updates);
                    $modified++;
                }
            }
        }

        return $modified;
    }

    /**
     * Add column to database table
     */
    protected function addColumnToTable(string $tableName, $field): void
    {
        try {
            DB::getSchemaBuilder()->table($tableName, function ($table) use ($field) {
                $this->addFieldToTable($table, $field);
            });

            \Log::info('Column added to table', [
                'table' => $tableName,
                'column' => $field->name
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to add column', [
                'table' => $tableName,
                'column' => $field->name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove column from database table
     */
    protected function removeColumnFromTable(string $tableName, string $columnName): void
    {
        try {
            DB::getSchemaBuilder()->table($tableName, function ($table) use ($columnName) {
                $table->dropColumn($columnName);
            });

            \Log::info('Column removed from table', [
                'table' => $tableName,
                'column' => $columnName
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to remove column', [
                'table' => $tableName,
                'column' => $columnName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle frontend modification request
     */
    public function handleFrontendModification(string $userMessage): array
    {
        try {
            // Detect which file to modify
            $filePath = $this->detectFileFromMessage($userMessage);

            if (!$filePath) {
                return [
                    'success' => false,
                    'message' => '❌ Δεν μπόρεσα να καταλάβω ποιο αρχείο θέλεις να τροποποιήσω. Πες μου (π.χ. "στην αρχική σελίδα", "στο home").',
                ];
            }

            $fullPath = base_path($filePath);

            if (!File::exists($fullPath)) {
                return [
                    'success' => false,
                    'message' => "❌ Το αρχείο {$filePath} δεν υπάρχει.",
                ];
            }

            // Read current file content
            $currentContent = File::get($fullPath);

            // Generate operations from AI
            $operations = $this->aiManager->generateFrontendModifications($userMessage, $currentContent);

            if (empty($operations)) {
                return [
                    'success' => false,
                    'message' => '❌ Δεν κατάλαβα τι αλλαγές θέλεις να κάνω.',
                ];
            }

            // Apply operations using BladeCompiler
            $compiler = new BladeCompiler();
            $result = $compiler->apply($operations, $filePath);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => "❌ Αποτυχία: {$result['error']}\n\n" .
                                "Απέτυχε στο operation #{$result['failed_at_operation']}.\n" .
                                "Έγινε rollback στην προηγούμενη έκδοση.",
                ];
            }

            $message = "✅ Ενημέρωσα το **{$filePath}**!\n\n";
            $message .= "**Αλλαγές:**\n";
            foreach ($operations as $index => $op) {
                $desc = $op['description'] ?? $op['action'];
                $message .= "- {$desc}\n";
            }
            $message .= "\n**Backup:** {$result['backup']}\n\n";
            $message .= "⚠️ Έλεγξε τη σελίδα για να δεις αν όλα λειτουργούν σωστά!";

            return [
                'success' => true,
                'message' => $message,
                'file' => $filePath,
                'operations' => $operations,
                'backup' => $result['backup'],
            ];

        } catch (\Exception $e) {
            \Log::error('Frontend modification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Detect file path from user message
     */
    protected function detectFileFromMessage(string $message): ?string
    {
        $message = mb_strtolower($message);

        // Map keywords to file paths
        $fileMap = [
            'αρχική' => 'resources/views/frontend/home.blade.php',
            'home' => 'resources/views/frontend/home.blade.php',
            'homepage' => 'resources/views/frontend/home.blade.php',

            'blog' => 'resources/views/frontend/templates/blog.blade.php',
            'blog post' => 'resources/views/frontend/templates/blog-post.blade.php',
            'άρθρο' => 'resources/views/frontend/templates/blog-post.blade.php',

            'page' => 'resources/views/frontend/page.blade.php',
            'σελίδα' => 'resources/views/frontend/page.blade.php',
        ];

        foreach ($fileMap as $keyword => $file) {
            if (str_contains($message, $keyword)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Check if a string is valid JSON
     */
    protected function isJson(string $string): bool
    {
        if (empty($string) || !is_string($string)) {
            return false;
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Handle page section creation (unified structured JSON approach)
     */
    public function handlePageSectionCreation(string $userMessage, array $context = []): array
    {
        // Detect page type (home, about, etc.)
        $pageType = $this->detectPageType($userMessage);

        // Detect position (before/after which section)
        $position = $this->detectSectionPosition($userMessage, $pageType, $context);

        // Generate structured HTML JSON using AI
        $result = $this->aiManager->getProvider()->generateStructuredHTML($userMessage);

        if (isset($result['error'])) {
            return [
                'success' => false,
                'message' => '❌ Σφάλμα: ' . $result['error']
            ];
        }

        // Extract section name from the structure (use first heading if available)
        $sectionName = $this->extractSectionName($result['structure']) ?? 'Custom Section';

        // Create the section with structured JSON
        $section = \App\Models\PageSection::create([
            'page_type' => $pageType,
            'section_type' => 'structured_html',
            'name' => $sectionName,
            'order' => $position['order'],
            'is_active' => true,
            'content' => [
                'structure' => $result['structure']
            ],
            'settings' => [
                'container' => false, // Structure defines its own container
                'padding' => false,   // Structure defines its own padding
            ],
        ]);

        // Reorder other sections if needed
        if ($position['adjust_others']) {
            $this->adjustSectionOrders($pageType, $position['order'], $section->id);
        }

        $positionMessage = $position['message'] ?? '';

        return [
            'success' => true,
            'message' => "✅ Δημιούργησα ένα νέο section{$positionMessage}!\n\n💡 Το AI δημιούργησε structured JSON με Tailwind CSS που αποδίδεται δυναμικά σε HTML.",
            'section_id' => $section->id,
            'section_type' => 'structured_html',
            'page_type' => $pageType,
        ];
    }

    /**
     * Extract section name from structured JSON (use first h1/h2/h3 found)
     */
    protected function extractSectionName(array $structure): ?string
    {
        // Check current node
        if (isset($structure['type']) && in_array($structure['type'], ['h1', 'h2', 'h3'])) {
            return $structure['content'] ?? null;
        }

        // Recursively search children
        if (isset($structure['children']) && is_array($structure['children'])) {
            foreach ($structure['children'] as $child) {
                $name = $this->extractSectionName($child);
                if ($name) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * Handle page section modification
     */
    public function handlePageSectionModification(string $userMessage, array $context): array
    {
        // Detect which section to modify from context or message
        $sectionInfo = $this->detectSectionFromContext($userMessage, $context);

        if (!$sectionInfo) {
            return [
                'success' => false,
                'message' => 'Δεν μπόρεσα να καταλάβω ποιο section θέλεις να τροποποιήσω. Μπορείς να διευκρινίσεις;'
            ];
        }

        $section = \App\Models\PageSection::find($sectionInfo['section_id']);

        if (!$section) {
            return [
                'success' => false,
                'message' => 'Δεν βρέθηκε το section.'
            ];
        }

        // Check if this is already a structured_html section or needs conversion
        if ($section->section_type === 'structured_html') {
            // Modify the existing structured JSON (not regenerate from scratch)
            $currentStructure = $section->content['structure'] ?? [];

            $result = $this->aiManager->getProvider()->modifyStructuredHTML($currentStructure, $userMessage);

            if (isset($result['error'])) {
                return [
                    'success' => false,
                    'message' => '❌ Σφάλμα: ' . $result['error']
                ];
            }

            // Update the section with modified structure
            $section->update([
                'content' => ['structure' => $result['structure']],
                'name' => $this->extractSectionName($result['structure']) ?? $section->name,
            ]);

            return [
                'success' => true,
                'message' => "✅ Ενημέρωσα το section!\n\n💡 Το AI τροποποίησε το υπάρχον structured JSON με βάση το αίτημά σου.",
                'section_id' => $section->id,
            ];
        } else {
            // Legacy section - convert to structured_html
            // First, render the current section to understand what it looks like
            $currentDescription = $this->describeLegacySection($section);

            // Ask AI to create new structured HTML based on current + modifications
            $prompt = "Δημιούργησε ένα section που είναι παρόμοιο με: {$currentDescription}\n\nΑλλά κάνε αυτές τις αλλαγές: {$userMessage}";

            $result = $this->aiManager->getProvider()->generateStructuredHTML($prompt);

            if (isset($result['error'])) {
                return [
                    'success' => false,
                    'message' => '❌ Σφάλμα: ' . $result['error']
                ];
            }

            // Convert section to structured_html type
            $section->update([
                'section_type' => 'structured_html',
                'content' => ['structure' => $result['structure']],
                'settings' => ['container' => false, 'padding' => false],
                'name' => $this->extractSectionName($result['structure']) ?? $section->name,
            ]);

            return [
                'success' => true,
                'message' => "✅ Μετέτρεψα το section σε structured HTML και το ενημέρωσα!\n\n💡 Από εδώ και πέρα αυτό το section χρησιμοποιεί την νέα structured JSON αρχιτεκτονική.",
                'section_id' => $section->id,
            ];
        }
    }

    /**
     * Describe a legacy section for AI context
     */
    protected function describeLegacySection(\App\Models\PageSection $section): string
    {
        $sectionTypes = \App\Models\PageSection::getSectionTypes();
        $typeName = $sectionTypes[$section->section_type]['name'] ?? $section->section_type;

        $description = "Ένα '{$typeName}' section";

        if (!empty($section->content)) {
            $description .= " με περιεχόμενο: " . json_encode($section->content, JSON_UNESCAPED_UNICODE);
        }

        return $description;
    }

    /**
     * Detect if user wants to modify content or settings
     */
    protected function detectModificationType(string $message): string
    {
        $message = mb_strtolower($message);

        // Settings/Design keywords
        $settingsKeywords = [
            // Greek
            'design', 'σχεδιασμός', 'εμφάνιση', 'στυλ', 'χρώμα', 'μέγεθος',
            'full screen', 'πλήρης οθόνη', 'ύψος', 'πλάτος',
            'overlay', 'σκιά', 'διαφάνεια', 'opacity',
            'alignment', 'στοίχιση', 'αριστερά', 'δεξιά', 'κέντρο',
            'columns', 'στήλες', 'grid', 'layout', 'διάταξη',
            'autoplay', 'αυτόματη αναπαραγωγή',
            'arrows', 'βέλη', 'dots', 'κουκίδες',
            'animation', 'κίνηση', 'animated',
            'lightbox', 'carousel',
            'show', 'hide', 'εμφάνιση', 'απόκρυψη',
            // English
            'height', 'width', 'color', 'background', 'style',
            'left', 'right', 'center',
        ];

        foreach ($settingsKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return 'settings';
            }
        }

        // Default to content
        return 'content';
    }

    /**
     * Detect section type from user message
     */
    protected function detectSectionType(string $message): ?string
    {
        $message = mb_strtolower($message);

        $patterns = [
            'hero_slider' => ['slider', 'carousel', 'slideshow'],
            'hero_simple' => ['hero', 'banner'],
            'about_us' => ['about', 'σχετικά'],
            'features_grid' => ['features', 'χαρακτηριστικά', 'πλεονεκτήματα'],
            'blog_posts_list' => ['blog', 'άρθρα', 'posts', 'articles'],
            'testimonials' => ['testimonial', 'μαρτυρίες', 'κριτικές'],
            'call_to_action' => ['cta', 'call to action'],
            'stats_counter' => ['stats', 'statistics', 'στατιστικά', 'αριθμοί'],
            'gallery' => ['gallery', 'φωτογραφίες', 'εικόνες'],
            'contact_form' => ['contact', 'επικοινωνία', 'φόρμα'],
        ];

        foreach ($patterns as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * Detect page type from message (defaults to 'home')
     */
    protected function detectPageType(string $message): string
    {
        $message = mb_strtolower($message);

        if (preg_match('/(στην αρχική|home page|homepage|αρχική)/u', $message)) {
            return 'home';
        }

        if (preg_match('/(about|σχετικά)/u', $message)) {
            return 'about';
        }

        if (preg_match('/(contact|επικοινωνία)/u', $message)) {
            return 'contact';
        }

        // Default to home
        return 'home';
    }

    /**
     * Detect section position from message
     */
    protected function detectSectionPosition(string $message, string $pageType, array $context = []): array
    {
        $message = mb_strtolower($message);

        // Check for positional keywords
        $patterns = [
            'before' => ['πριν', 'πάνω από', 'above', 'before'],
            'after' => ['μετά', 'κάτω από', 'below', 'after', 'underneath'],
        ];

        $position = null;
        $positionType = null;

        foreach ($patterns as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    $positionType = $type;
                    break 2;
                }
            }
        }

        if ($positionType) {
            // Try to find the reference section
            $referenceSection = $this->findReferenceSectionFromMessage($message, $pageType);

            if ($referenceSection) {
                $order = $positionType === 'before' ? $referenceSection->order : $referenceSection->order + 1;

                return [
                    'order' => $order,
                    'adjust_others' => true,
                    'message' => " {$positionType} '{$referenceSection->name}'"
                ];
            }
        }

        // Check if context has selected section
        if (isset($context['selected_section_id'])) {
            $selectedSection = \App\Models\PageSection::find($context['selected_section_id']);
            if ($selectedSection) {
                return [
                    'order' => $selectedSection->order + 1,
                    'adjust_others' => true,
                    'message' => " μετά από '{$selectedSection->name}'"
                ];
            }
        }

        // Default: add at the end
        $maxOrder = \App\Models\PageSection::where('page_type', $pageType)->max('order') ?? 0;

        return [
            'order' => $maxOrder + 1,
            'adjust_others' => false,
            'message' => ''
        ];
    }

    /**
     * Find reference section from message
     */
    protected function findReferenceSectionFromMessage(string $message, string $pageType): ?\App\Models\PageSection
    {
        $sections = \App\Models\PageSection::where('page_type', $pageType)->get();

        foreach ($sections as $section) {
            $sectionName = mb_strtolower($section->name);
            if (str_contains($message, $sectionName)) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Adjust section orders after insertion
     */
    protected function adjustSectionOrders(string $pageType, int $insertOrder, int $excludeId): void
    {
        // Move all sections at or after the insert position down by 1
        \App\Models\PageSection::where('page_type', $pageType)
            ->where('id', '!=', $excludeId)
            ->where('order', '>=', $insertOrder)
            ->increment('order');
    }

    /**
     * Detect section from conversation context
     */
    protected function detectSectionFromContext(string $message, array $context): ?array
    {
        // PRIORITY 1: Check for explicit section_id in context (from SectionEditor)
        if (isset($context['section_id'])) {
            return [
                'section_id' => $context['section_id']
            ];
        }

        // PRIORITY 2: Look for recently created/mentioned sections in conversation history
        if (isset($context['conversation_history'])) {
            foreach (array_reverse($context['conversation_history']) as $msg) {
                if ($msg['role'] === 'assistant' && isset($msg['metadata']['section_id'])) {
                    return [
                        'section_id' => $msg['metadata']['section_id']
                    ];
                }
            }
        }

        // PRIORITY 3: Try to find section by type mentioned in message
        $sectionType = $this->detectSectionType($message);
        $pageType = $this->detectPageType($message);

        if ($sectionType) {
            $section = \App\Models\PageSection::where('page_type', $pageType)
                ->where('section_type', $sectionType)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($section) {
                return ['section_id' => $section->id];
            }
        }

        return null;
    }

    /**
     * Handle page section reordering
     */
    public function handlePageSectionReordering(string $userMessage): array
    {
        $message = mb_strtolower($userMessage);

        // Detect page type
        $pageType = $this->detectPageType($userMessage);

        // Detect which section to move
        $sectionType = $this->detectSectionType($userMessage);

        if (!$sectionType) {
            return [
                'success' => false,
                'message' => 'Δεν μπόρεσα να καταλάβω ποιο section θέλεις να μετακινήσω. Μπορείς να διευκρινίσεις;'
            ];
        }

        // Find the section to move
        $section = \App\Models\PageSection::where('page_type', $pageType)
            ->where('section_type', $sectionType)
            ->first();

        if (!$section) {
            return [
                'success' => false,
                'message' => "Δεν βρέθηκε {$sectionType} section στη σελίδα {$pageType}."
            ];
        }

        // Determine the target position
        $position = $this->detectTargetPosition($userMessage, $pageType, $section);

        if ($position === null) {
            return [
                'success' => false,
                'message' => 'Δεν μπόρεσα να καταλάβω που θέλεις να μετακινήσω το section. Μπορείς να πεις "στην αρχή", "στο τέλος", "πάνω από το features", κλπ;'
            ];
        }

        // Perform the reordering
        $result = $this->reorderSection($section, $position, $pageType);

        return $result;
    }

    /**
     * Detect target position for section reordering
     */
    protected function detectTargetPosition(string $message, string $pageType, $movingSection): ?array
    {
        $message = mb_strtolower($message);

        // Move to top/first
        if (preg_match('/(πάνω μεριά|αρχή|πρώτο|πρώτη|top|first|beginning)/u', $message)) {
            return ['type' => 'absolute', 'position' => 0];
        }

        // Move to bottom/last
        if (preg_match('/(κάτω μεριά|τέλος|τελευταίο|τελευταία|bottom|last|end)/u', $message)) {
            $maxOrder = \App\Models\PageSection::where('page_type', $pageType)->max('order') ?? 0;
            return ['type' => 'absolute', 'position' => $maxOrder];
        }

        // Move before/after another section
        if (preg_match('/(πάνω από|πριν από|before|above)/u', $message)) {
            $targetType = $this->detectTargetSectionType($message, $movingSection->section_type);
            if ($targetType) {
                $targetSection = \App\Models\PageSection::where('page_type', $pageType)
                    ->where('section_type', $targetType)
                    ->first();
                if ($targetSection) {
                    return ['type' => 'before', 'target_section' => $targetSection];
                }
            }
        }

        if (preg_match('/(κάτω από|μετά από|after|below)/u', $message)) {
            $targetType = $this->detectTargetSectionType($message, $movingSection->section_type);
            if ($targetType) {
                $targetSection = \App\Models\PageSection::where('page_type', $pageType)
                    ->where('section_type', $targetType)
                    ->first();
                if ($targetSection) {
                    return ['type' => 'after', 'target_section' => $targetSection];
                }
            }
        }

        return null;
    }

    /**
     * Detect the target section type from message (for relative positioning)
     */
    protected function detectTargetSectionType(string $message, string $excludeType): ?string
    {
        $patterns = [
            'hero_slider' => ['slider', 'carousel', 'slideshow'],
            'hero_simple' => ['hero', 'banner'],
            'about_us' => ['about', 'σχετικά'],
            'features_grid' => ['features', 'χαρακτηριστικά', 'πλεονεκτήματα'],
            'blog_posts_list' => ['blog', 'άρθρα', 'posts', 'articles'],
            'testimonials' => ['testimonial', 'μαρτυρίες', 'κριτικές'],
            'call_to_action' => ['cta', 'call to action'],
            'stats_counter' => ['stats', 'statistics', 'στατιστικά', 'αριθμοί'],
            'gallery' => ['gallery', 'φωτογραφίες', 'εικόνες'],
            'contact_form' => ['contact', 'επικοινωνία', 'φόρμα'],
        ];

        foreach ($patterns as $type => $keywords) {
            if ($type === $excludeType) continue; // Skip the section we're moving

            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * Perform the actual section reordering
     */
    protected function reorderSection($section, array $position, string $pageType): array
    {
        $sectionTypes = \App\Models\PageSection::getSectionTypes();
        $sectionName = $sectionTypes[$section->section_type]['name'] ?? $section->section_type;

        if ($position['type'] === 'absolute') {
            // Move to specific position (0 = first, max = last)
            $targetOrder = $position['position'];

            // Get all sections for this page ordered
            $allSections = \App\Models\PageSection::where('page_type', $pageType)
                ->orderBy('order')
                ->get();

            // Remove the section from its current position
            $allSections = $allSections->filter(fn($s) => $s->id !== $section->id);

            // Insert at target position
            $newOrder = [];
            $inserted = false;

            foreach ($allSections as $index => $s) {
                if ($index == $targetOrder && !$inserted) {
                    $newOrder[] = $section;
                    $inserted = true;
                }
                $newOrder[] = $s;
            }

            // If not inserted yet, add at the end
            if (!$inserted) {
                $newOrder[] = $section;
            }

            // Update order for all sections
            foreach ($newOrder as $index => $s) {
                $s->update(['order' => $index]);
            }

            $positionText = $targetOrder === 0 ? 'στην αρχή' : 'στο τέλος';

            return [
                'success' => true,
                'message' => "✅ Μετέφερα το {$sectionName} section {$positionText} της σελίδας!",
                'section_id' => $section->id,
            ];

        } elseif ($position['type'] === 'before') {
            // Move before another section
            $targetSection = $position['target_section'];
            $targetName = $sectionTypes[$targetSection->section_type]['name'] ?? $targetSection->section_type;

            // Get all sections
            $allSections = \App\Models\PageSection::where('page_type', $pageType)
                ->orderBy('order')
                ->get();

            // Remove the section from its current position
            $allSections = $allSections->filter(fn($s) => $s->id !== $section->id);

            // Insert before target
            $newOrder = [];
            foreach ($allSections as $s) {
                if ($s->id === $targetSection->id) {
                    $newOrder[] = $section;
                }
                $newOrder[] = $s;
            }

            // Update order
            foreach ($newOrder as $index => $s) {
                $s->update(['order' => $index]);
            }

            return [
                'success' => true,
                'message' => "✅ Μετέφερα το {$sectionName} section πάνω από το {$targetName}!",
                'section_id' => $section->id,
            ];

        } elseif ($position['type'] === 'after') {
            // Move after another section
            $targetSection = $position['target_section'];
            $targetName = $sectionTypes[$targetSection->section_type]['name'] ?? $targetSection->section_type;

            // Get all sections
            $allSections = \App\Models\PageSection::where('page_type', $pageType)
                ->orderBy('order')
                ->get();

            // Remove the section from its current position
            $allSections = $allSections->filter(fn($s) => $s->id !== $section->id);

            // Insert after target
            $newOrder = [];
            foreach ($allSections as $s) {
                $newOrder[] = $s;
                if ($s->id === $targetSection->id) {
                    $newOrder[] = $section;
                }
            }

            // Update order
            foreach ($newOrder as $index => $s) {
                $s->update(['order' => $index]);
            }

            return [
                'success' => true,
                'message' => "✅ Μετέφερα το {$sectionName} section κάτω από το {$targetName}!",
                'section_id' => $section->id,
            ];
        }

        return [
            'success' => false,
            'message' => 'Κάτι πήγε στραβά με τη μετακίνηση.'
        ];
    }
}
