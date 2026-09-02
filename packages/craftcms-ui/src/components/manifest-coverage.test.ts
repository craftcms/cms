import {existsSync, readFileSync} from 'node:fs';
import {join} from 'node:path';
import {describe, expect, it} from 'vite-plus/test';

/**
 * The custom elements manifest is the canonical description of this package:
 * Storybook's controls and API tables are generated from it, editors read it
 * for IntelliSense, and consumers read it to find out what an element takes.
 * Everything in it comes from JSDoc in the source, which means a new property
 * lands undocumented by default and nothing complains.
 *
 * These checks hold the line at fully described. They read the built manifest,
 * so run `npm run build:manifest` first — the `pretest` and `prestorybook`
 * hooks already do.
 */

const MANIFEST = join(import.meta.dirname, '../../dist/custom-elements.json');

interface Described {
  name?: string;
  description?: string;
}

interface Declaration extends Described {
  tagName?: string;
  customElement?: boolean;
  summary?: string;
  attributes?: Described[];
  slots?: Described[];
  events?: Described[];
  cssParts?: Described[];
  cssProperties?: Described[];
}

const manifest = existsSync(MANIFEST)
  ? (JSON.parse(readFileSync(MANIFEST, 'utf8')) as {
      modules: {declarations?: Declaration[]}[];
    })
  : null;

const elements = (manifest?.modules ?? [])
  .flatMap((module) => module.declarations ?? [])
  .filter((declaration) => declaration.customElement)
  .sort((a, b) => (a.tagName ?? '').localeCompare(b.tagName ?? ''));

const cases = elements.map((element) => ({
  tag: element.tagName ?? element.name ?? 'unknown',
  element,
}));

/**
 * Placeholder API from the component scaffold. `craft-status` shipped with all
 * of it — a slot, a part, a CSS property, and an event that never existed —
 * described in the manifest as though it were real, which is worse than being
 * undocumented.
 */
const SCAFFOLD_NAMES = new Set(['example', 'craft-event-name', '--example']);
const SCAFFOLD_TEXT = /\b(lorem ipsum|some new status|tbd|todo|fixme)\b/i;

describe('the manifest is built', () => {
  it('exists', () => {
    expect(
      manifest,
      `${MANIFEST} is missing — run \`npm run build:manifest\``
    ).not.toBeNull();
  });

  /** A guard that silently matches nothing would be worse than none at all. */
  it('describes every custom element in the package', () => {
    expect(elements.length).toBeGreaterThan(60);
  });
});

describe('every element says what it is', () => {
  it.each(cases)('$tag', ({element}) => {
    expect(
      element.summary ?? element.description ?? '',
      `${element.tagName} has no @summary, so it is blank in the docs and in IntelliSense`
    ).not.toBe('');
  });
});

describe('every part of an element API is described', () => {
  it.each(cases)('$tag', ({element}) => {
    const undescribed: string[] = [];

    for (const [kind, entries] of [
      ['attribute', element.attributes],
      ['slot', element.slots],
      ['event', element.events],
      ['CSS part', element.cssParts],
      ['CSS property', element.cssProperties],
    ] as const) {
      for (const entry of entries ?? []) {
        if (!entry.description) {
          undescribed.push(`${kind} ${entry.name ?? '(unnamed)'}`);
        }
      }
    }

    expect(undescribed).toEqual([]);
  });
});

describe('no scaffold placeholders survive into the manifest', () => {
  it.each(cases)('$tag', ({element}) => {
    const entries = [
      ...(element.attributes ?? []),
      ...(element.slots ?? []),
      ...(element.events ?? []),
      ...(element.cssParts ?? []),
      ...(element.cssProperties ?? []),
    ];

    const placeholders = entries
      .filter(
        (entry) =>
          SCAFFOLD_NAMES.has(entry.name ?? '') ||
          SCAFFOLD_TEXT.test(entry.description ?? '')
      )
      .map((entry) => entry.name);

    expect(placeholders).toEqual([]);
    expect(SCAFFOLD_TEXT.test(element.summary ?? '')).toBe(false);
  });
});
