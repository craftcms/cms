/**
 * Proof of concept: generate PHP component base classes from the Custom
 * Elements Manifest.
 *
 * For every custom element tagged `@phpComponent`, this emits an abstract PHP
 * base class mirroring its attributes (fluent setters + `toHtml()`), into:
 *   src/Cp/Components/Generated/<Name>Component.php
 *
 * The generated base is meant to be extended by a hand-written concrete class
 * (e.g. `Indicator extends IndicatorComponent`) that adds behavior. Regenerating
 * never touches the hand-written subclass.
 *
 * Usage (from packages/craftcms-cp/):
 *   npm run build:manifest && node scripts/generate-php-components.js
 */

import {mkdirSync, readFileSync, writeFileSync} from 'fs';
import {dirname, resolve} from 'path';
import {fileURLToPath} from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');
const MANIFEST = resolve(ROOT, 'dist/custom-elements.json');
const OUT_DIR = resolve(ROOT, '../../src/Cp/Components/Generated');
const PHP_NAMESPACE = 'CraftCms\\Cms\\Cp\\Components\\Generated';

// Short PHP type name -> fully-qualified class to `use`. Drives both imports
// and `->value` resolution for backed enums. A `@phpType {Color|string}` tag in
// the web component routes through here.
const ENUM_IMPORTS = {
  Color: 'CraftCms\\Cms\\Shared\\Enums\\Color',
};

// TS type aliases that resolve to a plain PHP type. e.g. `VariantKey` is a
// string union, so it maps to `string`. A `@phpType` tag can always override.
const TYPE_ALIASES = {
  VariantKey: 'string',
};

// ─── Helpers ────────────────────────────────────────────────────────────────

function pascalFromTag(tagName) {
  return tagName
    .split('-')
    .filter((part, i) => !(i === 0 && (part === 'craft' || part === 'c')))
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('');
}

const isStringLiteralUnion = (text) =>
  /^'[^']*'(\s*\|\s*'[^']*')*$/.test(text.trim());

/**
 * Maps a manifest attribute to PHP type info.
 * Returns {type, paramDoc, enum} where `enum` is the backed-enum short name (or null).
 */
function mapType(attr) {
  // An explicit @phpType override wins.
  if (attr.phpType) {
    const tokens = attr.phpType.split('|').map((t) => t.trim());
    const enumName = tokens.find((t) => ENUM_IMPORTS[t]) ?? null;
    return {type: attr.phpType, paramDoc: null, enum: enumName};
  }

  const text = (attr.type?.text ?? 'string').trim();
  const nullable = /(^|\|)\s*null\s*($|\|)/.test(text);
  const base = text
    .split('|')
    .map((t) => t.trim())
    .filter((t) => t !== 'null')
    .join(' | ');

  let type;
  let paramDoc = null;

  if (TYPE_ALIASES[base]) {
    type = TYPE_ALIASES[base];
  } else if (isStringLiteralUnion(base)) {
    type = 'string';
    paramDoc = base; // e.g. 'md' | 'lg'
  } else if (base === 'string') {
    type = 'string';
  } else if (base === 'number') {
    type = 'int|float';
  } else if (base === 'boolean') {
    type = 'bool';
  } else {
    type = 'mixed';
  }

  if (nullable && type !== 'mixed') {
    type = type.includes('|') ? `${type}|null` : `?${type}`;
  }

  return {type, paramDoc, enum: null};
}

// Widens a PHP type to also accept null (used when an attribute has no default,
// so the generated property can be initialized to null rather than left unset).
function makeNullable(type) {
  if (type === 'mixed' || type.startsWith('?') || /(^|\|)null(\||$)/.test(type)) {
    return type;
  }

  return type.includes('|') ? `${type}|null` : `?${type}`;
}

const indent = (lines, pad = '    ') =>
  lines.map((l) => (l ? pad + l : l)).join('\n');

// ─── Code generation ────────────────────────────────────────────────────────

function generateSetter(attr, field, mapped) {
  const doc = mapped.paramDoc
    ? `    /**\n     * @param  ${mapped.paramDoc}  $${field}\n     */\n`
    : '';

  return (
    doc +
    `    public function ${field}(${mapped.type} $${field}): static\n` +
    `    {\n` +
    `        $this->${field} = $${field};\n\n` +
    `        return $this;\n` +
    `    }`
  );
}

function generateRenderLine(attr, field, mapped, effectiveDefault) {
  const resolved = mapped.enum
    ? `$this->${field} instanceof ${mapped.enum} ? $this->${field}->value : $this->${field}`
    : `$this->${field}`;

  // Omit the attribute when it still holds its default, keeping the markup
  // clean (the web component applies the same defaults itself). `Html::tag`
  // renders bool `true` as a bare attribute and omits `false`/`null`.
  if (effectiveDefault === 'null') {
    return `'${attr.name}' => ${resolved},`;
  }

  return `'${attr.name}' => $this->${field} === ${effectiveDefault} ? null : ${mapped.enum ? `(${resolved})` : resolved},`;
}

