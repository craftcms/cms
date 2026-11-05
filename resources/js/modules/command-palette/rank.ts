import type {CommandPaletteItem} from './types';

/** Lowercased, accents removed, so “Café” matches “cafe”. */
export function normalize(value: string): string {
  return value
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLowerCase()
    .trim();
}

function isSubsequence(needle: string, haystack: string): boolean {
  let index = 0;

  for (const char of haystack) {
    if (char === needle[index]) {
      index++;

      if (index === needle.length) {
        return true;
      }
    }
  }

  return needle.length === 0;
}

/**
 * How closely a label matches the query: an exact match beats a prefix,
 * which beats a word that starts with the query, which beats the query
 * appearing anywhere, which beats its letters appearing in order. Zero when
 * none of those hold.
 *
 * With `loose: false`, letters in order don't count, for results listed
 * ahead of the rest, which a stray match would otherwise put at the top.
 */
export function labelScore(
  label: string,
  query: string,
  {loose = true}: {loose?: boolean} = {}
): number {
  const haystack = normalize(label);
  const needle = normalize(query);

  if (!needle) {
    return 0;
  }

  if (haystack === needle) {
    return 1000;
  }

  if (haystack.startsWith(needle)) {
    return 800;
  }

  const words = needle.split(/\s+/);
  const labelWords = haystack.split(/[\s\-_/→]+/);
  let total = 0;

  for (const word of words) {
    if (labelWords.some((labelWord) => labelWord.startsWith(word))) {
      total += 600;
    } else if (haystack.includes(word)) {
      total += 400;
    } else if (loose && isSubsequence(word, haystack.replace(/\s+/g, ''))) {
      total += 100;
    } else {
      return 0;
    }
  }

  return total / words.length;
}

/**
 * Whether every word of the query appears in the item's label, description,
 * hint or keywords, ignoring case and accents — the same test the server's
 * providers use, so an item matches the same wherever it's searched.
 */
export function matchesWords(item: CommandPaletteItem, query: string): boolean {
  const haystack = normalize(
    [item.label, item.description, item.hint, item.keywords]
      .filter(Boolean)
      .join(' ')
  );

  return normalize(query)
    .split(/\s+/)
    .filter(Boolean)
    .every((word) => haystack.includes(word));
}

/** Whether choosing the item destroys or disables something. */
export function isDestructive(item: CommandPaletteItem): boolean {
  return item.variant === 'danger';
}

/** The items in their own order, except that destructive ones go last. */
export function destructiveLast(
  items: CommandPaletteItem[]
): CommandPaletteItem[] {
  return [
    ...items.filter((item) => !isDestructive(item)),
    ...items.filter(isDestructive),
  ];
}

/**
 * Orders items by how closely their labels match the query.
 *
 * Providers have already decided each item matches — on its description,
 * hint, keywords or content, say — so one whose label doesn't still makes the list,
 * after those whose labels do. Among equal matches, destructive items go
 * last and shorter labels win, being closer to what was typed; otherwise the
 * providers' own order holds.
 */
export function rankItems(
  items: CommandPaletteItem[],
  query: string
): CommandPaletteItem[] {
  const normalizedQuery = normalize(query);

  return items
    .map((item, index) => {
      let score = labelScore(item.label, query);

      if (
        score === 0 &&
        [item.description, item.hint, item.keywords].some(
          (text) => text && normalize(text).includes(normalizedQuery)
        )
      ) {
        score = 50;
      }

      return {item, index, score};
    })
    .sort(
      (a, b) =>
        b.score - a.score ||
        Number(isDestructive(a.item)) - Number(isDestructive(b.item)) ||
        a.item.label.length - b.item.label.length ||
        a.index - b.index
    )
    .map(({item}) => item);
}
