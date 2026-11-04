import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftSlideRule from './slide-rule.js';
import './slide-rule.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(): Promise<CraftSlideRule> {
  await import('../../styles/cp.css');
  document.body.innerHTML =
    '<craft-slide-rule style="width: 400px"></craft-slide-rule>';
  const rule = document.querySelector<CraftSlideRule>('craft-slide-rule')!;
  await rule.updateComplete;
  return rule;
}

function slider(rule: CraftSlideRule): HTMLElement {
  return rule.shadowRoot!.querySelector<HTMLElement>('[role="slider"]')!;
}

function pointer(target: Element, type: string, clientX: number) {
  target.dispatchEvent(
    new PointerEvent(type, {
      pointerId: 1,
      clientX,
      bubbles: true,
      composed: true,
      cancelable: true,
    })
  );
}

it('moves focus to the slider when the host is focused', async () => {
  const rule = await render();

  rule.focus();

  expect(rule.shadowRoot!.activeElement).toBe(slider(rule));
});

it('ends a drag the browser cancels, keeping the value it reached', async () => {
  const rule = await render();
  const graduation = rule.shadowRoot!.querySelector('.graduation')!;
  const events: string[] = [];
  for (const type of ['change', 'craft-drag-end']) {
    rule.addEventListener(type, () => events.push(type));
  }

  pointer(graduation, 'pointerdown', 200);
  pointer(graduation, 'pointermove', 150);
  await rule.updateComplete;
  const reached = rule.value;
  pointer(graduation, 'pointercancel', 150);
  pointer(graduation, 'pointermove', 100);
  await rule.updateComplete;

  expect(reached).not.toBe(0);
  expect(rule.value).toBe(reached);
  expect(events).toEqual(['change', 'craft-drag-end']);
});
