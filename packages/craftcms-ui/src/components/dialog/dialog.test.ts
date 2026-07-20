import {beforeEach, describe, expect, it} from 'vitest';
import type CraftDialog from './dialog.js';
import './dialog.js';

// happy-dom may not implement the full native <dialog> API Lion's overlay
// controller uses; polyfill the minimum.
if (typeof HTMLDialogElement !== 'undefined') {
  HTMLDialogElement.prototype.showModal ??= function (this: HTMLDialogElement) {
    this.setAttribute('open', '');
  };
  HTMLDialogElement.prototype.close ??= function (this: HTMLDialogElement) {
    this.removeAttribute('open');
  };
}

async function createDialog(
  configure: (dialog: CraftDialog) => void = () => {}
): Promise<CraftDialog> {
  const dialog = document.createElement('craft-dialog') as CraftDialog;
  dialog.setAttribute('label', 'Test Dialog');
  const message = document.createElement('p');
  message.textContent = 'Dialog body text';
  dialog.append(message);
  configure(dialog);
  document.body.append(dialog);

  await dialog.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  return dialog;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

/**
 * Waits out Lion's async OverlayController show/hide cycle so its 'show'/
 * 'hide' events have synced back before the next state change.
 */
function settle(dialog: CraftDialog): Promise<void> {
  return dialog.updateComplete.then(
    () => new Promise((resolve) => setTimeout(resolve))
  );
}

describe('craft-dialog', () => {
  it('wraps default-slot content into a slot="content" child', async () => {
    const dialog = await createDialog();
    const content = dialog.querySelector('[slot="content"]');
    expect(content).not.toBeNull();
    expect(content!.textContent).toContain('Dialog body text');
  });

  it('renders the label as a header title', async () => {
    const dialog = await createDialog();
    const title = dialog.querySelector('.craft-dialog__title');
    expect(title).not.toBeNull();
    expect(title!.textContent).toBe('Test Dialog');
  });

  it('renders a close button that closes the dialog', async () => {
    const dialog = await createDialog();
    dialog.opened = true;
    await settle(dialog);
    expect(dialog.opened).toBe(true);

    const close = dialog.querySelector<HTMLButtonElement>(
      '.craft-dialog__close'
    );
    expect(close).not.toBeNull();
    close!.click();
    await settle(dialog);
    expect(dialog.opened).toBe(false);
  });

  it('opens when the open attribute is set before connecting', async () => {
    const dialog = await createDialog((d) => d.setAttribute('open', ''));
    expect(dialog.opened).toBe(true);
  });

  it('reflects the opened state back to the open attribute', async () => {
    const dialog = await createDialog();
    dialog.opened = true;
    await settle(dialog);
    expect(dialog.hasAttribute('open')).toBe(true);

    dialog.opened = false;
    await settle(dialog);
    expect(dialog.hasAttribute('open')).toBe(false);
  });

  it('moves footer-slotted children into the footer', async () => {
    const dialog = await createDialog((d) => {
      const button = document.createElement('button');
      button.slot = 'footer';
      button.textContent = 'Done';
      d.append(button);
    });
    const footer = dialog.querySelector('.craft-dialog__footer');
    expect(footer).not.toBeNull();
    expect(footer!.querySelector('button')?.textContent).toBe('Done');
  });

  it('closes when a data-dialog="close" descendant is clicked', async () => {
    const dialog = await createDialog((d) => {
      const button = document.createElement('button');
      button.slot = 'footer';
      button.setAttribute('data-dialog', 'close');
      button.textContent = 'Close';
      d.append(button);
    });
    dialog.opened = true;
    await settle(dialog);

    dialog.querySelector<HTMLButtonElement>('[data-dialog="close"]')!.click();
    await settle(dialog);
    expect(dialog.opened).toBe(false);
  });

  it('emits craft-show and craft-hide', async () => {
    const dialog = await createDialog();
    const events: string[] = [];
    dialog.addEventListener('craft-show', () => events.push('craft-show'));
    dialog.addEventListener('craft-hide', () => events.push('craft-hide'));

    dialog.opened = true;
    await settle(dialog);
    dialog.opened = false;
    await settle(dialog);

    expect(events).toEqual(['craft-show', 'craft-hide']);
  });
});
