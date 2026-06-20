/**
 * new-builder element picker: opened from the tree's ＋child / ＋sib buttons.
 * Lets you choose WHAT element to add (heading, link, image, list, columns, …)
 * instead of always inserting a blank div. Inserts via rootEl.__nb.addAt.
 *
 * NB.createElementPicker(rootEl) sets rootEl.__nbElements = { open(mode, id) }.
 */
(function () {
    'use strict';

    var NB = window.NB;

    var GROUPS = [
        {
            label: 'Text', items: [
                { label: 'Heading 1', make: function () { return { type: 'h1', classes: 'text-4xl font-bold text-gray-900', content: 'Heading 1' }; } },
                { label: 'Heading 2', make: function () { return { type: 'h2', classes: 'text-3xl font-bold text-gray-900', content: 'Heading 2' }; } },
                { label: 'Heading 3', make: function () { return { type: 'h3', classes: 'text-xl font-semibold text-gray-800', content: 'Heading 3' }; } },
                { label: 'Paragraph', make: function () { return { type: 'p', classes: 'text-gray-600 leading-relaxed', content: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.' }; } },
                { label: 'Span', make: function () { return { type: 'span', content: 'text' }; } },
                { label: 'Quote', make: function () { return { type: 'blockquote', classes: 'border-l-4 border-gray-300 pl-4 italic text-gray-600', content: 'A quote goes here.' }; } },
                { label: 'List', make: function () { return { type: 'ul', classes: 'list-disc pl-6 text-gray-600 space-y-1', children: [{ type: 'li', content: 'Item one' }, { type: 'li', content: 'Item two' }] }; } },
                { label: 'Divider', make: function () { return { type: 'hr', classes: 'my-6 border-gray-200' }; } },
            ],
        },
        {
            label: 'Media', items: [
                { label: 'Image', make: function () { return { type: 'img', classes: 'w-full rounded-lg', attributes: { src: 'https://placehold.co/600x400', alt: '' } }; } },
                { label: 'Video / embed', make: function () { return { type: 'iframe', classes: 'w-full aspect-video rounded-lg', attributes: { src: '', frameborder: '0', allowfullscreen: '' } }; } },
            ],
        },
        {
            label: 'Interactive', items: [
                { label: 'Link', make: function () { return { type: 'a', classes: 'text-blue-600 underline', attributes: { href: '#' }, content: 'Link' }; } },
                { label: 'Button', make: function () { return { type: 'a', classes: 'inline-block px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700', attributes: { href: '#' }, content: 'Button' }; } },
            ],
        },
        {
            label: 'Layout', items: [
                { label: 'Container', make: function () { return { type: 'div', classes: 'max-w-7xl mx-auto px-4', children: [] }; } },
                { label: 'Section', make: function () { return { type: 'section', classes: 'py-16 px-4', children: [] }; } },
                { label: 'Flex row', make: function () { return { type: 'div', classes: 'flex items-center gap-4', children: [] }; } },
                { label: '2 columns', make: function () { return { type: 'div', classes: 'grid grid-cols-1 md:grid-cols-2 gap-6', children: [{ type: 'div', classes: 'p-4', content: 'Column one' }, { type: 'div', classes: 'p-4', content: 'Column two' }] }; } },
                { label: '3 columns', make: function () { return { type: 'div', classes: 'grid grid-cols-1 md:grid-cols-3 gap-6', children: [{ type: 'div', classes: 'p-4', content: 'One' }, { type: 'div', classes: 'p-4', content: 'Two' }, { type: 'div', classes: 'p-4', content: 'Three' }] }; } },
                { label: 'Blank div', make: function () { return { type: 'div', children: [] }; } },
            ],
        },
    ];

    function escHtml(v) {
        return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    NB.createElementPicker = function createElementPicker(rootEl) {
        var items = [];
        var listHtml = '';
        for (var g = 0; g < GROUPS.length; g++) {
            listHtml += '<div class="nb-pal-group"><div class="nb-pal-group-label">' + escHtml(GROUPS[g].label) + '</div><div class="nb-pal-buttons">';
            for (var i = 0; i < GROUPS[g].items.length; i++) {
                var idx = items.push(GROUPS[g].items[i]) - 1;
                listHtml += '<button type="button" class="nb-pal-btn" data-el-add="' + idx + '">' + escHtml(GROUPS[g].items[i].label) + '</button>';
            }
            listHtml += '</div></div>';
        }

        var overlay = document.createElement('div');
        overlay.className = 'nb-media-overlay hidden';
        overlay.innerHTML =
            '<div class="nb-el-modal">' +
            '<div class="nb-media-head">' +
            '<strong>Add element</strong>' +
            '<span class="nb-el-mode" data-el-mode></span>' +
            '<button type="button" class="nb-media-close" data-el-cancel title="Close">&times;</button>' +
            '</div>' +
            '<div class="nb-el-list">' + listHtml + '</div>' +
            '</div>';
        rootEl.appendChild(overlay);

        var modeEl = overlay.querySelector('[data-el-mode]');
        var ctx = { mode: 'child', id: null };

        function open(mode, id) {
            ctx.mode = mode;
            ctx.id = id;
            modeEl.textContent = mode === 'child' ? 'as a child' : 'as next sibling';
            overlay.classList.remove('hidden');
        }
        function close() { overlay.classList.add('hidden'); }

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay || e.target.closest('[data-el-cancel]')) { close(); return; }
            var btn = e.target.closest('[data-el-add]');
            if (btn) {
                var item = items[parseInt(btn.getAttribute('data-el-add'), 10)];
                if (item && rootEl.__nb && typeof rootEl.__nb.addAt === 'function') {
                    rootEl.__nb.addAt(ctx.mode, ctx.id, item.make());
                }
                close();
            }
        });

        rootEl.__nbElements = { open: open };
    };
}());
