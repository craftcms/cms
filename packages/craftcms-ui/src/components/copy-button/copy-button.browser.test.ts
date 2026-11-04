import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftCopyButton from './copy-button.js';
import './copy-button.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(label: string): Promise<CraftCopyButton> {
  const button = document.createElement('craft-copy-button');
  button.value = 'You copied me!';
  button.textContent = label;
  document.body.append(button);
  await button.updateComplete;
  // Slot changes are signaled after rendering, and each can render again
  await new Promise((resolve) => setTimeout(resolve, 100));
  await button.updateComplete;

  return button;
}

function labelSlot(button: CraftCopyButton): HTMLSlotElement | null {
  return button.shadowRoot!.querySelector('craft-button slot');
}

it('shows its label inside the button, and settles there', async () => {
  const button = await render('Copy to clipboard');

  expect(labelSlot(button)?.assignedNodes()[0]?.textContent).toBe(
    'Copy to clipboard'
  );
  expect(
    button.shadowRoot!.querySelector('craft-button')!.getAttribute('aria-label')
  ).toBeNull();
});

it('is icon-only, with its own name, without a label', async () => {
  const button = await render('');

  expect(labelSlot(button)).toBeNull();
  expect(
    button.shadowRoot!.querySelector('craft-button')!.getAttribute('aria-label')
  ).toBe('Copy to clipboard');
});
