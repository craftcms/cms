import {describe, expect, it} from 'vite-plus/test';
import {diffWords, renderDiff} from './diff';

const join = (parts: ReturnType<typeof diffWords>, without: string) =>
  parts
    .filter((part) => part.type !== without)
    .map((part) => part.text)
    .join('');

describe('diffWords', () => {
  it('rebuilds both texts from its parts', () => {
    const before = 'A little kitten was tucked beneath a basket, shivering.';
    const after = 'A little kitten was shivering beneath a basket.';
    const parts = diffWords(before, after);

    expect(join(parts, 'insert')).toBe(before);
    expect(join(parts, 'delete')).toBe(after);
  });

  it('groups a changed phrase into one deletion and one insertion', () => {
    expect(diffWords('the quick brown fox', 'the slow red fox')).toEqual([
      {type: 'equal', text: 'the '},
      {type: 'delete', text: 'quick brown'},
      {type: 'insert', text: 'slow red'},
      {type: 'equal', text: ' fox'},
    ]);
  });
});

describe('renderDiff', () => {
  it('counts each separately changed phrase as one edit', () => {
    expect(
      renderDiff(
        'The quick brown fox jumps over the dog.',
        'The slow red fox jumps over the lazy dog.'
      ).edits
    ).toBe(2);
  });
});
