import {defineConfig} from 'vite';

/**
 * Dev-only Vite config for the interactive playground.
 *
 * This is ADDITIVE and never part of the shipped package: the npm package is
 * built by tsdown (`npm run build`) and tested by Vitest. Vite exists purely to
 * serve `playground/` with HMR (`npm run dev`) and to compile-verify it in CI
 * (`npm run playground:build`).
 *
 * `root: 'playground'` makes `playground/index.html` the dev entrypoint. The
 * playground imports `../src/*` directly, so edits to the real source hot-reload
 * instantly with no build step.
 */
export default defineConfig({
  root: 'playground',
  build: {
    // Keep playground build artifacts out of the package's `dist/` (tsdown owns
    // that). Emitted relative to `root`, i.e. `playground/dist`.
    outDir: 'dist',
    emptyOutDir: true,
  },
});
