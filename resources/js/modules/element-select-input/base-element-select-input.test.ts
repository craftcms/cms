import {expect, it} from 'vite-plus/test';
import {BaseElementSelectInput} from './base-element-select-input';
import {EntrySelectInput} from './entry-select-input';
import {TagSelectInput} from './tag-select-input';

it('preserves legacy element select input extension points', () => {
  class UninitializedElementSelectInput extends BaseElementSelectInput {}

  const input = new UninitializedElementSelectInput();

  expect('_ignoreSearchBlur' in input).toBe(true);
  expect('_initialized' in input).toBe(true);
  expect('_$replaceElement' in input).toBe(true);
  expect(
    Object.getOwnPropertyDescriptor(
      BaseElementSelectInput.prototype,
      'thumbLoader'
    )
  ).toMatchObject({get: expect.any(Function)});
  expect(
    Object.getOwnPropertyDescriptor(
      BaseElementSelectInput.prototype,
      '_animateStructureElementAway'
    )?.value
  ).toBeInstanceOf(Function);

  expect(
    Object.getOwnPropertyDescriptor(EntrySelectInput.prototype, 'section')
  ).toMatchObject({get: expect.any(Function)});
  expect(
    Object.getOwnPropertyDescriptor(
      EntrySelectInput.prototype,
      'showElementEditor'
    )?.value
  ).toBeInstanceOf(Function);

  for (const method of [
    'focusOption',
    'searchForTags',
    'focusSelectedOption',
    'focusFirstOption',
    'killSearchMenu',
  ]) {
    expect(Object.hasOwn(TagSelectInput.prototype, method)).toBe(true);
  }
  expect(
    Object.getOwnPropertyDescriptor(TagSelectInput.prototype, 'fieldName')
  ).toMatchObject({get: expect.any(Function)});

  expect(BaseElementSelectInput.defaults).toMatchObject({
    allowAdd: true,
    allowRemove: true,
    modalSettings: {},
  });
  expect(TagSelectInput.defaults).toMatchObject({tagGroupId: null});
});
