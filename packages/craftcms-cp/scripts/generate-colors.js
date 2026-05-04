import {writeFileSync} from 'fs';
import {dirname, resolve} from 'path';
import {fileURLToPath} from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');
const OUT_FILE = resolve(ROOT, 'src/styles/shared/colorable.css');

/*
This is the same as the colors in constants/colors but we can't import
typescript into this JS file so we had to duplicate for now.
 */
const availableColors = [
  'red',
  'orange',
  'amber',
  'yellow',
  'lime',
  'green',
  'emerald',
  'teal',
  'cyan',
  'sky',
  'blue',
  'indigo',
  'violet',
  'purple',
  'fuchsia',
  'pink',
  'rose',
  'white',
  'gray',
  'black',
];

const semanticColors = {
  neutral: 'slate',
  brand: 'red',
  accent: 'blue',
  info: 'blue',
  success: 'emerald',
  warning: 'orange',
  danger: 'red',
};

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

function buildColorableTokens() {
  return availableColors
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

function buildSemanticTokens() {
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

function generateStyles(colors) {
  return `/* Auto-generated by scripts/generate-colors.js — do not edit manually */

:root {
${buildColorableTokens()}
${buildSemanticTokens()}
}

${[...availableColors, ...semanticColors].map((c) => buildStyleBlock(c)).join('\n')}
`;
}

const css = generateStyles(availableColors);
writeFileSync(OUT_FILE, css);
console.log(`Generated ${OUT_FILE}`);
