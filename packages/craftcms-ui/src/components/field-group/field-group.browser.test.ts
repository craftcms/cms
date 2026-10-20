import {beforeEach, expect, it} from 'vite-plus/test';
import './field-group.js';
import '../select/select.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('uses intrinsic widths when space allows and stacks them when it does not', async () => {
  const fieldGroup = document.createElement('craft-field-group');
  fieldGroup.classList.add('auto-widths');
  fieldGroup.style.width = '720px';
  fieldGroup.style.setProperty('--c-spacing-lg', '24px');
  fieldGroup.style.setProperty('--c-spacing-sm', '8px');
  fieldGroup.innerHTML = `
    <div>Minimum</div>
    <div class="width-25">Mode</div>
  `;
  document.body.append(fieldGroup);
  await fieldGroup.updateComplete;

  const minimum = fieldGroup.querySelector<HTMLElement>('div:not([class])')!;
  const mode = fieldGroup.querySelector<HTMLElement>('.width-25')!;
  expect(minimum.getBoundingClientRect().width).toBeLessThan(
    mode.getBoundingClientRect().width
  );
  expect(minimum.getBoundingClientRect().top).toBe(
    mode.getBoundingClientRect().top
  );
  expect(
    mode.getBoundingClientRect().left - minimum.getBoundingClientRect().right
  ).toBeGreaterThanOrEqual(8);

  fieldGroup.style.width = '390px';
  await new Promise((resolve) => requestAnimationFrame(() => resolve(null)));

  expect(mode.getBoundingClientRect().top).toBeGreaterThan(
    minimum.getBoundingClientRect().top
  );
});

it('sizes an intrinsic select for its longest option', async () => {
  const fieldGroup = document.createElement('craft-field-group');
  fieldGroup.classList.add('auto-widths');
  fieldGroup.style.width = '900px';
  fieldGroup.style.setProperty('--c-spacing-sm', '8px');
  fieldGroup.innerHTML = `
    <div>Minimum</div>
    <div>
      <craft-select>
        <select slot="input">
          <option selected>Total</option>
          <option>Per group</option>
        </select>
      </craft-select>
    </div>
  `;
  document.body.append(fieldGroup);
  await fieldGroup.updateComplete;

  const [minimum, mode] = fieldGroup.children as unknown as [
    HTMLElement,
    HTMLElement,
  ];
  const select = mode.querySelector('craft-select')!;
  const selectedOptionWidth = document.createElement('craft-select');
  selectedOptionWidth.style.width = 'max-content';
  selectedOptionWidth.innerHTML = `
    <select slot="input">
      <option selected>Total</option>
    </select>
  `;
  document.body.append(selectedOptionWidth);
  await Promise.all([
    select.updateComplete,
    selectedOptionWidth.updateComplete,
  ]);

  expect(select.getBoundingClientRect().width).toBeGreaterThan(
    selectedOptionWidth.getBoundingClientRect().width
  );
  expect(mode.getBoundingClientRect().top).toBe(
    minimum.getBoundingClientRect().top
  );
});

it('renders grouped inputs’ sub-labels smaller, lighter, and semibold', async () => {
  await import('../../styles/cp.css');
  await import('../field/field.js');
  document.body.innerHTML = `
    <craft-field fieldset label="Field Limit">
      <craft-field-group slot="input" class="auto-widths">
        <craft-field><span slot="label">Maximum</span><input slot="input"></craft-field>
      </craft-field-group>
    </craft-field>
  `;
  const outer = document.querySelector('craft-field')!;
  const inner = outer.querySelector<typeof outer>(
    'craft-field-group craft-field'
  )!;
  await outer.updateComplete;
  await inner.updateComplete;

  const label = (field: Element) =>
    getComputedStyle(field.shadowRoot!.querySelector('.form-field__label')!);

  expect(label(inner).fontWeight).toBe('600');
  expect(parseFloat(label(inner).fontSize)).toBeLessThan(
    parseFloat(label(outer).fontSize)
  );
  expect(label(inner).color).not.toBe(label(outer).color);
  expect(label(outer).fontWeight).toBe('700');
  // A <span> label sits as tightly as a <label> one.
  const subLabel = inner.querySelector('[slot="label"]');
  expect(subLabel).toBeInstanceOf(HTMLSpanElement);
  expect(getComputedStyle(subLabel!).lineHeight).toBe(
    getComputedStyle(subLabel!).fontSize
  );
});

it('draws a hairline between separated fields that share a row, and only there', async () => {
  const fieldGroup = document.createElement('craft-field-group');
  fieldGroup.separated = true;
  fieldGroup.style.width = '900px';
  fieldGroup.style.setProperty('--c-field-group-separator-spacing', '16px');
  fieldGroup.innerHTML = `
    <div class="width-50">Start</div>
    <div class="width-50">End</div>
    <div>Below</div>
  `;
  document.body.append(fieldGroup);
  await fieldGroup.updateComplete;
  const [start, end, below] = fieldGroup.querySelectorAll<HTMLElement>('div');
  const frames = () =>
    new Promise((resolve) =>
      requestAnimationFrame(() => requestAnimationFrame(resolve))
    );
  const follows = () =>
    [start, end, below].map((child) =>
      child!.hasAttribute('data-follows-column')
    );
  await frames();

  expect(follows()).toEqual([false, true, false]);
  expect(
    end!.getBoundingClientRect().left - start!.getBoundingClientRect().right
  ).toBe(32);
  const hairline = getComputedStyle(end!, '::before');
  expect(hairline.borderInlineStartWidth).toBe('1px');
  const gapMiddle =
    (start!.getBoundingClientRect().right + end!.getBoundingClientRect().left) /
    2;
  expect(
    end!.getBoundingClientRect().left + parseFloat(hairline.left)
  ).toBeCloseTo(gapMiddle, 0);

  fieldGroup.style.width = '390px';
  await frames();
  expect(follows()).toEqual([false, false, false]);

  fieldGroup.style.width = '900px';
  start!.hidden = true;
  await frames();
  expect(follows()).toEqual([false, false, false]);
});
