import {diffWordsWithSpace, type Change} from 'diff';

/** A stretch of a piece's text. */
export interface DiffPart {
  text: string;
  /** Whether the text is part of a highlighted passage. */
  marked: boolean;
}

/** Unchanged, inserted or deleted text, or unchanged text that's collapsed. */
export interface DiffPiece {
  kind: 'equal' | 'insert' | 'delete' | 'gap';
  /** The text, split where highlighted passages start and end. */
  parts: DiffPart[];
}

type Range = [number, number];

/** Unchanged text kept on each side of a change when collapsed. */
const CONTEXT = 80;

/** Hidden stretches shorter than this are shown anyway. */
const MIN_GAP = 40;

/** How long, in milliseconds, diffing word by word may take, so the page doesn't stall. */
const DIFF_TIMEOUT = 100;

const isUnchanged = (change: Change): boolean =>
  !change.added && !change.removed;

/**
 * Diffs two texts word by word, with each rewritten phrase as one deletion and
 * one insertion. Whitespace is diffed too, so a new paragraph shows, and the
 * unchanged parts are the current text's own. Texts that take too long to diff
 * are shown as one deletion and one insertion.
 *
 * This is the slow step, so its result can be kept for {@link layoutDiff}.
 */
export function diffText(current: string, proposed: string): Change[] {
  const changes = diffWordsWithSpace(current, proposed, {
    timeout: DIFF_TIMEOUT,
  }) ?? [
    {value: current, added: false, removed: true, count: 1},
    {value: proposed, added: true, removed: false, count: 1},
  ];
  const grouped: Change[] = [];
  let removed = '';
  let added = '';

  const flush = () => {
    if (removed) {
      grouped.push({value: removed, added: false, removed: true, count: 1});
    }

    if (added) {
      grouped.push({value: added, added: true, removed: false, count: 1});
    }

    removed = added = '';
  };

  changes.forEach((change, index) => {
    const next = changes[index + 1];
    // Spaces between two changes belong to both sides of the phrase. A line
    // break doesn't, so edits in different paragraphs stay apart.
    const isBetween =
      isUnchanged(change) &&
      /^[^\S\r\n\u2028\u2029]+$/.test(change.value) &&
      Boolean(removed || added) &&
      next !== undefined &&
      !isUnchanged(next);

    if (change.removed || isBetween) {
      removed += change.value;
    }

    if (change.added || isBetween) {
      added += change.value;
    }

    if (isUnchanged(change) && !isBetween) {
      flush();
      grouped.push(change);
    }
  });

  flush();

  return grouped;
}

/** Returns the number of edits in a diff: a deletion and the insertion right after it are one. */
export function countEdits(changes: Change[]): number {
  return changes.filter(
    (change, index) =>
      !isUnchanged(change) && (index === 0 || isUnchanged(changes[index - 1]!))
  ).length;
}

/**
 * Diffs two texts and lays the diff out, as {@link diffText} and
 * {@link layoutDiff} do, and counts its edits.
 */
export function renderDiff(
  current: string,
  proposed: string,
  highlights: string[] = [],
  expanded = false
): {pieces: DiffPiece[]; collapsible: boolean; edits: number} {
  const changes = diffText(current, proposed);

  return {
    ...layoutDiff(current, changes, highlights, expanded),
    edits: current ? countEdits(changes) : 0,
  };
}

/**
 * Turns a diff of `current`, from {@link diffText}, into pieces to render: long
 * unchanged stretches collapse to some context around each change and each
 * highlighted passage, unless `expanded` is set, and the highlighted passages of
 * the current text are marked. New text, with no current text, is shown as it
 * is, rather than as one insertion, and so is text with nothing to show around.
 *
 * Also returns whether collapsing hides any text, expanded or not.
 */
export function layoutDiff(
  current: string,
  changes: Change[],
  highlights: string[] = [],
  expanded = false
): {pieces: DiffPiece[]; collapsible: boolean} {
  if (!current) {
    return {
      pieces: [
        {
          kind: 'equal',
          parts: [
            {
              text: changes.map((change) => change.value).join(''),
              marked: false,
            },
          ],
        },
      ],
      collapsible: false,
    };
  }

  const ranges = highlights.flatMap((passage): Range[] => {
    const start = current.indexOf(passage);

    return start === -1 ? [] : [[start, start + passage.length]];
  });
  const pieces: DiffPiece[] = [];
  let offset = 0;
  let collapsible = false;

  changes.forEach((change, index) => {
    const text = change.value;

    if (change.added) {
      pieces.push({kind: 'insert', parts: [{text, marked: false}]});

      return;
    }

    const start = offset;
    offset += text.length;

    if (change.removed || text.length <= CONTEXT * 2 + MIN_GAP) {
      pieces.push({
        kind: change.removed ? 'delete' : 'equal',
        parts: highlight(text, start, ranges),
      });

      return;
    }

    const kept: DiffPiece[] = [];
    const gap: DiffPiece = {kind: 'gap', parts: [{text: '…', marked: false}]};
    let cursor = 0;

    for (const [from, to] of keptWindows(
      text,
      start,
      index > 0,
      index < changes.length - 1,
      ranges
    )) {
      if (from > cursor) {
        kept.push(gap);
      }

      kept.push({
        kind: 'equal',
        parts: highlight(text.slice(from, to), start + from, ranges),
      });
      cursor = to;
    }

    if (cursor < text.length) {
      kept.push(gap);
    }

    collapsible ||= kept.includes(gap);
    pieces.push(
      ...(expanded
        ? [{kind: 'equal', parts: highlight(text, start, ranges)} as DiffPiece]
        : kept)
    );
  });

  return {pieces, collapsible};
}

/**
 * The parts of an unchanged stretch worth showing: some context next to the
 * changes on either side, and around each highlighted passage. Offsets are
 * relative to the stretch, widened to word breaks, and merged when close, or
 * extended to the stretch's ends when close to them. With nothing to show
 * around, all of it is kept.
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

  if (!windows.length) {
    return [[0, text.length]];
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

  if (merged[0]![0] < MIN_GAP) {
    merged[0]![0] = 0;
  }

  if (text.length - merged.at(-1)![1] < MIN_GAP) {
    merged.at(-1)![1] = text.length;
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

/** Splits a stretch of the current text into highlighted and plain parts. */
function highlight(text: string, start: number, ranges: Range[]): DiffPart[] {
  const overlaps = (from: number, to: number) =>
    ranges.some(([rangeFrom, rangeTo]) => rangeFrom < to && rangeTo > from);
  const bounds = new Set<number>([0, text.length]);

  for (const [from, to] of ranges) {
    bounds.add(Math.min(Math.max(from - start, 0), text.length));
    bounds.add(Math.min(Math.max(to - start, 0), text.length));
  }

  const points = [...bounds].sort((a, b) => a - b);

  return points.slice(1).flatMap((end, index): DiffPart[] => {
    const from = points[index]!;

    return end > from
      ? [
          {
            text: text.slice(from, end),
            marked: overlaps(start + from, start + end),
          },
        ]
      : [];
  });
}
