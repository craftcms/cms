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

  if (isStringLiteralUnion(base)) {
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

function generateRenderLine(attr, field, mapped) {
  const resolved = mapped.enum
    ? `$this->${field} instanceof ${mapped.enum} ? $this->${field}->value : $this->${field}`
    : `$this->${field}`;

  // Omit the attribute when it still holds its default, keeping the markup
  // clean (the web component applies the same defaults itself).
  if (attr.default === undefined || attr.default === 'null') {
    return `'${attr.name}' => ${resolved},`;
  }

  return `'${attr.name}' => $this->${field} === ${attr.default} ? null : ${mapped.enum ? `(${resolved})` : resolved},`;
}

function generateClass(decl) {
  const tagName = decl.tagName;
  const baseName = `${pascalFromTag(tagName)}Component`;
  const attributes = decl.attributes ?? [];

  const enums = new Set();
  const fields = [];
  const setters = [];
  const renderLines = [];

  for (const attr of attributes) {
    const field = attr.fieldName ?? attr.name;
    const mapped = mapType(attr);
    if (mapped.enum) {
      enums.add(mapped.enum);
    }

    const def = attr.default !== undefined ? ` = ${attr.default}` : '';
    fields.push(`    protected ${mapped.type} $${field}${def};`);
    setters.push(generateSetter(attr, field, mapped));
    renderLines.push(generateRenderLine(attr, field, mapped));
  }

  const imports = [
    ...[...enums].map((e) => `use ${ENUM_IMPORTS[e]};`),
    'use CraftCms\\Cms\\Support\\Arr;',
    'use CraftCms\\Cms\\Support\\Html;',
    'use Illuminate\\Contracts\\Support\\Htmlable;',
    'use Stringable;',
  ].join('\n');

  const summary = (decl.summary ?? decl.description ?? '')
    .split('\n')
    .map((l) => ` * ${l}`.trimEnd())
    .join('\n');

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
abstract class ${baseName} implements Htmlable, Stringable
{
${fields.join('\n\n')}

    /** @var array<string, mixed> Additional HTML attributes for the host element. */
    protected array $attributes = [];

    final public function __construct() {}

    public static function make(): static
    {
        return app(static::class);
    }

${setters.join('\n\n')}

    /**
     * Merges additional HTML attributes (e.g. \`slot\`, \`class\`) onto the host element.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = Arr::merge($this->attributes, $attributes);

        return $this;
    }

    public function toHtml(): string
    {
        return Html::tag('${tagName}', '', Arr::merge($this->attributes, [
${indent(renderLines, '            ')}
        ]));
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }
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
