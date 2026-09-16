import {describe, expect, it} from 'vitest';
import {
  cascadeStructureSelection,
  deselectDescendants,
  hideCollapsedDescendants,
  selectDescendantsOfSelected,
} from './useElementIndexStructure';

// hoodie
//   blue
//     blue-small
//   red
// tee
const rows = [
  {id: 1, level: 1},
  {id: 2, level: 2},
  {id: 3, level: 3},
  {id: 4, level: 2},
  {id: 5, level: 1},
];

describe('hideCollapsedDescendants', () => {
  it('hides every row beneath a collapsed row', () => {
    const visible = hideCollapsedDescendants(rows, (id) => id === 1);

    expect(visible.map((row) => row.id)).toEqual([1, 5]);
  });

  it('keeps siblings of a collapsed nested row', () => {
    const visible = hideCollapsedDescendants(rows, (id) => id === 2);

    expect(visible.map((row) => row.id)).toEqual([1, 2, 4, 5]);
  });
});

describe('cascadeStructureSelection', () => {
  it('selects the whole branch when a parent is selected', () => {
    expect(cascadeStructureSelection({}, {'1': true}, rows)).toEqual({
      '1': true,
      '2': true,
      '3': true,
      '4': true,
    });
  });

  it('releases descendants and ancestors when a row is deselected', () => {
    const all = {'1': true, '2': true, '3': true, '4': true};

    expect(
      cascadeStructureSelection(all, {'1': true, '3': true, '4': true}, rows)
    ).toEqual({'4': true});
  });

  it('keeps a row selected in the same change that releases its branch', () => {
    expect(cascadeStructureSelection({'2': true}, {'3': true}, rows)).toEqual({
      '3': true,
    });
  });

  it('returns the incoming state untouched when nothing changed', () => {
    const next = {'5': true};

    expect(cascadeStructureSelection({'5': true}, next, rows)).toBe(next);
  });
});

describe('selectDescendantsOfSelected', () => {
  it('pulls newly loaded children into a selected branch', () => {
    expect(selectDescendantsOfSelected({'2': true}, rows)).toEqual({
      '2': true,
      '3': true,
    });
  });

  it('returns the same object when nothing needs adding', () => {
    const selection = {'5': true};

    expect(selectDescendantsOfSelected(selection, rows)).toBe(selection);
  });
});

describe('deselectDescendants', () => {
  it('drops the rows a collapse is about to hide', () => {
    expect(
      deselectDescendants({'1': true, '2': true, '3': true}, rows, 2)
    ).toEqual({'1': true, '2': true});
  });
});
