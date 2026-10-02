import source from '../../../tailwind.css?raw';

/**
 * The Tailwind theme namespaces `tailwind.css` fills, longest first so
 * `border-color-*` and `background-color-*` aren't mistaken for `color-*`.
 */
export const themeNamespaces = [
  'background-color',
  'border-color',
  'z-index',
  'padding',
  'margin',
  'color',
  'space',
  'gap',
] as const;

export type ThemeNamespace = (typeof themeNamespaces)[number];

export interface ThemeEntry {
  namespace: ThemeNamespace;
  /** The name Tailwind appends to the utility, e.g. `quiet` in `border-quiet`. */
  name: string;
  /** The declared value, whitespace collapsed. */
  value: string;
  /** The first Craft token the value reads, e.g. `--c-color-border-quiet`. */
  token: string | null;
}

/**
 * Every mapping in `tailwind.css`'s `@theme` block, read from the file itself
 * so the reference can't drift from what the theme generates.
 */
export function themeEntries(css: string = source): ThemeEntry[] {
  const themeStart = css.indexOf('@theme');
  const body = (themeStart === -1 ? '' : css.slice(themeStart)).replace(
    /\/\*[\s\S]*?\*\//g,
    ''
  );

  const entries: ThemeEntry[] = [];

  for (const [, property, rawValue] of body.matchAll(
    /--([a-z0-9-]+):\s*([^;]+);/g
  )) {
    const namespace = themeNamespaces.find((candidate) =>
      property.startsWith(`${candidate}-`)
    );

    if (!namespace) {
      continue;
    }

    const value = rawValue
      .replace(/\s+/g, ' ')
      .replace(/\(\s+/g, '(')
      .replace(/\s+\)/g, ')')
      .trim();

    entries.push({
      namespace,
      name: property.slice(namespace.length + 1),
      value,
      token: value.match(/var\((--c-[\w-]+)/)?.[1] ?? null,
    });
  }

  return entries;
}

export function entriesIn(namespace: ThemeNamespace): ThemeEntry[] {
  return themeEntries().filter((entry) => entry.namespace === namespace);
}
