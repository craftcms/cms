import {readdirSync, readFileSync} from 'node:fs';
import {extname, join, relative, resolve} from 'node:path';
import {breakpointsTailwind} from '@vueuse/core';
import {afterEach, expect, it} from 'vite-plus/test';
import {useCpBreakpoints} from './useCpBreakpoints';

const VARIABLES = resolve(
  import.meta.dirname,
  '../../../../packages/craftcms-ui/src/styles/shared/variables.css'
);

/** The `--breakpoint-*` declarations the stylesheets publish, in px. */
function declaredBreakpoints(): Record<string, number> {
  const css = readFileSync(VARIABLES, 'utf8');
  const found: Record<string, number> = {};

  for (const [, name, value] of css.matchAll(
    /--breakpoint-([a-z0-9]+):\s*([0-9.]+)rem;/g
  )) {
    if (name && value) {
      found[name] = Number(value) * 16;
    }
  }

  return found;
}

afterEach(() => {
  document.documentElement.style.removeProperty('--breakpoint-lg');
});

it('publishes the same breakpoints the JS scale uses', () => {
  const declared = declaredBreakpoints();

  expect(Object.keys(declared).length).toBeGreaterThan(0);

  for (const [name, px] of Object.entries(declared)) {
    expect(breakpointsTailwind[name as keyof typeof breakpointsTailwind]).toBe(
      px
    );
  }
});

it('takes its widths from the stylesheet rather than a copy of the scale', () => {
  document.documentElement.style.setProperty('--breakpoint-lg', '1234px');

  const {lg} = useCpBreakpoints();

  expect(lg.value).toBe(window.innerWidth >= 1234);
});

const ROOT = resolve(import.meta.dirname, '../../../..');

/**
 * A viewport media query names its width through `var(--breakpoint-*)`, which
 * the PostCSS plugin substitutes. A literal is how the CP ends up folding at
 * one width in CSS and another in JS, so it has to be listed below with a
 * reason.
 */

const EXCEPTIONS: Record<string, string> = {
  // Below `sm`, which Tailwind's scale doesn't reach; the toolbar needs a
  // stage between stacked and its `sm` row.
  'resources/js/modules/elements/components/ElementIndexToolbar.vue': '480px',
  // Predates the scale.
  'resources/css/global-sidebar.css': '1999px',
  'resources/css/notifications.css': 'calc(600rem / 16)',
};

function stylesheets(dir: string, found: string[] = []): string[] {
  for (const entry of readdirSync(dir, {withFileTypes: true})) {
    const path = join(dir, entry.name);

    if (entry.isDirectory()) {
      stylesheets(path, found);
    } else if (['.vue', '.css'].includes(extname(entry.name))) {
      found.push(path);
    }
  }

  return found;
}

it('keeps viewport media queries on the shared breakpoints', () => {
  const offenders: string[] = [];
  const files = [
    ...stylesheets(join(ROOT, 'resources/js')),
    ...stylesheets(join(ROOT, 'resources/css')),
  ];

  for (const file of files) {
    const name = relative(ROOT, file);

    for (const [query] of readFileSync(file, 'utf8').matchAll(/@media[^{]+/g)) {
      for (const [length] of query.matchAll(/[0-9.]+(?:px|rem)/g)) {
        if (!length || EXCEPTIONS[name]?.includes(length)) {
          continue;
        }

        offenders.push(`${name}: ${query.trim()}`);
      }
    }
  }

  expect(offenders).toEqual([]);
});

it('references breakpoints in a form the plugin substitutes', () => {
  const malformed: string[] = [];
  const files = [
    ...stylesheets(join(ROOT, 'resources/js')),
    ...stylesheets(join(ROOT, 'resources/css')),
  ];

  for (const file of files) {
    const name = relative(ROOT, file);

    for (const [query] of readFileSync(file, 'utf8').matchAll(/@media[^{]+/g)) {
      if (!query.includes('--breakpoint-')) {
        continue;
      }

      // Anything but a bare `var(--breakpoint-*)` ships a query that can never
      // match — `--var(…)` reads as valid enough to miss by eye.
      const withoutRefs = query.replace(
        /(?<![\w-])var\(--breakpoint-[a-z0-9]+\)/g,
        ''
      );

      if (withoutRefs.includes('--breakpoint-')) {
        malformed.push(`${name}: ${query.trim()}`);
      }
    }
  }

  expect(malformed).toEqual([]);
});
