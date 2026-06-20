/**
 * new-builder icon picker: browse Tabler Icons (rendered via the webfont in the
 * admin) and insert the chosen icon as INLINE SVG into the document (fetched
 * from the Tabler CDN), so the output has no font dependency.
 *
 * NB.createIconPicker(rootEl) wires the toolbar "Icon" button + modal.
 */
(function () {
    'use strict';

    var NB = window.NB;

    // A curated set of common Tabler icon slugs (search filters this list).
    var ICONS = [
        'home', 'home-2', 'user', 'users', 'settings', 'search', 'heart', 'heart-filled', 'star', 'star-filled',
        'check', 'circle-check', 'x', 'circle-x', 'plus', 'plus-circle', 'minus', 'edit', 'pencil', 'trash',
        'mail', 'phone', 'map-pin', 'map', 'calendar', 'clock', 'history', 'camera', 'photo', 'video',
        'music', 'file', 'files', 'folder', 'download', 'upload', 'link', 'external-link', 'share', 'bell',
        'bookmark', 'tag', 'shopping-cart', 'shopping-bag', 'credit-card', 'cash', 'wallet', 'gift', 'truck', 'building',
        'building-store', 'building-bank', 'briefcase', 'world', 'compass', 'flag', 'eye', 'eye-off', 'lock', 'lock-open',
        'key', 'shield', 'shield-check', 'alert-circle', 'alert-triangle', 'info-circle', 'help', 'message', 'message-circle', 'send',
        'thumb-up', 'thumb-down', 'chevron-right', 'chevron-left', 'chevron-up', 'chevron-down', 'arrow-right', 'arrow-left', 'arrow-up', 'arrow-down',
        'arrow-narrow-right', 'menu-2', 'dots', 'dots-vertical', 'filter', 'adjustments', 'refresh', 'rotate', 'login', 'logout',
        'sun', 'moon', 'cloud', 'cloud-rain', 'droplet', 'flame', 'bolt', 'wifi', 'battery', 'plug',
        'device-mobile', 'device-laptop', 'device-desktop', 'printer', 'headphones', 'microphone', 'volume', 'code', 'terminal', 'database',
        'server', 'cpu', 'chart-bar', 'chart-line', 'chart-pie', 'trending-up', 'trending-down', 'activity', 'target', 'award',
        'trophy', 'medal', 'certificate', 'school', 'book', 'notebook', 'paint', 'palette', 'brush', 'scissors',
        'ruler', 'calculator', 'clipboard', 'list', 'checklist', 'layout', 'columns', 'table', 'box', 'package',
        'archive', 'inbox', 'paperclip', 'pin', 'rocket', 'plane', 'car', 'bike', 'walk', 'run',
        'coffee', 'pizza', 'cake', 'leaf', 'tree', 'plant', 'paw', 'temperature', 'umbrella', 'snowflake',
        'wind', 'fingerprint', 'scan', 'qrcode', 'barcode', 'ticket', 'discount', 'percentage', 'receipt', 'door',
        'bed', 'sofa', 'lamp', 'hourglass', 'repeat', 'player-play', 'player-pause', 'player-stop', 'quote', 'circle',
        'brand-facebook', 'brand-instagram', 'brand-x', 'brand-youtube', 'brand-linkedin', 'brand-github', 'brand-whatsapp', 'brand-telegram', 'brand-tiktok', 'brand-google',
    ];

    function escA(v) {
        return String(v).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    NB.createIconPicker = function createIconPicker(rootEl) {
        var overlay = document.createElement('div');
        overlay.className = 'nb-media-overlay hidden';
        overlay.innerHTML =
            '<div class="nb-media-modal">' +
            '<div class="nb-media-head">' +
            '<strong>Tabler Icons</strong>' +
            '<input type="search" class="nb-media-search" placeholder="Search icons…">' +
            '<button type="button" class="nb-media-close" title="Close">&times;</button>' +
            '</div>' +
            '<div class="nb-icon-grid" data-icon-grid></div>' +
            '<div class="nb-media-status" data-icon-status></div>' +
            '</div>';
        rootEl.appendChild(overlay);

        var grid = overlay.querySelector('[data-icon-grid]');
        var status = overlay.querySelector('[data-icon-status]');
        var search = overlay.querySelector('.nb-media-search');

        function renderGrid(q) {
            q = (q || '').toLowerCase();
            var list = q ? ICONS.filter(function (n) { return n.indexOf(q) !== -1; }) : ICONS;
            grid.innerHTML = list.map(function (n) {
                return '<button type="button" class="nb-icon-item" data-icon="' + escA(n) + '" title="' + escA(n) + '">' +
                    '<i class="ti ti-' + escA(n) + '"></i></button>';
            }).join('');
            status.textContent = list.length ? '' : 'No icons match. Tip: search uses Tabler slugs (e.g. “arrow-right”).';
        }

        function open() {
            overlay.classList.remove('hidden');
            search.value = '';
            renderGrid('');
            search.focus();
        }
        function close() { overlay.classList.add('hidden'); }

        function insertIcon(name) {
            status.textContent = 'Loading “' + name + '”…';
            var urls = [
                'https://cdn.jsdelivr.net/npm/@tabler/icons@3/icons/outline/' + name + '.svg',
                'https://cdn.jsdelivr.net/npm/@tabler/icons@3/icons/' + name + '.svg',
            ];
            (function tryUrl(i) {
                if (i >= urls.length) { status.textContent = 'Could not load that icon.'; return; }
                fetch(urls[i]).then(function (r) {
                    if (!r.ok) { throw new Error('404'); }
                    return r.text();
                }).then(function (svg) {
                    var Core = window.NewBuilderCore;
                    var roots = Core.htmlToJsonRoots(svg);
                    if (roots && roots[0] && rootEl.__nb && typeof rootEl.__nb.insertBlock === 'function') {
                        rootEl.__nb.insertBlock(roots[0]);
                        close();
                    } else {
                        status.textContent = 'Could not parse that icon.';
                    }
                }).catch(function () { tryUrl(i + 1); });
            }(0));
        }

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay || e.target.closest('.nb-media-close')) { close(); return; }
            var it = e.target.closest('[data-icon]');
            if (it) { insertIcon(it.getAttribute('data-icon')); }
        });

        search.addEventListener('input', function () { renderGrid(search.value.trim()); });

        rootEl.addEventListener('click', function (e) {
            if (e.target.closest('[data-icons-open]')) {
                e.preventDefault();
                open();
            }
        });
    };
}());
