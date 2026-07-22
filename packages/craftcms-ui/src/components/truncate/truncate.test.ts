import {beforeEach, describe, expect, it} from 'vitest';
import type CraftTruncate from './truncate.js';
import './truncate.js';

async function fixture(text: string): Promise<CraftTruncate> {
  const el = document.createElement('craft-truncate') as CraftTruncate;
  el.textContent = text;
  document.body.append(el);
  await el.updateComplete;
  return el;
}

// happy-dom has no layout engine, so fake the inner span's metrics and nudge a
// re-measure through a slotchange.
function simulateOverflow(el: CraftTruncate, overflow: boolean) {
  const span = el.renderRoot.querySelector('.truncate') as HTMLElement;
  Object.defineProperty(span, 'scrollWidth', {
    value: overflow ? 500 : 100,
    configurable: true,
  });
  Object.defineProperty(span, 'clientWidth', {value: 100, configurable: true});
  span.querySelector('slot')!.dispatchEvent(new Event('slotchange'));
  return el.updateComplete;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-truncate', () => {
  it('renders its content in a truncating container', async () => {
    const el = await fixture('Hello world');
    const truncate = el.renderRoot.querySelector('.truncate');
    expect(truncate).not.toBeNull();
    expect(truncate!.querySelector('slot')).not.toBeNull();
  });

  it('does not render a tooltip when the content fits', async () => {
    const el = await fixture('Short');
    await simulateOverflow(el, false);
    expect(el.renderRoot.querySelector('craft-tooltip')).toBeNull();
  });

  it('renders a tooltip with the full text when the content overflows', async () => {
    const el = await fixture('A very long label that will not fit');
    await simulateOverflow(el, true);

    const tooltip = el.renderRoot.querySelector('craft-tooltip');
    expect(tooltip).not.toBeNull();
    expect(tooltip!.getAttribute('for')).toBe('content');
    expect(tooltip!.textContent).toContain(
      'A very long label that will not fit'
    );
  });

  it('does not render a tooltip when disabled, even on overflow', async () => {
    const el = await fixture('A very long label that will not fit');
    el.disabled = true;
    await simulateOverflow(el, true);
    expect(el.renderRoot.querySelector('craft-tooltip')).toBeNull();
  });
});
