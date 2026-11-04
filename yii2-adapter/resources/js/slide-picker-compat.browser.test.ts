import '@craftcms/ui/components/slide-picker/slide-picker';
import $ from 'jquery';
import {afterEach, beforeAll, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import type CraftSlidePicker from '@craftcms/ui/components/slide-picker/slide-picker';

const legacyWindow = window as any;

beforeAll(async () => {
  legacyWindow.jQuery = legacyWindow.$ = $;
  legacyWindow.Craft = {t: (_category: string, message: string) => message};
  await import('../../../packages/craftcms-legacy/cpcompat/src/cp-compat.js');
  await import('../../../packages/craftcms-legacy/cpcompat/src/ui.js');
});

afterEach(() => {
  document.body.innerHTML = '';
});

function createSlidePicker(value: number, settings: Record<string, unknown>) {
  const picker = new legacyWindow.Craft.SlidePicker(value, {
    min: 1,
    max: 4,
    step: 1,
    ...settings,
  });
  $(document.body).append(picker.$container);
  return picker;
}

async function pressOnSlider(key: string) {
  const slider = document.querySelector<HTMLElement>(
    'craft-slide-picker-slider'
  )!;
  slider.focus();
  await userEvent.keyboard(key);
}

it('names and describes the slider through Craft.SlidePicker', async () => {
  document.body.innerHTML = '<p id="width-help">How wide the widget is.</p>';
  createSlidePicker(2, {label: 'Width', describedBy: 'width-help'});

  const slider = page.getByRole('slider');

  await expect.element(slider).toHaveAccessibleName('Width');
  await expect
    .element(slider)
    .toHaveAccessibleDescription('How wide the widget is.');
  await expect.element(slider).toHaveAttribute('aria-valuenow', '2');
});

it('moves and reports the value through Craft.SlidePicker', async () => {
  const changes: number[] = [];
  const picker = createSlidePicker(2, {
    label: 'Width',
    onChange: (value: number) => changes.push(value),
  });
  const slider = page.getByRole('slider');

  picker.setValue(3, false);
  await expect.element(slider).toHaveAttribute('aria-valuenow', '3');

  await pressOnSlider('{ArrowUp}');

  await expect.element(slider).toHaveAttribute('aria-valuenow', '4');
  expect(changes).toEqual([4]);
  expect(picker.value).toBe(4);
});

it('keeps a read-only Craft.SlidePicker from changing', async () => {
  const changes: number[] = [];
  createSlidePicker(2, {
    label: 'Width',
    readOnly: true,
    onChange: (value: number) => changes.push(value),
  });
  const slider = page.getByRole('slider');
  await expect.element(slider).toHaveAttribute('aria-readonly', 'true');

  await pressOnSlider('{ArrowUp}');

  await expect.element(slider).toHaveAttribute('aria-valuenow', '2');
  expect(changes).toEqual([]);
});

it('names the slider after its field label and describes it with the instructions', async () => {
  const $field = legacyWindow.Craft.ui.createSlidePickerField({
    id: 'widget-width',
    label: 'Width',
    instructions: 'How many columns the widget spans.',
    min: 1,
    max: 4,
    step: 1,
    value: 2,
  });
  $(document.body).append($field);
  await document.querySelector<CraftSlidePicker>('craft-slide-picker')!
    .updateComplete;

  const slider = page.getByRole('slider');

  await expect.element(slider).toHaveAccessibleName('Width');
  await expect
    .element(slider)
    .toHaveAccessibleDescription('How many columns the widget spans.');
});
