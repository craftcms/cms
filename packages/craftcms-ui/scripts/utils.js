import {existsSync, readFileSync, writeFileSync} from 'fs';
import {globby} from 'globby';
import {dirname} from 'path';
import {fileURLToPath} from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
export const getRootDir = () => process.env.ROOT_DIR || dirname(__dirname);
export const getDistDir = () => process.env.DIST_DIR || `${getRootDir()}/dist`;

/**
 * Writes a file only if its contents changed, and returns whether it did.
 */
export function writeFileIfChanged(path, content) {
  if (existsSync(path) && readFileSync(path, 'utf8') === content) {
    return false;
  }

  writeFileSync(path, content);
  return true;
}

export async function resolveFrom(pattern) {
  const files = await globby(pattern);

  return files.reduce((acc, file) => {
    const segments = file.split('/');
    const [_, src, ...rest] = segments;
    const name = segments.pop()?.replace('.ts', '');
    if (!name) {
      return acc;
    }

    acc[rest.join('/')] = file;
    return acc;
  }, {});
}

/** The cards, filled chips, and badges in a color, which have a surface of their own. */
function surfaces(color) {
  return [
    `craft-card[data-color='${color}']`,
    `craft-chip[data-color='${color}']:not([appearance~='plain'])`,
    `craft-badge[fill='${color}']`,
  ];
}

const inLightTheme = (selector) => `${selector}:not([data-theme='dark'] *)`;
const inDarkTheme = (selector) => `[data-theme='dark'] ${selector}`;

function contentsSelector(selectors) {
  return `:where(\n  ${selectors.map((selector) => `${selector} > *`).join(',\n  ')}\n)`;
}

/**
 * What's in a surface that looks dark: a black card, badge, or (filled) chip in
 * the light theme, and a white one in the dark theme, where white and black
 * swap. It's themed dark, so it holds up against the dark fill wherever the
 * surface is nested. Zero specificity, so the contents' own colors still win.
 * Matches the copies in tokens.css and variables.css.
 */
export const DARK_SURFACE_CONTENTS_SELECTOR = contentsSelector([
  ...surfaces('black').map(inLightTheme),
  ...surfaces('white').map(inDarkTheme),
]);

/**
 * What's in a surface that looks light: a white card, badge, or (filled) chip
 * in the light theme, and a black one in the dark theme. It's themed light, so
 * a light surface nested in a dark one doesn't take its dark theme. Matches the
 * copies in tokens.css and variables.css.
 */
export const LIGHT_SURFACE_CONTENTS_SELECTOR = contentsSelector([
  ...surfaces('white').map(inLightTheme),
  ...surfaces('black').map(inDarkTheme),
]);

/**
 * A white, gray, or slate card, which takes the page's colors rather than a
 * tint. Tinted cards recolor what's in them through inherited custom
 * properties, so these re-resolve the tokens on themselves, or a neutral card
 * nested in a tinted one would take the tint. Zero specificity, like the
 * selector above. tokens.css re-resolves its text tokens for the same cards.
 */
export const NEUTRAL_CARD_SELECTOR = `:where(
  craft-card:is([data-color='white'], [data-color='gray'], [data-color='slate'])
)`;