function camelCase(name) {
  return name
    .split(/[-_]/)
    .map((part, i) =>
      i === 0 ? part : part.charAt(0).toUpperCase() + part.slice(1)
    )
    .join('');
}

// Method names already provided by the ViewComponent base.
const RESERVED_METHODS = [
  'make',
  'attributes',
  'slot',
  'toHtml',
  '__toString',
  '__construct',
];

function generateSlotSetter(methodName, slotKey) {
  const key = slotKey === '' ? "''" : `'${slotKey}'`;

  return (
    `    public function ${methodName}(Closure|Htmlable|Stringable|string $content): static\n` +
    `    {\n` +
    `        $this->slots[${key}] = $content;\n\n` +
    `        return $this;\n` +
    `    }`
  );
}

function generateClass(decl) {
  const tagName = decl.tagName;
  const baseName = `${pascalFromTag(tagName)}Component`;
  const attributes = decl.attributes ?? [];

  const enums = new Set();
  const fields = [];
  const setters = [];
  const renderLines = [];
  const usedNames = new Set(RESERVED_METHODS);

  for (const attr of attributes) {
    const field = attr.fieldName ?? attr.name;
    const mapped = mapType(attr);
    if (mapped.enum) {
      enums.add(mapped.enum);
    }
    usedNames.add(field);

    // CEM only captures literal defaults; non-literal/absent ones come through
    // as `undefined`. In that case make the property nullable and default null
    // so it's always initialized (and omitted from the rendered markup).
    const hasDefault =
      attr.default !== undefined && attr.default !== 'undefined';
    const type = hasDefault ? mapped.type : makeNullable(mapped.type);
    const effectiveDefault = hasDefault ? attr.default : 'null';

    fields.push(`    protected ${type} $${field} = ${effectiveDefault};`);
    setters.push(generateSetter(attr, field, {...mapped, type}));
    renderLines.push(generateRenderLine(attr, field, mapped, effectiveDefault));
  }

  // Each manifest slot becomes a fluent callback setter. The default (unnamed)
  // slot maps to `content()`; names that would clash with an attribute setter
  // (or a base method) get a `Slot` suffix.
  const slotSetters = [];
  for (const slot of decl.slots ?? []) {
    const slotKey = slot.name ?? '';
    let methodName = slotKey === '' ? 'content' : camelCase(slotKey);
    while (usedNames.has(methodName)) {
      methodName += 'Slot';
    }
    usedNames.add(methodName);
    slotSetters.push(generateSlotSetter(methodName, slotKey));
  }

  const hasSlots = slotSetters.length > 0;

  const importClasses = ['CraftCms\\Cms\\Cp\\Components\\ViewComponent'];
  for (const e of enums) {
    importClasses.push(ENUM_IMPORTS[e]);
  }
  if (hasSlots) {
    importClasses.push(
      'Closure',
      'Illuminate\\Contracts\\Support\\Htmlable',
      'Stringable'
    );
  }
  importClasses.sort((a, b) => (a < b ? -1 : a > b ? 1 : 0));
  const imports = importClasses.map((c) => `use ${c};`).join('\n');

  const summary = (decl.summary ?? decl.description ?? '')
    .split('\n')
    .map((l) => ` * ${l}`.trimEnd())
    .join('\n');

  const members = [
    fields.join('\n\n'),
    setters.join('\n\n'),
    slotSetters.join('\n\n'),
    `    protected function tagName(): string\n    {\n        return '${tagName}';\n    }`,
    `    protected function hostAttributes(): array\n    {\n        return [\n${indent(renderLines, '            ')}\n        ];\n    }`,
  ]
    .filter(Boolean)
    .join('\n\n');

  return {
    baseName,
    code: `<?php

declare(strict_types=1);

namespace ${PHP_NAMESPACE};

${imports}

/**
${summary ? summary + '\n *\n' : ''} * @generated from the \`${tagName}\` custom element. Do not edit by hand.
 *           Run \`npm run generate:php\` in packages/craftcms-cp to regenerate.
 *           Add behavior in the concrete subclass, not here.
 */
abstract class ${baseName} extends ViewComponent
{
${members}
}
`,
  };
}

// ─── Main ───────────────────────────────────────────────────────────────────

export default function main() {
  const manifest = JSON.parse(readFileSync(MANIFEST, 'utf8'));

  const declarations = (manifest.modules ?? [])
    .flatMap((mod) => mod.declarations ?? [])
    .filter((decl) => decl.phpComponent === true);

  if (declarations.length === 0) {
    console.log('No @phpComponent custom elements found in the manifest.');
    return;
  }

  mkdirSync(OUT_DIR, {recursive: true});

  for (const decl of declarations) {
    const {baseName, code} = generateClass(decl);
    const filePath = resolve(OUT_DIR, `${baseName}.php`);
    writeFileSync(filePath, code);
    console.log(`  Generated: src/Cp/Components/Generated/${baseName}.php`);
  }

  console.log(`\n  ${declarations.length} PHP component base class(es) generated.`);
}

main();
