import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser';
import type CraftButton from '../button/button.js';
import type CraftDialog from './dialog.js';
import '../../styles/cp.css';
import '../button/button.js';
import './dialog.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function openDialog(
  configure: (dialog: CraftDialog) => void = () => {}
): Promise<CraftDialog> {
  const dialog = document.createElement('craft-dialog') as CraftDialog;
  dialog.label = 'Test dialog';
  configure(dialog);
  document.body.append(dialog);
  dialog.opened = true;
  await dialog.updateComplete;
  return dialog;
}

function shadow<E extends Element = HTMLElement>(
  dialog: CraftDialog,
  selector: string
): E {
  return dialog.shadowRoot!.querySelector<E>(selector)!;
}

function button(text: string, slot?: string): HTMLButtonElement {
  const element = document.createElement('button');
  element.textContent = text;
  if (slot) {
    element.slot = slot;
  }
  return element;
}

function deepActiveElement(): Element | null {
  let active = document.activeElement;
  while (active?.shadowRoot?.activeElement) {
    active = active.shadowRoot.activeElement;
  }
  return active;
}

function longText(): string {
  return Array.from(
    {length: 80},
    (_, i) => `Line ${i + 1} of a dialog body that has to scroll. `
  ).join('');
}

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

it('opens a default dialog as modal', async () => {
  const outside = button('Outside');
  document.body.append(outside);
  const dialog = await openDialog();

  expect(shadow(dialog, 'dialog').matches(':modal')).toBe(true);

  outside.focus();
  expect(document.activeElement).not.toBe(outside);
});

it('moves focus into the dialog on open', async () => {
  const dialog = await openDialog((dialog) => dialog.append(button('Inside')));

  expect(deepActiveElement()).toBe(shadow(dialog, '.close'));
});

it('draws no focus ring around the dialog when it takes focus itself', async () => {
  const dialog = await openDialog((dialog) => {
    dialog.noHeader = true;
  });
  const native = shadow<HTMLDialogElement>(dialog, 'dialog');

  await userEvent.keyboard('{Shift}');
  native.focus();

  expect(native.matches(':focus-visible')).toBe(true);
  expect(getComputedStyle(native).outlineStyle).toBe('none');
});

it('focuses an autofocus element on open', async () => {
  const target = button('Autofocus');
  target.autofocus = true;
  await openDialog((dialog) => dialog.append(button('First'), target));

  expect(deepActiveElement()).toBe(target);
});

it('keeps Tab and Shift+Tab inside a modal dialog', async () => {
  document.body.append(button('Before'));
  const inside = button('Inside');
  const footer = button('Footer', 'footer');
  const dialog = await openDialog((dialog) => dialog.append(inside, footer));
  const close = shadow(dialog, '.close');
  const allowed = [close, inside, footer];

  for (let i = 0; i < 6; i++) {
    await userEvent.keyboard('{Tab}');
    const active = deepActiveElement();
    if (active !== document.body) {
      expect(allowed).toContain(active);
    }
  }

  for (let i = 0; i < 6; i++) {
    await userEvent.keyboard('{Shift>}{Tab}{/Shift}');
    const active = deepActiveElement();
    if (active !== document.body) {
      expect(allowed).toContain(active);
    }
  }
});

it('returns focus to the invoker on close', async () => {
  const invoker = button('Open');
  document.body.append(invoker);
  const dialog = document.createElement('craft-dialog') as CraftDialog;
  dialog.label = 'Test dialog';
  document.body.append(dialog);
  invoker.addEventListener('click', () => (dialog.opened = true));

  await userEvent.click(invoker);
  await dialog.updateComplete;
  await userEvent.click(shadow(dialog, '.close'));
  await dialog.updateComplete;

  expect(dialog.opened).toBe(false);
  expect(document.activeElement).toBe(invoker);
});

