import {writeFileSync, readFileSync} from 'fs';
import {dirname, resolve} from 'path';
import {fileURLToPath} from 'url';
import {transformSync} from 'esbuild';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');
const OUT_FILE = resolve(ROOT, 'src/styles/shared/colorable.css');
const OUT_LIT_FILE = resolve(ROOT, 'src/styles/colorable.styles.ts');
const OUT_TAILWIND_FILE = resolve(ROOT, 'tailwind.css');
const DATA_FILE = resolve(ROOT, 'src/constants/colors.data.ts');

/*
The canonical color data lives in src/constants/colors.data.ts (shared with
constants/colors.ts). This script can't import TypeScript directly, so we
transpile it with esbuild and import the result.
 */
async function loadColorData() {
  const {code} = transformSync(readFileSync(DATA_FILE, 'utf8'), {
    loader: 'ts',
    format: 'esm',
  });
  return import(
    `data:text/javascript;base64,${Buffer.from(code).toString('base64')}`
  );
}

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

function buildStyleBlock(color) {
  return `.cp-color-${color},
[data-color='${color}'] {
  --c-color-fill-quiet: var(--c-color-${color}-fill-quiet);
  --c-color-border-quiet: var(--c-color-${color}-border-quiet);
  --c-color-on-quiet: var(--c-color-${color}-on-quiet);
  --c-color-fill-normal: var(--c-color-${color}-fill-normal);
  --c-color-border-normal: var(--c-color-${color}-border-normal);
  --c-color-on-normal: var(--c-color-${color}-on-normal);
  --c-color-fill-loud: var(--c-color-${color}-fill-loud);
  --c-color-border-loud: var(--c-color-${color}-border-loud);
  --c-color-on-loud: var(--c-color-${color}-on-loud);
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
    --c-color-fill-quiet: var(--c-color-${color}-fill-quiet);
    --c-color-border-quiet: var(--c-color-${color}-border-quiet);
    --c-color-on-quiet: var(--c-color-${color}-on-quiet);
    --c-color-fill-normal: var(--c-color-${color}-fill-normal);
    --c-color-border-normal: var(--c-color-${color}-border-normal);
    --c-color-on-normal: var(--c-color-${color}-on-normal);
    --c-color-fill-loud: var(--c-color-${color}-fill-loud);
    --c-color-border-loud: var(--c-color-${color}-border-loud);
    --c-color-on-loud: var(--c-color-${color}-on-loud);
  }`;
}

