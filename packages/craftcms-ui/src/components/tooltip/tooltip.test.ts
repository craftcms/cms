import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftTooltip from './tooltip.js';
import './tooltip.js';

async function createFixture(
  tooltipAttrs: Record<string, string> = {}
): Promise<{tooltip: CraftTooltip; button: HTMLButtonElement}> {
  const button = document.createElement('button');
  button.id = 'trigger';
  button.textContent = 'Trigger';
  document.body.append(button);

  const tooltip = document.createElement('craft-tooltip') as CraftTooltip;
  tooltip.setAttribute('for', 'trigger');
  for (const [name, value] of Object.entries(tooltipAttrs)) {
    tooltip.setAttribute(name, value);
  }
  tooltip.textContent = 'Tooltip content';
  document.body.append(tooltip);

  await tooltip.updateComplete;
  // OverlayMixin defers controller setup to updateComplete.then(); flush it.
  await new Promise((resolve) => setTimeout(resolve));
  return {tooltip, button};
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-tooltip', () => {
  it('wraps default-slot content into a slot="content" child', async () => {
    const {tooltip} = await createFixture();
    const content = tooltip.querySelector('[slot="content"]');
    expect(content).not.toBeNull();
    expect(content!.textContent).toContain('Tooltip content');
  });

  // The matching `hidden` case isn't asserted here: happy-dom resolves shadow
  // styles by source order rather than specificity, so Lion's more specific
  // `:host([hidden])` loses to this rule in the test environment even though it
  // wins in a browser (verified in Chromium).
  it('takes up no space in its parent layout', async () => {
    const {tooltip} = await createFixture();
    expect(getComputedStyle(tooltip).display).toBe('contents');
  });

  it('resolves the invoker from the for attribute', async () => {
    const {tooltip, button} = await createFixture();
    expect(tooltip._overlayInvokerNode).toBe(button);
  });

  it('opens via show() and emits craft-show', async () => {
    const {tooltip} = await createFixture();
    const events: string[] = [];
    tooltip.addEventListener('craft-show', () => events.push('craft-show'));
    tooltip.addEventListener('craft-after-show', () =>
      events.push('craft-after-show')
    );

    await tooltip.show();
    await new Promise((resolve) => setTimeout(resolve));

    expect(tooltip.opened).toBe(true);
    expect(events).toEqual(['craft-show', 'craft-after-show']);
  });

  it('closes via hide() and emits craft-hide', async () => {
    const {tooltip} = await createFixture();
    const events: string[] = [];
    tooltip.addEventListener('craft-hide', () => events.push('craft-hide'));
    tooltip.addEventListener('craft-after-hide', () =>
      events.push('craft-after-hide')
    );

    await tooltip.show();
    await new Promise((resolve) => setTimeout(resolve));
    await tooltip.hide();
    await new Promise((resolve) => setTimeout(resolve));

    expect(tooltip.opened).toBe(false);
    expect(events).toEqual(['craft-hide', 'craft-after-hide']);
  });

  it('toggles on invoker click when trigger includes click', async () => {
    const {tooltip, button} = await createFixture({trigger: 'click'});

    button.click();
    await tooltip.updateComplete;
    expect(tooltip.opened).toBe(true);

    button.click();
    await tooltip.updateComplete;
    expect(tooltip.opened).toBe(false);
  });
});
