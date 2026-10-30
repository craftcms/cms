import {describe, expect, it} from 'vite-plus/test';
import {labelScore, rankItems} from './rank';
import type {CommandPaletteItem} from './types';

function item(label: string, extra: Partial<CommandPaletteItem> = {}) {
  return {id: label, label, ...extra};
}

describe('labelScore', () => {
  it('ranks closer matches higher', () => {
    const scores = [
      labelScore('Sections', 'sections'),
      labelScore('Sections index', 'sections'),
      labelScore('Create section', 'section'),
      labelScore('Intersections', 'section'),
      labelScore('Site settings', 'stst'),
    ];

    expect([...scores].sort((a, b) => b - a)).toEqual(scores);
    expect(scores.every((score) => score > 0)).toBe(true);
  });

  it('ignores case and accents', () => {
    expect(labelScore('Café menu', 'CAFE')).toBe(
      labelScore('cafe menu', 'cafe')
    );
  });

  it('only counts letters in order when matching loosely', () => {
    expect(labelScore('Discard changes', 'caches')).toBeGreaterThan(0);
    expect(labelScore('Discard changes', 'caches', {loose: false})).toBe(0);
    expect(
      labelScore('Discard changes', 'chan', {loose: false})
    ).toBeGreaterThan(0);
  });

  it('requires every word to match', () => {
    expect(labelScore('Create entry type', 'entry field')).toBe(0);
  });
});

describe('rankItems', () => {
  it('puts label matches before items matched on other text, shortest first', () => {
    const ranked = rankItems(
      [
        item('Set language to German', {keywords: 'Deutsch'}),
        item('Deutsch export'),
        item('Deutsch'),
      ],
      'deutsch'
    );

    expect(ranked.map(({label}) => label)).toEqual([
      'Deutsch',
      'Deutsch export',
      'Set language to German',
    ]);
  });

  it('puts destructive items after equal matches', () => {
    const ranked = rankItems(
      [item('Delete Blog', {variant: 'danger'}), item('Delete Blogs')],
      'delete'
    );

    expect(ranked.map(({label}) => label)).toEqual([
      'Delete Blogs',
      'Delete Blog',
    ]);
  });
});
