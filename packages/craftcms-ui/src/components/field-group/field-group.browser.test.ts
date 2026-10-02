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
