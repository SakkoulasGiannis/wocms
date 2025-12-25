@push('scripts')
    <script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>
    <script src="https://unpkg.com/grapesjs"></script>
    <script src="https://unpkg.com/grapesjs-blocks-basic"></script>
    <!-- CodeMirror JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <!-- CodeMirror Modes -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <!-- CodeMirror Addons - Code Folding -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/foldcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/foldgutter.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/brace-fold.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/xml-fold.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/indent-fold.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/comment-fold.min.js"></script>
    <!-- CodeMirror Addons - Autocomplete -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/hint/show-hint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/hint/html-hint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/hint/xml-hint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/hint/css-hint.min.js"></script>
    <!-- CodeMirror Addons - Tag Matching & Closing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/matchtags.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closetag.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closebrackets.min.js"></script>
    <!-- CodeMirror Addons - Search & Selection -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/search/search.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/search/searchcursor.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/dialog/dialog.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/selection/active-line.min.js"></script>
    <!-- CodeMirror Addons - Multiple Selections -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/selection/mark-selection.min.js"></script>
    <!-- CodeMirror Addons - Comments -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/comment/comment.min.js"></script>
    <!-- JS Beautify for code formatting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.14.7/beautify-html.min.js"></script>
    <script>
        // GrapeJS editors storage
        window.grapeEditors = window.grapeEditors || {};

        // Simple function to sync GrapeJS content to Livewire
        window.syncGrapeJS = function() {
            if (!window.grapeEditors || Object.keys(window.grapeEditors).length === 0) {
                console.log('No GrapeJS editors');
                return;
            }

            for (const [fieldName, editor] of Object.entries(window.grapeEditors)) {
                try {
                    const html = editor.getHtml();
                    const css = editor.getCss();
                    const cssFieldName = fieldName + '_css';

                    console.log('Sync field:', fieldName);

                    // Set to Livewire component directly
                    @this.set('fieldValues.' + fieldName, html);
                    @this.set('fieldValues.' + cssFieldName, css);
                } catch (e) {
                    console.error('Error:', e);
                }
            }
        }

        // Enhanced CodeMirror configuration factory
        window.createEnhancedCodeMirror = function(textarea) {
            return CodeMirror.fromTextArea(textarea, {
                mode: 'htmlmixed',
                theme: 'monokai',

                // Basic settings
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 4,
                tabSize: 4,
                indentWithTabs: false,

                // Code folding
                foldGutter: true,
                gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],

                // Auto-completion
                extraKeys: {
                    "Ctrl-S": function(cm) {           // Save/Apply changes
                        applyCodeChangesWithoutClosing();
                        return false; // Prevent default browser save
                    },
                    "Cmd-S": function(cm) {            // Mac Save
                        applyCodeChangesWithoutClosing();
                        return false;
                    },
                    "Ctrl-Space": "autocomplete",      // Trigger autocomplete
                    "Cmd-Space": "autocomplete",       // Mac autocomplete
                    "Ctrl-/": "toggleComment",         // Toggle comment
                    "Cmd-/": "toggleComment",          // Mac toggle comment
                    "Ctrl-F": "findPersistent",        // Find/Replace dialog
                    "Cmd-F": "findPersistent",         // Mac Find
                    "Ctrl-H": "replace",               // Replace
                    "Cmd-Alt-F": "replace",            // Mac Replace
                    "Ctrl-D": function(cm) {           // Delete line
                        cm.execCommand("deleteLine");
                    },
                    "Ctrl-Shift-D": function(cm) {     // Duplicate line
                        var cursor = cm.getCursor();
                        var line = cm.getLine(cursor.line);
                        cm.replaceRange('\n' + line, {line: cursor.line, ch: line.length});
                    },
                    "Alt-Up": function(cm) {           // Move line up
                        var cursor = cm.getCursor();
                        if (cursor.line > 0) {
                            var line = cm.getLine(cursor.line);
                            cm.replaceRange('', {line: cursor.line, ch: 0}, {line: cursor.line + 1, ch: 0});
                            cm.replaceRange(line + '\n', {line: cursor.line - 1, ch: 0});
                            cm.setCursor(cursor.line - 1, cursor.ch);
                        }
                    },
                    "Alt-Down": function(cm) {         // Move line down
                        var cursor = cm.getCursor();
                        if (cursor.line < cm.lineCount() - 1) {
                            var line = cm.getLine(cursor.line);
                            cm.replaceRange('', {line: cursor.line, ch: 0}, {line: cursor.line + 1, ch: 0});
                            cm.replaceRange(line + '\n', {line: cursor.line + 1, ch: 0});
                            cm.setCursor(cursor.line + 1, cursor.ch);
                        }
                    }
                },

                // Tag and bracket features
                autoCloseTags: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                matchTags: {bothTags: true},

                // Visual enhancements
                styleActiveLine: true,
                highlightSelectionMatches: {
                    showToken: /\w/,
                    annotateScrollbar: true
                },

                // Hints
                hintOptions: {
                    completeSingle: false,
                    closeOnUnfocus: true,
                    alignWithWord: true,
                    closeCharacters: /[\s()\[\]{};:>,]/
                }
            });
        };

        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({el, component}) => {
                // Keep code editor modal open after Livewire update if flag is set
                if (window.keepCodeEditorOpen) {
                    setTimeout(() => {
                        const modal = document.getElementById('code-editor-modal');
                        if (modal && !modal.classList.contains('active')) {
                            modal.classList.add('active');
                            console.log('Restored code editor modal after Livewire update');
                        }
                        // Reset flag
                        window.keepCodeEditorOpen = false;
                    }, 100);
                }

                // Reinitialize Trix editors after Livewire updates
                el.querySelectorAll('trix-editor').forEach(editor => {
                    if (!editor.editor) {
                        editor.addEventListener('trix-change', function (e) {
                            const fieldName = this.getAttribute('data-field-name');
                            @this.
                            set('fieldValues.' + fieldName, this.value);
                        });
                    }
                });

                // Reinitialize GrapeJS editors only if they don't exist
                el.querySelectorAll('.gjs-editor').forEach(container => {
                    const fieldName = container.id.replace('gjs-', '');

                    // Only reinitialize if editor doesn't exist
                    if (!window.grapeEditors || !window.grapeEditors[fieldName]) {
                        if (typeof window.initializeGrapeJSEditor === 'function') {
                            console.log('Reinitializing GrapeJS for field:', fieldName);
                            setTimeout(() => window.initializeGrapeJSEditor(fieldName), 100);
                        }
                    } else {
                        console.log('GrapeJS editor exists, skipping reinitialization for:', fieldName);
                    }
                });
            });
        });
    </script>

    <script>
        // Repeater field functions
        function addRepeaterItem(fieldName) {
            // Reload the page to let Livewire handle the new item
            @this.
            call('addRepeaterItem', fieldName);
        }

        function removeRepeaterItem(fieldName, index) {
            if (confirm('Are you sure you want to remove this item?')) {
                @this.
                call('removeRepeaterItem', fieldName, index);
            }
        }

        // Fullscreen toggle function for GrapeJS
        function toggleFullscreen(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            if (container.classList.contains('fullscreen')) {
                // Exit fullscreen
                container.classList.remove('fullscreen');

                // Remove fullscreen buttons toolbar if exists
                const toolbarBtns = container.querySelector('.fullscreen-toolbar-btns');
                if (toolbarBtns) toolbarBtns.remove();

                // Reset scroll
                document.body.style.overflow = '';
            } else {
                // Enter fullscreen
                container.classList.add('fullscreen');

                // Disable body scroll
                document.body.style.overflow = 'hidden';

                // Extract field name from container ID (gjs-container-{fieldName})
                const fieldName = containerId.replace('gjs-container-', '');

                // Add toolbar with buttons
                const toolbar = document.createElement('div');
                toolbar.className = 'fullscreen-toolbar-btns';
                toolbar.style.cssText = 'position: fixed; top: 1rem; right: 1rem; z-index: 100000; display: flex; gap: 0.5rem;';

                // Edit Code button
                const editCodeBtn = document.createElement('button');
                editCodeBtn.className = 'inline-flex items-center px-3 py-2 bg-indigo-700 text-white text-sm rounded hover:bg-indigo-800 transition shadow-lg';
                editCodeBtn.type = 'button';
                editCodeBtn.innerHTML = `
    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
    </svg>
    Edit Code
`;
                editCodeBtn.onclick = () => openCodeEditorForField(fieldName);

                // Exit button
                const exitBtn = document.createElement('button');
                exitBtn.className = 'inline-flex items-center px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition shadow-lg';
                exitBtn.type = 'button';
                exitBtn.innerHTML = `
    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
    Exit Full Page
`;
                exitBtn.onclick = () => toggleFullscreen(containerId);

                toolbar.appendChild(editCodeBtn);
                toolbar.appendChild(exitBtn);
                container.insertBefore(toolbar, container.firstChild);
            }
        }

        // ESC key to exit fullscreen
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const fullscreenContainer = document.querySelector('.gjs-container-wrapper.fullscreen');
                if (fullscreenContainer) {
                    toggleFullscreen(fullscreenContainer.id);
                }

                // Also close code editor if open
                const codeModal = document.getElementById('code-editor-modal');
                if (codeModal && codeModal.classList.contains('active')) {
                    closeCodeEditor();
                }
            }
        });

        // CodeMirror instance storage
        let codeEditorInstance = null;

        // Track if modal should stay open after Livewire update
        window.keepCodeEditorOpen = false;

        // Open code editor for a specific field
        function openCodeEditorForField(fieldName) {
            const grapeEditor = window.grapeEditors[fieldName];
            if (!grapeEditor) {
                console.error('GrapeJS editor not found for field:', fieldName);
                alert('Editor not found. Please make sure the page is fully loaded.');
                return;
            }
            window.currentGrapeEditor = grapeEditor;
            openCodeEditor(grapeEditor);
        }

        // Open code editor modal for full page
        function openCodeEditor(grapeEditor) {
            const modal = document.getElementById('code-editor-modal');
            const modalTitle = modal.querySelector('.code-editor-header h3');
            const textarea = document.getElementById('code-editor-textarea');

            // Clear editing component flag
            window.editingComponent = null;
            window.editingFullPage = true;

            // Get current HTML from GrapeJS
            const currentHtml = grapeEditor.getHtml();

            // Update modal title
            modalTitle.textContent = 'Edit Full Page HTML';

            // Check if CodeMirror needs to be recreated
            let needsRecreate = false;
            if (!codeEditorInstance) {
                needsRecreate = true;
            } else {
                try {
                    // Check if CodeMirror is properly attached
                    const wrapper = codeEditorInstance.getWrapperElement();
                    if (!wrapper || !wrapper.parentNode) {
                        console.warn('CodeMirror wrapper detached');
                        needsRecreate = true;
                    }
                } catch (e) {
                    console.warn('CodeMirror instance corrupted:', e);
                    needsRecreate = true;
                }
            }

            if (needsRecreate) {
                // Clean up old instance if it exists
                if (codeEditorInstance && codeEditorInstance.toTextArea) {
                    try {
                        codeEditorInstance.toTextArea();
                    } catch (e) {
                        console.warn('Failed to clean up CodeMirror:', e);
                    }
                }

                // Make sure textarea is visible and not already converted
                textarea.style.display = '';

                // Create new instance with enhanced features
                codeEditorInstance = window.createEnhancedCodeMirror(textarea);
                console.log('Enhanced CodeMirror instance created/recreated');
            }

            // Set current HTML to editor
            codeEditorInstance.setValue(currentHtml);

            // Show modal
            modal.classList.add('active');

            // Refresh CodeMirror (fixes display issues)
            setTimeout(() => {
                codeEditorInstance.refresh();
                codeEditorInstance.focus();
                // Auto-format code on open
                formatCode();
            }, 100);
        }

        // Open code editor modal for selected component only
        function openComponentCodeEditor() {
            const modal = document.getElementById('code-editor-modal');
            const modalTitle = modal.querySelector('.code-editor-header h3');
            const textarea = document.getElementById('code-editor-textarea');

            // Always get fresh component from current editor
            if (!window.currentGrapeEditor) {
                console.error('No GrapeJS editor found');
                alert('Editor not found. Please try again.');
                return;
            }

            const component = window.currentGrapeEditor.getSelected();

            // Verify component exists
            if (!component) {
                console.error('No component selected');
                alert('Please select a component first.');
                return;
            }

            // Store the current component reference
            window.editingComponent = component;

            // Set editing component flag
            window.editingFullPage = false;

            // Get component HTML
            const componentHtml = component.toHTML();

            // Update modal title
            const componentName = component.get('tagName') || 'Component';
            modalTitle.textContent = `Edit Component HTML (${componentName})`;

            // Check if CodeMirror needs to be recreated
            let needsRecreate = false;
            if (!codeEditorInstance) {
                needsRecreate = true;
            } else {
                try {
                    // Check if CodeMirror is properly attached
                    const wrapper = codeEditorInstance.getWrapperElement();
                    if (!wrapper || !wrapper.parentNode) {
                        console.warn('CodeMirror wrapper detached');
                        needsRecreate = true;
                    }
                } catch (e) {
                    console.warn('CodeMirror instance corrupted:', e);
                    needsRecreate = true;
                }
            }

            if (needsRecreate) {
                // Clean up old instance if it exists
                if (codeEditorInstance && codeEditorInstance.toTextArea) {
                    try {
                        codeEditorInstance.toTextArea();
                    } catch (e) {
                        console.warn('Failed to clean up CodeMirror:', e);
                    }
                }

                // Make sure textarea is visible and not already converted
                textarea.style.display = '';

                // Create new instance with enhanced features
                codeEditorInstance = window.createEnhancedCodeMirror(textarea);
                console.log('Enhanced CodeMirror instance created/recreated');
            }

            // Set component HTML to editor
            codeEditorInstance.setValue(componentHtml);

            // Show modal
            modal.classList.add('active');

            // Refresh CodeMirror (fixes display issues)
            setTimeout(() => {
                codeEditorInstance.refresh();
                codeEditorInstance.focus();
                // Auto-format code on open
                formatCode();
            }, 100);
        }

        // Close code editor modal
        function closeCodeEditor() {
            const modal = document.getElementById('code-editor-modal');
            modal.classList.remove('active');

            // Clear editing component reference
            window.editingComponent = null;
            window.editingFullPage = false;

            // Clear the keep open flag
            window.keepCodeEditorOpen = false;
        }

        // Apply code changes to GrapeJS
        function applyCodeChanges() {
            if (!codeEditorInstance || !window.currentGrapeEditor) {
                console.error('CodeMirror or GrapeJS editor not found');
                return;
            }

            const newHtml = codeEditorInstance.getValue();

            try {
                if (window.editingComponent && !window.editingFullPage) {
                    // Get fresh component from editor (in case it was replaced)
                    const currentSelected = window.currentGrapeEditor.getSelected();

                    // Use the currently selected component if available, otherwise use stored one
                    const component = currentSelected || window.editingComponent;

                    if (!component) {
                        throw new Error('No component found. Please select a component and try again.');
                    }

                    const parent = component.parent();

                    if (!parent) {
                        throw new Error('Component has no parent. It may have been removed.');
                    }

                    const index = parent.components().indexOf(component);

                    console.log('Updating component at index:', index);

                    // Use replaceWith to replace the component
                    // replaceWith returns an array of new components
                    const newComponents = component.replaceWith(newHtml);
                    const newComponent = Array.isArray(newComponents) ? newComponents[0] : newComponents;

                    console.log('Component replaced successfully', newComponent);

                    // Select the new component in the editor so next edit works
                    if (newComponent) {
                        // First deselect everything
                        window.currentGrapeEditor.select();

                        // Then select the new component after a delay
                        // This ensures the component:selected event fires and adds the toolbar
                        setTimeout(() => {
                            window.currentGrapeEditor.select(newComponent);
                            console.log('New component selected after replace');
                        }, 100);
                    }

                    // Trigger update event for proper save handling
                    setTimeout(() => {
                        const html = window.currentGrapeEditor.getHtml();
                        const css = window.currentGrapeEditor.getCss();

                        // Find field name
                        let fieldName = null;
                        for (const [name, editor] of Object.entries(window.grapeEditors || {})) {
                            if (editor === window.currentGrapeEditor) {
                                fieldName = name;
                                break;
                            }
                        }

                        if (fieldName) {
                            const cssFieldName = fieldName + '_css';
                            @this.set('fieldValues.' + fieldName, html);
                            @this.set('fieldValues.' + cssFieldName, css);
                            console.log('Component change saved to Livewire');
                        }
                    }, 100);
                } else {
                    // Update full page HTML
                    console.log('Updating full page with new HTML, length:', newHtml.length);

                    // Store current CSS before updating
                    const currentCss = window.currentGrapeEditor.getCss();
                    console.log('Current CSS length:', currentCss.length);

                    // Clear and rebuild components
                    const wrapper = window.currentGrapeEditor.DomComponents.getWrapper();
                    const oldComponents = wrapper.components();
                    console.log('Old components count:', oldComponents.length);

                    // Clear all components
                    oldComponents.reset();

                    // Add new HTML
                    window.currentGrapeEditor.setComponents(newHtml);

                    // Restore CSS if it was lost
                    const newCss = window.currentGrapeEditor.getCss();
                    console.log('New CSS length after setComponents:', newCss.length);

                    if (newCss.length === 0 && currentCss.length > 0) {
                        console.log('CSS was lost, restoring...');
                        window.currentGrapeEditor.setStyle(currentCss);
                    }

                    console.log('New components count:', wrapper.components().length);
                    console.log('Full page HTML updated successfully');

                    // Force complete canvas refresh
                    setTimeout(() => {
                        const editor = window.currentGrapeEditor;

                        // Trigger change
                        editor.trigger('change:changesCount');

                        // Force canvas refresh
                        const canvas = editor.Canvas;
                        if (canvas) {
                            console.log('Refreshing canvas...');
                            canvas.refresh();

                            // Also refresh the frame
                            const frame = canvas.getFrame();
                            if (frame) {
                                const frameEl = frame.getEl();
                                if (frameEl) {
                                    frameEl.contentWindow.location.reload = function() {};
                                }
                            }
                        }

                        // Force editor refresh
                        editor.refresh();

                        console.log('Canvas refresh complete');
                    }, 100);
                }

                // Close modal with delay to allow refresh
                setTimeout(() => {
                    closeCodeEditor();
                }, 300);
            } catch (e) {
                console.error('Failed to update HTML:', e);
                alert('Error updating HTML: ' + e.message + '\nPlease check the console for details.');
            }
        }

        // Apply code changes without closing modal (for Ctrl/Cmd + S)
        function applyCodeChangesWithoutClosing() {
            if (!codeEditorInstance || !window.currentGrapeEditor) {
                console.error('CodeMirror or GrapeJS editor not found');
                return;
            }

            const newHtml = codeEditorInstance.getValue();

            try {
                if (window.editingComponent && !window.editingFullPage) {
                    // Get fresh component from editor (in case it was replaced)
                    const currentSelected = window.currentGrapeEditor.getSelected();

                    // Use the currently selected component if available, otherwise use stored one
                    const component = currentSelected || window.editingComponent;

                    if (!component) {
                        throw new Error('No component found. Please select a component and try again.');
                    }

                    const parent = component.parent();

                    if (!parent) {
                        throw new Error('Component has no parent. It may have been removed.');
                    }

                    const index = parent.components().indexOf(component);

                    console.log('Updating component at index:', index);

                    // Use replaceWith to replace the component
                    const newComponents = component.replaceWith(newHtml);
                    const newComponent = Array.isArray(newComponents) ? newComponents[0] : newComponents;

                    console.log('Component replaced successfully', newComponent);

                    // Select the new component in the editor so next edit works
                    if (newComponent) {
                        window.currentGrapeEditor.select();
                        setTimeout(() => {
                            window.currentGrapeEditor.select(newComponent);
                            console.log('New component selected after replace');
                        }, 100);
                    }

                    // Trigger update event for proper save handling
                    setTimeout(() => {
                        const html = window.currentGrapeEditor.getHtml();
                        const css = window.currentGrapeEditor.getCss();

                        let fieldName = null;
                        for (const [name, editor] of Object.entries(window.grapeEditors || {})) {
                            if (editor === window.currentGrapeEditor) {
                                fieldName = name;
                                break;
                            }
                        }

                        if (fieldName) {
                            const cssFieldName = fieldName + '_css';

                            // Set flag to keep modal open after Livewire update
                            window.keepCodeEditorOpen = true;

                            @this.set('fieldValues.' + fieldName, html);
                            @this.set('fieldValues.' + cssFieldName, css);
                            console.log('Component change saved to Livewire');
                        }
                    }, 100);
                } else {
                    // Update full page HTML
                    console.log('Updating full page with new HTML (without closing), length:', newHtml.length);

                    // Store current CSS before updating
                    const currentCss = window.currentGrapeEditor.getCss();
                    console.log('Current CSS length:', currentCss.length);

                    // Clear and rebuild components
                    const wrapper = window.currentGrapeEditor.DomComponents.getWrapper();
                    const oldComponents = wrapper.components();
                    console.log('Old components count:', oldComponents.length);

                    // Clear all components
                    oldComponents.reset();

                    // Add new HTML
                    window.currentGrapeEditor.setComponents(newHtml);

                    // Restore CSS if it was lost
                    const newCss = window.currentGrapeEditor.getCss();
                    console.log('New CSS length after setComponents:', newCss.length);

                    if (newCss.length === 0 && currentCss.length > 0) {
                        console.log('CSS was lost, restoring...');
                        window.currentGrapeEditor.setStyle(currentCss);
                    }

                    console.log('New components count:', wrapper.components().length);
                    console.log('Full page HTML updated successfully');

                    // Force complete canvas refresh
                    setTimeout(() => {
                        const editor = window.currentGrapeEditor;

                        // Trigger change
                        editor.trigger('change:changesCount');

                        // Force canvas refresh
                        const canvas = editor.Canvas;
                        if (canvas) {
                            console.log('Refreshing canvas (without closing)...');
                            canvas.refresh();

                            // Also refresh the frame
                            const frame = canvas.getFrame();
                            if (frame) {
                                const frameEl = frame.getEl();
                                if (frameEl) {
                                    frameEl.contentWindow.location.reload = function() {};
                                }
                            }
                        }

                        // Force editor refresh
                        editor.refresh();

                        console.log('Canvas refresh complete (without closing)');
                    }, 100);
                }

                // Show success feedback
                setTimeout(() => {
                    console.log('Code changes applied without closing. Modal should stay open.');
                    showSaveNotification();
                }, 200);
            } catch (e) {
                console.error('Failed to update HTML:', e);
                alert('Error updating HTML: ' + e.message + '\nPlease check the console for details.');
            }
        }

        // Show save notification
        function showSaveNotification() {
            const modal = document.getElementById('code-editor-modal');
            const header = modal.querySelector('.code-editor-header');

            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: absolute;
                top: 1rem;
                right: 50%;
                transform: translateX(50%);
                background: #10b981;
                color: white;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 999999;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.9rem;
                animation: slideDown 0.3s ease-out;
            `;
            notification.innerHTML = `
                <svg style="width: 1.25rem; height: 1.25rem;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>Changes applied successfully!</span>
            `;

            // Add animation style
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateX(50%) translateY(-1rem);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(50%) translateY(0);
                    }
                }
            `;
            document.head.appendChild(style);

            modal.appendChild(notification);

            // Remove after 2 seconds
            setTimeout(() => {
                notification.style.animation = 'slideDown 0.3s ease-in reverse';
                setTimeout(() => {
                    notification.remove();
                    style.remove();
                }, 300);
            }, 2000);
        }

        // Format code using js-beautify
        function formatCode() {
            if (!codeEditorInstance) {
                alert('Code editor not initialized');
                return;
            }

            const currentCode = codeEditorInstance.getValue();

            try {
                const formatted = html_beautify(currentCode, {
                    indent_size: 4,
                    indent_char: ' ',
                    max_preserve_newlines: 2,
                    preserve_newlines: true,
                    wrap_line_length: 120,
                    end_with_newline: true,
                    indent_inner_html: true,
                    unformatted: ['pre', 'code'],
                    content_unformatted: ['pre', 'textarea']
                });

                codeEditorInstance.setValue(formatted);
                codeEditorInstance.refresh();
            } catch (e) {
                console.error('Failed to format code:', e);
                alert('Error formatting code: ' + e.message);
            }
        }

        // Improve code with AI
        async function improveCodeWithAI() {
            console.log('=== improveCodeWithAI() called ===');

            if (!codeEditorInstance) {
                alert('Code editor not initialized');
                return;
            }

            const promptInput = document.getElementById('code-ai-prompt');
            const btn = document.getElementById('code-ai-improve-btn');
            const iconDefault = document.getElementById('code-ai-icon-default');
            const iconLoading = document.getElementById('code-ai-icon-loading');
            const btnText = document.getElementById('code-ai-btn-text');
            const resultDiv = document.getElementById('code-ai-result');
            const messageEl = document.getElementById('code-ai-message');

            const prompt = promptInput.value.trim();
            if (!prompt) {
                alert('⚠️ Please enter a prompt for the AI');
                return;
            }

            // Set loading state
            btn.disabled = true;
            iconDefault.style.display = 'none';
            iconLoading.style.display = 'block';
            btnText.textContent = 'Processing...';
            resultDiv.style.display = 'none';

            try {
                // Get current code from editor
                const currentCode = codeEditorInstance.getValue();

                console.log('Current code length:', currentCode.length);
                console.log('Prompt:', prompt);

                // Call Livewire method
                const improvedCode = await @this.call('improveCode', currentCode, prompt);

                console.log('Improved code received, length:', improvedCode.length);

                // Update editor with improved code
                codeEditorInstance.setValue(improvedCode);
                codeEditorInstance.refresh();

                // Format the improved code
                formatCode();

                // Show success message
                messageEl.textContent = '✅ Code improved successfully! Click "Save" or "Apply & Close" to update the page.';
                messageEl.style.color = '#4ade80';
                resultDiv.style.display = 'block';

                // Clear prompt
                promptInput.value = '';

            } catch (error) {
                console.error('Code improvement failed:', error);
                messageEl.textContent = '❌ Error: ' + error.message;
                messageEl.style.color = '#f87171';
                resultDiv.style.display = 'block';
            } finally {
                btn.disabled = false;
                iconDefault.style.display = 'block';
                iconLoading.style.display = 'none';
                btnText.textContent = 'Improve Code';
            }
        }

        // Wrap selection in tags
        function wrapSelection() {
            if (!codeEditorInstance) return;

            const tag = prompt('Enter tag name (e.g., div, span, section):', 'div');
            if (!tag) return;

            const selection = codeEditorInstance.getSelection();
            if (selection) {
                const wrapped = `<${tag}>\n    ${selection}\n</${tag}>`;
                codeEditorInstance.replaceSelection(wrapped);
            }
        }

        // Insert Blade syntax
        function insertBladeSyntax() {
            if (!codeEditorInstance) return;

            @verbatim
            const options = [
                '{{$content->title}}',
                '{{$content->slug}}',
                '{{$node->url_path}}',
                '@if($content->featured)',
                '@endif',
                '@foreach($pages as $page)',
                '@endforeach'
            ];
            @endverbatim

            const selection = prompt('Choose Blade syntax:\n' + options.map((o, i) => `${i + 1}. ${o}`).join('\n'), '1');
            const index = parseInt(selection) - 1;

            if (index >= 0 && index < options.length) {
                codeEditorInstance.replaceSelection(options[index]);
            }
        }

        // Select all code
        function selectAll() {
            if (!codeEditorInstance) return;
            codeEditorInstance.execCommand('selectAll');
        }

        // Show keyboard shortcuts
        function showKeyboardShortcuts() {
            const shortcuts = `
═══════════════════════════════════════════════════════
     CODE EDITOR - KEYBOARD SHORTCUTS
═══════════════════════════════════════════════════════

💾 SAVE:
   Ctrl/Cmd + S        Apply changes (without closing)

🔍 SEARCH & NAVIGATION:
   Ctrl/Cmd + F        Find
   Ctrl/Cmd + H        Replace
   Ctrl/Cmd + G        Find next
   Shift + Ctrl/Cmd+G  Find previous

✨ AUTO-COMPLETION:
   Ctrl/Cmd + Space    Trigger autocomplete

📝 EDITING:
   Ctrl/Cmd + /        Toggle comment
   Ctrl + D            Delete line
   Ctrl + Shift + D    Duplicate line
   Alt + ↑             Move line up
   Alt + ↓             Move line down
   Tab                 Indent selection
   Shift + Tab         Unindent selection

📋 SELECTION:
   Ctrl/Cmd + A        Select all
   Ctrl/Cmd + D        Select word
   Alt + Click         Add cursor (multiple)

🔧 CODE FOLDING:
   Click [▼] icon      Fold/unfold code block

💡 SMART FEATURES:
   • Auto-close tags (type <div> auto-adds </div>)
   • Auto-close brackets [{()}]
   • Matching tag highlight
   • Active line highlight
   • Code folding for nested structures

═══════════════════════════════════════════════════════
            `;
            alert(shortcuts);
        }

        // Global GrapeJS initialization function
        window.initializeGrapeJSEditor = function(fieldName, forceReinit = false) {
            const editorId = 'gjs-' + fieldName;

            // If editor already exists and we're not forcing reinit, don't recreate it
            if (!forceReinit && window.grapeEditors && window.grapeEditors[fieldName]) {
                console.log('GrapeJS editor already initialized for:', fieldName);
                return window.grapeEditors[fieldName];
            }

            // Wait for grapesjs library to load
            if (typeof grapesjs === 'undefined') {
                setTimeout(() => window.initializeGrapeJSEditor(fieldName, forceReinit), 100);
                return;
            }

            const container = document.getElementById(editorId);

            if (!container) {
                // Element not ready yet, try again
                setTimeout(() => window.initializeGrapeJSEditor(fieldName, forceReinit), 100);
                return;
            }

            const dataInput = document.getElementById('gjs-data-' + fieldName);

            // Only destroy if forcing reinit
            if (forceReinit && window.grapeEditors && window.grapeEditors[fieldName]) {
                try {
                    console.log('Destroying existing editor for:', fieldName);
                    window.grapeEditors[fieldName].destroy();
                } catch (e) {
                    console.warn('Failed to destroy existing editor:', e);
                }
                delete window.grapeEditors[fieldName];
            }

            if (!window.grapeEditors) {
                window.grapeEditors = {};
            }

            console.log('Initializing new GrapeJS editor for:', fieldName);

            const editor = grapesjs.init({
                container: '#' + editorId,
                height: '600px',
                width: 'auto',
                storageManager: false,
                plugins: ['gjs-blocks-basic'],
                pluginsOpts: {
                    'gjs-blocks-basic': {
                        flexGrid: true,
                        stylePrefix: 'gjs-',
                    }
                },
                canvas: {
                    scripts: [
                        'https://cdn.tailwindcss.com'
                    ],
                },
            });

            // Store editor globally
            window.grapeEditors[fieldName] = editor;

            // Add custom command for editing component code
            editor.Commands.add('edit-component-code', {
                run: function(editor, sender) {
                    const selected = editor.getSelected();
                    if (!selected) {
                        alert('Please select a component first');
                        return;
                    }
                    window.currentGrapeEditor = editor;
                    // Don't pass the component, the function will get it fresh from editor
                    openComponentCodeEditor();
                }
            });

            // Add custom toolbar button to components when selected
            editor.on('component:selected', (component) => {
                if (!component) {
                    console.log('No component in component:selected event');
                    return;
                }

                const toolbar = component.get('toolbar');
                const commandExists = toolbar.some(item => item.command === 'edit-component-code');

                console.log('Component selected:', component.get('tagName'), 'Has edit button:', commandExists);

                if (!commandExists) {
                    toolbar.unshift({
                        attributes: { class: '', title: 'Edit HTML' },
                        command: 'edit-component-code',
                        label: '<svg style="width:16px;height:16px;fill:currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>',
                    });
                    component.set('toolbar', toolbar);
                    console.log('Added edit button to toolbar');
                }
            });

            // Add custom Tailwind-styled blocks
            const blockManager = editor.BlockManager;

            // Hero Section
            blockManager.add('hero-section', {
                label: 'Hero Section',
                category: 'Tailwind',
                content: `
<section class="bg-gradient-to-r from-blue-500 to-purple-600 text-white py-20">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-5xl font-bold mb-4">Welcome to Our Amazing Site</h1>
        <p class="text-xl mb-8">Build beautiful pages with Tailwind CSS</p>
        <button class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">Get Started</button>
    </div>
</section>
`,
            });

            // Feature Card
            blockManager.add('feature-card', {
                label: 'Feature Card',
                category: 'Tailwind',
                content: `
<div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
    <div class="text-blue-600 text-4xl mb-4">🚀</div>
    <h3 class="text-xl font-bold mb-2">Amazing Feature</h3>
    <p class="text-gray-600">Description of your amazing feature goes here.</p>
</div>
`,
            });

            // Two Column Layout
            blockManager.add('two-columns', {
                label: 'Two Columns',
                category: 'Tailwind',
                content: `
<div class="container mx-auto px-4 py-12">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-gray-100 p-6 rounded-lg">
            <h3 class="text-2xl font-bold mb-4">Column 1</h3>
            <p>Content for column 1</p>
        </div>
        <div class="bg-gray-100 p-6 rounded-lg">
            <h3 class="text-2xl font-bold mb-4">Column 2</h3>
            <p>Content for column 2</p>
        </div>
    </div>
</div>
`,
            });

            // Navbar
            blockManager.add('navbar', {
                label: 'Navigation Bar',
                category: 'Tailwind',
                content: `
<nav class="bg-white shadow-lg">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div class="text-2xl font-bold text-blue-600">Logo</div>
            <div class="flex space-x-6">
                <a href="#" class="text-gray-700 hover:text-blue-600 transition">Home</a>
                <a href="#" class="text-gray-700 hover:text-blue-600 transition">About</a>
                <a href="#" class="text-gray-700 hover:text-blue-600 transition">Services</a>
                <a href="#" class="text-gray-700 hover:text-blue-600 transition">Contact</a>
            </div>
        </div>
    </div>
</nav>
`,
            });

            // Footer
            blockManager.add('footer', {
                label: 'Footer',
                category: 'Tailwind',
                content: `
<footer class="bg-gray-800 text-white py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h4 class="text-xl font-bold mb-4">About Us</h4>
                <p class="text-gray-400">Your company description here.</p>
            </div>
            <div>
                <h4 class="text-xl font-bold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Home</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">About</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Services</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xl font-bold mb-4">Contact</h4>
                <p class="text-gray-400">Email: info@example.com</p>
                <p class="text-gray-400">Phone: (123) 456-7890</p>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; 2024 Your Company. All rights reserved.</p>
        </div>
    </div>
</footer>
`,
            });

            // Container Block - Simple div container for dragging other blocks inside (Tailwind only)
            blockManager.add('container-block', {
                label: `
                    <div style="text-align: center;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="9" y1="9" x2="15" y2="9"></line>
                            <line x1="9" y1="15" x2="15" y2="15"></line>
                        </svg>
                        <div style="font-size: 11px; margin-top: 5px;">Container</div>
                    </div>
                `,
                category: 'Layout',
                content: `<div class="p-4 min-h-[100px]"></div>`,
                attributes: {
                    title: 'Container - Drag other blocks inside'
                }
            });

            // Load existing content after editor is ready
            editor.on('load', () => {
                console.log('GrapeJS editor loaded for field:', fieldName);

                // Load HTML from the field
                if (dataInput && dataInput.value && dataInput.value.trim() !== '') {
                    try {
                        editor.setComponents(dataInput.value);
                        console.log('Loaded HTML into editor from field:', fieldName);
                    } catch (e) {
                        console.error('Failed to load HTML for field:', fieldName, e);
                    }
                }

                // Load CSS from the separate {fieldname}_css field
                const cssFieldName = fieldName + '_css';
                const cssValue = @this.get('fieldValues.' + cssFieldName);
                console.log('Attempting to load CSS for field:', cssFieldName);
                console.log('CSS value length:', cssValue ? cssValue.length : 0);
                console.log('CSS value preview:', cssValue ? cssValue.substring(0, 100) : 'null');

                if (cssValue && cssValue.trim() !== '') {
                    try {
                        editor.setStyle(cssValue);
                        console.log('✅ Successfully loaded CSS into editor from field:', cssFieldName);
                    } catch (e) {
                        console.error('❌ Failed to load CSS for field:', cssFieldName, e);
                    }
                } else {
                    console.warn('⚠️ No CSS to load for field:', cssFieldName);
                }
            });

            // NO AUTO-SAVE - Only save when user clicks Update button

            return editor;
        };
    </script>
@endpush
