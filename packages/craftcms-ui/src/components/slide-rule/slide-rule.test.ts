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

  it('leaves the graduations themselves unmarked', async () => {
    // The span from zero is the indicator's job. Lighting up graduations
    // can't say where a continuous value is -- it usually falls between two.
    const element = await createSlideRule({value: 10.4});

    expect(
      graduations(element).filter((g) => g.classList.contains('selected'))
    ).toHaveLength(0);
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

describe('craft-slide-rule positioning', () => {
  it('waits for a measurable window rather than centring against zero', async () => {
    // Rendered inside something with no layout — a closed dialog — the window
    // measures 0, and centring against that lands the strip half its own
    // length out: 20 degrees for the default range.
    const host = document.createElement('div');
    host.style.display = 'none';
    document.body.append(host);

    const rule = document.createElement('craft-slide-rule');
    host.append(rule);
    await rule.updateComplete;

    const list =
      rule.shadowRoot!.querySelector<HTMLElement>('.graduations ul')!;

    expect(list.style.transform).toBe('');
  });
});

describe('craft-slide-rule edge fade', () => {
  it('fades the graduations with a mask rather than the page background', async () => {
    // The fade used to be a gradient of --gray-900 painted on top, which only
    // reads as a fade over a --gray-900 background and smears a dark band
    // across anything lighter. Masking the window fades the graduations
    // themselves, so the rule holds up wherever it is put.
    const element = await createSlideRule();
    const window_ =
      element.shadowRoot!.querySelector<HTMLElement>('.graduations')!;

    expect(element.shadowRoot!.querySelector('.overlay')).toBe(null);

    const mask = getComputedStyle(window_).getPropertyValue('mask-image');

    expect(mask).toContain('linear-gradient');
    expect(mask).not.toBe('none');
  });

  it('leaves the middle unmasked, where the value is read', async () => {
    const element = await createSlideRule();
    const mask = getComputedStyle(
      element.shadowRoot!.querySelector<HTMLElement>('.graduations')!
    ).getPropertyValue('mask-image');

    // Transparent only at the two ends; fully opaque across the middle, where
    // the cursor sits and the value is read.
    expect(mask.replace(/\s+/g, ' ')).toMatch(
      /transparent 0%, [^,]+ 15%, [^,]+ 85%, transparent 100%/
    );
  });
});

describe('craft-slide-rule value indicator', () => {
  function indicator(element: CraftSlideRule): HTMLElement {
    return element.shadowRoot!.querySelector('.indicator') as HTMLElement;
  }

  it('spans from the middle of the window out to zero', async () => {
    // 10px a degree by default, and the value always sits under the cursor in
    // the middle -- so the band is however far zero is from there.
    const element = await createSlideRule({value: 12});

    expect(indicator(element).style.inlineSize).toBe('120px');
  });

  it('lands on a fractional value, which no graduation could', async () => {
    const element = await createSlideRule({value: 12.4});

    expect(indicator(element).style.inlineSize).toBe('124px');
  });

  it('grows the other way once the value goes negative', async () => {
    // Positive values slide the strip left, putting zero to the left of the
    // cursor; negative values put it to the right.
    const positive = await createSlideRule({value: 12});
    const negative = await createSlideRule({value: -12});

    expect(indicator(positive).style.translate).toBe('-100%');
    expect(indicator(negative).style.translate).toBe('0');
    expect(indicator(negative).style.inlineSize).toBe('120px');
  });

  it('is hidden at zero, where it would be nothing but its own borders', async () => {
    const element = await createSlideRule({value: 0});

    expect(indicator(element).hidden).toBe(true);
  });

  it('comes back as soon as the value leaves zero', async () => {
    const element = await createSlideRule({value: 0});

    pressKey(element, 'ArrowUp');
    await element.updateComplete;

    expect(indicator(element).hidden).toBe(false);
    expect(indicator(element).style.inlineSize).toBe('10px');
  });

  it('is hidden again on the way back to zero', async () => {
    const element = await createSlideRule({value: 1});

    expect(indicator(element).hidden).toBe(false);

    pressKey(element, 'ArrowDown');
    await element.updateComplete;

    expect(indicator(element).hidden).toBe(true);
  });
});

describe('craft-slide-rule change events', () => {
  it('keeps the value continuous rather than settling on a graduation', async () => {
    // The image editor straightens by fractions of a degree where the backend
    // can, so the rule must not round on its way through.
    const element = await createSlideRule({min: -45.3});

    pressKey(element, 'Home');
    await element.updateComplete;

    expect(element.value).toBe(-45.3);
  });

  it('stays quiet when a move lands on the value already showing', async () => {
    const element = await createSlideRule({value: 45});
    const changes: number[] = [];

    element.addEventListener('change', (event) => {
      changes.push((event as CustomEvent<{value: number}>).detail.value);
    });

    pressKey(element, 'ArrowUp');
    pressKey(element, 'End');
    await element.updateComplete;

    // Already at the ceiling, so neither key moved it anywhere new.
    expect(changes).toEqual([]);

    pressKey(element, 'ArrowDown');
    await element.updateComplete;

    expect(changes).toEqual([44]);
  });
});
