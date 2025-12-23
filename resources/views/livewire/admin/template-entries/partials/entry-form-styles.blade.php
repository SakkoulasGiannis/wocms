@push('styles')
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
    <!-- CodeMirror CSS -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <!-- CodeMirror Addons CSS -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/foldgutter.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/hint/show-hint.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/dialog/dialog.min.css">
    <!-- Load Tailwind CSS for GrapeJS preview -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Alpine.js x-cloak */
        [x-cloak] {
            display: none !important;
        }

        trix-toolbar .trix-button-group button {
            border: none;
        }

        trix-editor {
            min-height: 300px;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.75rem;
        }

        trix-editor:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6;
        }

        .gjs-editor {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .gjs-cv-canvas {
            background-color: #ffffff;
        }

        /* Fullscreen mode styles */
        .gjs-container-wrapper.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            background: white;
            padding: 1rem;
        }

        .gjs-container-wrapper.fullscreen .gjs-editor {
            height: calc(100vh - 4rem) !important;
            border-radius: 0;
        }

        .gjs-container-wrapper.fullscreen .fullscreen-toolbar-btns {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 99999 !important;
        }

        div#gjs-html {
            margin-top: 40px;
        }

        /* CodeMirror HTML Editor Modal */
        .code-editor-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }

        .code-editor-modal.active {
            display: flex;
        }

        .code-editor-container {
            background: #272822;
            width: 100%;
            height: 100%;
            border-radius: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .code-editor-header {
            background: #1e1e1e;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #444;
        }

        .code-editor-header h3 {
            color: #fff;
            margin: 0;
            font-size: 1.1rem;
        }

        .code-editor-actions {
            display: flex;
            gap: 0.5rem;
        }

        .code-editor-body {
            flex: 1;
            overflow: hidden;
            display: flex;
        }

        .code-editor-sidebar {
            width: 200px;
            background: #1e1e1e;
            border-right: 1px solid #444;
            padding: 1rem;
            overflow-y: auto;
        }

        .code-editor-sidebar h4 {
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            margin: 0 0 0.75rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #444;
        }

        .code-editor-sidebar button {
            width: 100%;
            text-align: left;
            padding: 0.5rem 0.75rem;
            background: #2d2d2d;
            border: 1px solid #3d3d3d;
            color: #ccc;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .code-editor-sidebar button:hover {
            background: #3d3d3d;
            border-color: #4d4d4d;
            color: #fff;
        }

        .code-editor-sidebar button svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }

        .code-editor-main {
            flex: 1;
            overflow: hidden;
        }

        .CodeMirror {
            height: 100%;
            font-size: 14px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
        }

        /* Enhanced CodeMirror styling */
        .CodeMirror-foldmarker {
            color: #61afef;
            text-shadow: 0 0 2px rgba(97, 175, 239, 0.5);
            cursor: pointer;
            font-family: monospace;
        }

        .CodeMirror-foldgutter {
            width: 12px;
        }

        .CodeMirror-foldgutter-open,
        .CodeMirror-foldgutter-folded {
            cursor: pointer;
            color: #888;
        }

        .CodeMirror-foldgutter-open:hover,
        .CodeMirror-foldgutter-folded:hover {
            color: #61afef;
        }

        .CodeMirror-activeline-background {
            background: rgba(255, 255, 255, 0.05) !important;
        }

        .CodeMirror-hints {
            background: #272822;
            border: 1px solid #3d3d3d;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .CodeMirror-hint {
            color: #ccc;
            padding: 4px 8px;
        }

        .CodeMirror-hint-active {
            background: #3d3d3d;
            color: #fff;
        }

        .CodeMirror-matchingtag {
            background: rgba(97, 175, 239, 0.2);
        }

        .cm-searching {
            background: rgba(255, 255, 0, 0.3);
            border-bottom: 2px solid #f39c12;
        }
    </style>
@endpush
