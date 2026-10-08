import {describe, expect, it} from 'vite-plus/test';
import {
  isPasteable,
  nestedElementReorderOffset,
  type NestedElement,
} from './nested-elements';

describe('nestedElementReorderOffset', () => {
  const elements = [11, 12, 13, 14].map((id) => ({id}) as NestedElement);

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
      nestedElementReorderOffset(elements, selectedIds, from, to, pageOffset)
    ).toBe(expected);
  });
});

describe('isPasteable', () => {
  const copied = [{type: 'Entry', id: 11, data: {entryTypeId: 9}}];

  it.each([
    {
      label: 'accepts any element of the type when nothing is restricted',
      pasteableData: null,
      expected: true,
    },
    {
      label: 'rejects elements missing the restricted attribute',
      pasteableData: {attribute: 'productTypeId', values: [9]},
      expected: false,
    },
  ])('$label', ({pasteableData, expected}) => {
    expect(
      isPasteable(copied, {elementType: 'Entry', pasteableData, room: true})
    ).toBe(expected);
  });

  it('rejects other element types', () => {
    expect(isPasteable(copied, {elementType: 'Address', room: true})).toBe(
      false
    );
  });
});