it('focuses the element returnFocus returns on close', async () => {
  const invoker = button('Open');
  const target = button('Target');
  document.body.append(invoker, target);
  let receivedOpener: HTMLElement | null = null;
  invoker.focus();
  const dialog = await openDialog((dialog) => {
    dialog.returnFocus = (opener) => {
      receivedOpener = opener;
      return target;
    };
  });

  await userEvent.keyboard('{Escape}');
  await dialog.updateComplete;

  expect(receivedOpener).toBe(invoker);
  expect(document.activeElement).toBe(target);
});

it('leaves focus alone when it is outside the dialog at close', async () => {
  const invoker = button('Open');
  const outside = button('Outside');
  const target = button('Target');
  document.body.append(invoker, outside, target);
  invoker.focus();
  const dialog = await openDialog((dialog) => {
    dialog.nonModal = true;
    dialog.returnFocus = () => target;
  });

  outside.focus();
  dialog.opened = false;
  await dialog.updateComplete;

  expect(document.activeElement).toBe(outside);
});

it('applies returnFocus when the native dialog is closed directly', async () => {
  const invoker = button('Open');
  const target = button('Target');
  document.body.append(invoker, target);
  invoker.focus();
  const dialog = await openDialog((dialog) => {
    dialog.returnFocus = () => target;
  });

  const native = shadow<HTMLDialogElement>(dialog, 'dialog');
  const closed = new Promise((resolve) =>
    native.addEventListener('close', resolve, {once: true})
  );
  native.close();
  await closed;
  await dialog.updateComplete;

  expect(dialog.opened).toBe(false);
  expect(document.activeElement).toBe(target);
});

it('leaves focus on the opener when a non-modal dialog closes natively with focus already outside it', async () => {
  const opener = button('Open');
  const target = button('Target');
  document.body.append(opener, target);
  opener.focus();
  const returnFocus = vi.fn(() => target);
  const dialog = await openDialog((dialog) => {
    dialog.nonModal = true;
    dialog.returnFocus = returnFocus;
  });

  opener.focus();
  const native = shadow<HTMLDialogElement>(dialog, 'dialog');
  const closed = new Promise((resolve) =>
    native.addEventListener('close', resolve, {once: true})
  );
  native.close();
  await closed;
  await dialog.updateComplete;

  expect(dialog.opened).toBe(false);
  expect(returnFocus).not.toHaveBeenCalled();
  expect(document.activeElement).toBe(opener);
});

it('keeps the native return target when returnFocus returns nothing', async () => {
  const invoker = button('Open');
  document.body.append(invoker);
  const dialog = document.createElement('craft-dialog') as CraftDialog;
  dialog.label = 'Test dialog';
  dialog.returnFocus = () => null;
  document.body.append(dialog);
  invoker.addEventListener('click', () => (dialog.opened = true));

  await userEvent.click(invoker);
  await dialog.updateComplete;
  await userEvent.keyboard('{Escape}');
  await dialog.updateComplete;

  expect(document.activeElement).toBe(invoker);
});

async function openFromMenuItem(): Promise<{
  dialog: CraftDialog;
  menuInvoker: HTMLButtonElement;
}> {
  await import('../action-menu/action-menu.js');
  const template = document.createElement('template');
  template.innerHTML = `
    <craft-action-menu>
      <button slot="invoker" type="button">Field actions</button>
      <div slot="content">
        <craft-action-item>Copy attribute name</craft-action-item>
      </div>
    </craft-action-menu>
    <craft-dialog label="Copy attribute name"><p>Text</p></craft-dialog>
  `;
  document.body.append(template.content);
  const menu = document.querySelector('craft-action-menu') as HTMLElement & {
    updateComplete: Promise<unknown>;
  };
  const menuInvoker =
    menu.querySelector<HTMLButtonElement>('[slot="invoker"]')!;
  const item = menu.querySelector('craft-action-item') as HTMLElement & {
    action: unknown;
  };
  const dialog = document.querySelector('craft-dialog') as CraftDialog;
  item.action = {type: 'event', name: 'test:open-dialog'};
  window.addEventListener('test:open-dialog', () => (dialog.opened = true), {
    once: true,
  });
  await menu.updateComplete;

  menuInvoker.focus();
  await userEvent.keyboard('{Enter}');
  await menu.updateComplete;
  item.focus();
  await userEvent.keyboard('{Enter}');
  await dialog.updateComplete;
  expect(dialog.opened).toBe(true);

  return {dialog, menuInvoker};
}

