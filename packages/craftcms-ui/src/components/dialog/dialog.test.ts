import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftDialog from './dialog.js';
import './dialog.js';

// happy-dom does not implement the full native <dialog> API; polyfill the
// minimum the component drives.
if (typeof HTMLDialogElement !== 'undefined') {
  HTMLDialogElement.prototype.showModal ??= function (this: HTMLDialogElement) {
    this.setAttribute('open', '');
  };
  HTMLDialogElement.prototype.show ??= function (this: HTMLDialogElement) {
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
  return dialog;
}

function shadow<E extends Element = HTMLElement>(
  dialog: CraftDialog,
  selector: string
): E | null {
  return dialog.shadowRoot!.querySelector<E>(selector);
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-dialog', () => {
  it('projects content without relocating it', async () => {
    const dialog = await createDialog();

    // The whole point of the shadow-slot rewrite: a framework that rendered
    // these nodes still owns them, in the position it put them.
    const paragraph = dialog.querySelector('p');
    expect(paragraph).not.toBeNull();
    expect(paragraph!.parentElement).toBe(dialog);
    expect(dialog.querySelector('[slot="content"]')).toBeNull();

    const slot = shadow<HTMLSlotElement>(dialog, '.body slot');
    expect(slot).not.toBeNull();
    expect(slot!.assignedNodes({flatten: true})).toContain(paragraph);
  });

  it('renders the label as a header title', async () => {
    const dialog = await createDialog();
    const title = shadow(dialog, '.title');

    expect(title).not.toBeNull();
    expect(title!.textContent).toBe('Test Dialog');
  });

  it('labels the dialog with the title', async () => {
    const dialog = await createDialog();

    expect(shadow(dialog, 'dialog')!.getAttribute('aria-labelledby')).toBe(
      shadow(dialog, '.title')!.id
    );
  });

  it('renders no header when there is no label', async () => {
    // An empty header is just a band of padding above the body.
    const dialog = document.createElement('craft-dialog') as CraftDialog;
    dialog.append(document.createElement('p'));
    document.body.append(dialog);
    await dialog.updateComplete;

    expect(shadow(dialog, '.header')).toBeNull();
    expect(shadow(dialog, '.title')).toBeNull();
    expect(shadow(dialog, '.close')).toBeNull();
  });

  it('drops aria-labelledby along with the header', async () => {
    // Pointing at a heading that isn't rendered would leave the dialog with a
    // broken accessible name rather than none.
    const dialog = document.createElement('craft-dialog') as CraftDialog;
    document.body.append(dialog);
    await dialog.updateComplete;

    expect(shadow(dialog, 'dialog')!.hasAttribute('aria-labelledby')).toBe(
      false
    );
  });

  it('brings the header back when a label is set later', async () => {
    const dialog = document.createElement('craft-dialog') as CraftDialog;
    document.body.append(dialog);
    await dialog.updateComplete;
    expect(shadow(dialog, '.header')).toBeNull();

    dialog.label = 'Now labelled';
    await dialog.updateComplete;

    expect(shadow(dialog, '.title')!.textContent).toBe('Now labelled');
    expect(shadow(dialog, 'dialog')!.getAttribute('aria-labelledby')).toBe(
      shadow(dialog, '.title')!.id
    );
  });

  it('renders a close button that closes the dialog', async () => {
    const dialog = await createDialog();
    dialog.opened = true;
    await dialog.updateComplete;
    expect(dialog.opened).toBe(true);

    shadow<HTMLButtonElement>(dialog, '.close')!.click();
    await dialog.updateComplete;

    expect(dialog.opened).toBe(false);
  });

  it('opens when the open attribute is set before connecting', async () => {
    const dialog = await createDialog((d) => d.setAttribute('open', ''));

    expect(dialog.opened).toBe(true);
    expect(shadow<HTMLDialogElement>(dialog, 'dialog')!.open).toBe(true);
  });

  it('reflects the opened state back to the open attribute', async () => {
    const dialog = await createDialog();

    dialog.opened = true;
    await dialog.updateComplete;
    expect(dialog.hasAttribute('open')).toBe(true);

    dialog.opened = false;
    await dialog.updateComplete;
    expect(dialog.hasAttribute('open')).toBe(false);
  });

  it('shows the footer only when footer content is slotted', async () => {
    const bare = await createDialog();
    expect(shadow(bare, '.footer')!.hasAttribute('hidden')).toBe(true);

    const dialog = await createDialog((d) => {
      const button = document.createElement('button');
      button.slot = 'footer';
      button.textContent = 'Done';
      d.append(button);
    });

    const footer = shadow(dialog, '.footer')!;
    expect(footer.hasAttribute('hidden')).toBe(false);
    expect(
      footer
        .querySelector<HTMLSlotElement>('slot[name="footer"]')!
        .assignedNodes({flatten: true})
    ).toContain(dialog.querySelector('button'));
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
    await dialog.updateComplete;

    dialog.querySelector<HTMLButtonElement>('[data-dialog="close"]')!.click();
    await dialog.updateComplete;

    expect(dialog.opened).toBe(false);
  });

  it('emits craft-show and craft-hide', async () => {
    const dialog = await createDialog();
    const events: string[] = [];
    dialog.addEventListener('craft-show', () => events.push('craft-show'));
    dialog.addEventListener('craft-hide', () => events.push('craft-hide'));

    dialog.opened = true;
    await dialog.updateComplete;
    dialog.opened = false;
    await dialog.updateComplete;

    expect(events).toEqual(['craft-show', 'craft-hide']);
  });

  it('does not emit lifecycle events on first render', async () => {
    const events: string[] = [];
    const dialog = document.createElement('craft-dialog') as CraftDialog;
    dialog.addEventListener('craft-hide', () => events.push('craft-hide'));
    document.body.append(dialog);
    await dialog.updateComplete;

    expect(events).toEqual([]);
  });

  it('emits the after-* pair once the update settles', async () => {
    const dialog = await createDialog();
    const events: string[] = [];
    dialog.addEventListener('craft-after-show', () =>
      events.push('craft-after-show')
    );
    dialog.addEventListener('craft-after-hide', () =>
      events.push('craft-after-hide')
    );

    dialog.opened = true;
    await dialog.updateComplete;
    await Promise.resolve();
    expect(events).toEqual(['craft-after-show']);

    dialog.opened = false;
    await dialog.updateComplete;
    await Promise.resolve();
    expect(events).toEqual(['craft-after-show', 'craft-after-hide']);
  });

  describe('non-modal', () => {
    it('opens without entering the top layer', async () => {
      const calls: string[] = [];
      const dialog = await createDialog((d) => d.setAttribute('non-modal', ''));
      const native = shadow<HTMLDialogElement>(dialog, 'dialog')!;

      native.show = () => {
        calls.push('show');
        native.setAttribute('open', '');
      };
      native.showModal = () => calls.push('showModal');

      dialog.opened = true;
      await dialog.updateComplete;

      expect(calls).toEqual(['show']);
    });

    it('renders its own backdrop, since ::backdrop only paints for the top layer', async () => {
      const dialog = await createDialog((d) => d.setAttribute('non-modal', ''));
      expect(shadow(dialog, '.backdrop')).toBeNull();

      dialog.opened = true;
      await dialog.updateComplete;

      expect(shadow(dialog, '.backdrop')).not.toBeNull();
    });

    it('closes on Escape, which the platform no longer handles', async () => {
      const dialog = await createDialog((d) => d.setAttribute('non-modal', ''));
      dialog.opened = true;
      await dialog.updateComplete;

      dialog.dispatchEvent(
        new KeyboardEvent('keydown', {key: 'Escape', bubbles: true})
      );
      await dialog.updateComplete;

      expect(dialog.opened).toBe(false);
    });
  });

  describe('closeOnOutsideClick', () => {
    it('ignores backdrop clicks by default', async () => {
      const dialog = await createDialog();
      dialog.opened = true;
      await dialog.updateComplete;

      shadow<HTMLDialogElement>(dialog, 'dialog')!.click();
      await dialog.updateComplete;

      expect(dialog.opened).toBe(true);
    });

    it('closes on a backdrop click when enabled', async () => {
      const dialog = await createDialog((d) =>
        d.setAttribute('close-on-outside-click', '')
      );
      dialog.opened = true;
      await dialog.updateComplete;

      shadow<HTMLDialogElement>(dialog, 'dialog')!.click();
      await dialog.updateComplete;

      expect(dialog.opened).toBe(false);
    });
  });
});
