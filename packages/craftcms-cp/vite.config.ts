import {dirname, resolve} from 'node:path';
import {fileURLToPath} from 'node:url';
import {defineConfig} from 'vite';
import {globby} from 'globby';

const __dirname = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  build: {
    sourcemap: true,
    lib: {
      entry: {
        cp: resolve(__dirname, 'src/index.ts'),
        ...(await globby('./src/utilities/**/!(*.(style|test)).ts')).reduce(
          (acc: {[key: string]: string}, file) => {
            const name = file.split('/').pop()?.replace('.ts', '');

            if (!name) {
              return acc;
            }

            acc[name] = file;
            return acc;
          },
          {}
        ),
      },
      name: 'Cp',
    },
    emptyOutDir: true,
    rollupOptions: {},
  },
});