it('returns focus to the action menu invoker after a menu item opens it', async () => {
  const {dialog, menuInvoker} = await openFromMenuItem();

  await userEvent.keyboard('{Escape}');
  await dialog.updateComplete;

  expect(dialog.opened).toBe(false);
  expect(document.activeElement).toBe(menuInvoker);
});

it('returns focus to the action menu invoker when focus left the dialog before it closed', async () => {
  const {dialog, menuInvoker} = await openFromMenuItem();

  (deepActiveElement() as HTMLElement).blur();
  expect(dialog.matches(':focus-within')).toBe(false);
  dialog.opened = false;
  await dialog.updateComplete;

  expect(document.activeElement).toBe(menuInvoker);
});

it('returns focus to the action menu invoker when the native dialog closes by itself', async () => {
  const {dialog, menuInvoker} = await openFromMenuItem();

  const native = shadow<HTMLDialogElement>(dialog, 'dialog');
  const closed = new Promise((resolve) =>
    native.addEventListener('close', resolve, {once: true})
  );
  native.close();
  await closed;
  await dialog.updateComplete;

  expect(dialog.opened).toBe(false);
  expect(document.activeElement).toBe(menuInvoker);
});

it('closes on Escape through craft-before-hide', async () => {
  const dialog = await openDialog();
  let asked = 0;
  dialog.addEventListener('craft-before-hide', () => asked++);

  await userEvent.keyboard('{Escape}');
  await dialog.updateComplete;

  expect(asked).toBe(1);
  expect(dialog.opened).toBe(false);
});

it('stays open on Escape when craft-before-hide is canceled', async () => {
  const invoker = button('Open');
  document.body.append(invoker);
  const dialog = document.createElement('craft-dialog') as CraftDialog;
  dialog.label = 'Test dialog';
  document.body.append(dialog);
  invoker.addEventListener('click', () => (dialog.opened = true));
  dialog.addEventListener('craft-before-hide', (event) =>
    event.preventDefault()
  );

  await userEvent.click(invoker);
  await dialog.updateComplete;
  await userEvent.keyboard('{Escape}');
  await dialog.updateComplete;

  expect(dialog.opened).toBe(true);
  expect(shadow<HTMLDialogElement>(dialog, 'dialog').open).toBe(true);
});

it('handles Escape itself in modal mode, not through the platform', async () => {
  // Dispatched rather than typed: an untrusted key never reaches the
  // platform's close watchers, which can stop delivering Escape to a dialog
  // beneath one that closed itself asynchronously.
  const inside = button('Inside');
  const dialog = await openDialog((dialog) => dialog.append(inside));
  const escape = new KeyboardEvent('keydown', {
    key: 'Escape',
    bubbles: true,
    composed: true,
    cancelable: true,
  });

  inside.dispatchEvent(escape);
  await dialog.updateComplete;

  expect(escape.defaultPrevented).toBe(true);
  expect(dialog.opened).toBe(false);
});

it('leaves Escape to content that claims it', async () => {
  const inside = button('Inside');
  inside.addEventListener('keydown', (event) => event.preventDefault());
  const dialog = await openDialog((dialog) => dialog.append(inside));

  inside.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: 'Escape',
      bubbles: true,
      composed: true,
      cancelable: true,
    })
  );
  await dialog.updateComplete;

  expect(dialog.opened).toBe(true);
});

