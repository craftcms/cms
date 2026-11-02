import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftPopover from './popover.js';
import styles from './popover.styles.js';
import './popover.js';

async function createFixture(
  configure: (popover: CraftPopover, button: HTMLButtonElement) => void = (
    popover
  ) => {
    popover.setAttribute('for', 'popover-trigger');
  }
): Promise<{popover: CraftPopover; button: HTMLButtonElement}> {
  const button = document.createElement('button');
  button.id = 'popover-trigger';
  button.textContent = 'Open';
  document.body.append(button);

  const popover = document.createElement('craft-popover') as CraftPopover;
  configure(popover, button);
  popover.textContent = 'Popover content';
  document.body.append(popover);

  await popover.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  return {popover, button};
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-popover', () => {
  it('wraps default-slot content into a slot="content" child', async () => {
    const {popover} = await createFixture();
    const content = popover.querySelector('[slot="content"]');
    expect(content).not.toBeNull();
    expect(content!.textContent).toContain('Popover content');
  });

  it('leaves comments out of the default-slot content it wraps', async () => {
    const popover = document.createElement('craft-popover') as CraftPopover;
    const content = document.createElement('div');
    content.slot = 'content';
    content.textContent = 'Slotted content';
    popover.append(document.createComment('A note about the content'), content);
    document.body.append(popover);
    await popover.updateComplete;

    expect([...popover.querySelectorAll('[slot="content"]')]).toStrictEqual([
      content,
    ]);
  });

  // Matters where the Popover API is missing and the top layer isn't used.
  it('stacks at the popover layer token', () => {
    expect(styles.cssText).toContain('z-index: var(--c-layer-popover, 9999)');
  });

  it('resolves the invoker from the for attribute', async () => {
    const {popover, button} = await createFixture();
    expect(popover.invoker).toBe(button);
  });

  it('prefers an explicit anchor element over for', async () => {
    const anchor = document.createElement('button');
    anchor.id = 'real-anchor';
    document.body.append(anchor);

    const {popover} = await createFixture((p) => {
      p.setAttribute('for', 'popover-trigger');
      p.anchor = anchor;
    });

    expect(popover.invoker).toBe(anchor);
  });

  it('uses a virtual anchor context element as its invoker', async () => {
    let reference!: {
      contextElement: HTMLElement;
      getBoundingClientRect(): DOMRect;
    };
    const {popover, button} = await createFixture(
      (configuredPopover, configuredButton) => {
        reference = {
          contextElement: configuredButton,
          getBoundingClientRect: () => new DOMRect(10, 20),
        };
        configuredPopover.anchor = reference;
      }
    );

    expect(popover.invoker).toBe(button);
  });

  it('marks the invoker as the thing that expands the overlay', async () => {
    const {popover, button} = await createFixture();

    expect(button.getAttribute('aria-expanded')).toBe('false');

    await popover.show();

    expect(button.getAttribute('aria-expanded')).toBe('true');
  });

  it('leaves the invoker alone when the consumer owns the aria', async () => {
    const {popover, button} = await createFixture((popover) => {
      popover.setAttribute('for', 'popover-trigger');
      popover.setAttribute('without-invoker-aria', '');
    });

    // The invoker may be a positioning anchor rather than the control, and a
    // generic element can't carry `aria-expanded` at all.
    await popover.show();

    expect(button.hasAttribute('aria-expanded')).toBe(false);
  });

  it('tracks opened state through show() and hide()', async () => {
    const {popover} = await createFixture();
    await popover.show();
    expect(popover.opened).toBe(true);
    await popover.hide();
    expect(popover.opened).toBe(false);
  });

  it('show()/hide() resolve and emit craft events', async () => {
    const {popover} = await createFixture();
    const events: string[] = [];
    for (const type of [
      'craft-show',
      'craft-after-show',
      'craft-hide',
      'craft-after-hide',
    ]) {
      popover.addEventListener(type, () => events.push(type));
    }

    await popover.show();
    await new Promise((resolve) => setTimeout(resolve));
    await popover.hide();
    await new Promise((resolve) => setTimeout(resolve));

    expect(events).toEqual([
      'craft-show',
      'craft-after-show',
      'craft-hide',
      'craft-after-hide',
    ]);
  });

  it('returns the invoker to aria-expanded="false" when it closes', async () => {
    const {popover, button} = await createFixture();
    await popover.show();
    await popover.hide();

    expect(button.getAttribute('aria-expanded')).toBe('false');
  });

  it("keeps a consumer's own aria-expanded with without-invoker-aria", async () => {
    const {popover, button} = await createFixture((popover, button) => {
      popover.setAttribute('for', 'popover-trigger');
      popover.setAttribute('without-invoker-aria', '');
      button.setAttribute('aria-expanded', 'false');
    });

    button.setAttribute('aria-expanded', 'true');
    await popover.show();
    await popover.hide();

    expect(button.getAttribute('aria-expanded')).toBe('true');
  });

  it('announces a requested change once, before it happens', async () => {
    const {popover, button} = await createFixture();
    const events: string[] = [];
    for (const type of ['before-opened', 'before-closed', 'opened-changed']) {
      popover.addEventListener(type, () =>
        events.push(`${type}:${popover.opened}`)
      );
    }

    button.click();
    await popover.updateComplete;
    await popover.hide();

    expect(events).toEqual([
      'before-opened:false',
      'opened-changed:true',
      'before-closed:true',
      'opened-changed:false',
    ]);
  });

  it('stays closed when before-opened is cancelled', async () => {
    const {popover, button} = await createFixture();
    popover.addEventListener('before-opened', (event) =>
      event.preventDefault()
    );

    button.click();
    await popover.updateComplete;

    expect(popover.opened).toBe(false);
  });

  it("doesn't open from a disabled invoker", async () => {
    const {popover, button} = await createFixture((popover, button) => {
      popover.setAttribute('for', 'popover-trigger');
      button.setAttribute('aria-disabled', 'true');
    });

    button.click();
    await popover.updateComplete;

    expect(popover.opened).toBe(false);
  });

  it('ignores invoker clicks when manual', async () => {
    const {popover, button} = await createFixture((popover) => {
      popover.setAttribute('for', 'popover-trigger');
      popover.setAttribute('manual', '');
    });

    button.click();
    await popover.updateComplete;

    expect(popover.opened).toBe(false);
  });

  // Its wrapper would otherwise be announced as an unnamed group around the
  // dialog.
  it('keeps its top-layer wrapper out of the accessibility tree', async () => {
    const {popover} = await createFixture();

    expect(
      popover.shadowRoot!.querySelector('.overlay-layer')!.getAttribute('role')
    ).toBe('none');
  });

  it('toggles on invoker click', async () => {
    const {popover, button} = await createFixture();

    button.click();
    await new Promise((resolve) => setTimeout(resolve));
    expect(popover.opened).toBe(true);

    button.click();
    await new Promise((resolve) => setTimeout(resolve));
    expect(popover.opened).toBe(false);
  });
});
