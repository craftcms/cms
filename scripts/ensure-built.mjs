import {existsSync} from 'node:fs';
import {execSync} from 'node:child_process';

// One-time bootstrap for `pnpm run dev`: build artifacts that the dev server
// depends on but that no watcher produces automatically. No-op when present.

const run = (command) => execSync(command, {stdio: 'inherit'});

// The Vite app resolves @craftcms/garnish types (and production builds resolve
// its code) from dist/.
if (!existsSync('packages/craftcms-garnish/dist/index.js')) {
  run('pnpm run build:garnish');
}

// resources/js/legacy.ts imports the webpack-built legacy CP CSS, so the dev
// server fails without at least one legacy build. The legacy build itself
// imports @craftcms/ui dist files, so cp must be built first.
if (!existsSync('cms-assets/resources/legacy/cp/dist/css/cp.css')) {
  if (!existsSync('packages/craftcms-ui/dist/cp.mjs')) {
    run('pnpm run build:ui');
  }
  run('pnpm run build:bundles');
}
