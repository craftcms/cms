import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftPopover from './popover.js';
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

  it('resolves the invoker from the for attribute', async () => {
    const {popover, button} = await createFixture();
    expect(popover._overlayInvokerNode).toBe(button);
  });

  it('prefers an explicit anchor element over for', async () => {
    const anchor = document.createElement('button');
    anchor.id = 'real-anchor';
    document.body.append(anchor);

    const {popover} = await createFixture((p) => {
      p.setAttribute('for', 'popover-trigger');
      p.anchor = anchor;
    });

    expect(popover._overlayInvokerNode).toBe(anchor);
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

    expect(popover._overlayInvokerNode).toBe(button);
    expect(popover._overlayReferenceNode).toBe(reference);
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
