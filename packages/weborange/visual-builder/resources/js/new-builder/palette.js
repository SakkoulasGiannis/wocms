/**
 * new-builder palette: a library of ready-made blocks (contract JSON) you can
 * insert into the document. Clicking a block calls the builder's insertBlock
 * API (exposed on rootEl.__nb) which appends it to the selected node (or as a
 * new root) and selects it.
 *
 * Each block factory returns FRESH JSON every call (no shared references).
 *
 * NB.createPalette(rootEl) -> wires the palette panel.
 */
(function () {
    'use strict';

    var NB = window.NB;

    var GROUPS = [
        {
            label: 'Layout',
            items: [
                { label: 'Section', make: function () { return { type: 'section', classes: 'py-16 px-4', children: [] }; } },
                { label: 'Container', make: function () { return { type: 'div', classes: 'max-w-7xl mx-auto px-4', children: [] }; } },
                { label: 'Flex row', make: function () { return { type: 'div', classes: 'flex items-center gap-4', children: [] }; } },
                {
                    label: '2 columns', make: function () {
                        return {
                            type: 'div', classes: 'grid grid-cols-1 md:grid-cols-2 gap-6', children: [
                                { type: 'div', classes: 'p-4', content: 'Column one' },
                                { type: 'div', classes: 'p-4', content: 'Column two' },
                            ],
                        };
                    },
                },
                {
                    label: '3 columns', make: function () {
                        return {
                            type: 'div', classes: 'grid grid-cols-1 md:grid-cols-3 gap-6', children: [
                                { type: 'div', classes: 'p-4', content: 'Column one' },
                                { type: 'div', classes: 'p-4', content: 'Column two' },
                                { type: 'div', classes: 'p-4', content: 'Column three' },
                            ],
                        };
                    },
                },
            ],
        },
        {
            label: 'Content',
            items: [
                { label: 'Heading', make: function () { return { type: 'h2', classes: 'text-3xl font-bold text-gray-900', content: 'Heading' }; } },
                { label: 'Subheading', make: function () { return { type: 'h3', classes: 'text-xl font-semibold text-gray-800', content: 'Subheading' }; } },
                { label: 'Paragraph', make: function () { return { type: 'p', classes: 'text-gray-600 leading-relaxed', content: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt.' }; } },
                { label: 'Button', make: function () { return { type: 'a', classes: 'inline-block px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700', content: 'Click me', attributes: { href: '#' } }; } },
                { label: 'Image', make: function () { return { type: 'img', classes: 'w-full rounded-lg', attributes: { src: 'https://placehold.co/800x450', alt: '' } }; } },
                {
                    label: 'List', make: function () {
                        return {
                            type: 'ul', classes: 'list-disc pl-6 text-gray-600 space-y-1', children: [
                                { type: 'li', content: 'Item one' },
                                { type: 'li', content: 'Item two' },
                                { type: 'li', content: 'Item three' },
                            ],
                        };
                    },
                },
            ],
        },
        {
            label: 'Dynamic',
            items: [
                {
                    label: 'Repeater (loop)', make: function () {
                        return {
                            type: 'div',
                            classes: 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6',
                            attributes: { 'data-vb-loop': '{"source":"","limit":6,"order_by":"created_at","order_dir":"desc"}' },
                            children: [
                                {
                                    type: 'a', classes: 'block rounded-xl border border-gray-200 overflow-hidden bg-white hover:shadow-md transition',
                                    attributes: { href: '/blog/{slug}' }, children: [
                                        { type: 'img', classes: 'w-full h-44 object-cover', attributes: { src: '{featured_image|https://placehold.co/600x400}', alt: '{title}' } },
                                        {
                                            type: 'div', classes: 'p-5', children: [
                                                { type: 'h3', classes: 'text-lg font-bold text-gray-900', content: '{title}' },
                                                { type: 'p', classes: 'mt-2 text-sm text-gray-500', content: '{excerpt|Read more}' },
                                            ],
                                        },
                                    ],
                                },
                            ],
                        };
                    },
                },
            ],
        },
        {
            label: 'Components',
            items: [
                {
                    label: 'Hero', make: function () {
                        return {
                            type: 'section', classes: 'py-24 px-4 text-center bg-gray-50', children: [
                                {
                                    type: 'div', classes: 'max-w-3xl mx-auto', children: [
                                        { type: 'h1', classes: 'text-4xl md:text-5xl font-bold text-gray-900', content: 'Your headline here' },
                                        { type: 'p', classes: 'mt-4 text-lg text-gray-600', content: 'A short supporting sentence that explains the value proposition.' },
                                        { type: 'a', classes: 'inline-block mt-8 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700', content: 'Get started', attributes: { href: '#' } },
                                    ],
                                },
                            ],
                        };
                    },
                },
                {
                    label: 'Card', make: function () {
                        return {
                            type: 'div', classes: 'rounded-xl border border-gray-200 shadow-sm overflow-hidden bg-white max-w-sm', children: [
                                { type: 'img', classes: 'w-full h-48 object-cover', attributes: { src: 'https://placehold.co/600x400', alt: '' } },
                                {
                                    type: 'div', classes: 'p-5', children: [
                                        { type: 'h3', classes: 'text-lg font-semibold text-gray-900', content: 'Card title' },
                                        { type: 'p', classes: 'mt-2 text-sm text-gray-600', content: 'Card description goes here. Keep it short and useful.' },
                                        { type: 'a', classes: 'inline-block mt-4 text-blue-600 font-medium', content: 'Learn more →', attributes: { href: '#' } },
                                    ],
                                },
                            ],
                        };
                    },
                },
                {
                    label: 'CTA band', make: function () {
                        return {
                            type: 'section', classes: 'py-16 px-4 bg-blue-600 text-center', children: [
                                {
                                    type: 'div', classes: 'max-w-2xl mx-auto', children: [
                                        { type: 'h2', classes: 'text-3xl font-bold text-white', content: 'Ready to get started?' },
                                        { type: 'p', classes: 'mt-3 text-blue-100', content: 'Join us today and see the difference.' },
                                        { type: 'a', classes: 'inline-block mt-6 px-6 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50', content: 'Contact us', attributes: { href: '#' } },
                                    ],
                                },
                            ],
                        };
                    },
                },
                {
                    label: 'Navbar', make: function () {
                        return {
                            type: 'nav', classes: 'flex items-center justify-between px-6 py-4 bg-white shadow', children: [
                                { type: 'span', classes: 'font-bold text-xl text-gray-900', content: 'Logo' },
                                {
                                    type: 'div', classes: 'flex items-center gap-6 text-sm text-gray-700', children: [
                                        { type: 'a', classes: 'hover:text-blue-600', content: 'Home', attributes: { href: '#' } },
                                        { type: 'a', classes: 'hover:text-blue-600', content: 'About', attributes: { href: '#' } },
                                        { type: 'a', classes: 'hover:text-blue-600', content: 'Contact', attributes: { href: '#' } },
                                    ],
                                },
                            ],
                        };
                    },
                },
            ],
        },
    ];

    /** Build a "Forms" group from the host-provided list (one block per form). */
    function formsGroup(rootEl) {
        var el = rootEl.querySelector('[data-vb-forms]');
        if (!el) { return null; }
        var forms;
        try { forms = JSON.parse(el.textContent || '[]'); } catch (e) { forms = []; }
        if (!forms || !forms.length) { return null; }
        return {
            label: 'Forms',
            items: forms.map(function (f) {
                return {
                    label: '📋 ' + f.name,
                    make: function () {
                        return {
                            type: 'div', classes: 'vb-form my-6', attributes: { 'data-vb-form': f.slug },
                            children: [
                                { type: 'p', classes: 'text-sm text-gray-500 italic border border-dashed border-gray-300 rounded p-4 text-center', content: '📋 Form: ' + f.name + ' (renders live on the page)' },
                            ],
                        };
                    },
                };
            }),
        };
    }

    /** Build a "Sliders" group from the host-provided list (one block per slider). */
    function slidersGroup(rootEl) {
        var el = rootEl.querySelector('[data-vb-sliders]');
        if (!el) { return null; }
        var sliders;
        try { sliders = JSON.parse(el.textContent || '[]'); } catch (e) { sliders = []; }
        if (!sliders || !sliders.length) { return null; }
        return {
            label: 'Sliders',
            items: sliders.map(function (s) {
                return {
                    label: '🖼️ ' + s.name,
                    make: function () {
                        return {
                            type: 'div', classes: 'vb-slider my-6', attributes: { 'data-vb-slider': String(s.id) },
                            children: [
                                { type: 'p', classes: 'text-sm text-gray-500 italic border border-dashed border-gray-300 rounded p-4 text-center', content: '🖼️ Slider: ' + s.name + ' (renders live on the page)' },
                            ],
                        };
                    },
                };
            }),
        };
    }

    NB.createPalette = function createPalette(rootEl) {
        var panel = rootEl.querySelector('[data-palette-panel]');
        var list = rootEl.querySelector('[data-palette-list]');
        if (!panel || !list) { return; }

        var groups = GROUPS.slice();
        var fg = formsGroup(rootEl);
        if (fg) { groups.push(fg); }
        var sg = slidersGroup(rootEl);
        if (sg) { groups.push(sg); }

        // Flatten items so a button can reference its factory by index.
        var items = [];
        var html = '';
        for (var g = 0; g < groups.length; g++) {
            var group = groups[g];
            html += '<div class="nb-pal-group"><div class="nb-pal-group-label">' +
                NB.escapeHtml(group.label) + '</div><div class="nb-pal-buttons">';
            for (var i = 0; i < group.items.length; i++) {
                var idx = items.push(group.items[i]) - 1;
                html += '<button type="button" class="nb-pal-btn" data-palette-add="' + idx + '">' +
                    NB.escapeHtml(group.items[i].label) + '</button>';
            }
            html += '</div></div>';
        }
        list.innerHTML = html;

        function open() { panel.classList.remove('hidden'); }
        function close() { panel.classList.add('hidden'); }

        rootEl.addEventListener('click', function (e) {
            if (e.target.closest('[data-palette-open]')) {
                e.preventDefault();
                open();
                return;
            }
            if (e.target.closest('[data-palette-cancel]')) {
                e.preventDefault();
                close();
                return;
            }
            var add = e.target.closest('[data-palette-add]');
            if (add) {
                e.preventDefault();
                var item = items[parseInt(add.getAttribute('data-palette-add'), 10)];
                if (item && rootEl.__nb && typeof rootEl.__nb.insertBlock === 'function') {
                    rootEl.__nb.insertBlock(item.make());
                }
            }
        });
    };
}());
