import '@craftcms/ui';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import type CraftSlidePickerButton from '@craftcms/ui/components/slide-picker-button/slide-picker-button';
import {Element} from './element';
import type {Tab} from './tab';

vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));

afterEach(() => {
  delete window.Craft;
  document.body.innerHTML = '';
});

it('names the width button after the field it sizes', () => {
  window.Craft = Object.assign(Object.create(null), {
    t: (_category: string, message: string) => message,
  });
  const container = document.createElement('div');
  container.className = 'fld-element fld-field';
  container.dataset.uid = 'body-uid';
  container.dataset.attribute = 'body';
  container.dataset.hasCustomWidth = '';
  container.innerHTML = '<span class="fld-element-label">Body</span>';
  document.body.append(container);
  const tab = {
    designer: {settings: {customizableUi: true, readOnly: false}},
    config: {elements: [{uid: 'body-uid', width: 50}]},
  } as unknown as Tab;

  new Element(tab, container);

  const button = container.querySelector(
    'craft-slide-picker-button'
  ) as CraftSlidePickerButton;
  expect(button.context).toBe('Body');
  expect(button.label).toBe('Width');
});
