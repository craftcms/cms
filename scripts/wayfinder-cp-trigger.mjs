#!/usr/bin/env node
/**
 * Rewrites Wayfinder-generated route modules so their URLs respect the
 * runtime-configured CP trigger (`Cms::config()->cpTrigger`) instead of the
 * `/admin` prefix baked in at generation time.
 *
 * Every generated `*.url()` body ends in `+ queryParams(options)` (and `return`
 * appears nowhere else in generated files), so the base path can be reliably
 * wrapped in `cpUrl(...)` from `resources/js/wayfinder/cp-trigger.ts`.
 *
 * This runs as a Vite `transform` hook (see `vite.config.js`) rather than a
 * post-generation file rewrite: the `@laravel/vite-plugin-wayfinder` plugin
 * regenerates these files on the fly, which would clobber any on-disk edits.
 * Transforming at import time keeps the generated files pristine while still
 * applying to both dev and production builds.
 */

/**
 * Wraps a generated route module's URL bases in `cpUrl(...)` and injects the
 * import. Returns the transformed source, or `null` when nothing applies (no
 * URL builders, or already transformed).
 *
 * @param {string} code
 * @returns {string | null}
 */
export function transformWayfinderSource(code) {
  // Nothing to wrap, or already processed.
  if (!code.includes(' + queryParams(options)') || code.includes('cpUrl(')) {
    return null;
  }

  // Wrap each url() base path (everything between `return ` and the trailing
  // `+ queryParams(options)`) in cpUrl().
  const wrapped = code.replace(
    /return ([\s\S]*?) \+ queryParams\(options\)/g,
    'return cpUrl($1) + queryParams(options)'
  );

  if (wrapped === code) {
    return null;
  }

  // Add the cpUrl import as a sibling of the existing wayfinder import, reusing
  // its (depth-specific) relative module specifier.
  return wrapped.replace(
    /^(import .*from '(.*\/wayfinder)')$/m,
    "$1\nimport { cpUrl } from '$2/cp-trigger'"
  );
}

/** True for Wayfinder-generated route module ids that should be transformed. */
export function isWayfinderRouteModule(id) {
  const normalized = id.split('?')[0].replace(/\\/g, '/');
  return (
    /\/resources\/js\/(actions|routes)\//.test(normalized) &&
    normalized.endsWith('.ts')
  );
}
