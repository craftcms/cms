import {globby} from 'globby';
import {readFileSync} from 'fs';
import {dirname, resolve} from 'path';
import {fileURLToPath} from 'node:url';
import {transformSync} from 'esbuild';

const __dirname = dirname(fileURLToPath(import.meta.url));
export const getRootDir = () => process.env.ROOT_DIR || dirname(__dirname);
export const getDistDir = () => process.env.DIST_DIR || `${getRootDir()}/dist`;

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

/*
The canonical color data lives in src/constants/colors.data.ts (shared with
constants/colors.ts). Scripts can't import TypeScript directly, so we transpile
it with esbuild and import the result.
 */
export async function loadColorData() {
  const dataFile = resolve(getRootDir(), 'src/constants/colors.data.ts');
  const {code} = transformSync(readFileSync(dataFile, 'utf8'), {
    loader: 'ts',
    format: 'esm',
  });
  return import(
    `data:text/javascript;base64,${Buffer.from(code).toString('base64')}`
  );
}
