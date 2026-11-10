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

it('is named by its label attribute when icon-only', async () => {
  const button = document.createElement('craft-copy-button');
  button.label = 'Copy the cleanup command';
  document.body.append(button);
  await button.updateComplete;

  expect(
    button.shadowRoot!.querySelector('craft-button')!.getAttribute('aria-label')
  ).toBe('Copy the cleanup command');
});

it('shows its own icon until copied, then the copy outcome', async () => {
  const button = document.createElement('craft-copy-button');
  button.icon = 'robot';
  document.body.append(button);
  await button.updateComplete;
  const inner = () => button.shadowRoot!.querySelector('craft-button')!;

  expect(inner().getAttribute('icon')).toBe('robot');

  await button.showStatus('success');

  expect(inner().getAttribute('icon')).toBe('check');
});

it('pulls its button flush, like craft-button', async () => {
  const button = await render('Copy prompt');
  // The CP supplies the button's padding; the test page doesn't.
  button.style.setProperty('--c-form-control-spacing-inline', '12px');
  button.size = 'medium';
  button.variant = 'plain';
  button.flush = 'inline-start';
  await button.updateComplete;
  const inner = button.shadowRoot!.querySelector('craft-button')!;
  await inner.updateComplete;

  // 12px of padding plus craft-button's default 1px border.
  expect(getComputedStyle(inner).marginInlineStart).toBe('-13px');
});

it('keeps its tooltip for the copy outcome when told to hide it', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const button = await render('Copy prompt');
  button.hideTooltip = true;
  await button.updateComplete;
  const tooltip = button.shadowRoot!.querySelector('craft-tooltip')!;

  await userEvent.hover(button);
  await new Promise((resolve) => setTimeout(resolve, 300));

  expect(tooltip.open).toBe(false);
  // It still describes the button, so it has nothing to say until a copy.
  expect(tooltip.textContent?.trim()).toBe('');

  await button.showStatus('success');

  expect(tooltip.open).toBe(true);
  expect(tooltip.textContent?.trim()).toBe('Copied');
});
