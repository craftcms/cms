import {ref} from 'vue';
import {describe, expect, it, vi} from 'vitest';
import type {ViewState} from '@/modules/elements/types/view-state';
import {
  cascadeStructureSelection,
  deselectDescendants,
  hideCollapsedDescendants,
  loadedBranchDepth,
  resolveStructureMove,
  selectDescendantsOfSelected,
  useElementIndexStructure,
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

describe('resolveStructureMove', () => {
  const tree = [
    {id: 1, level: 1, hasDescendants: true},
    {id: 2, level: 2, hasDescendants: true},
    {id: 3, level: 3, hasDescendants: false},
    {id: 4, level: 2, hasDescendants: false},
    {id: 5, level: 1, hasDescendants: false},
  ];
  const onFirstPage = {startsAtTop: true};
  const onLaterPage = {startsAtTop: false};

  it('moves up to the start of the parent', () => {
    expect(
      resolveStructureMove(tree, 4, {type: 'up'}, onFirstPage)
    ).toMatchObject({parentId: 1, level: 2});
  });

  it('moves down after the next sibling', () => {
    expect(
      resolveStructureMove(tree, 2, {type: 'down'}, onFirstPage)
    ).toMatchObject({prevId: 4, level: 2});
  });

  it('refuses moves past the first or last sibling', () => {
    expect(resolveStructureMove(tree, 2, {type: 'up'}, onFirstPage)).toBeNull();
    expect(
      resolveStructureMove(tree, 4, {type: 'down'}, onFirstPage)
    ).toBeNull();
  });

  it('only moves to the very top of the tree from the first page', () => {
    expect(resolveStructureMove(tree, 5, {type: 'up'}, onFirstPage)).toEqual({
      level: 1,
    });
    expect(resolveStructureMove(tree, 5, {type: 'up'}, onLaterPage)).toBeNull();
  });

  it('indents after the previous sibling’s last child', () => {
    expect(
      resolveStructureMove(tree, 4, {type: 'indent'}, onFirstPage)
    ).toMatchObject({prevId: 3, newParentId: 2, level: 3});
  });

  it('indents after the last child of a sibling with children', () => {
    expect(
      resolveStructureMove(tree, 5, {type: 'indent'}, onFirstPage)
    ).toEqual({prevId: 4, newParentId: 1, level: 2});
  });

  it('indents as the only child of a childless sibling', () => {
    const flat = [
      {id: 1, level: 1},
      {id: 2, level: 1},
    ];

    expect(
      resolveStructureMove(flat, 2, {type: 'indent'}, onFirstPage)
    ).toEqual({parentId: 1, newParentId: 1, level: 2});
  });

  it('outdents after the parent', () => {
    expect(
      resolveStructureMove(tree, 4, {type: 'outdent'}, onFirstPage)
    ).toMatchObject({prevId: 1, level: 1});
    expect(
      resolveStructureMove(tree, 1, {type: 'outdent'}, onFirstPage)
    ).toBeNull();
  });

  it('drops a branch as the first child of a row', () => {
    expect(
      resolveStructureMove(tree, 2, {type: 'child', targetId: 5}, onFirstPage)
    ).toMatchObject({parentId: 5, newParentId: 5, level: 2});
  });

  it('drops a row out to an ancestor’s level', () => {
    expect(
      resolveStructureMove(
        tree,
        4,
        {type: 'reparent', targetId: 3, level: 1},
        onFirstPage
      )
    ).toMatchObject({prevId: 1, level: 1});
  });

  it('ignores drops that leave the row where it is', () => {
    expect(
      resolveStructureMove(tree, 4, {type: 'after', targetId: 2}, onFirstPage)
    ).toBeNull();
  });
});

describe('loadedBranchDepth', () => {
  const tree = [
    {id: 1, level: 1, hasDescendants: true},
    {id: 2, level: 2, hasDescendants: true},
    {id: 3, level: 3, hasDescendants: false},
  ];

  it('measures the loaded branch', () => {
    expect(loadedBranchDepth(tree, 1, () => false)).toBe(2);
    expect(loadedBranchDepth(tree, 3, () => false)).toBe(0);
  });

  it('is unknown when part of the branch is collapsed', () => {
    expect(loadedBranchDepth(tree, 1, (id) => id === 2)).toBeNull();
  });
});

describe('useElementIndexStructure', () => {
  const visitor = {merge: vi.fn(), visit: vi.fn(), currentQuery: () => ({})};
  const route = {url: () => '/cp/entries'};
  const viewState = ref<ViewState>({
    inlineEditing: false,
    mode: 'structure',
    showHeaderColumn: true,
    static: false,
  });

  // The saved mode is shared by every source, so it can outlive a switch to a
  // source without a structure.
  it('only treats structure sources as being in structure mode', () => {
    const source = ref<{structureId: number | null}>({structureId: 1});
    const {isStructure} = useElementIndexStructure(
      {
        structure: null,
        get source() {
          return source.value;
        },
      },
      viewState,
      route,
      visitor
    );

    expect(isStructure.value).toBe(true);

    source.value = {structureId: null};

    expect(isStructure.value).toBe(false);
  });
});
