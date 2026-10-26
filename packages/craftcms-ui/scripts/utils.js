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

/**
 * What's in a black card, badge, or (filled) chip, which is themed dark so it
 * holds up against the dark fill. Zero specificity, so the contents' own colors
 * still win. Matches the copies in tokens.css and variables.css.
 */
export const BLACK_CONTENTS_SELECTOR = `:where(
  craft-card[data-color='black'] > *,
  craft-chip[data-color='black']:not([appearance~='plain']) > *,
  craft-badge[fill='black'] > *
)`;

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