it('lets the keyboard reach a scrolling body with nothing focusable', async () => {
  const dialog = await openDialog((dialog) => {
    dialog.noClose = true;
    dialog.textContent = longText();
  });
  const body = shadow(dialog, '.body');
  expect(body.scrollHeight).toBeGreaterThan(body.clientHeight);
  expect(deepActiveElement()).toBe(body);

  await userEvent.keyboard('{ArrowDown}');
  await expect.poll(() => body.scrollTop).toBeGreaterThan(0);
});

it('contains Tab in a non-modal dialog and releases it on close', async () => {
  const after = button('After');
  const inside = button('Inside');
  const dialog = await openDialog((dialog) => {
    dialog.nonModal = true;
    dialog.noClose = true;
    dialog.append(inside);
  });
  document.body.append(after);

  inside.focus();
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).toBe(inside);

  dialog.opened = false;
  await dialog.updateComplete;
  inside.blur();
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).not.toBe(inside);
});

it('scrolls a focused control clear of the footer', async () => {
  const first = button('First');
  const last = button('Last');
  const dialog = await openDialog((dialog) => {
    dialog.noClose = true;
    dialog.label = '';
    const text = document.createElement('p');
    text.textContent = longText();
    dialog.append(first, text, last, button('Footer', 'footer'));
  });

  first.focus();
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).toBe(last);

  const control = last.getBoundingClientRect();
  const footer = shadow(dialog, '.footer').getBoundingClientRect();
  expect(control.bottom).toBeLessThanOrEqual(footer.top + 1);
});

it('scrolls a focused control clear of the header', async () => {
  const first = button('First');
  const last = button('Last');
  const dialog = await openDialog((dialog) => {
    const text = document.createElement('p');
    text.textContent = longText();
    dialog.append(first, text, last);
  });
  shadow(dialog, '.body').scrollTop = 10_000;

  last.focus();
  await userEvent.keyboard('{Shift>}{Tab}{/Shift}');
  expect(document.activeElement).toBe(first);

  const control = first.getBoundingClientRect();
  const header = shadow(dialog, '.header').getBoundingClientRect();
  expect(control.top).toBeGreaterThanOrEqual(header.bottom);
});

it('gives the close button 24px of vertical room in the header', async () => {
  const dialog = await openDialog();
  const {height} = shadow(dialog, '.header').getBoundingClientRect();

  expect(height).toBeGreaterThan(24);
});

it('keeps the label as the name under no-header', async () => {
  const dialog = await openDialog((dialog) => {
    dialog.noHeader = true;
    dialog.append(button('Inside'));
  });

  const labelledBy = shadow(dialog, 'dialog').getAttribute('aria-labelledby');
  const heading = dialog.shadowRoot!.getElementById(labelledBy ?? '')!;
  expect(heading.textContent?.trim()).toBe('Test dialog');

  const {width, height} = heading.getBoundingClientRect();
  expect([width, height]).toEqual([0, 0]);

  const surface = shadow(dialog, '.surface').getBoundingClientRect();
  const body = shadow(dialog, '.body').getBoundingClientRect();
  expect(body.top - surface.top).toBeLessThanOrEqual(1);
});

it('outlines the surface when it overlays the page', async () => {
  const dialog = await openDialog();
  const style = getComputedStyle(shadow(dialog, '.surface'));

  expect(style.outlineStyle).toBe('solid');
  expect(parseFloat(style.outlineWidth)).toBeGreaterThanOrEqual(1);
});

