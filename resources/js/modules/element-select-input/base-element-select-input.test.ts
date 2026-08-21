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

/**
 * An instance with just enough stubbed to observe the order of
 * `onModalSelect()`'s side effects — the real method wants jQuery, garnish and
 * a live modal, none of which bear on when the replaced chip is dropped.
 */
function replaceFlowInput() {
  class UninitializedElementSelectInput extends BaseElementSelectInput {}

  const log: string[] = [];
  const input = new UninitializedElementSelectInput();

  input.settings = {
    viewMode: 'list',
    elementType: 'entry',
    limit: null,
    maintainHierarchy: false,
    showActionMenu: false,
    criteria: {},
  };
  input.$elements = {length: 1};

  const removeElement = vi.fn(() => {
    log.push('removeElement');
    input.$elements = {length: 0};
    // Stands in for the host reacting to the value change. Vue schedules its
    // re-render as a microtask, so anything that lands after this marker is
    // too late to be part of the same combined update.
    void Promise.resolve().then(() => log.push('host re-render'));
  });
  const selectElements = vi.fn(() => {
    log.push('selectElements');
  });

  input.removeElement = removeElement;
  input.selectElements = selectElements as never;
  input.updateDisabledElementsInModal = vi.fn();

  vi.stubGlobal('$', (value: unknown) => ({rendered: value}));
  vi.stubGlobal('Craft', {
    sendActionRequest: vi.fn(async () => {
      log.push('render-elements');
      return {data: {elements: {7: ['<div/>']}, headHtml: '', bodyHtml: ''}};
    }),
    appendHeadHtml: vi.fn(),
    appendBodyHtml: vi.fn(),
    setElementSize: vi.fn(),
  });

  return {input, log, removeElement, selectElements};
}

it('drops the replaced chip only once the replacement has rendered', async () => {
  const {input, log, removeElement} = replaceFlowInput();
  const $chip = {chip: true};
  input._$replaceElement = $chip;

  await input.onModalSelect([{id: 7, siteId: 1}]);

  expect(removeElement).toHaveBeenCalledWith($chip);
  expect(input._$replaceElement).toBeNull();

  // Removing before the request tore down hosts that key on this input's
  // value, leaving the resumed flow writing into a detached DOM — Replace
  // opened its modal but never completed. The removal and the insertion also
  // have to share one synchronous stretch, so those hosts see a single
  // combined change rather than an empty intermediate one.
  expect(log).toEqual([
    'render-elements',
    'removeElement',
    'selectElements',
    'host re-render',
  ]);
});

it('frees the replaced chip’s slot before applying the limit', async () => {
  const {input, selectElements} = replaceFlowInput();
  input.settings.limit = 1;
  input._$replaceElement = {chip: true};

  await input.onModalSelect([{id: 7, siteId: 1}]);

  // The outgoing chip still occupied the field's only slot when the limit was
  // applied, the replacement would be sliced away and nothing would be added.
  expect(selectElements).toHaveBeenCalledWith([
    expect.objectContaining({id: 7}),
  ]);
});
