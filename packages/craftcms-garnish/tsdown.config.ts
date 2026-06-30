import {defineConfig} from 'tsdown';

export default defineConfig({
  // Multiple entry points so consumers can opt into the legacy compatibility
  // layer separately from the modern core. tsdown/Rolldown will code-split
  // shared chunks between these automatically.
  entry: {
    index: './src/index.ts',
    compat: './src/compat.ts',
  },
  format: ['esm'],
  target: 'es2022',
  platform: 'browser',
  // Emit TypeScript declarations alongside the JS output.
  dts: true,
  sourcemap: true,
  // Clean dist before each build.
  clean: true,
  // Tree-shakeable, ESM-only output.
  treeshake: true,
  // Keep readable output during the scaffold phase; flip on for releases.
  minify: false,
  // No external deps yet — this is a zero-dependency library by design
  // (explicitly no jQuery). List peerDependencies under `deps.neverBundle`
  // as they appear so they are not inlined into the bundle.
  // jQuery is an OPTIONAL peer dependency of the `./compat` entry only. It is
  // resolved at runtime from the global scope, never imported, so it can't leak
  // into the bundle — but list it here so the build never inlines it should a
  // future `import 'jquery'` be added.
  deps: {
    neverBundle: ['jquery'],
  },
});
