<div class="h-full flex flex-col">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Code Editor</h1>
                <p class="mt-1 text-sm text-gray-600">Edit header and footer files</p>
            </div>

            <div class="flex items-center gap-3">
                @if($isDirty)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Unsaved Changes
                    </span>
                @endif

                <button wire:click="reset"
                        @if(!$isDirty) disabled @endif
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Reset
                </button>

                <button wire:click="save"
                        @if(!$isDirty) disabled @endif
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Save
                </button>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="mx-6 mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="ml-3 text-sm text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="ml-3 text-sm text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <div class="flex-1 flex overflow-hidden">
        {{-- File Selector Sidebar --}}
        <div class="w-64 bg-gray-50 border-r border-gray-200 overflow-y-auto">
            <div class="p-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Files</h3>
                <div class="space-y-1">
                    @foreach($files as $name => $path)
                        <button wire:click="selectFile('{{ $path }}')"
                                class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors
                                       {{ $selectedFile === $path ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="truncate">{{ $name }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- Backups Section --}}
                @if(count($backups) > 0)
                    <div class="mt-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Recent Backups</h3>
                        <div class="space-y-1">
                            @foreach($backups as $backup)
                                <button wire:click="restoreBackup('{{ $backup['path'] }}')"
                                        class="w-full text-left px-3 py-2 rounded-md text-xs hover:bg-gray-100 transition-colors">
                                    <div class="flex items-start">
                                        <svg class="w-3 h-3 mr-2 mt-0.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-gray-700 truncate">{{ $backup['date'] }}</p>
                                            <p class="text-gray-400 text-xs">{{ $backup['size'] }}</p>
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Editor Area --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Editor Toolbar --}}
            <div class="bg-gray-50 border-b border-gray-200 px-4 py-2 flex items-center justify-between">
                <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    <span class="font-mono">{{ basename($selectedFile) }}</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span>Lines: <span id="lineCount">0</span></span>
                    <span>•</span>
                    <span>Chars: <span id="charCount">0</span></span>
                </div>
            </div>

            {{-- Monaco Editor Container --}}
            <div id="editor-container" class="flex-1" wire:ignore></div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"></script>
<script>
document.addEventListener('livewire:navigated', function() {
    initMonacoEditor();
});

function initMonacoEditor() {
    require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' }});

    require(['vs/editor/editor.main'], function() {
        const editor = monaco.editor.create(document.getElementById('editor-container'), {
            value: @js($fileContent),
            language: 'html',
            theme: 'vs-dark',
            automaticLayout: true,
            fontSize: 14,
            lineNumbers: 'on',
            minimap: { enabled: true },
            scrollBeyondLastLine: false,
            wordWrap: 'on',
            formatOnPaste: true,
            formatOnType: true,
        });

        // Update Livewire property on change
        editor.onDidChangeModelContent(function() {
            const content = editor.getValue();
            @this.set('fileContent', content);

            // Update stats
            const lineCount = editor.getModel().getLineCount();
            const charCount = content.length;
            document.getElementById('lineCount').textContent = lineCount;
            document.getElementById('charCount').textContent = charCount;
        });

        // Update stats on load
        const lineCount = editor.getModel().getLineCount();
        const charCount = editor.getValue().length;
        document.getElementById('lineCount').textContent = lineCount;
        document.getElementById('charCount').textContent = charCount;

        // Listen for file changes from Livewire
        window.addEventListener('fileContentUpdated', event => {
            editor.setValue(event.detail.content);
        });

        // Keyboard shortcuts
        editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, function() {
            @this.call('save');
        });
    });
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMonacoEditor);
} else {
    initMonacoEditor();
}
</script>
@endpush
