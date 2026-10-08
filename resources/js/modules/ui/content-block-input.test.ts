import {afterEach, expect, it} from 'vite-plus/test';
import './content-block-input';

afterEach(() => document.body.replaceChildren());

it('adds and explicitly clears a Content Block', () => {
  const input = document.createElement('craft-content-block-input');
  input.setAttribute('add-label', 'Create block');
  input.setAttribute('clear-label', 'Discard block');
  input.setAttribute('empty-label', 'Nothing here');
  input.innerHTML = `
    <craft-empty>
      <craft-button data-content-block-add></craft-button>
    </craft-empty>
  `;
  document.body.append(input);

  input.querySelector<HTMLElement>('[data-content-block-add]')!.click();
  expect(input.querySelector('[data-content-block]')).not.toBeNull();
  expect(input.querySelector('[data-content-block-remove]')?.textContent).toBe(
    'Discard block'
  );

  input.querySelector<HTMLElement>('[data-content-block-remove]')!.click();
  expect(input.querySelector('[data-content-block]')).toBeNull();
  expect(input.querySelector('craft-empty')?.getAttribute('label')).toBe(
    'Nothing here'
  );
  expect(input.querySelector('[data-content-block-add]')?.textContent).toBe(
    'Create block'
  );
});
