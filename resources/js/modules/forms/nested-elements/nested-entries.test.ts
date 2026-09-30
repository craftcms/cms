import {describe, expect, it} from 'vite-plus/test';
import {
  isPasteable,
  nestedEntryReorderOffset,
  type NestedEntry,
} from './nested-entries';

describe('nestedEntryReorderOffset', () => {
  const entries = [11, 12, 13, 14].map((id) => ({id}) as NestedEntry);

  it.each([
    {
      label: 'moves a selected group down after removing it from the page',
      selectedIds: [11, 13],
      from: 0,
      to: 3,
      pageOffset: 20,
      expected: 22,
    },
    {
      label: 'moves a selected group up at the page offset',
      selectedIds: [12, 14],
      from: 3,
      to: 0,
      pageOffset: 20,
      expected: 20,
    },
  ])('$label', ({selectedIds, from, to, pageOffset, expected}) => {
    expect(
      nestedEntryReorderOffset(entries, selectedIds, from, to, pageOffset)
    ).toBe(expected);
  });
});

describe('isPasteable', () => {
  it.each([
    {
      label: 'accepts any entry type when no entry types are restricted',
      requireEntryTypeId: false,
      expected: true,
    },
    {
      label:
        'rejects clipboard entries when the caller requires a known entry type',
      requireEntryTypeId: true,
      expected: false,
    },
  ])('$label', ({requireEntryTypeId, expected}) => {
    expect(
      isPasteable([{type: 'Entry', id: 11, data: {entryTypeId: 9}}], {
        elementType: 'Entry',
        entryTypeIds: [],
        room: true,
        requireEntryTypeId,
      })
    ).toBe(expected);
  });
});
