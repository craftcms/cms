import {defineConfig} from 'vite-plus';

export default defineConfig({
  pack: {
    entry: {
      index: './src/index.ts',
      compat: './src/compat.ts',
    },
    format: ['esm'],
    target: 'es2022',
    platform: 'browser',
    dts: true,
    sourcemap: true,
    clean: true,
    treeshake: true,
    minify: false,
    deps: {
      neverBundle: ['jquery'],
    },
  },
});
