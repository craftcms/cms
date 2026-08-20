import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {defineElement} from '@/common/web-components';
import CraftElementSelectInput from './element-select-input.ce';

const destroy = vi.fn();
interface PluginSettings {
  id: string;
  limit: number;
}

let receivedSettings: PluginSettings | undefined;

class PluginElementSelectInput {
  constructor(settings: PluginSettings) {
    receivedSettings = settings;
  }

  destroy(): void {
    destroy();
  }

  getSelectedElementIds(): number[] {
    return [7, 3];
  }

  on(): void {}
}

defineElement('craft-element-select-input', CraftElementSelectInput);

beforeEach(() => {
  receivedSettings = undefined;
  destroy.mockClear();
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
  const element = document.createElement('craft-element-select-input');
  element.id = 'related-elements';
  element.setAttribute('input-class', 'Craft.PluginElementSelectInput');
  element.setAttribute('settings', JSON.stringify({limit: 2, id: 'ignored'}));
  element.append(document.createElement('ul'));
  if (!element.firstElementChild)
    throw new Error('Expected the elements list fixture.');
  element.firstElementChild.className = 'elements';
  document.body.append(element);

  expect(receivedSettings).toEqual({limit: 2, id: 'related-elements'});
  expect(element.selectedIds).toEqual([7, 3]);
  expect(window.Craft.initUiElements).toHaveBeenCalledWith(element);

  element.remove();
  expect(destroy).toHaveBeenCalledOnce();
});
