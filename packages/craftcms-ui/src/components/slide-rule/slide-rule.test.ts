import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftSlideRule from './slide-rule.js';
import './slide-rule.js';

async function createSlideRule(
  props: Partial<Pick<CraftSlideRule, 'min' | 'max' | 'value'>> = {}
): Promise<CraftSlideRule> {
  const element = document.createElement('craft-slide-rule');
  Object.assign(element, props);
  document.body.append(element);
  await element.updateComplete;

  return element;
}

function root(element: CraftSlideRule): HTMLElement {
  return element.shadowRoot!.querySelector('.slide-rule') as HTMLElement;
}

function graduations(element: CraftSlideRule): HTMLElement[] {
  return [
    ...element.shadowRoot!.querySelectorAll('.graduation'),
  ] as HTMLElement[];
}

function pressKey(element: CraftSlideRule, key: string) {
  root(element).dispatchEvent(
    new KeyboardEvent('keydown', {key, bubbles: true})
  );
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-slide-rule', () => {
  it('draws a graduation for every degree in the graduation range', async () => {
    const element = await createSlideRule();
    // -70..70 inclusive => 141
    expect(graduations(element)).toHaveLength(141);
  });

  it('labels every fifth graduation as a main graduation', async () => {
    const element = await createSlideRule();
    const main = graduations(element).filter((g) =>
      g.classList.contains('main-graduation')
    );
    // -70..70 divisible by 5 => 29
    expect(main).toHaveLength(29);
  });

  it('selects graduations between zero and the current value', async () => {
    const element = await createSlideRule({value: 10});
    const selected = graduations(element)
      .filter((g) => g.classList.contains('selected'))
      .map((g) => Number(g.dataset.graduation));

    expect(selected).toEqual([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
  });

  it('moves the value with the keyboard and emits change', async () => {
    const element = await createSlideRule({value: 0});
    const handler = vi.fn();
    element.addEventListener('change', handler as EventListener);

    pressKey(element, 'ArrowRight');
    await element.updateComplete;
    expect(element.value).toBe(1);

    pressKey(element, 'PageUp');
    await element.updateComplete;
    expect(element.value).toBe(11);

    pressKey(element, 'Home');
    await element.updateComplete;
    expect(element.value).toBe(-45);

    pressKey(element, 'End');
    await element.updateComplete;
    expect(element.value).toBe(45);

    expect(handler).toHaveBeenCalledTimes(4);
    expect((handler.mock.calls.at(-1)![0] as CustomEvent).detail).toEqual({
      value: 45,
    });
  });

  it('clamps to the slide range even when pushed past it', async () => {
    const element = await createSlideRule({value: 44});

    pressKey(element, 'PageUp'); // 44 + 10 -> clamped to 45
    await element.updateComplete;

    expect(element.value).toBe(45);
  });

  it('exposes slider ARIA state', async () => {
    const element = await createSlideRule({value: 12});
    const r = root(element);

    expect(r.getAttribute('role')).toBe('slider');
    expect(r.getAttribute('aria-valuemin')).toBe('-45');
    expect(r.getAttribute('aria-valuemax')).toBe('45');
    expect(r.getAttribute('aria-valuenow')).toBe('12');
    expect(r.getAttribute('aria-valuetext')).toContain('12');
  });
});
