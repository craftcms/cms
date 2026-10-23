import {diffWordsWithSpace} from 'diff';
import {describe, expect, it, vi} from 'vite-plus/test';
import {renderDiff, type DiffPiece} from './diff';

vi.mock('diff', async (importOriginal) => {
  const actual = await importOriginal<typeof import('diff')>();

  return {...actual, diffWordsWithSpace: vi.fn(actual.diffWordsWithSpace)};
});

const plain = (kind: DiffPiece['kind'], text: string): DiffPiece => ({
  kind,
  parts: [{text, marked: false}],
});

const textOf = (pieces: DiffPiece[]) =>
  pieces.flatMap((piece) => piece.parts.map((part) => part.text)).join('');

describe('renderDiff', () => {
  it('counts each separately changed phrase as one edit', () => {
    expect(
      renderDiff(
        'The quick brown fox jumps over the dog.',
        'The slow red fox jumps over the lazy dog.'
      ).edits
    ).toBe(2);
  });

  it('shows a rewritten phrase as one deletion and one insertion', () => {
    expect(
      renderDiff('The quick brown fox.', 'The slow red fox.').pieces
    ).toEqual([
      plain('equal', 'The '),
      plain('delete', 'quick brown'),
      plain('insert', 'slow red'),
      plain('equal', ' fox.'),
    ]);
  });

  it('shows new text, with no current text, as it is, rather than as an insertion', () => {
    expect(renderDiff('', 'A kitten was shivering.').pieces).toEqual([
      plain('equal', 'A kitten was shivering.'),
    ]);
  });

  it('collapses long unchanged text around highlighted passages, until expanded', () => {
    const filler = 'Filler words pad out this paragraph nicely. '.repeat(10);
    const current = `Old start. ${filler}A highlighted passage. ${filler}`;
    const proposed = `New start. ${filler}A highlighted passage. ${filler}`;
    const quotes = ['A highlighted passage.'];

    const collapsed = renderDiff(current, proposed, quotes);
    const expanded = renderDiff(current, proposed, quotes, true);

    expect(
      collapsed.pieces.flatMap((piece) =>
        piece.parts.filter((part) => part.marked)
      )
    ).toEqual([{text: 'A highlighted passage.', marked: true}]);
    expect(textOf(collapsed.pieces).length).toBeLessThan(filler.length * 2);
    expect(collapsed.collapsible).toBe(true);
    // Expanded, it can still be collapsed again.
    expect(expanded.pieces.some((piece) => piece.kind === 'gap')).toBe(false);
    expect(expanded.collapsible).toBe(true);
  });

  it('shows texts that take too long to diff as one deletion and one insertion', () => {
    // As the package does when it times out.
    vi.mocked(diffWordsWithSpace).mockReturnValueOnce(undefined as never);

    expect(renderDiff('Old text.', 'New text.')).toEqual({
      pieces: [plain('delete', 'Old text.'), plain('insert', 'New text.')],
      collapsible: false,
      edits: 1,
    });
  });

  it('shows unchanged text in full, with nothing to collapse around', () => {
    const same = 'Unchanged words fill this paragraph. '.repeat(10);

    expect(renderDiff(same, same)).toEqual({
      pieces: [plain('equal', same)],
      collapsible: false,
      edits: 0,
    });
  });

  it('keeps edits in different paragraphs apart', () => {
    expect(
      renderDiff('Intro dog\n\nThen more', 'Intro cat\n\nLater more').pieces
    ).toEqual([
      plain('equal', 'Intro '),
      plain('delete', 'dog'),
      plain('insert', 'cat'),
      plain('equal', '\n\n'),
      plain('delete', 'Then'),
      plain('insert', 'Later'),
      plain('equal', ' more'),
    ]);
    expect(renderDiff('A B', 'C D').edits).toBe(2);
  });

  it('shows short stretches at the text’s start and end rather than collapsing them', () => {
    const quote = 'A quoted passage. '.repeat(15);
    const text = `Intro. ${'prefix '.repeat(6)}${quote}${'suffix '.repeat(6)}End.`;
    const {pieces, collapsible} = renderDiff(text, text, [quote]);

    expect(textOf(pieces)).toBe(text);
    expect(collapsible).toBe(false);
  });

  it('marks a quoted passage inside deleted text within the one deletion', () => {
    expect(
      renderDiff('A quick brown fox ran.', 'A slow fox ran.', ['brown']).pieces
    ).toEqual([
      plain('equal', 'A '),
      {
        kind: 'delete',
        parts: [
          {text: 'quick ', marked: false},
          {text: 'brown', marked: true},
          {text: ' ', marked: false},
        ],
      },
      plain('insert', 'slow '),
      plain('equal', 'fox ran.'),
    ]);
  });
});
