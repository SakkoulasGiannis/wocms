/**
 * Node round-trip test for new-builder core.
 *
 * The browser uses the real DOM. In Node there is no DOM, so we inject a tiny
 * HTML fragment parser that produces DOM-like element objects (with tagName,
 * attributes, childNodes, getAttribute) good enough for elementToNode().
 *
 * Run:  node public/js/new-builder/roundtrip-test.cjs
 */
'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

// The project's package.json sets "type":"module", so Node would treat the
// .js core file as ESM. We instead load it as a classic script (the same way
// the browser <script> tag does) by evaluating it in a CommonJS sandbox.
function loadCore() {
    const src = fs.readFileSync(path.join(__dirname, 'builder-core.js'), 'utf8');
    const sandbox = { module: { exports: {} }, self: {} };
    vm.runInNewContext(src, sandbox);
    return sandbox.module.exports;
}
const core = loadCore();

/* ---------------------------------------------------------------------------
 * Minimal HTML fragment parser (test-only).
 * Handles tags, attributes (single/double/unquoted/boolean), text nodes,
 * void elements, and arbitrary nesting. Not a spec-complete parser, but
 * sufficient for the builder's structural HTML.
 * ------------------------------------------------------------------------- */
const VOID = core.VOID_ELEMENTS;

function makeElement(tag) {
    const attrs = [];
    return {
        nodeType: 1,
        tagName: tag.toUpperCase(),
        attributes: attrs,
        childNodes: [],
        getAttribute(name) {
            const a = attrs.find((x) => x.name === name);
            return a ? a.value : null;
        },
    };
}

function makeText(value) {
    return { nodeType: 3, nodeValue: value };
}

function parseAttributes(str) {
    const attrs = [];
    const re = /([^\s=/]+)(?:\s*=\s*("([^"]*)"|'([^']*)'|([^\s>]+)))?/g;
    let m;
    while ((m = re.exec(str)) !== null) {
        const name = m[1];
        if (!name) {
            continue;
        }
        let value = '';
        if (m[3] !== undefined) {
            value = m[3];
        } else if (m[4] !== undefined) {
            value = m[4];
        } else if (m[5] !== undefined) {
            value = m[5];
        }
        attrs.push({ name, value });
    }
    return attrs;
}

function decodeEntities(str) {
    return str
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&#39;/g, "'")
        .replace(/&amp;/g, '&');
}

