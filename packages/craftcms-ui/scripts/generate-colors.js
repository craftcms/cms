import {writeFileSync} from 'fs';
import {dirname, resolve} from 'path';
import {fileURLToPath} from 'url';
import {loadColorData} from './utils.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');
const OUT_FILE = resolve(ROOT, 'src/styles/shared/colorable.css');
const OUT_LIT_FILE = resolve(ROOT, 'src/styles/colorable.styles.ts');

const colorIncrements = {
  fill: {
    quiet: 50,
    normal: 100,
    loud: 600,
  },
  foreground: {
    quiet: 800,
    normal: 950,
    loud: 50,
  },
  border: {
    quiet: 400,
    normal: 600,
    loud: 800,
  },
};

function colorScale(color) {
  switch (color) {
    case 'white':
      return {
        fillQuiet: 'var(--color-white)',
        fillNormal: 'var(--color-white)',
        fillLoud: 'var(--color-white)',
        borderQuiet: 'var(--color-static-gray-200)',
        borderNormal: 'var(--color-static-gray-200)',
        borderLoud: 'var(--color-static-gray-200)',
        onQuiet: 'var(--color-static-gray-800)',
        onNormal: 'var(--color-static-gray-800)',
        onLoud: 'var(--color-static-gray-800)',
      };
    case 'black':
      return {
        fillQuiet: 'var(--color-static-gray-900)',
        fillNormal: 'var(--color-static-gray-900)',
        fillLoud: 'var(--color-static-gray-900)',
        borderQuiet: 'var(--color-static-gray-800)',
        borderNormal: 'var(--color-static-gray-800)',
        borderLoud: 'var(--color-static-gray-800)',
        onQuiet: 'var(--color-static-gray-100)',
        onNormal: 'var(--color-static-gray-100)',
        onLoud: 'var(--color-static-gray-100)',
      };
    default:
      const {fill, border, foreground} = colorIncrements;
      return {
        fillQuiet: `var(--color-${color}-${fill.quiet})`,
        fillNormal: `var(--color-${color}-${fill.normal})`,
        fillLoud: `var(--color-${color}-${fill.loud})`,
        borderQuiet: `var(--color-${color}-${border.quiet})`,
        borderNormal: `var(--color-${color}-${border.normal})`,
        borderLoud: `var(--color-${color}-${border.loud})`,
        onQuiet: `var(--color-${color}-${foreground.quiet})`,
        onNormal: `var(--color-${color}-${foreground.normal})`,
        onLoud: `var(--color-${color}-${foreground.loud})`,
      };
  }
}

function buildColorableTokens(paletteColors) {
  return paletteColors
    .map((color) => {
      const s = colorScale(color);
      return [
        `  /* ${color} */`,
        `  --c-color-${color}-fill-quiet: ${s.fillQuiet};`,
        `  --c-color-${color}-fill-normal: ${s.fillNormal};`,
        `  --c-color-${color}-fill-loud: ${s.fillLoud};`,
        `  --c-color-${color}-border-quiet: ${s.borderQuiet};`,
        `  --c-color-${color}-border-normal: ${s.borderNormal};`,
        `  --c-color-${color}-border-loud: ${s.borderLoud};`,
        `  --c-color-${color}-on-quiet: ${s.onQuiet};`,
        `  --c-color-${color}-on-normal: ${s.onNormal};`,
        `  --c-color-${color}-on-loud: ${s.onLoud};`,
      ].join('\n');
    })
    .join('\n\n');
}

function buildSemanticTokens(semanticColors) {
  let declarations = [];
  for (const [meaning, color] of Object.entries(semanticColors)) {
    const s = colorScale(color);
    const variables = [
      `  /* Semantics colors - ${meaning} */`,
      `  --c-color-${meaning}-fill-quiet: ${s.fillQuiet};`,
      `  --c-color-${meaning}-fill-normal: ${s.fillNormal};`,
      `  --c-color-${meaning}-fill-loud: ${s.fillLoud};`,
      `  --c-color-${meaning}-border-quiet: ${s.borderQuiet};`,
      `  --c-color-${meaning}-border-normal: ${s.borderNormal};`,
      `  --c-color-${meaning}-border-loud: ${s.borderLoud};`,
      `  --c-color-${meaning}-on-quiet: ${s.onQuiet};`,
      `  --c-color-${meaning}-on-normal: ${s.onNormal};`,
      `  --c-color-${meaning}-on-loud: ${s.onLoud};`,
    ].join('\n');

    declarations.push(variables);
  }

  return declarations.join('\n\n');
}

/**
 * The generic tokens, remapped to one color group.
 *
 * Anything carrying a palette selector paints in that group, and so does
 * everything under it: components author against the generic tokens, so they
 * inherit the group without knowing which one is active.
 */
