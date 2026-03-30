<div class="px-4 sm:px-0">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $imageMapId ? 'Edit Image Map' : 'Create Image Map' }}</h1>
            <p class="mt-1 text-sm text-gray-600">Draw shapes on the image to create interactive hotspots</p>
        </div>
        <a href="{{ route('admin.imagemaps.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg shadow-sm transition">
            <i class="fa fa-arrow-left mr-2"></i>Back
        </a>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" id="image-map-form">
        <!-- Details -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" wire:model.live.debounce.300ms="title" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Image map name">
                    @error('title') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input type="text" wire:model="slug" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="auto-generated">
                    @error('slug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Base Image *</label>
                @if($currentImageUrl)
                    <div class="mb-2">
                        <img src="{{ $currentImageUrl }}" alt="Current image" class="max-h-32 rounded-lg border border-gray-200">
                    </div>
                @endif
                <input type="file" wire:model="imageUpload" accept="image/*"
                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <div wire:loading wire:target="imageUpload" class="text-xs text-blue-600 mt-1"><i class="fa fa-spinner fa-spin mr-1"></i>Uploading...</div>
                @error('imageUpload') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="flex items-center gap-2 mt-4">
                <input type="checkbox" wire:model="active" id="active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="active" class="text-sm font-medium text-gray-700">Active</label>
            </div>
        </div>

        <!-- Fabric.js Canvas Editor -->
        @if($currentImageUrl)
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gray-50 rounded-t-lg">
                <div class="flex items-center gap-1" id="editor-toolbar">
                    <span class="text-xs font-semibold text-gray-400 uppercase mr-2">Tools:</span>
                    <button type="button" onclick="editorSetTool('select')" id="tool-select" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 bg-blue-600 text-white">
                        <i class="fa fa-mouse-pointer mr-1"></i> Select
                    </button>
                    <button type="button" onclick="editorSetTool('rect')" id="tool-rect" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">
                        <i class="fa fa-square mr-1"></i> Rect
                    </button>
                    <button type="button" onclick="editorSetTool('circle')" id="tool-circle" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">
                        <i class="fa fa-circle mr-1"></i> Circle
                    </button>
                    <button type="button" onclick="editorSetTool('polygon')" id="tool-polygon" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">
                        <i class="fa fa-draw-polygon mr-1"></i> Polygon
                    </button>
                    <span class="border-l border-gray-300 h-6 mx-1"></span>
                    <button type="button" onclick="editorDeleteSelected()" title="Select a shape first, then click Delete (or press Delete key)" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-red-200 bg-white text-red-600 hover:bg-red-50">
                        <i class="fa fa-trash mr-1"></i> Delete Selected
                    </button>
                </div>
                <span class="text-xs text-gray-500" id="shape-count">0 shape(s)</span>
            </div>

            <div wire:ignore class="overflow-hidden">
                <canvas id="image-map-canvas"></canvas>
            </div>

            <!-- Shape Properties -->
            <div id="shape-props" class="border-t border-gray-200 p-4 bg-gray-50 rounded-b-lg" style="display:none;">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700"><i class="fa fa-cog mr-1"></i> Shape Properties</h3>
                    <button type="button" onclick="editorToggleLock()" id="lock-btn" title="Lock/Unlock shape position" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                        <i class="fa fa-unlock mr-1" id="lock-icon"></i> <span id="lock-label">Unlocked</span>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                        <input type="text" id="shape-title" oninput="editorUpdateProp('title', this.value)" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                        <input type="text" id="shape-desc" oninput="editorUpdateProp('description', this.value)" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Link URL</label>
                        <input type="text" id="shape-link" oninput="editorUpdateProp('link', this.value)" class="w-full rounded-lg border-gray-300 text-sm" placeholder="https://...">
                    </div>
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Color</label>
                            <input type="color" id="shape-color" oninput="editorUpdateColor(this.value)" class="w-full h-9 rounded-lg border-gray-300 cursor-pointer" value="#1563df">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Opacity</label>
                            <input type="range" id="shape-opacity" min="0.1" max="0.8" step="0.05" value="0.3" oninput="editorUpdateOpacity(this.value)" class="w-full">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="bg-white rounded-lg shadow mb-6 p-12 text-center">
            <i class="fa fa-image text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500 text-lg">Upload a base image to start creating your interactive map</p>
        </div>
        @endif


        <!-- Save Button -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.imagemaps.index') }}" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition">Cancel</a>
            <button type="button" wire:loading.attr="disabled" onclick="editorSaveMap()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition disabled:opacity-50">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save mr-2"></i>{{ $imageMapId ? 'Update' : 'Create' }} Image Map</span>
                <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin mr-2"></i>Saving...</span>
            </button>
        </div>

        <!-- Usage / Embed Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg shadow mb-6 p-5 mt-6">
            <h2 class="text-sm font-semibold text-blue-800 mb-2"><i class="fa fa-code mr-1"></i> How to embed this Image Map</h2>
            <div class="space-y-2 text-sm text-blue-900">
                <div>
                    <span class="font-medium">Livewire component:</span>
                    <code class="ml-1 bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs select-all">&lt;livewire:imagemaps.image-map-display slug="{{ $slug ?: 'your-slug' }}" /&gt;</code>
                </div>
                <div>
                    <span class="font-medium">Blade include:</span>
                    <code class="ml-1 bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs select-all">@@include('imagemaps::frontend.image-map', ['imageMap' => $imageMap])</code>
                </div>
            </div>
            <p class="text-xs text-blue-600 mt-2"><i class="fa fa-info-circle mr-1"></i> Fully responsive — shapes scale automatically on all screen sizes. No JavaScript required.</p>
        </div>
    </form>
</div>

@if($currentImageUrl)
@push('styles')
<style>
    .canvas-container { margin: 0 auto !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script>
(function() {
    // Editor state
    let canvas, activeTool = 'select', imgW = 0, imgH = 0;
    let isDrawing = false, drawStart = null, currentObj = null;
    let polyPoints = [], polyDots = [];

    function initEditor() {
        canvas = new fabric.Canvas('image-map-canvas', { selection: true, preserveObjectStacking: true });

        // Get width from the wire:ignore container (the canvas parent's parent is too small initially)
        const wireIgnoreDiv = document.querySelector('[wire\\:ignore]');
        const maxW = wireIgnoreDiv ? wireIgnoreDiv.offsetWidth - 2 : 800;

        fabric.Image.fromURL(@js($currentImageUrl), function(img) {
            const scale = maxW / img.width;
            imgW = maxW;
            imgH = img.height * scale;
            canvas.setWidth(imgW);
            canvas.setHeight(imgH);
            canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), { scaleX: scale, scaleY: scale });

            // Load existing shapes
            loadShapes();
        }, { crossOrigin: 'anonymous' });

        canvas.on('mouse:down', onMouseDown);
        canvas.on('mouse:move', onMouseMove);
        canvas.on('mouse:up', onMouseUp);
        canvas.on('mouse:dblclick', onDblClick);
        canvas.on('selection:created', (e) => { highlightSelected(e.selected[0]); showProps(e.selected[0]); });
        canvas.on('selection:updated', (e) => { highlightSelected(e.selected[0]); showProps(e.selected[0]); });
        canvas.on('selection:cleared', () => { clearHighlight(); hideProps(); });

        // Keyboard Delete/Backspace to remove selected shape
        document.addEventListener('keydown', function(e) {
            if (!canvas) return;
            // Don't intercept if user is typing in an input field
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            if (e.key === 'Delete' || e.key === 'Backspace') {
                e.preventDefault();
                editorDeleteSelected();
            }
        });
    }

    function loadShapes() {
        try {
            const data = JSON.parse(@js($shapesJson));
            (data.shapes || []).forEach(s => addShapeFromData(s));
            updateCount();
        } catch(e) {}
    }

    function addShapeFromData(d) {
        let obj;
        const c = d.color || '#1563df', o = d.opacity || 0.3;
        if (d.type === 'rect') {
            obj = new fabric.Rect({ left: d.x/100*imgW, top: d.y/100*imgH, width: d.width/100*imgW, height: d.height/100*imgH, fill: c, opacity: o, stroke: c, strokeWidth: 2 });
        } else if (d.type === 'circle') {
            const r = d.radius/100*imgW;
            obj = new fabric.Circle({ left: d.cx/100*imgW - r, top: d.cy/100*imgH - r, radius: r, fill: c, opacity: o, stroke: c, strokeWidth: 2 });
        } else if (d.type === 'polygon') {
            const pts = d.points.map(p => ({ x: p.x/100*imgW, y: p.y/100*imgH }));
            obj = new fabric.Polygon(pts, { fill: c, opacity: o, stroke: c, strokeWidth: 2 });
        }
        if (obj) {
            const locked = !!d.locked;
            obj.shapeData = { id: d.id, type: d.type, title: d.title||'', description: d.description||'', link: d.link||'', color: c, opacity: o, locked: locked };
            if (locked) {
                obj.set({ lockMovementX: true, lockMovementY: true, lockScalingX: true, lockScalingY: true, lockRotation: true, hasControls: false, hasBorders: false });
            }
            canvas.add(obj);
        }
    }

    // Tools
    window.editorSetTool = function(tool) {
        if (activeTool === 'polygon' && tool !== 'polygon') cancelPoly();
        activeTool = tool;
        canvas.isDrawingMode = false;
        document.querySelectorAll('#editor-toolbar button').forEach(b => {
            b.className = b.className.replace(/bg-blue-600 text-white/g, 'bg-white text-gray-700 hover:bg-gray-100');
        });
        const btn = document.getElementById('tool-' + tool);
        if (btn) btn.className = btn.className.replace(/bg-white text-gray-700 hover:bg-gray-100/g, 'bg-blue-600 text-white');

        if (tool === 'select') {
            canvas.selection = true;
            canvas.forEachObject(o => { o.selectable = true; o.evented = true; });
        } else {
            canvas.selection = false;
            canvas.discardActiveObject();
            canvas.forEachObject(o => { o.selectable = false; o.evented = false; });
        }
        canvas.renderAll();
    };

    window.editorDeleteSelected = function() {
        const a = canvas.getActiveObject();
        if (a && a.shapeData) {
            canvas.remove(a);
            canvas.discardActiveObject();
            hideProps();
            updateCount();
            canvas.renderAll();
        } else {
            // Flash the delete button to indicate nothing is selected
            const btn = document.querySelector('#editor-toolbar button:last-child');
            if (btn) {
                btn.classList.add('ring-2', 'ring-red-400');
                setTimeout(() => btn.classList.remove('ring-2', 'ring-red-400'), 500);
            }
            alert('Select a shape first, then click Delete');
        }
    };

    // Drawing
    function onMouseDown(opt) {
        // Click on empty canvas area in select mode → deselect
        if (activeTool === 'select' && !opt.target) {
            canvas.discardActiveObject();
            clearHighlight();
            hideProps();
            canvas.renderAll();
            return;
        }
        if (activeTool === 'select') return;
        if (activeTool === 'polygon') { addPolyPoint(opt); return; }
        const p = canvas.getPointer(opt.e);
        isDrawing = true; drawStart = p;
        if (activeTool === 'rect') {
            currentObj = new fabric.Rect({ left: p.x, top: p.y, width: 0, height: 0, fill: '#1563df', opacity: 0.3, stroke: '#1563df', strokeWidth: 2, selectable: false, evented: false });
        } else if (activeTool === 'circle') {
            currentObj = new fabric.Circle({ left: p.x, top: p.y, radius: 0, fill: '#e74c3c', opacity: 0.3, stroke: '#e74c3c', strokeWidth: 2, selectable: false, evented: false });
        }
        if (currentObj) canvas.add(currentObj);
    }

    function onMouseMove(opt) {
        if (!isDrawing || !currentObj) return;
        const p = canvas.getPointer(opt.e);
        if (activeTool === 'rect') {
            currentObj.set({ left: Math.min(drawStart.x, p.x), top: Math.min(drawStart.y, p.y), width: Math.abs(p.x - drawStart.x), height: Math.abs(p.y - drawStart.y) });
        } else if (activeTool === 'circle') {
            const r = Math.sqrt(Math.pow(p.x-drawStart.x,2)+Math.pow(p.y-drawStart.y,2))/2;
            currentObj.set({ left: (drawStart.x+p.x)/2-r, top: (drawStart.y+p.y)/2-r, radius: r });
        }
        canvas.renderAll();
    }

    function onMouseUp() {
        if (!isDrawing) return;
        isDrawing = false;
        if (currentObj) {
            if ((currentObj.width||currentObj.radius*2||0) < 5) { canvas.remove(currentObj); currentObj = null; return; }
            currentObj.set({ selectable: true, evented: true });
            currentObj.shapeData = { id: 'shape_'+Date.now(), type: activeTool, title: '', description: '', link: '', color: currentObj.fill, opacity: currentObj.opacity };
            currentObj = null;
            updateCount();
            editorSetTool('select');
        }
    }

    // Polygon
    function addPolyPoint(opt) {
        const p = canvas.getPointer(opt.e);
        polyPoints.push(p);
        const dot = new fabric.Circle({ left: p.x-4, top: p.y-4, radius: 4, fill: '#2ecc71', stroke: '#fff', strokeWidth: 2, selectable: false, evented: false, _poly: true });
        canvas.add(dot); polyDots.push(dot);
        if (polyPoints.length > 1) {
            const prev = polyPoints[polyPoints.length-2];
            const line = new fabric.Line([prev.x, prev.y, p.x, p.y], { stroke: '#2ecc71', strokeWidth: 2, strokeDashArray: [5,5], selectable: false, evented: false, _poly: true });
            canvas.add(line); polyDots.push(line);
        }
        canvas.renderAll();
    }

    function onDblClick() {
        if (activeTool === 'polygon' && polyPoints.length >= 3) closePoly();
    }

    function closePoly() {
        polyDots.forEach(d => canvas.remove(d)); polyDots = [];
        const poly = new fabric.Polygon(polyPoints.map(p => ({x:p.x,y:p.y})), { fill: '#2ecc71', opacity: 0.3, stroke: '#2ecc71', strokeWidth: 2 });
        poly.shapeData = { id: 'shape_'+Date.now(), type: 'polygon', title: '', description: '', link: '', color: '#2ecc71', opacity: 0.3 };
        canvas.add(poly); polyPoints = [];
        updateCount(); editorSetTool('select'); canvas.setActiveObject(poly);
    }

    function cancelPoly() { polyDots.forEach(d => canvas.remove(d)); polyDots = []; polyPoints = []; canvas.renderAll(); }

    // Visual highlight for selected shape
    let _prevStrokeWidth = null;
    function highlightSelected(obj) {
        clearHighlight();
        if (!obj || !obj.shapeData) return;
        _prevStrokeWidth = obj.strokeWidth || 2;
        obj.set({ strokeWidth: 4, strokeDashArray: [8, 4], stroke: obj.shapeData.color || '#1563df' });
        canvas.renderAll();
    }
    function clearHighlight() {
        canvas.getObjects().forEach(o => {
            if (o.shapeData) {
                o.set({ strokeWidth: 2, strokeDashArray: null, stroke: o.shapeData.color || '#1563df' });
            }
        });
        _prevStrokeWidth = null;
        canvas.renderAll();
    }

    // Properties panel
    function showProps(obj) {
        if (!obj || !obj.shapeData) return;
        const d = obj.shapeData;
        document.getElementById('shape-title').value = d.title || '';
        document.getElementById('shape-desc').value = d.description || '';
        document.getElementById('shape-link').value = d.link || '';
        document.getElementById('shape-color').value = d.color || '#1563df';
        document.getElementById('shape-opacity').value = d.opacity || 0.3;
        document.getElementById('shape-props').style.display = '';
        updateLockUI(!!d.locked);
    }

    function hideProps() {
        document.getElementById('shape-props').style.display = 'none';
        updateLockUI(false);
    }

    // Lock/Unlock shape movement
    window.editorToggleLock = function() {
        const a = canvas.getActiveObject();
        if (!a || !a.shapeData) return;
        const isLocked = a.lockMovementX;
        const newLock = !isLocked;
        a.set({
            lockMovementX: newLock,
            lockMovementY: newLock,
            lockScalingX: newLock,
            lockScalingY: newLock,
            lockRotation: newLock,
            hasControls: !newLock,
            hasBorders: !newLock,
        });
        a.shapeData.locked = newLock;
        updateLockUI(newLock);
        canvas.renderAll();
    };

    function updateLockUI(locked) {
        const icon = document.getElementById('lock-icon');
        const label = document.getElementById('lock-label');
        const btn = document.getElementById('lock-btn');
        if (!icon || !label || !btn) return;
        if (locked) {
            icon.className = 'fa fa-lock mr-1';
            label.textContent = 'Locked';
            btn.className = 'px-3 py-1.5 rounded-lg text-sm font-medium border border-amber-400 bg-amber-50 text-amber-700 hover:bg-amber-100 transition';
        } else {
            icon.className = 'fa fa-unlock mr-1';
            label.textContent = 'Unlocked';
            btn.className = 'px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition';
        }
    }

    window.editorUpdateProp = function(prop, val) {
        const a = canvas.getActiveObject();
        if (a && a.shapeData) a.shapeData[prop] = val;
    };

    window.editorUpdateColor = function(c) {
        const a = canvas.getActiveObject();
        if (a && a.shapeData) { a.set({ fill: c, stroke: c }); a.shapeData.color = c; canvas.renderAll(); }
    };

    window.editorUpdateOpacity = function(o) {
        const a = canvas.getActiveObject();
        if (a && a.shapeData) { a.set({ opacity: parseFloat(o) }); a.shapeData.opacity = parseFloat(o); canvas.renderAll(); }
    };

    function updateCount() {
        const count = canvas.getObjects().filter(o => o.shapeData).length;
        document.getElementById('shape-count').textContent = count + ' shape(s)';
    }

    function buildShapesJson() {
        if (!canvas) return '{"shapes":[]}';
        canvas.discardActiveObject(); canvas.renderAll();

        const shapes = [];
        canvas.getObjects().forEach(obj => {
            if (!obj.shapeData) return;
            const d = { ...obj.shapeData };
            if (d.type === 'rect') {
                const b = obj.getBoundingRect(true);
                d.x = b.left/imgW*100; d.y = b.top/imgH*100; d.width = b.width/imgW*100; d.height = b.height/imgH*100;
            } else if (d.type === 'circle') {
                const b = obj.getBoundingRect(true);
                d.cx = (b.left+b.width/2)/imgW*100; d.cy = (b.top+b.height/2)/imgH*100; d.radius = b.width/2/imgW*100;
            } else if (d.type === 'polygon') {
                const m = obj.calcTransformMatrix();
                d.points = obj.points.map(p => {
                    const t = fabric.util.transformPoint(new fabric.Point(p.x-obj.pathOffset.x, p.y-obj.pathOffset.y), m);
                    return { x: t.x/imgW*100, y: t.y/imgH*100 };
                });
            }
            shapes.push(d);
        });

        return JSON.stringify({ shapes, settings: { defaultColor: '#1563df', defaultOpacity: 0.3, showTooltips: true } });
    }

    // Save via POST (bypasses Livewire re-render issues)
    window.editorSaveMap = function() {
        const json = buildShapesJson();

        fetch('/admin/image-maps/save-shapes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                id: {{ $imageMapId ?? 'null' }},
                title: document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="title"]').value,
                slug: document.querySelector('input[wire\\:model="slug"]').value,
                active: document.querySelector('#active').checked ? 1 : 0,
                shapes_json: json,
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Show success inline
                const msg = document.createElement('div');
                msg.className = 'mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg';
                msg.innerHTML = '<i class="fa fa-check-circle mr-2"></i>Image Map saved successfully!';
                document.querySelector('.mb-6')?.after(msg);
                setTimeout(() => msg.remove(), 3000);

                // Redirect to edit if new
                if (data.id && !window.location.pathname.includes('/edit')) {
                    window.location.href = '/admin/image-maps/' + data.id + '/edit';
                }
            } else {
                alert('Error: ' + (data.errors ? Object.values(data.errors).flat().join('\n') : 'Save failed'));
            }
        })
        .catch(err => alert('Save error: ' + err.message));
    };

    // Init when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditor);
    } else {
        initEditor();
    }
})();
</script>
@endpush
@endif
