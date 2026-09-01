import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {defineElement} from '@/common/web-components';
import CraftElementSelectInput from './element-select-input.ce';

const destroy = vi.fn();
const showReplaceModal = vi.fn();
const removeElementOrSelection = vi.fn();

interface PluginSettings {
  id: string;
  limit: number;
}

let receivedSettings: PluginSettings | undefined;

/**
 * The slice of the jQuery collection API `CraftElementSelectInput` uses to
 * resolve a chip out of the controller's `$elements`.
 */
type FakeElements = {
  ids: number[];
  length: number;
  filter(selector: string): FakeElements;
};

function fakeElements(ids: number[]): FakeElements {
  return {
    ids,
    length: ids.length,
    filter(selector) {
      const id = Number(selector.match(/\[data-id="(\d+)"\]/)?.[1]);
      return fakeElements(ids.filter((value) => value === id));
    },
  };
}

class PluginElementSelectInput {
  $elements = fakeElements([7, 3]);

  constructor(settings: PluginSettings) {
    receivedSettings = settings;
  }

  destroy(): void {
    destroy();
  }

  getSelectedElementIds(): number[] {
    return [7, 3];
  }

  showReplaceModal($element: FakeElements): void {
    showReplaceModal($element);
  }

  removeElementOrSelection($element: FakeElements): void {
    removeElementOrSelection($element);
  }

  on(): void {}
}

defineElement('craft-element-select-input', CraftElementSelectInput);

function bootInput(): InstanceType<typeof CraftElementSelectInput> {
  const element = document.createElement('craft-element-select-input');
  element.id = 'related-elements';
  element.setAttribute('input-class', 'Craft.PluginElementSelectInput');
  element.setAttribute('settings', JSON.stringify({limit: 2, id: 'ignored'}));
  element.append(document.createElement('ul'));
  if (!element.firstElementChild)
    throw new Error('Expected the elements list fixture.');
  element.firstElementChild.className = 'elements';
  document.body.append(element);

  return element;
}

beforeEach(() => {
  receivedSettings = undefined;
  destroy.mockClear();
  showReplaceModal.mockClear();
  removeElementOrSelection.mockClear();
  vi.stubGlobal('Craft', {
    initUiElements: vi.fn(),
    PluginElementSelectInput,
  });
});

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

it('boots plugin input classes through the custom element interface', () => {
  const element = bootInput();

  expect(receivedSettings).toEqual({limit: 2, id: 'related-elements'});
  expect(element.selectedIds).toEqual([7, 3]);
  expect(window.Craft.initUiElements).toHaveBeenCalledWith(element);

  element.remove();
  expect(destroy).toHaveBeenCalledOnce();
});

it('forwards the chip actions to the input, resolved by element ID', () => {
  const element = bootInput();

  element.replaceElement(7);
  expect(showReplaceModal).toHaveBeenCalledWith(
    expect.objectContaining({ids: [7]})
  );

  element.removeElement('3');
  expect(removeElementOrSelection).toHaveBeenCalledWith(
    expect.objectContaining({ids: [3]})
  );
});

it('ignores chip actions for elements the input has no chip for', () => {
  const element = bootInput();

  element.replaceElement(11);
  element.removeElement(11);

  expect(showReplaceModal).not.toHaveBeenCalled();
  expect(removeElementOrSelection).not.toHaveBeenCalled();
});
