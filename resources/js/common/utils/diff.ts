export interface DiffPart {
  type: 'equal' | 'insert' | 'delete';
  text: string;
}

/**
 * Beyond this many comparisons the changed middle is shown as one deletion
 * and one insertion, rather than diffed word by word.
 */
const MAX_COMPARISONS = 2_000_000;

/**
 * Diffs two texts word by word, keeping whitespace, so the parts joined back
 * together give the original texts.
 */
export function diffWords(before: string, after: string): DiffPart[] {
  const a = tokenize(before);
  const b = tokenize(after);

  let start = 0;
  while (start < a.length && start < b.length && a[start] === b[start]) {
    start++;
  }

  let endA = a.length;
  let endB = b.length;
  while (endA > start && endB > start && a[endA - 1] === b[endB - 1]) {
    endA--;
    endB--;
  }

  const parts: DiffPart[] = [];
  push(parts, 'equal', a.slice(0, start));

  const middleA = a.slice(start, endA);
  const middleB = b.slice(start, endB);

  if (middleA.length * middleB.length > MAX_COMPARISONS) {
    push(parts, 'delete', middleA);
    push(parts, 'insert', middleB);
  } else {
    for (const part of diffTokens(middleA, middleB)) {
      push(parts, part.type, [part.text]);
    }
  }

  push(parts, 'equal', a.slice(endA));

  return groupChanges(parts);
}

/**
 * Folds whitespace between two changes into them, and gives each run of
 * changes one deletion followed by one insertion, so edits read as phrases
 * instead of alternating words.
 */
function groupChanges(parts: DiffPart[]): DiffPart[] {
  const grouped: DiffPart[] = [];
  let deleted = '';
  let inserted = '';

  const flush = () => {
    if (deleted) grouped.push({type: 'delete', text: deleted});
    if (inserted) grouped.push({type: 'insert', text: inserted});
    deleted = inserted = '';
  };

  parts.forEach((part, index) => {
    const between =
      part.type === 'equal' &&
      /^\s+$/.test(part.text) &&
      (deleted || inserted) &&
      parts[index + 1] !== undefined &&
      parts[index + 1]!.type !== 'equal';

    if (part.type === 'delete' || between) {
      deleted += part.text;
    }

    if (part.type === 'insert' || between) {
      inserted += part.text;
    }

    if (part.type === 'equal' && !between) {
      flush();
      grouped.push(part);
    }
  });

  flush();

  return grouped;
}

function tokenize(text: string): string[] {
  return text.match(/\s+|[^\s]+/g) ?? [];
}

/** A longest-common-subsequence diff over tokens. */
function diffTokens(a: string[], b: string[]): DiffPart[] {
  const width = b.length + 1;
  const lengths = new Uint32Array((a.length + 1) * width);

  for (let i = a.length - 1; i >= 0; i--) {
    for (let j = b.length - 1; j >= 0; j--) {
      lengths[i * width + j] =
        a[i] === b[j]
          ? lengths[(i + 1) * width + j + 1]! + 1
          : Math.max(
              lengths[(i + 1) * width + j]!,
              lengths[i * width + j + 1]!
            );
    }
  }

  const parts: DiffPart[] = [];
  let i = 0;
  let j = 0;

  while (i < a.length && j < b.length) {
    if (a[i] === b[j]) {
      parts.push({type: 'equal', text: a[i++]!});
      j++;
    } else if (lengths[(i + 1) * width + j]! >= lengths[i * width + j + 1]!) {
      parts.push({type: 'delete', text: a[i++]!});
    } else {
      parts.push({type: 'insert', text: b[j++]!});
    }
  }

  while (i < a.length) parts.push({type: 'delete', text: a[i++]!});
  while (j < b.length) parts.push({type: 'insert', text: b[j++]!});

  return parts;
}

function push(
  parts: DiffPart[],
  type: DiffPart['type'],
  tokens: string[]
): void {
  const text = tokens.join('');

  if (!text) {
    return;
  }

  const last = parts.at(-1);

  if (last?.type === type) {
    last.text += text;
  } else {
    parts.push({type, text});
  }
}

export interface DiffPiece {
  kind: 'equal' | 'insert' | 'delete' | 'gap';
  text: string;
  /** Whether the text is part of a highlighted passage. */
  marked: boolean;
}

type Range = [number, number];

/** Unchanged text kept on each side of a change when collapsed. */
const CONTEXT = 80;

/** Hidden stretches shorter than this are shown anyway. */
const MIN_GAP = 40;

