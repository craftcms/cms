import '@craftcms/ui';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import type CraftSlidePickerButton from '@craftcms/ui/components/slide-picker-button/slide-picker-button';
import {Element} from './element';

vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));

afterEach(() => {
  delete window.Craft;
  document.body.innerHTML = '';
});

it('names the width button after the field it sizes', () => {
  window.Craft = Object.assign(Object.create(null), {
    t: (_category: string, message: string) => message,
  });
  const element = Object.create(Element.prototype) as Element;
  element.$container = document.createElement('div');
  element.$container.className = 'fld-element fld-field';
  element.$container.dataset.attribute = 'body';
  element.$container.dataset.hasCustomWidth = '';
  element.$container.innerHTML = '<span class="fld-element-label">Body</span>';
  element.uid = 'body-uid';
  (element as any).tab = {
    designer: {settings: {customizableUi: true, readOnly: false}},
    config: {elements: [{uid: 'body-uid', width: 50}]},
  };
  document.body.append(element.$container);

  element.initUi();

  const button = (element.$container as HTMLElement).querySelector(
    'craft-slide-picker-button'
  ) as CraftSlidePickerButton;
  expect(button.context).toBe('Body');
  expect(button.label).toBe('Width');
});
