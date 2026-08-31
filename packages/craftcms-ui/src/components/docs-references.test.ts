import {globSync, readFileSync, readdirSync} from 'node:fs';
import {join} from 'node:path';
import {describe, expect, it} from 'vite-plus/test';

/**
 * A docs page references its stories by name — `<Canvas of={ChipStories.Icon}>`.
 * Rename or drop the story and MDX resolves `of` to `undefined`, which does not
 * fail the build: Storybook renders the whole page as an error instead, so the
 * component silently loses its documentation until someone opens it.
 *
 * Cross-check every reference against the story file's actual exports.
 */

const COMPONENTS = join(import.meta.dirname, '.');
const SRC = join(import.meta.dirname, '..');

/** `?path=/docs/<id>--docs` — how the pages link to each other. */
const CROSS_LINK = /\?path=\/docs\/([a-z0-9-]+)--docs/g;

/** `<name>Stories.Foo` — how the MDX pages address their stories. */
const REFERENCE = /\b[A-Za-z]+Stories\.([A-Za-z][A-Za-z0-9]*)/g;
const EXPORT = /^export const ([A-Za-z][A-Za-z0-9]*)\s*:/gm;

function matchAll(source: string, pattern: RegExp): string[] {
  return [...source.matchAll(pattern)].map((match) => match[1]!);
}

/** Every `[mdx, stories]` pair sitting in a component directory. */
function docPages(): Array<{component: string; mdx: string; stories: string}> {
  const pages: Array<{component: string; mdx: string; stories: string}> = [];

  for (const component of readdirSync(COMPONENTS, {withFileTypes: true})) {
    if (!component.isDirectory()) {
      continue;
    }

    const dir = join(COMPONENTS, component.name);
    const files = readdirSync(dir);
    const mdx = files.find((file) => file.endsWith('.mdx'));
    const stories = files.find((file) => file.endsWith('.stories.ts'));

    if (mdx && stories) {
      pages.push({
        component: component.name,
        mdx: join(dir, mdx),
        stories: join(dir, stories),
      });
    }
  }

  return pages;
}

const pages = docPages();

/**
 * Storybook's own id derivation: lowercase, non-alphanumerics collapsed to
 * dashes. Renaming a story group changes every id under it, so a cross-link
 * written before the move silently points at nothing.
 */
function docsId(title: string): string {
  return title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
}

/**
 * Every docs id the package declares — from story titles anywhere under `src`,
 * plus MDX pages that stand alone with their own `<Meta title>`.
 */
const knownIds = new Set(
  [
    ...globSync(join(SRC, '**/*.stories.ts')).flatMap((file) => {
      const title = /title:\s*'([^']+)'/.exec(readFileSync(file, 'utf8'))?.[1];
      return title ? [docsId(title)] : [];
    }),
    ...globSync(join(SRC, '**/*.mdx')).flatMap((file) => {
      const title = /<Meta\s+title="([^"]+)"/.exec(
        readFileSync(file, 'utf8')
      )?.[1];
      return title ? [docsId(title)] : [];
    }),
  ].filter(Boolean)
);

describe('docs pages reference stories that exist', () => {
  /** A guard that silently matches nothing would be worse than none at all. */
  it('finds the documented components', () => {
    expect(pages.length).toBeGreaterThan(0);
  });

  it.each(pages)('$component', ({mdx, stories}) => {
    const referenced = new Set(matchAll(readFileSync(mdx, 'utf8'), REFERENCE));
    const exported = new Set(matchAll(readFileSync(stories, 'utf8'), EXPORT));

    const missing = [...referenced].filter((name) => !exported.has(name));

    expect(missing).toEqual([]);
  });
});

describe('docs pages link to pages that exist', () => {
  it.each(pages)('$component', ({mdx}) => {
    const linked = matchAll(readFileSync(mdx, 'utf8'), CROSS_LINK);
    const dead = linked.filter((id) => !knownIds.has(id));

    expect(dead).toEqual([]);
  });
});