function buildDeclarations(color, indent = '  ') {
  return ['fill', 'border', 'on']
    .flatMap((role) =>
      ['quiet', 'normal', 'loud'].map(
        (strength) =>
          `${indent}--c-color-${role}-${strength}: var(--c-color-${color}-${role}-${strength});`
      )
    )
    .join('\n');
}

/**
 * The selectors that put an element in a color group.
 *
 * `.cp-palette-*` and `[data-palette]` are the current spellings — the class
 * follows the CP's `.cp-*` class convention, and the attribute matches the
 * class, so the two read as one idea rather than two. It is a plain stylesheet
 * rule, not a Tailwind utility, so it carries no `cp:` prefix and works in the
 * legacy shell and inside shadow roots as well.
 *
 * `.cp-color-*` and `[data-color]` are the Craft 5 spellings. They stay on the
 * same rule so existing markup and plugins keep working; new code should reach
 * for the palette spellings. Note that `[data-color]` is *not* the same axis as
 * `[data-theme='dark']`, which selects the light/dark color scheme — which is
 * why this one couldn't simply become `data-theme`.
 */
function paletteSelectors(color) {
  return [
    `.cp-palette-${color}`,
    `[data-palette='${color}']`,
    `.cp-color-${color}`,
    `[data-color='${color}']`,
  ];
}

function buildStyleBlock(color) {
  return `${paletteSelectors(color).join(',\n')} {
${buildDeclarations(color)}
}`;
}

function generateStyles(paletteColors, semanticColors) {
  return `/* Auto-generated by scripts/generate-colors.js — do not edit manually */

:root {
${buildColorableTokens(paletteColors)}
${buildSemanticTokens(semanticColors)}
}

${[...paletteColors, ...Object.keys(semanticColors)]
  .map((c) => buildStyleBlock(c))
  .join('\n')}
`;
}

/**
 * The same mapping as `buildStyleBlock`, for a Lit stylesheet: `colorable.css`
 * is a document stylesheet, so it reaches light DOM and component *hosts* but
 * stops at every shadow boundary. Components adopt this one to color elements
 * inside their own shadow root.
 */
function buildLitBlock(selectors, color) {
  return `  ${selectors.join(',\n  ')} {
${buildDeclarations(color, '    ')}
  }`;
}

function generateLitStyles(paletteColors, semanticColors) {
  const palette = [...paletteColors, ...Object.keys(semanticColors)]
    .map((color) =>
      buildLitBlock(
        [
          `:host([data-palette='${color}'])`,
          `[data-palette='${color}']`,
          `:host([data-color='${color}'])`,
          `[data-color='${color}']`,
        ],
        color
      )
    )
    .join('\n\n');

  const variants = Object.keys(semanticColors)
    .map((variant) =>
      buildLitBlock(
        [`:host([variant~='${variant}'])`, `[data-variant~='${variant}']`],
        variant
      )
    )
    .join('\n\n');

  return `/* Auto-generated by scripts/generate-colors.js — do not edit manually */

import {css} from 'lit';

/**
 * The full palette, for a shadow root that paints with arbitrary colors (a
 * chip, a badge, an icon). Most components want {@link variantStyles} instead
 * — this is every color in the system, so adopting it isn't free.
 *
 * Matches a host carrying \`data-palette\` (or its deprecated \`data-color\`
 * spelling), or any element inside the shadow root that carries it, mirroring
 * the palette rules in \`shared/colorable.css\`.
 */
export const paletteStyles = css\`
${palette}
\`;

/**
 * The semantic variants a component exposes as API. Two selectors each:
 *
 * - \`:host([variant~='…'])\` — the whole component wears it, the usual case.
 * - \`[data-variant~='…')\` — one region inside the shadow root wears it:
 *
 *       html\\\`<div class="callout__action" data-variant=\\\${this.variant}>\\\`
 *
 * Custom properties inherit, so the host rule still reaches the whole shadow
 * tree; a region set this way overrides what it inherited, and a region that
 * should stay neutral under a colored host can say \`data-variant="neutral"\`.
 */
export const variantStyles = css\`
${variants}
\`;
`;
}

export default async function main() {
  const {paletteColors, semanticColors} = await loadColorData();
  const css = generateStyles(paletteColors, semanticColors);
  writeFileSync(OUT_FILE, css);
  console.log(`Generated ${OUT_FILE}`);

  writeFileSync(OUT_LIT_FILE, generateLitStyles(paletteColors, semanticColors));
  console.log(`Generated ${OUT_LIT_FILE}`);
}

// Run when invoked directly (node scripts/generate-colors.js), but not when
// imported by build.js (which awaits the default export instead).
if (import.meta.url === `file://${process.argv[1]}`) {
  main();
}