function parseFragment(html) {
    const roots = [];
    const stack = [];
    const tagRe = /<\/?([a-zA-Z][a-zA-Z0-9-]*)((?:[^>"']|"[^"]*"|'[^']*')*?)\s*(\/?)>/g;
    let lastIndex = 0;
    let m;

    function pushText(text) {
        if (text === '') {
            return;
        }
        const node = makeText(decodeEntities(text));
        if (stack.length) {
            stack[stack.length - 1].childNodes.push(node);
        }
        // top-level text nodes are ignored (no root text wrapper)
    }

    while ((m = tagRe.exec(html)) !== null) {
        pushText(html.slice(lastIndex, m.index));
        lastIndex = tagRe.lastIndex;

        const isClose = html[m.index + 1] === '/';
        const tag = m[1].toLowerCase();
        const selfClose = m[3] === '/' || VOID.has(tag);

        if (isClose) {
            for (let i = stack.length - 1; i >= 0; i--) {
                if (stack[i].tagName.toLowerCase() === tag) {
                    stack.length = i;
                    break;
                }
            }
            continue;
        }

        const el = makeElement(tag);
        el.attributes.push(...parseAttributes(m[2] || ''));

        if (stack.length) {
            stack[stack.length - 1].childNodes.push(el);
        } else {
            roots.push(el);
        }

        if (!selfClose) {
            stack.push(el);
        }
    }
    pushText(html.slice(lastIndex));
    return roots;
}

core.setFragmentParser(parseFragment);

/* ---------------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------------- */
// Canonicalize so object KEY ORDER does not affect equality (attribute order is
// a documented normalization). Recursively sorts object keys, preserving arrays.
function canonical(value) {
    if (Array.isArray(value)) {
        return value.map(canonical);
    }
    if (value && typeof value === 'object') {
        const out = {};
        for (const key of Object.keys(value).sort()) {
            out[key] = canonical(value[key]);
        }
        return out;
    }
    return value;
}

// Compare via canonical JSON strings. (The core is loaded in a separate vm
// realm, so its objects have a different Object.prototype; deepStrictEqual would
// reject them on prototype grounds. String comparison sidesteps that and also
// makes attribute-order normalization explicit.)
function deepEqual(a, b, path = 'root') {
    assert.strictEqual(
        JSON.stringify(canonical(a)),
        JSON.stringify(canonical(b)),
        `Mismatch at ${path}`
    );
}

let passed = 0;
function ok(label) {
    passed++;
    console.log(`  ✓ ${label}`);
}

/* ---------------------------------------------------------------------------
 * Test 1: the exact contract example
 * ------------------------------------------------------------------------- */
console.log('\nTest 1: contract example');
const exampleHtml = `
<div data-somthing='abc' class='someclass' id='something'>
    <h1 class='sometailwindclass'>something</h1>
    <div>
        <h2>something</h2>
        <span>something</span>
        <div class='someclass' id='something'>ec</div>
    </div>
</div>`;

const expected = {
    type: 'div',
    classes: 'someclass',
    attributes: { id: 'something', 'data-somthing': 'abc' },
    children: [
        { type: 'h1', classes: 'sometailwindclass', content: 'something', children: [] },
        {
            type: 'div',
            children: [
                { type: 'h2', content: 'something', children: [] },
                { type: 'span', content: 'something', children: [] },
                { type: 'div', classes: 'someclass', attributes: { id: 'something' }, children: [], content: 'ec' },
            ],
        },
    ],
};

const produced = core.htmlToJson(exampleHtml);
console.log('  produced JSON:');
console.log(JSON.stringify(produced, null, 2).split('\n').map((l) => '    ' + l).join('\n'));

// Compare on normalized content (key order does not matter for JSON equality).
deepEqual(produced, expected, 'contract example');
ok('htmlToJson matches the contract exactly');

// Round-trip stability: HTML -> JSON -> HTML -> JSON
const html2 = core.jsonToHtml(produced);
const produced2 = core.htmlToJson(html2);
deepEqual(produced2, produced, 'round-trip');
ok('jsonToHtml(htmlToJson(x)) re-parses to identical JSON');

/* ---------------------------------------------------------------------------
 * Test 2: deep nesting (6 levels)
 * ------------------------------------------------------------------------- */
console.log('\nTest 2: deep nesting (6 levels)');
const deepHtml = `
<section id="lvl1">
  <div class="lvl2">
    <div class="lvl3">
      <ul class="lvl4">
        <li class="lvl5">
          <a href="/x" data-deep="yes">deep link</a>
        </li>
      </ul>
    </div>
  </div>
</section>`;
const deepJson = core.htmlToJson(deepHtml);
const deepRound = core.htmlToJson(core.jsonToHtml(deepJson));
deepEqual(deepRound, deepJson, 'deep nesting round-trip');
// Verify depth chain reaches the <a>.
let cur = deepJson;
let depth = 1;
while (cur.children && cur.children.length) {
    cur = cur.children[0];
    depth++;
}
assert.strictEqual(cur.type, 'a', 'deepest node should be <a>');
assert.strictEqual(cur.attributes['data-deep'], 'yes');
assert.ok(depth >= 6, `expected depth >= 6, got ${depth}`);
ok(`reaches depth ${depth} and round-trips stably`);

/* ---------------------------------------------------------------------------
 * Test 3: element with BOTH text and children
 * ------------------------------------------------------------------------- */
console.log('\nTest 3: element with both content and children');
const mixedHtml = `<div class="card">Hello<span>world</span><p>tail</p></div>`;
const mixedJson = core.htmlToJson(mixedHtml);
assert.strictEqual(mixedJson.content, 'Hello', 'direct text captured');
assert.strictEqual(mixedJson.children.length, 2, 'two child elements');
const mixedRound = core.htmlToJson(core.jsonToHtml(mixedJson));
deepEqual(mixedRound, mixedJson, 'mixed content round-trip');
ok('content + children captured and round-trips');

/* ---------------------------------------------------------------------------
 * Test 4: multiple roots + void elements
 * ------------------------------------------------------------------------- */
console.log('\nTest 4: multiple roots + void elements');
const multiHtml = `<img src="/a.png" alt="A"><div class="b">two</div>`;
const multiJson = core.htmlToJson(multiHtml);
assert.ok(Array.isArray(multiJson), 'multiple roots -> array');
assert.strictEqual(multiJson.length, 2);
assert.strictEqual(multiJson[0].type, 'img');
const multiRound = core.htmlToJson(core.jsonToHtml(multiJson));
deepEqual(multiRound, multiJson, 'multi-root round-trip');
ok('multiple roots returned as array, void elements handled');

console.log(`\nAll ${passed} assertions passed.\n`);