function generateLitStyles(paletteColors, semanticColors) {
  const palette = [...paletteColors, ...Object.keys(semanticColors)]
    .map((color) =>
      buildLitBlock(
        [`:host([data-color='${color}'])`, `[data-color='${color}']`],
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
 * Matches a host carrying \`data-color\`, or any element inside the shadow
 * root that carries it, mirroring the \`[data-color]\` rules in
 * \`shared/colorable.css\`.
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

/**
 * Foundational tokens that aren't derived from the palette, so they can't be
 * generated from `colors.data.ts`. Kept in step with
 * `src/styles/shared/tokens.css` by hand — the shape there is hand-authored
 * too (and `--c-text-*` is overloaded: `--c-text-quiet` is a color while
 * `--c-text-sm` is a font size), so there's nothing mechanical to read.
 *
 * Each entry is `[section heading, [[tailwind key, craft token], …]]`. The
 * tailwind key lands in the `--color-*` namespace, which is what names the
 * utility: `surface-raised` → `bg-surface-raised`, `text-quiet` →
 * `text-text-quiet`.
 */
const foundationalTokens = [
  [
    'Surfaces',
    [
      ['surface-default', '--c-surface-default'],
      ['surface-raised', '--c-surface-raised'],
      ['surface-sunken', '--c-surface-sunken'],
      ['surface-overlay', '--c-surface-overlay'],
      ['surface-form', '--c-surface-form'],
      ['surface-shade', '--c-surface-shade'],
    ],
  ],
  [
    'Text',
    [
      ['text-default', '--c-text-default'],
      ['text-quiet', '--c-text-quiet'],
      ['text-link', '--c-text-link'],
      ['text-white', '--c-text-white'],
      ['text-black', '--c-text-black'],
    ],
  ],
  [
    "Contextual — whatever the nearest [data-color] / variant resolved to",
    [
      ['fill-quiet', '--c-color-fill-quiet'],
      ['fill-normal', '--c-color-fill-normal'],
      ['fill-loud', '--c-color-fill-loud'],
      ['border-quiet', '--c-color-border-quiet'],
      ['border-normal', '--c-color-border-normal'],
      ['border-loud', '--c-color-border-loud'],
      ['on-quiet', '--c-color-on-quiet'],
      ['on-normal', '--c-color-on-normal'],
      ['on-loud', '--c-color-on-loud'],
    ],
  ],
  [
    'Form controls',
    [
      ['form-fill', '--c-form-control-fill'],
      ['form-text', '--c-form-control-text'],
      ['form-border', '--c-form-control-border-color'],
      ['input-fill', '--c-input-fill'],
      ['input-text', '--c-input-text'],
      ['input-border', '--c-input-border-color'],
      ['select-fill', '--c-select-fill'],
      ['select-text', '--c-select-text'],
      ['select-border', '--c-select-border-color'],
    ],
  ],
  [
    'Surfaces — panes and modals',
    [
      ['pane-fill', '--c-pane-fill'],
      ['pane-text', '--c-pane-text'],
      ['pane-border', '--c-pane-border-color'],
      ['modal-fill', '--c-modal-fill'],
      ['modal-text', '--c-modal-text'],
      ['modal-border', '--c-modal-border-color'],
    ],
  ],
  ['Focus', [['focus-outline', '--c-color-focus-outline']]],
];

/** Status tokens in `tokens.css`, each with a fill / border / text. */
const statusNames = ['live', 'enabled', 'pending', 'expired', 'disabled'];

/**
 * Static (theme-invariant) semantic colors in `tokens.css`. Deliberately not
 * `semanticColors` — `--c-color-static-*` covers `brand`, which has no
 * theme-aware counterpart.
 */
const staticNames = [
  'neutral',
  'brand',
  'accent',
  'info',
  'success',
  'warning',
  'danger',
];

const colorableRoles = [
  ['fill', ['quiet', 'normal', 'loud']],
  ['border', ['quiet', 'normal', 'loud']],
  ['on', ['quiet', 'normal', 'loud']],
];

function themeSection(heading, entries) {
  return [
    `  /* ——— ${heading} ——— */`,
    ...entries.map(([key, token]) => `  --color-${key}: var(${token});`),
  ].join('\n');
}

/** The nine colorable tokens a `--c-color-<name>-*` group exposes. */
function colorableSection(name) {
  return themeSection(
    name,
    colorableRoles.flatMap(([role, strengths]) =>
      strengths.map((strength) => [
        `${name}-${role}-${strength}`,
        `--c-color-${name}-${role}-${strength}`,
      ])
    )
  );
}

/**
 * Craft's design tokens, republished in Tailwind's `--color-*` namespace so
 * every one of them gets `bg-*` / `text-*` / `border-*` / `ring-*` / … utility
 * classes generated for it.
 *
 * The mapping is mechanical: drop the `--c-color-` (or `--c-`) prefix, and
 * what's left names the utility. `--c-color-neutral-border-quiet` becomes
 * `--color-neutral-border-quiet`, i.e. `border-neutral-border-quiet`,
 * `bg-neutral-border-quiet`, and so on.
 */
function generateTailwindTheme(paletteColors, semanticColors) {
  const sections = [
    ...foundationalTokens.map(([heading, entries]) =>
      themeSection(heading, entries)
    ),
    themeSection(
      'Status',
      statusNames.flatMap((name) =>
        ['fill', 'border', 'text'].map((role) => [
          `status-${name}-${role}`,
          `--c-status-${name}-${role}`,
        ])
      )
    ),
    themeSection(
      'Static — same value in every theme',
      staticNames.flatMap((name) =>
        ['fill', 'border', 'on'].map((role) => [
          `static-${name}-${role}`,
          `--c-color-static-${name}-${role}`,
        ])
      )
    ),
    ...Object.keys(semanticColors).map((name) => colorableSection(name)),
    ...paletteColors.map((name) => colorableSection(name)),
  ];

  return `/* Auto-generated by scripts/generate-colors.js — do not edit manually */

/**
 * Craft CMS Control Panel — Tailwind v4 theme integration.
 *
 * Publishes Craft's design tokens into Tailwind's \`--color-*\` namespace so
 * that utility classes are generated for every one of them. The token name is
 * the utility name: \`--c-color-neutral-border-quiet\` becomes
 * \`border-neutral-border-quiet\`, \`--c-surface-raised\` becomes
 * \`bg-surface-raised\`.
 *
 * Usage in a CSS entry file:
 *
 *     @import 'tailwindcss/theme.css' layer(theme);
 *     @import 'tailwindcss/utilities.css' layer(utilities);
 *     @import '@craftcms/ui/styles/cp.css' layer(cp);  // :root token values
 *     @import '@craftcms/ui/tailwind.css' layer(theme);
 *
 * The \`--c-*\` variables have to be in scope (via \`cp.css\`) for the
 * \`var()\`s below to resolve at render time. \`@theme inline\` makes the
 * generated utilities reference those custom properties directly rather than
 * snapshotting their values, so \`[data-theme='dark']\` overrides and
 * \`[data-color]\` context both flow through untouched.
 */

@theme inline {
${sections.join('\n\n')}
}
`;
}

export default async function main() {
  const {paletteColors, semanticColors} = await loadColorData();
  const css = generateStyles(paletteColors, semanticColors);
  writeFileSync(OUT_FILE, css);
  console.log(`Generated ${OUT_FILE}`);

  writeFileSync(OUT_LIT_FILE, generateLitStyles(paletteColors, semanticColors));
  console.log(`Generated ${OUT_LIT_FILE}`);

  writeFileSync(
    OUT_TAILWIND_FILE,
    generateTailwindTheme(paletteColors, semanticColors)
  );
  console.log(`Generated ${OUT_TAILWIND_FILE}`);
}

// Run when invoked directly (node scripts/generate-colors.js), but not when
// imported by build.js (which awaits the default export instead).
if (import.meta.url === `file://${process.argv[1]}`) {
  main();
}
