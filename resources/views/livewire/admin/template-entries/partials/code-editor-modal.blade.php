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
            </div>
            <!-- Main Editor -->
            <div class="code-editor-main">
                <textarea id="code-editor-textarea"></textarea>
            </div>
        </div>
    </div>
</div>
