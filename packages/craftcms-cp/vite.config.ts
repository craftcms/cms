import {dirname, resolve} from 'node:path';
import {fileURLToPath} from 'node:url';
import {defineConfig} from 'vite';
import {globby} from 'globby';
// @ts-ignore
import dts from 'unplugin-dts/vite';

const __dirname = dirname(fileURLToPath(import.meta.url));

async function resolveFrom(pattern: string) {
  const files = await globby(pattern);

  return files.reduce((acc: Record<string, string>, file) => {
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

export default defineConfig({
  build: {
    sourcemap: true,
    lib: {
      entry: {
        cp: resolve(__dirname, 'src/index.ts'),
        ...(await resolveFrom(
          './src/components/**/!(*.(stories|style|test)).ts'
        )),
        ...(await resolveFrom('./src/services/**/!(*.(style|test)).ts')),
        ...(await resolveFrom('./src/utilities/**/!(*.(style|test)).ts')),
      },
      name: 'Cp',
    },
    emptyOutDir: true,
    rollupOptions: {},
  },
  plugins: [dts({entryRoot: './src'})],
});
