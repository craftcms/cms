import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftButton from '../button/button.js';
import type CraftDialog from './dialog.js';
import '../button/button.js';
import './dialog.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('centers a modal dialog in the viewport', async () => {
  const dialog = document.createElement('craft-dialog') as CraftDialog;
  dialog.label = 'Centered dialog';
  dialog.textContent = 'Dialog content';
  document.body.append(dialog);
  dialog.opened = true;
  await dialog.updateComplete;

  const bounds = dialog
    .shadowRoot!.querySelector('dialog')!
    .getBoundingClientRect();

  expect(
    Math.abs(bounds.left + bounds.width / 2 - window.innerWidth / 2)
  ).toBeLessThan(1);
  expect(
    Math.abs(bounds.top + bounds.height / 2 - window.innerHeight / 2)
  ).toBeLessThan(1);
});

describe('closing with a held key', () => {
  /**
   * A `craft-button` that opens a dialog, focused when the dialog opens so the
   * platform hands focus back to it on close. The button clicks on any Enter
   * or Space keyup, so a keyup left over from the press that closed the dialog
   * would open it again.
   */
  async function fixture(): Promise<{
    opener: CraftButton;
    dialog: CraftDialog;
    opens: () => number;
  }> {
    const opener = document.createElement('craft-button') as CraftButton;
    opener.textContent = 'Open';
    const dialog = document.createElement('craft-dialog') as CraftDialog;
    dialog.label = 'Held key';
    dialog.innerHTML = '<button type="button" class="pick">Pick</button>';
    document.body.append(opener, dialog);

    let count = 0;
    opener.addEventListener('click', () => {
      count++;
      dialog.opened = true;
    });

    await opener.updateComplete;
    opener.focus();
    dialog.opened = true;
    await dialog.updateComplete;

    return {opener, dialog, opens: () => count};
  }

  function closeButton(dialog: CraftDialog): HTMLButtonElement {
    return dialog.shadowRoot!.querySelector<HTMLButtonElement>('.close')!;
  }

  it('closes on Enter without reopening from the leftover keyup', async () => {
    const {userEvent} = await import('vite-plus/test/browser');
    const {opener, dialog, opens} = await fixture();

    closeButton(dialog).focus();
    await userEvent.keyboard('{Enter}');
    await dialog.updateComplete;

    expect(dialog.opened).toBe(false);
    expect(document.activeElement).toBe(opener);
    expect(opens()).toBe(0);
  });

  it('swallows the leftover keyup when other code closes the dialog', async () => {
    const {userEvent} = await import('vite-plus/test/browser');
    const {dialog, opens} = await fixture();
    const pick = dialog.querySelector<HTMLButtonElement>('.pick')!;
    pick.addEventListener('click', () => {
      dialog.opened = false;
    });

    pick.focus();
    await userEvent.keyboard('{Enter}');
    await dialog.updateComplete;

    expect(dialog.opened).toBe(false);
    expect(opens()).toBe(0);
  });

  it('lets the next Enter press through', async () => {
    const {userEvent} = await import('vite-plus/test/browser');
    const {dialog, opens} = await fixture();

    closeButton(dialog).focus();
    await userEvent.keyboard('{Enter}');
    await dialog.updateComplete;
    await userEvent.keyboard('{Enter}');
    await dialog.updateComplete;

    expect(opens()).toBe(1);
    expect(dialog.opened).toBe(true);
  });

  it('gives up waiting for the keyup once the window loses focus', async () => {
    const {userEvent} = await import('vite-plus/test/browser');
    const {dialog, opens} = await fixture();

    closeButton(dialog).focus();
    await userEvent.keyboard('{Enter>}');
    await dialog.updateComplete;
    window.dispatchEvent(new Event('blur'));
    await userEvent.keyboard('{/Enter}');

    expect(opens()).toBe(1);
  });

  it('gives up waiting for the keyup once the same key is pressed again', async () => {
    const {userEvent} = await import('vite-plus/test/browser');
    const {dialog, opens} = await fixture();

    closeButton(dialog).focus();
    await userEvent.keyboard('{Enter>}');
    await dialog.updateComplete;
    window.dispatchEvent(new KeyboardEvent('keydown', {key: 'Enter'}));
    await userEvent.keyboard('{/Enter}');

    expect(opens()).toBe(1);
  });
});