it('fits a 320px viewport without horizontal scroll', async () => {
  await page.viewport(320, 640);
  try {
    const dialog = await openDialog((dialog) => {
      dialog.append(longText(), button('Footer', 'footer'));
    });

    const surface = shadow(dialog, '.surface').getBoundingClientRect();
    const header = shadow(dialog, '.header').getBoundingClientRect();
    const footer = shadow(dialog, '.footer').getBoundingClientRect();
    const body = shadow(dialog, '.body');

    expect(surface.left).toBeGreaterThanOrEqual(0);
    expect(surface.right).toBeLessThanOrEqual(320);
    expect(document.documentElement.scrollWidth).toBeLessThanOrEqual(320);
    expect(body.scrollWidth).toBeLessThanOrEqual(body.clientWidth);
    expect(body.scrollHeight).toBeGreaterThan(body.clientHeight);
    expect(header.top).toBeGreaterThanOrEqual(0);
    expect(footer.bottom).toBeLessThanOrEqual(640);
  } finally {
    await page.viewport(1280, 720);
  }
});

it('does not animate opening or closing', async () => {
  const dialog = await openDialog();

  for (const element of [
    shadow(dialog, 'dialog'),
    shadow(dialog, '.surface'),
  ]) {
    const style = getComputedStyle(element);
    expect(style.animationName).toBe('none');
    expect(
      style.transitionDuration.split(',').every((d) => parseFloat(d) === 0)
    ).toBe(true);
  }
  const backdrop = getComputedStyle(shadow(dialog, 'dialog'), '::backdrop');
  expect(backdrop.animationName).toBe('none');
});

describe('closing with a held key', () => {
  /** A `craft-button` opener, focused so the dialog hands focus back to it on close. */
  async function fixture(): Promise<{
    opener: CraftButton;
    dialog: CraftDialog;
    openCount: () => number;
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

    return {opener, dialog, openCount: () => count};
  }

  function closeButton(dialog: CraftDialog): HTMLButtonElement {
    return dialog.shadowRoot!.querySelector<HTMLButtonElement>('.close')!;
  }

  it('closes on Enter without reopening from the leftover keyup', async () => {
    const {opener, dialog, openCount} = await fixture();

    closeButton(dialog).focus();
    await userEvent.keyboard('{Enter}');
    await dialog.updateComplete;

    expect(dialog.opened).toBe(false);
    expect(document.activeElement).toBe(opener);
    expect(openCount()).toBe(0);
  });

  it('swallows the leftover keyup when other code closes the dialog', async () => {
    const {dialog, openCount} = await fixture();
    const pick = dialog.querySelector<HTMLButtonElement>('.pick')!;
    pick.addEventListener('click', () => {
      dialog.opened = false;
    });

    pick.focus();
    await userEvent.keyboard('{Enter}');
    await dialog.updateComplete;

    expect(dialog.opened).toBe(false);
    expect(openCount()).toBe(0);
  });

  it('lets the next Enter press through', async () => {
    const {dialog, openCount} = await fixture();

    closeButton(dialog).focus();
    await userEvent.keyboard('{Enter}');
    await dialog.updateComplete;
    await userEvent.keyboard('{Enter}');
    await dialog.updateComplete;

    expect(openCount()).toBe(1);
    expect(dialog.opened).toBe(true);
  });

  it('gives up waiting for the keyup once the window loses focus', async () => {
    const {dialog, openCount} = await fixture();
    const pick = dialog.querySelector<HTMLButtonElement>('.pick')!;
    pick.addEventListener('click', () => {
      dialog.opened = false;
    });

    pick.focus();
    await userEvent.keyboard('{Enter>}');
    await dialog.updateComplete;
    window.dispatchEvent(new Event('blur'));
    await userEvent.keyboard('{/Enter}');

    expect(openCount()).toBe(1);
  });

  it('gives up waiting for the keyup once the same key is pressed again', async () => {
    const {dialog, openCount} = await fixture();
    const pick = dialog.querySelector<HTMLButtonElement>('.pick')!;
    pick.addEventListener('click', () => {
      dialog.opened = false;
    });

    pick.focus();
    await userEvent.keyboard('{Enter>}');
    await dialog.updateComplete;
    window.dispatchEvent(new KeyboardEvent('keydown', {key: 'Enter'}));
    await userEvent.keyboard('{/Enter}');

    expect(openCount()).toBe(1);
  });
});