/** Returns the number of separate changes in a diff. */
function countEdits(parts: DiffPart[]): number {
  return parts.filter(
    (part, index) =>
      part.type !== 'equal' &&
      (index === 0 || parts[index - 1]!.type === 'equal')
  ).length;
}

/**
 * Turns a diff into pieces to render: long unchanged stretches collapse to
 * some context around each change and each highlighted passage, unless
 * `expanded` is set, and the highlighted passages of the current text are marked.
 * Also returns whether anything was collapsed, and the number of edits.
 */
export function renderDiff(
  current: string,
  proposed: string,
  highlights: string[] = [],
  expanded = false
): {pieces: DiffPiece[]; collapsed: boolean; edits: number} {
  const parts = diffWords(current, proposed);
  const ranges = highlights.flatMap((passage): Range[] => {
    const start = current.indexOf(passage);

    return start === -1 ? [] : [[start, start + passage.length]];
  });
  const pieces: DiffPiece[] = [];
  let offset = 0;
  let collapsed = false;

  parts.forEach((part, index) => {
    if (part.type === 'insert') {
      pieces.push({kind: 'insert', text: part.text, marked: false});

      return;
    }

    const start = offset;
    offset += part.text.length;

    if (
      part.type !== 'equal' ||
      expanded ||
      part.text.length <= CONTEXT * 2 + MIN_GAP
    ) {
      pieces.push(...highlight(part.type, part.text, start, ranges));

      return;
    }

    let cursor = 0;

    for (const [from, to] of keptWindows(
      part.text,
      start,
      index > 0,
      index < parts.length - 1,
      ranges
    )) {
      if (from > cursor) {
        pieces.push({kind: 'gap', text: '…', marked: false});
        collapsed = true;
      }

      pieces.push(
        ...highlight('equal', part.text.slice(from, to), start + from, ranges)
      );
      cursor = to;
    }

    if (cursor < part.text.length) {
      pieces.push({kind: 'gap', text: '…', marked: false});
      collapsed = true;
    }
  });

  return {pieces, collapsed, edits: countEdits(parts)};
}

/**
 * The parts of an unchanged stretch worth showing: some context next to the
 * changes on either side, and around each highlighted passage. Offsets are
 * relative to the stretch, widened to word breaks, and merged when close.
 */
function keptWindows(
  text: string,
  start: number,
  afterChange: boolean,
  beforeChange: boolean,
  ranges: Range[]
): Range[] {
  const windows: Range[] = [];

  if (afterChange) windows.push([0, CONTEXT]);
  if (beforeChange) windows.push([text.length - CONTEXT, text.length]);

  for (const [from, to] of ranges) {
    if (from < start + text.length && to > start) {
      windows.push([from - start - CONTEXT / 2, to - start + CONTEXT / 2]);
    }
  }

  const merged: Range[] = [];

  for (const [from, to] of windows
    .map(
      ([from, to]): Range => [
        wordStart(text, Math.max(from, 0)),
        wordEnd(text, Math.min(to, text.length)),
      ]
    )
    .sort((a, b) => a[0] - b[0])) {
    const last = merged.at(-1);

    if (last && from - last[1] < MIN_GAP) {
      last[1] = Math.max(last[1], to);
    } else {
      merged.push([from, to]);
    }
  }

  return merged;
}

/** Moves an offset back to the start of the word it's in. */
function wordStart(text: string, offset: number): number {
  const space = text.slice(0, offset).search(/\s\S*$/);

  return offset === 0 || space === -1 ? 0 : space + 1;
}

/** Moves an offset forward to the end of the word it's in. */
function wordEnd(text: string, offset: number): number {
  const space = text.slice(offset).search(/\s/);

  return space === -1 ? text.length : offset + space;
}

/** Splits a stretch of the current text into highlighted and plain pieces. */
function highlight(
  kind: 'equal' | 'delete',
  text: string,
  start: number,
  ranges: Range[]
): DiffPiece[] {
  const overlaps = (from: number, to: number) =>
    ranges.some(([rangeFrom, rangeTo]) => rangeFrom < to && rangeTo > from);
  const bounds = new Set<number>([0, text.length]);

  for (const [from, to] of ranges) {
    bounds.add(Math.min(Math.max(from - start, 0), text.length));
    bounds.add(Math.min(Math.max(to - start, 0), text.length));
  }

  const points = [...bounds].sort((a, b) => a - b);

  return points.slice(1).flatMap((end, index): DiffPiece[] => {
    const from = points[index]!;

    return end > from
      ? [
          {
            kind,
            text: text.slice(from, end),
            marked: overlaps(start + from, start + end),
          },
        ]
      : [];
  });
}
