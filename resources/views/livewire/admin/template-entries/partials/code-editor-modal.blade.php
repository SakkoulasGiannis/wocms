<!-- CodeMirror HTML Editor Modal -->
<div id="code-editor-modal" class="code-editor-modal" wire:ignore.self>
    <div class="code-editor-container">
        <div class="code-editor-header">
            <h3>Edit HTML Code</h3>
            <div class="code-editor-actions">
                <button type="button" onclick="applyCodeChangesWithoutClosing()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
                        title="Save changes (Ctrl/Cmd + S)">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Save
                </button>
                <button type="button" onclick="applyCodeChanges()"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700"
                        title="Apply changes and close">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 13l4 4L19 7"/>
                    </svg>
                    Apply & Close
                </button>
                <button type="button" onclick="closeCodeEditor()"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancel
                </button>
            </div>
        </div>
        <div class="code-editor-body">
            <!-- Sidebar -->
            <div class="code-editor-sidebar">
                <h4>Tools</h4>
                <button type="button" onclick="formatCode()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Format Code
                </button>
                <button type="button" onclick="wrapSelection()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                    </svg>
                    Wrap Selection
                </button>
                <button type="button" onclick="insertBladeSyntax()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    Insert Blade
                </button>
                <button type="button" onclick="selectAll()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Select All
                </button>
                <button type="button" onclick="showKeyboardShortcuts()" style="margin-top: 1rem; border-top: 1px solid #444; padding-top: 1rem;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Keyboard Shortcuts
                </button>

                <!-- AI Assistant -->
                <div style="margin-top: 1rem; border-top: 1px solid #444; padding-top: 1rem;">
                    <h5 style="color: #a78bfa; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem; display: flex; align-items: center;">
                        <svg style="width: 1rem; height: 1rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        🤖 AI Assistant
                    </h5>
                    <textarea
                        id="code-ai-prompt"
                        placeholder="e.g., 'Add responsive classes', 'Fix accessibility issues'"
                        style="width: 100%; padding: 0.5rem; background: #1e1e1e; border: 1px solid #444; border-radius: 4px; color: #fff; font-size: 0.75rem; min-height: 60px; resize: vertical;"
                        rows="3"
                    ></textarea>
                    <button
                        type="button"
                        id="code-ai-improve-btn"
                        onclick="improveCodeWithAI()"
                        style="width: 100%; margin-top: 0.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 0.5rem; border-radius: 4px; color: white; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center;"
                    >
                        <svg id="code-ai-icon-default" style="width: 1rem; height: 1rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <svg id="code-ai-icon-loading" class="hidden animate-spin" style="width: 1rem; height: 1rem; margin-right: 0.5rem; display: none;" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="code-ai-btn-text">Improve Code</span>
                    </button>
                    <div id="code-ai-result" style="margin-top: 0.5rem; padding: 0.5rem; background: #1e1e1e; border-radius: 4px; font-size: 0.75rem; display: none;">
                        <p id="code-ai-message" style="margin: 0; color: #fff;"></p>
                    </div>
                </div>
            </div>
            <!-- Main Editor -->
            <div class="code-editor-main">
                <textarea id="code-editor-textarea"></textarea>
            </div>
        </div>
    </div>
</div>
