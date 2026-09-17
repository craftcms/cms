import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftButton from './button.js';
import './button.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

function isFlagged(button: CraftButton): boolean {
  return !!button.shadowRoot?.querySelector('.a11y-error');
}

/**
 * Mounts a button inside a container that starts hidden the way `craft-tabs`
 * hides a panel that isn't selected -- `display: none` and `visibility:
 * hidden` together. The inherited `visibility` is what empties the button's
 * name; `display: none` on an ancestor alone doesn't.
 */
async function mountHidden(label: string | null): Promise<{
  button: CraftButton;
  container: HTMLElement;
}> {
  const container = document.createElement('div');
  container.style.cssText = 'display: none; visibility: hidden;';

  const button = document.createElement('craft-button') as CraftButton;
  button.setAttribute('icon', 'image-landscape');

  if (label !== null) {
    button.textContent = label;
  }

  container.append(button);
  document.body.append(container);
  await button.updateComplete;

  // The name is computed asynchronously after the first render. An empty
  // string rather than undefined proves the check ran while still hidden.
  await vi.waitFor(() => expect(button.accessibleName).toBe(''));

  return {button, container};
}

it('does not flag a labelled button that was hidden when it first rendered', async () => {
  const {button, container} = await mountHidden('Cropping Rectangle');

  expect(isFlagged(button)).toBe(false);

  container.style.cssText = '';

  await vi.waitFor(() =>
    expect(button.accessibleName).toBe('Cropping Rectangle')
  );
  expect(isFlagged(button)).toBe(false);
});

it('still flags an unnamed button once it is shown', async () => {
  const {button, container} = await mountHidden(null);

  // Not judged while hidden: an empty name there says nothing either way.
  expect(isFlagged(button)).toBe(false);

  container.style.cssText = '';

  await vi.waitFor(() => expect(isFlagged(button)).toBe(true));
});

it('flags an unnamed button that is visible from the start', async () => {
  const button = document.createElement('craft-button') as CraftButton;
  button.setAttribute('icon', 'image-landscape');
  document.body.append(button);
  await button.updateComplete;

  await vi.waitFor(() => expect(isFlagged(button)).toBe(true));
});

describe('flush', () => {
  /**
   * A button at the top-left corner of a container, with the tokens and
   * border-box sizing the CP normally supplies.
   */
  async function mountFlush(
    attrs: Record<string, string>,
    label: string | null = 'Edit'
  ): Promise<{button: CraftButton; container: HTMLElement}> {
    const container = document.createElement('div');
    container.style.cssText = [
      'position: absolute',
      // Out of a line box, whose baseline would nudge the button down.
      'display: flex',
      'align-items: flex-start',
      'inset-block-start: 100px',
      'inset-inline-start: 100px',
      'font: 16px/normal sans-serif',
      '--c-size-control-md: 34px',
      '--c-size-control-sm: 24px',
      '--c-form-control-spacing-inline: 12px',
      '--c-spacing-sm: 6px',
    ].join(';');

    const button = document.createElement('craft-button') as CraftButton;
    button.style.boxSizing = 'border-box';
    button.setAttribute('variant', 'plain');
    button.setAttribute('aria-label', 'Edit');
    for (const [name, value] of Object.entries(attrs)) {
      button.setAttribute(name, value);
    }
    if (label !== null) {
      button.textContent = label;
    }

    container.append(button);
    document.body.append(container);
    await button.updateComplete;

    return {button, container};
  }

  function labelRect(button: CraftButton): DOMRect {
    const range = document.createRange();
    range.selectNodeContents(button);
    return range.getBoundingClientRect();
  }

  function iconRect(button: CraftButton): DOMRect {
    return button
      .shadowRoot!.querySelector('craft-icon')!
      .getBoundingClientRect();
  }

  it('lines the label up with the text around it on every side', async () => {
    const {button, container} = await mountFlush({flush: ''});
    const box = container.getBoundingClientRect();
    const label = labelRect(button);

    expect(label.left).toBeCloseTo(box.left, 0);
    expect(label.top).toBeCloseTo(box.top, 0);
    expect(label.right).toBeCloseTo(box.right, 0);
    expect(label.bottom).toBeCloseTo(box.bottom, 0);
  });

  it('only pulls in the sides it names', async () => {
    const {button} = await mountFlush({flush: 'inline-end block-start'});
    const style = getComputedStyle(button);

    expect(style.marginInlineStart).toBe('0px');
    expect(style.marginInlineEnd).toBe('-13px');
    expect(parseFloat(style.marginBlockStart)).toBeLessThan(0);
    expect(style.marginBlockEnd).toBe('0px');
  });

  it('takes both sides of an axis from its name', async () => {
    const {button} = await mountFlush({flush: 'inline'});
    const style = getComputedStyle(button);

    expect(style.marginInlineStart).toBe('-13px');
    expect(style.marginInlineEnd).toBe('-13px');
    expect(style.marginBlockStart).toBe('0px');
  });

  it('follows the padding of a smaller button', async () => {
    const {button} = await mountFlush({flush: 'inline-start', size: 'small'});

    expect(getComputedStyle(button).marginInlineStart).toBe('-7px');
  });

  it('lines an icon-only button’s icon up instead', async () => {
    const {button, container} = await mountFlush(
      {flush: '', icon: 'pen'},
      null
    );
    const box = container.getBoundingClientRect();
    const icon = iconRect(button);

    expect(icon.left).toBeCloseTo(box.left, 0);
    expect(icon.top).toBeCloseTo(box.top, 0);
  });

  it('leaves a button without it where it is', async () => {
    const {button} = await mountFlush({});
    const style = getComputedStyle(button);

    expect(style.marginInlineStart).toBe('0px');
    expect(style.marginBlockStart).toBe('0px');
  });
});

describe('icon spacing', () => {
  async function mountLabelled(
    attrs: Record<string, string>,
    dir: 'ltr' | 'rtl' = 'ltr'
  ): Promise<{icon: DOMRect; label: DOMRect}> {
    const container = document.createElement('div');
    container.dir = dir;
    container.style.cssText = '--c-spacing-sm: 6px; font: 16px sans-serif';

    const button = document.createElement('craft-button') as CraftButton;
    for (const [name, value] of Object.entries(attrs)) {
      button.setAttribute(name, value);
    }
    button.textContent = 'Edit';
    container.append(button);
    document.body.append(container);
    await button.updateComplete;

    const range = document.createRange();
    range.selectNodeContents(button);

    return {
      icon: button
        .shadowRoot!.querySelector('craft-icon')!
        .getBoundingClientRect(),
      label: range.getBoundingClientRect(),
    };
  }

  it('leaves a gap after a prefix icon', async () => {
    const {icon, label} = await mountLabelled({icon: 'pen'});

    expect(label.left - icon.right).toBeCloseTo(6, 0);
  });

  it('leaves a gap before a suffix icon', async () => {
    const {icon, label} = await mountLabelled({
      icon: 'pen',
      'icon-position': 'suffix',
    });

    expect(icon.left - label.right).toBeCloseTo(6, 0);
  });

  it('keeps the gap between them right to left', async () => {
    const {icon, label} = await mountLabelled({icon: 'pen'}, 'rtl');

    expect(icon.left - label.right).toBeCloseTo(6, 0);
  });
});
