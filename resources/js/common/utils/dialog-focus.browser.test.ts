import {expect, it} from 'vite-plus/test';
import {autofocusField} from './dialog-focus';

it('skips a field disabled by the fieldset around it', () => {
  document.body.innerHTML = `
    <div>
      <fieldset disabled><input aria-label="In a disabled fieldset"></fieldset>
      <input aria-label="Code">
    </div>`;

  expect(
    autofocusField(document.body.firstElementChild!)?.getAttribute('aria-label')
  ).toBe('Code');
});
