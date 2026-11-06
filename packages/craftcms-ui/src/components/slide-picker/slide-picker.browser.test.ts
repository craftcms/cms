import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftSlidePicker from './slide-picker.js';
import './slide-picker.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(attributes = '', value = 100): Promise<CraftSlidePicker> {
  await import('../../styles/cp.css');
  document.body.innerHTML = `<craft-slide-picker min="25" max="100" step="25" value="${value}" ${attributes}></craft-slide-picker>`;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  await picker.updateComplete;
  await slider(picker).updateComplete;
  return picker;
}

function slider(picker: CraftSlidePicker) {
  return picker.querySelector('craft-slide-picker-slider')!;
}

function segments(picker: CraftSlidePicker): HTMLElement[] {
  return [
    ...slider(picker).shadowRoot!.querySelectorAll<HTMLElement>(
      '.slide-picker__segment'
    ),
  ];
}

function fills(picker: CraftSlidePicker): string[] {
  return segments(picker).map(
    (segment) => getComputedStyle(segment).backgroundColor
  );
}

it('fills only the segments up to the hovered one', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render();
  const selected = fills(picker)[0];

  await userEvent.hover(segments(picker)[1]!);
  const [first, second, third, fourth] = fills(picker);

  expect(first).not.toBe('rgba(0, 0, 0, 0)');
  expect(second).toBe(first);
  expect(first).not.toBe(selected);
  expect(third).toBe('rgba(0, 0, 0, 0)');
  expect(fourth).toBe('rgba(0, 0, 0, 0)');
});

it('draws every segment at one height, with the selected ones filled darker', async () => {
  const picker = await render('', 50);
  const heights = segments(picker).map(
    (segment) => segment.getBoundingClientRect().height
  );
  const [first, second, third] = fills(picker);

  expect(new Set(heights).size).toBe(1);
  expect(second).toBe(first);
  expect(third).not.toBe(first);
  // The divider between selected segments is as light as unselected
  // segments' borders, not their dark outline.
  const firstStyle = getComputedStyle(segments(picker)[0]!);
  expect(firstStyle.borderInlineEndColor).not.toBe(firstStyle.borderTopColor);
  expect(firstStyle.borderInlineEndColor).toBe(
    getComputedStyle(segments(picker)[2]!).borderTopColor
  );
});

it('leaves a read-only picker’s fills alone while hovered', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render('readonly');
  const before = fills(picker);

  await userEvent.hover(segments(picker)[1]!);

  expect(fills(picker)).toEqual(before);
});

it('previews the current value like any other', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render('', 50);
  const selected = fills(picker)[0];

  await userEvent.hover(segments(picker)[1]!);
  const [first, second, third] = fills(picker);

  expect(first).not.toBe(selected);
  expect(second).toBe(first);
  expect(third).toBe('rgba(0, 0, 0, 0)');
  expect(getComputedStyle(segments(picker)[1]!).cursor).toBe('pointer');
});

it('is one Tab stop, on the slider', async () => {
  const {page, userEvent} = await import('vite-plus/test/browser');
  await render();

  await userEvent.tab();

  await expect.element(page.getByRole('slider')).toHaveFocus();
});

it('draws one focus ring, around the current segment', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render('', 50);

  await userEvent.tab();

  const current = slider(picker).shadowRoot!.querySelector('.is-last-active')!;
  expect(getComputedStyle(slider(picker)).outlineStyle).toBe('none');
  expect(getComputedStyle(current).outlineStyle).not.toBe('none');
});

it('is named by its label, and focused when the label is clicked', async () => {
  const {page} = await import('vite-plus/test/browser');
  await render('label="Columns"');

  await page.getByText('Columns', {exact: true}).click();

  const slider = page.getByRole('slider');
  await expect.element(slider).toHaveAccessibleName('Columns');
  await expect.element(slider).toHaveFocus();
});

it('takes its name and description from a surrounding field', async () => {
  const {page} = await import('vite-plus/test/browser');
  await import('../field/field.js');
  await import('../../styles/cp.css');
  const label = 'Card width';
  const helpText = 'How many columns the card spans.';
  const error = 'Card width must be at least 2 columns.';
  document.body.innerHTML = `<craft-field label="${label}" help-text="${helpText}"><craft-slide-picker slot="input" min="1" max="4" step="1" value="1"></craft-slide-picker><ul slot="feedback" class="errors"><li>${error}</li></ul></craft-field>`;

  const slider = page.getByRole('slider');

  await expect.element(slider).toHaveAccessibleName(label);
  await expect
    .element(slider)
    .toHaveAccessibleDescription(`${helpText} ${error}`);
});

it('has no name of its own until one is given', async () => {
  const {page} = await import('vite-plus/test/browser');
  await render();

  await expect.element(page.getByRole('slider')).toHaveAccessibleName('');
});

it('is named and described by the elements its host references', async () => {
  const {page} = await import('vite-plus/test/browser');
  await import('../../styles/cp.css');
  document.body.innerHTML = `<table><thead><tr><th id="width-heading">Width</th></tr></thead><tbody><tr><td><craft-slide-picker min="1" max="4" step="1" value="2" labelled-by="width-heading" described-by="width-help"></craft-slide-picker></td></tr></tbody></table><p id="width-help">How many columns the field spans.</p>`;

  const slider = page.getByRole('slider');

  await expect.element(slider).toHaveAccessibleName('Width');
  await expect
    .element(slider)
    .toHaveAccessibleDescription('How many columns the field spans.');
});

it('follows changes to the references on its host', async () => {
  const {page} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<span id="first">Width</span><span id="second">Columns</span><craft-slide-picker min="1" max="4" step="1" labelled-by="first"></craft-slide-picker>`;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  const slider = page.getByRole('slider');
  await expect.element(slider).toHaveAccessibleName('Width');

  picker.setAttribute('labelled-by', 'second');
  await expect.element(slider).toHaveAccessibleName('Columns');

  picker.removeAttribute('labelled-by');
  await expect.element(slider).toHaveAccessibleName('');
});

it('keeps its value when the form reset is canceled', async () => {
  document.body.innerHTML = `<form><craft-slide-picker name="columns" min="1" max="4" step="1" value="2"></craft-slide-picker><input name="title" value="Original"></form>`;
  const form = document.querySelector('form')!;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  const input = form.querySelector('input')!;
  await picker.updateComplete;
  form.addEventListener('reset', (event) => event.preventDefault());

  picker.value = 4;
  input.value = 'Changed';
  await picker.updateComplete;
  form.reset();
  await picker.updateComplete;

  expect(input.value).toBe('Changed');
  expect(picker.value).toBe(4);
  expect(new FormData(form).get('columns')).toBe('4');
});

it('picks up references changed while it was off the page once it is put back', async () => {
  const {page} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<span id="width">Width</span><craft-slide-picker min="1" max="4" step="1"></craft-slide-picker>`;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  await picker.updateComplete;

  picker.remove();
  picker.setAttribute('labelled-by', 'width');
  await expect(picker.updateComplete).resolves.toBeTypeOf('boolean');

  document.body.append(picker);
  await expect.element(page.getByRole('slider')).toHaveAccessibleName('Width');
});
