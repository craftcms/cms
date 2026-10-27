import {readFileSync} from 'fs';
import {dirname, resolve} from 'path';
import {fileURLToPath, pathToFileURL} from 'url';
import {transformSync} from 'esbuild';
import {format} from 'vite-plus/fmt';
import {
  BLACK_CONTENTS_SELECTOR,
  NEUTRAL_CARD_SELECTOR,
  writeFileIfChanged,
} from './utils.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');
const OUT_FILE = resolve(ROOT, 'src/styles/shared/colorable.css');
const OUT_LIT_FILE = resolve(ROOT, 'src/styles/colorable.styles.ts');
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

/**
 * A quiet border is its color mixed with transparent. How much is see-through
 * is `--c-color-border-quiet-transparency`, set in `shared/variables.css`, so
 * it can be tuned without regenerating.
 */
function quietBorder(color) {
  return `color-mix(in srgb, ${color}, transparent var(--c-color-border-quiet-transparency))`;
}

function colorScale(color) {
  switch (color) {
    case 'white':
      return {
        fillQuiet: 'var(--color-white)',
        fillNormal: 'var(--color-white)',
        fillLoud: 'var(--color-white)',
        borderQuiet: quietBorder('var(--color-static-gray-200)'),
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
        borderQuiet: quietBorder('var(--color-static-gray-800)'),
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
        borderQuiet: quietBorder(`var(--color-${color}-${border.quiet})`),
        borderNormal: `var(--color-${color}-${border.normal})`,
        borderLoud: `var(--color-${color}-${border.loud})`,
        onQuiet: `var(--color-${color}-${foreground.quiet})`,
        onNormal: `var(--color-${color}-${foreground.normal})`,
        onLoud: `var(--color-${color}-${foreground.loud})`,
      };
  }
}

/** White and black, whose colors are static rather than taken from the theme's palette. */
const staticColors = ['white', 'black'];

function buildColorableTokens(paletteColors) {
  return paletteColors
    .map((color) => colorTokens(color, colorScale(color)))
    .join('\n\n');
}

/**
 * White in the dark theme: filled like a white card, with gray's other colors.
 * `--c-surface-white` is set in `shared/tokens.css`.
 */
function darkWhiteScale() {
  const gray = (part) => `var(--c-color-gray-${part})`;

  return {
    fillQuiet: 'var(--c-surface-white)',
    fillNormal: 'var(--c-surface-white)',
    fillLoud: gray('fill-loud'),
    borderQuiet: gray('border-quiet'),
    borderNormal: gray('border-normal'),
    borderLoud: gray('border-loud'),
    onQuiet: gray('on-quiet'),
    onNormal: gray('on-normal'),
    onLoud: gray('on-loud'),
  };
}

/** Black, a shade darker, so it stands off the dark theme's surfaces. */
function darkerBlackScale() {
  return {
    ...colorScale('black'),
    fillQuiet: 'var(--color-static-gray-950)',
    fillNormal: 'var(--color-static-gray-950)',
    fillLoud: 'var(--color-static-gray-950)',
  };
}

function colorTokens(color, s) {
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

/*
  Also on [data-theme]: these tokens are built from the palette with var(),
  which is substituted where it's declared, so a themed subtree re-resolves them.
  Likewise what's in a black card, chip, or badge, and a neutral card, so it
  doesn't take a tinted card's colors (see utils.js).
*/
:root,
[data-theme],
${BLACK_CONTENTS_SELECTOR},
${NEUTRAL_CARD_SELECTOR} {
${buildColorableTokens(paletteColors)}
${buildSemanticTokens(semanticColors)}
}

/*
  In the dark theme, white is a lighter surface than the page rather than
  glaring white, with gray's other colors. Black stays black, a shade darker.
*/
[data-theme='dark'],
${BLACK_CONTENTS_SELECTOR} {
${colorTokens('white', darkWhiteScale())}

${colorTokens('black', darkerBlackScale())}
}

${[...paletteColors, ...Object.keys(semanticColors)]
  .map((c) => buildStyleBlock(c))
  .join('\n')}

/*
  A transparent button's text sits on the page rather than on its color's
  fill. White's and black's colors don't follow the page's palette, so their
  text can match the page; those buttons take the neutral colors instead.
*/
craft-button:is(${staticColors.map((c) => `[data-color='${c}']`).join(', ')}):is(
    [variant~='outline'],
    [variant~='dashed'],
    [variant~='plain'],
    [variant~='link']
  ) {
  --c-color-fill-quiet: var(--c-color-neutral-fill-quiet);
  --c-color-border-quiet: var(--c-color-neutral-border-quiet);
  --c-color-on-quiet: var(--c-color-neutral-on-quiet);
  --c-color-fill-normal: var(--c-color-neutral-fill-normal);
  --c-color-border-normal: var(--c-color-neutral-border-normal);
  --c-color-on-normal: var(--c-color-neutral-on-normal);
  --c-color-fill-loud: var(--c-color-neutral-fill-loud);
  --c-color-border-loud: var(--c-color-neutral-border-loud);
  --c-color-on-loud: var(--c-color-neutral-on-loud);
}
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

export default async function main() {
  const {paletteColors, semanticColors} = await loadColorData();

  const [css, lit] = await Promise.all([
    formatOutput(OUT_FILE, generateStyles(paletteColors, semanticColors)),
    formatOutput(
      OUT_LIT_FILE,
      generateLitStyles(paletteColors, semanticColors)
    ),
  ]);

  // Only write files whose contents changed, so a running Vite dev server
  // doesn't hot-update every component that imports them
  if (writeFileIfChanged(OUT_FILE, css)) {
    console.log(`Generated ${OUT_FILE}`);
  }

  if (writeFileIfChanged(OUT_LIT_FILE, lit)) {
    console.log(`Generated ${OUT_LIT_FILE}`);
  }
}

/*
Both outputs are committed, so run them through the repo formatter, with the
`fmt` settings from the root Vite config that `vp fmt` uses. Otherwise every
build rewrites them unformatted and `vp fmt` flips them back again.
 */
let fmtOptions;

async function formatOutput(file, content) {
  fmtOptions ??= loadFmtOptions();

  const {code, errors} = await format(file, content, await fmtOptions);
  if (errors.length) {
    throw new Error(`Couldn't format ${file}: ${errors[0].message}`);
  }

  return code;
}

async function loadFmtOptions() {
  const {default: config} = await import(
    pathToFileURL(resolve(ROOT, '../../vite.config.js')).href
  );

  // As `vp fmt` does, skip the plugins while the config evaluates. This runs
  // inside build.js, so put the variable back for the bundler afterwards.
  const previous = process.env.VP_RESOLVING_CONFIG_METADATA;
  process.env.VP_RESOLVING_CONFIG_METADATA = '1';

  try {
    const {fmt} = await config({mode: 'production', command: 'build'});

    return fmt;
  } finally {
    if (previous === undefined) {
      delete process.env.VP_RESOLVING_CONFIG_METADATA;
    } else {
      process.env.VP_RESOLVING_CONFIG_METADATA = previous;
    }
  }
}

// Run when invoked directly (node scripts/generate-colors.js), but not when
// imported by build.js (which awaits the default export instead).
if (import.meta.url === `file://${process.argv[1]}`) {
  main();
}
