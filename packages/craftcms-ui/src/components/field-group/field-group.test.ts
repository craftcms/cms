import {beforeEach, expect, it} from 'vite-plus/test';
import './field-group.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('provides responsive columns for field widths', async () => {
  const fieldGroup = document.createElement('craft-field-group');
  fieldGroup.innerHTML = `
    <craft-field class="width-33"></craft-field>
    <craft-field class="width-66"></craft-field>
  `;
  document.body.append(fieldGroup);
  await fieldGroup.updateComplete;

  expect(getComputedStyle(fieldGroup).gridTemplateColumns).toContain(
    'repeat(12'
  );
  expect(fieldGroup.querySelector('style')?.textContent).toContain(
    'craft-field-group > .width-33'
  );
  expect(fieldGroup.querySelector('style')?.textContent).toContain(
    'craft-field-group > .width-66'
  );
});
