import {globby} from 'globby';
import {dirname} from 'path';
import {fileURLToPath} from 'node:url';

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
