// Vendored jQuery UMD bundle. Its browser-global branch self-registers
// `window.jQuery` and `window.$` when evaluated — the `legacyUmdGlobalThis`
// Vite plugin shadows `module`/`exports`/`require` so that branch always wins.
//
// Kept in its own module so the registration completes before any jQuery
// plugin import evaluates (see legacy-jquery.ts for the ordering rationale).
import '../../legacy/jquery/dist/jquery.js';
