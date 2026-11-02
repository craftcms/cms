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

  it('takes up no space in its parent layout, and none at all when hidden', async () => {
    const {tooltip} = await createFixture();
    expect(getComputedStyle(tooltip).display).toBe('contents');

    tooltip.hidden = true;
    expect(getComputedStyle(tooltip).display).toBe('none');
  });

  it('gives its content the tooltip role and points the invoker at it', async () => {
    const {tooltip, button} = await createFixture();
    const content = tooltip.querySelector('[slot="content"]')!;

    expect(content.getAttribute('role')).toBe('tooltip');
    expect(button.getAttribute('aria-describedby')).toBe(content.id);
  });

  it('names the invoker instead with invoker-relation="label"', async () => {
    const {tooltip, button} = await createFixture({
      'invoker-relation': 'label',
    });
    const content = tooltip.querySelector('[slot="content"]')!;

    expect(button.getAttribute('aria-labelledby')).toBe(content.id);
    expect(button.hasAttribute('aria-describedby')).toBe(false);
  });

  it("keeps the invoker's own description alongside its own", async () => {
    const button = document.createElement('button');
    button.id = 'described';
    button.setAttribute('aria-describedby', 'help');
    document.body.append(button);
    const tooltip = document.createElement('craft-tooltip') as CraftTooltip;
    tooltip.setAttribute('for', 'described');
    tooltip.textContent = 'More';
    document.body.append(tooltip);
    await tooltip.updateComplete;
    const id = tooltip.querySelector('[slot="content"]')!.id;

    expect(button.getAttribute('aria-describedby')).toBe(`help ${id}`);

    tooltip.remove();
    await new Promise((resolve) => setTimeout(resolve));

    expect(button.getAttribute('aria-describedby')).toBe('help');
  });

  it('moves its reference when for changes', async () => {
    const {tooltip, button} = await createFixture();
    const other = document.createElement('button');
    other.id = 'other';
    document.body.append(other);

    tooltip.setAttribute('for', 'other');
    await tooltip.updateComplete;
    const id = tooltip.querySelector('[slot="content"]')!.id;

    expect(button.hasAttribute('aria-describedby')).toBe(false);
    expect(other.getAttribute('aria-describedby')).toBe(id);
  });

  it('reports itself closed once removed while open', async () => {
    const {tooltip} = await createFixture();
    await tooltip.show();

    tooltip.remove();
    await new Promise((resolve) => setTimeout(resolve));

    expect(tooltip.opened).toBe(false);
  });

  it('resolves the invoker from the for attribute', async () => {
    const {tooltip, button} = await createFixture();
    expect(tooltip.invoker).toBe(button);
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
