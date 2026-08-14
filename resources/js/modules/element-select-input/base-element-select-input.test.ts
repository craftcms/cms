import {afterEach, expect, it, vi} from 'vite-plus/test';
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
    typeof Object.getOwnPropertyDescriptor(
      BaseElementSelectInput.prototype,
      'thumbLoader'
    )?.get
  ).toBe('function');
  expect(
    typeof Object.getOwnPropertyDescriptor(
      BaseElementSelectInput.prototype,
      '_animateStructureElementAway'
    )?.value
  ).toBe('function');

  expect(
    typeof Object.getOwnPropertyDescriptor(
      EntrySelectInput.prototype,
      'section'
    )?.get
  ).toBe('function');
  expect(
    typeof Object.getOwnPropertyDescriptor(
      EntrySelectInput.prototype,
      'showElementEditor'
    )?.value
  ).toBe('function');

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
    typeof Object.getOwnPropertyDescriptor(
      TagSelectInput.prototype,
      'fieldName'
    )?.get
  ).toBe('function');

  expect(BaseElementSelectInput.defaults).toMatchObject({
    allowAdd: true,
    allowRemove: true,
    modalSettings: {},
  });
  expect(TagSelectInput.defaults).toEqual({tagGroupId: null});
});

afterEach(() => {
  vi.unstubAllGlobals();
});

/**
 * An instance with just enough state stubbed to exercise the chip actions —
 * `init()` needs the DOM, jQuery and garnish, none of which these behaviors
 * touch.
 */
function chipActionInput(elementSelect: any = null) {
  class UninitializedElementSelectInput extends BaseElementSelectInput {}

  const showModal = vi.fn();
  const removeElement = vi.fn();
  const input = new UninitializedElementSelectInput();
  input.settings = {allowRemove: true, elementType: 'entry', sortable: false};
  input.elementSelect = elementSelect;
  input.showModal = showModal;
  input.removeElement = removeElement;

  return {input, showModal, removeElement};
}

it('stages the element the modal should replace', () => {
  const {input, showModal} = chipActionInput();
  const $chip = {chip: true};

  input.showReplaceModal($chip);

  expect(input._$replaceElement).toBe($chip);
  expect(showModal).toHaveBeenCalledOnce();
});

it('removes the whole selection when the chip is part of it', () => {
  const $chip = {chip: true};
  const $selection = {selection: true};
  const {input, removeElement} = chipActionInput({
    isSelected: (candidate: any) => candidate === $chip,
    getSelectedItems: () => $selection,
  });

  input.removeElementOrSelection($chip);
  expect(removeElement).toHaveBeenCalledWith($selection);

  const $other = {chip: true};
  input.removeElementOrSelection($other);
  expect(removeElement).toHaveBeenLastCalledWith($other);
});

it('routes its injected chip actions through those methods', () => {
  vi.stubGlobal('Craft', {t: (_category: string, message: string) => message});

  const {input, showModal, removeElement} = chipActionInput();
  const $chip = {chip: true};
  const actions = input.defineElementActions($chip);

  expect(actions.map((action) => action.label)).toEqual(['Replace', 'Remove']);

  actions[0].callback();
  expect(input._$replaceElement).toBe($chip);
  expect(showModal).toHaveBeenCalledOnce();

  actions[1].callback();
  expect(removeElement).toHaveBeenCalledWith($chip);
});
