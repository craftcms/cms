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

const semanticColors = [
  // Semantic colors
  'neutral',
  'brand',
  'accent',
  'info',
  'success',
  'warning',
  'danger',
];

function lightScale(color) {
  switch (color) {
    case 'white':
      return {
        fillQuiet: 'var(--color-white)',
        fillNormal: 'var(--color-white)',
        fillLoud: 'var(--color-white)',
        borderQuiet: 'var(--color-gray-200)',
        borderNormal: 'var(--color-gray-200)',
        borderLoud: 'var(--color-gray-200)',
        onQuiet: 'var(--color-gray-800)',
        onNormal: 'var(--color-gray-800)',
        onLoud: 'var(--color-gray-800)',
      };
    case 'black':
      return {
        fillQuiet: 'var(--color-gray-900)',
        fillNormal: 'var(--color-gray-900)',
        fillLoud: 'var(--color-gray-900)',
        borderQuiet: 'var(--color-gray-800)',
        borderNormal: 'var(--color-gray-800)',
        borderLoud: 'var(--color-gray-800)',
        onQuiet: 'var(--color-gray-100)',
        onNormal: 'var(--color-gray-100)',
        onLoud: 'var(--color-gray-100)',
      };
    default:
      return {
        fillQuiet: `var(--color-${color}-50)`,
        fillNormal: `var(--color-${color}-200)`,
        fillLoud: `var(--color-${color}-600)`,
        borderQuiet: `var(--color-${color}-300)`,
        borderNormal: `var(--color-${color}-600)`,
        borderLoud: `var(--color-${color}-800)`,
        onQuiet: `var(--color-${color}-800)`,
        onNormal: `var(--color-${color}-700)`,
        onLoud: `var(--color-${color}-50)`,
      };
  }
}

function darkScale(color) {
  switch (color) {
    case 'white':
      return {
        fillQuiet: 'var(--color-gray-800)',
        fillNormal: 'var(--color-gray-800)',
        fillLoud: 'var(--color-gray-800)',
        borderQuiet: 'var(--color-gray-700)',
        borderNormal: 'var(--color-gray-700)',
        borderLoud: 'var(--color-gray-700)',
        onQuiet: 'var(--color-gray-200)',
        onNormal: 'var(--color-gray-200)',
        onLoud: 'var(--color-gray-200)',
      };
    case 'black':
      return {
        fillQuiet: 'var(--color-gray-950)',
        fillNormal: 'var(--color-gray-950)',
        fillLoud: 'var(--color-gray-950)',
        borderQuiet: 'var(--color-gray-800)',
        borderNormal: 'var(--color-gray-800)',
        borderLoud: 'var(--color-gray-800)',
        onQuiet: 'var(--color-gray-300)',
        onNormal: 'var(--color-gray-300)',
        onLoud: 'var(--color-gray-300)',
      };
    default:
      return {
        fillQuiet: `var(--color-${color}-950)`,
        fillNormal: `var(--color-${color}-600)`,
        fillLoud: `var(--color-${color}-500)`,
        borderQuiet: `var(--color-${color}-900)`,
        borderNormal: `var(--color-${color}-900)`,
        borderLoud: `var(--color-${color}-600)`,
        onQuiet: `var(--color-${color}-400)`,
        onNormal: `var(--color-${color}-200)`,
        onLoud: `var(--color-${color}-50)`,
      };
  }
}

function buildTokens(colors, scaleFn) {
  return colors
    .map((color) => {
      const s = scaleFn(color);
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
${buildTokens(colors, lightScale)}
}

[data-theme='dark'] {
${buildTokens(colors, darkScale)}
}

${[...availableColors, ...semanticColors].map((c) => buildStyleBlock(c)).join('\n')}
`;
}

const css = generateStyles(availableColors);
writeFileSync(OUT_FILE, css);
console.log(`Generated ${OUT_FILE}`);
