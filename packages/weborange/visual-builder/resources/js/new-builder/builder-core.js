/**
 * new-builder core: pure, dependency-free HTML <-> JSON conversion.
 *
 * JSON node shape:
 *   {
 *     "type":       <tagName lowercase>      (required)
 *     "classes":    <string>                 (the `class` attribute value; omitted if empty)
 *     "attributes": { ... }                  (all other attrs except `class`; omitted if none)
 *     "content":    <string>                 (direct text of the element, trimmed; omitted if empty)
 *     "children":   [ ...nodes ]             (always present, may be [])
 *   }
 *
 * Roots: a document fragment may contain multiple top-level elements. We therefore
 * always work with an ARRAY of root nodes internally. If there is exactly one root,
 * jsonToHtml accepts either that single object OR a one-element array, and the helpers
 * expose both single-root and multi-root behaviour (see parseRoots / serializeRoots).
 *
 * Normalizations (documented):
 *   - `type` is lowercased.
 *   - `content` is the concatenation of the element's DIRECT text nodes, trimmed.
 *     Whitespace-only text is ignored. Text interleaved between children is collapsed
 *     into this single `content` string (we do not preserve text position between
 *     children — round-trip places `content` before the children on re-serialization).
 *   - `attributes` keys are sorted alphabetically on serialization to JSON so that
 *     attribute order is stable across round-trips (HTML attribute order is not
 *     otherwise guaranteed to be meaningful).
 *   - Known void/self-closing elements are emitted without a closing tag.
 */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    } else {
        root.NewBuilderCore = api;
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    const VOID_ELEMENTS = new Set([
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'link', 'meta', 'param', 'source', 'track', 'wbr',
    ]);

    /**
     * Get a DOMParser-like environment. In the browser we use the real one.
     * In Node (for tests) the caller may inject a parser via setDomParser().
     */
    let injectedParseFragment = null;

    function setFragmentParser(fn) {
        injectedParseFragment = fn;
    }

    function getDocument() {
        if (typeof document !== 'undefined' && document.implementation) {
            return document;
        }
        return null;
    }

    /**
     * Parse an HTML string into an array of top-level Element nodes.
     * @returns {Element[]}
     */
    function parseHtmlToElements(htmlString) {
        const doc = getDocument();
        if (doc) {
            const container = doc.createElement('div');
            container.innerHTML = htmlString;
            return Array.from(container.children);
        }
        if (injectedParseFragment) {
            return injectedParseFragment(htmlString);
        }
        throw new Error('No DOM available: call NewBuilderCore.setFragmentParser() in non-browser environments.');
    }

    /**
     * Convert a single Element into a JSON node.
     */
    function elementToNode(el) {
        const node = { type: (el.tagName || '').toLowerCase() };

        const classValue = el.getAttribute ? el.getAttribute('class') : null;
        if (classValue && classValue.trim() !== '') {
            node.classes = classValue.trim();
        }

        const attributes = {};
        const attrList = el.attributes ? Array.from(el.attributes) : [];
        for (const attr of attrList) {
            if (attr.name === 'class') {
                continue;
            }
            attributes[attr.name] = attr.value;
        }
        if (Object.keys(attributes).length > 0) {
            node.attributes = attributes;
        }

        let directText = '';
        const children = [];
        const childNodes = el.childNodes ? Array.from(el.childNodes) : [];
        for (const child of childNodes) {
            if (child.nodeType === 3) { // text node
                directText += child.nodeValue || '';
            } else if (child.nodeType === 1) { // element
                children.push(elementToNode(child));
            }
        }

        const trimmed = directText.trim();
        if (trimmed !== '') {
            node.content = trimmed;
        }

        node.children = children;
        return node;
    }

    /**
     * htmlToJson: returns a single node if the HTML has exactly one root element,
     * otherwise an array of nodes.
     */
    function htmlToJson(htmlString) {
        const elements = parseHtmlToElements(htmlString);
        const nodes = elements.map(elementToNode);
        if (nodes.length === 1) {
            return nodes[0];
        }
        return nodes;
    }

    /**
     * Always return an array of root nodes (stable for the tree editor).
     */
    function htmlToJsonRoots(htmlString) {
        const elements = parseHtmlToElements(htmlString);
        return elements.map(elementToNode);
    }

    function escapeAttr(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escapeText(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function buildAttrString(node) {
        const parts = [];
        if (node.classes && String(node.classes).trim() !== '') {
            parts.push(`class="${escapeAttr(node.classes)}"`);
        }
        if (node.attributes && typeof node.attributes === 'object') {
            const keys = Object.keys(node.attributes).sort();
            for (const key of keys) {
                const val = node.attributes[key];
                if (val === '' || val === null || val === undefined) {
                    parts.push(escapeAttr(key));
                } else {
                    parts.push(`${escapeAttr(key)}="${escapeAttr(val)}"`);
                }
            }
        }
        return parts.length ? ' ' + parts.join(' ') : '';
    }

    /**
     * Serialize one node to pretty-printed HTML at a given indent depth.
     */
    function nodeToHtml(node, depth) {
        const indent = '    '.repeat(depth);
        const type = (node.type || 'div').toLowerCase();
        const attrs = buildAttrString(node);
        const children = Array.isArray(node.children) ? node.children : [];
        const content = (node.content !== undefined && node.content !== null)
            ? String(node.content).trim()
            : '';

        if (VOID_ELEMENTS.has(type)) {
            return `${indent}<${type}${attrs}>`;
        }

        const hasContent = content !== '';
        const hasChildren = children.length > 0;

        if (!hasContent && !hasChildren) {
            return `${indent}<${type}${attrs}></${type}>`;
        }

        // Single inline text, no children -> keep on one line.
        if (hasContent && !hasChildren) {
            return `${indent}<${type}${attrs}>${escapeText(content)}</${type}>`;
        }

        const lines = [`${indent}<${type}${attrs}>`];
        if (hasContent) {
            lines.push(`${'    '.repeat(depth + 1)}${escapeText(content)}`);
        }
        for (const child of children) {
            lines.push(nodeToHtml(child, depth + 1));
        }
        lines.push(`${indent}</${type}>`);
        return lines.join('\n');
    }

    /**
     * jsonToHtml: accepts a single node OR an array of nodes; returns pretty HTML.
     */
    function jsonToHtml(jsonTree) {
        const nodes = Array.isArray(jsonTree) ? jsonTree : [jsonTree];
        return nodes
            .filter((n) => n && n.type)
            .map((n) => nodeToHtml(n, 0))
            .join('\n');
    }

    return {
        htmlToJson,
        htmlToJsonRoots,
        jsonToHtml,
        elementToNode,
        nodeToHtml,
        setFragmentParser,
        VOID_ELEMENTS,
    };
}));
