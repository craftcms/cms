import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftSlidePicker from './slide-picker.js';
import './slide-picker.js';

async function createSlidePicker(
  props: Partial<Pick<CraftSlidePicker, 'min' | 'max' | 'step' | 'value'>> = {}
): Promise<CraftSlidePicker> {
  const element = document.createElement('craft-slide-picker');
  Object.assign(element, {min: 0, max: 100, step: 10, value: 0, ...props});
  document.body.append(element);
  await element.updateComplete;

  return element;
}

function slider(element: CraftSlidePicker): HTMLElement {
  return element.shadowRoot!.querySelector('.slide-picker') as HTMLElement;
}

function segments(element: CraftSlidePicker): HTMLElement[] {
  return [
    ...element.shadowRoot!.querySelectorAll('.slide-picker__segment'),
  ] as HTMLElement[];
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-slide-picker', () => {
  it('renders one segment per step across the range', async () => {
    const element = await createSlidePicker({min: 0, max: 100, step: 10});

    // 0, 10, …, 100 => 11 segments
    expect(segments(element)).toHaveLength(11);
  });

  it('marks segments active up to (and including) the current value', async () => {
    const element = await createSlidePicker({
      min: 0,
      max: 100,
      step: 25,
      value: 50,
    });

    const active = segments(element).filter((s) =>
      s.classList.contains('is-active')
    );
    // 0, 25, 50 are <= 50
    expect(active).toHaveLength(3);

    const lastActive = segments(element).filter((s) =>
      s.classList.contains('is-last-active')
    );
    expect(lastActive).toHaveLength(1);
    expect(lastActive[0]!.getAttribute('title')).toBe('50');
  });

  it('emits input then change on segment click', async () => {
    const element = await createSlidePicker({
      min: 0,
      max: 100,
      step: 10,
      value: 0,
    });
    const order: string[] = [];
    element.addEventListener('input', () => order.push('input'));
    element.addEventListener('change', () => order.push('change'));

    segments(element)[3]!.click(); // 0, 10, 20, 30 -> 30
    await element.updateComplete;

    expect(element.value).toBe(30);
    // Every step is a commit, so the pair fires together.
    expect(order).toEqual(['input', 'change']);
  });

  it('does not emit when the value is set programmatically', async () => {
    const element = await createSlidePicker({value: 0});
    const handler = vi.fn();
    element.addEventListener('input', handler as EventListener);
    element.addEventListener('change', handler as EventListener);

    element.value = 40;
    await element.updateComplete;

    expect(handler).not.toHaveBeenCalled();
  });

  it('clamps out-of-range values and snaps off-step values', async () => {
    const clamped = await createSlidePicker({
      min: 0,
      max: 100,
      step: 10,
      value: 250,
    });
    expect(clamped.value).toBe(100);

    const snapped = await createSlidePicker({
      min: 0,
      max: 100,
      step: 10,
      value: 23,
    });
    expect(snapped.value).toBe(20);
  });

  it('moves the value with the keyboard and emits each change', async () => {
    const element = await createSlidePicker({
      min: 0,
      max: 100,
      step: 10,
      value: 50,
    });
    const handler = vi.fn();
    element.addEventListener('change', handler as EventListener);

    const press = (key: string) =>
      slider(element).dispatchEvent(
        new KeyboardEvent('keydown', {key, bubbles: true})
      );

    press('ArrowUp');
    await element.updateComplete;
    expect(element.value).toBe(60);

    press('Home');
    await element.updateComplete;
    expect(element.value).toBe(0);

    press('End');
    await element.updateComplete;
    expect(element.value).toBe(100);

    expect(handler).toHaveBeenCalledTimes(3);
  });

  it('ignores input while read-only', async () => {
    const element = await createSlidePicker({
      min: 0,
      max: 100,
      step: 10,
      value: 50,
    });
    element.readonly = true;
    await element.updateComplete;

    const handler = vi.fn();
    element.addEventListener('change', handler as EventListener);

    segments(element)[0]!.click();
    slider(element).dispatchEvent(
      new KeyboardEvent('keydown', {key: 'ArrowUp', bubbles: true})
    );
    await element.updateComplete;

    expect(element.value).toBe(50);
    expect(handler).not.toHaveBeenCalled();
    expect(slider(element).getAttribute('tabindex')).toBe('-1');
    expect(slider(element).getAttribute('aria-readonly')).toBe('true');
  });

  it('exposes slider ARIA state', async () => {
    const element = await createSlidePicker({
      min: 0,
      max: 100,
      step: 25,
      value: 50,
    });
    const s = slider(element);

    expect(s.getAttribute('role')).toBe('slider');
    expect(s.getAttribute('aria-valuemin')).toBe('0');
    expect(s.getAttribute('aria-valuemax')).toBe('100');
    expect(s.getAttribute('aria-valuenow')).toBe('50');
    expect(s.getAttribute('aria-valuetext')).toBe('50');
  });

  it('ignores input while disabled', async () => {
    const element = await createSlidePicker({value: 50});
    element.disabled = true;
    await element.updateComplete;

    const handler = vi.fn();
    element.addEventListener('change', handler as EventListener);

    segments(element)[0]!.click();
    await element.updateComplete;

    expect(element.value).toBe(50);
    expect(handler).not.toHaveBeenCalled();
    expect(slider(element).getAttribute('tabindex')).toBe('-1');
    expect(slider(element).getAttribute('aria-disabled')).toBe('true');
  });

  it('formats the value text with valueUnit and valueLabel', async () => {
    const withUnit = await createSlidePicker({
      min: 0,
      max: 100,
      step: 25,
      value: 50,
    });
    withUnit.valueUnit = '%';
    await withUnit.updateComplete;
    expect(slider(withUnit).getAttribute('aria-valuetext')).toBe('50%');

    const withLabel = await createSlidePicker({
      min: 0,
      max: 100,
      step: 25,
      value: 50,
    });
    withLabel.valueLabel = (value) => `${value} columns`;
    await withLabel.updateComplete;
    expect(slider(withLabel).getAttribute('aria-valuetext')).toBe('50 columns');
  });
});

/**
 * happy-dom has no `ElementInternals`, so these run against
 * `element-internals-polyfill` (wired up in the components project's setup).
 * The polyfill carries `setFormValue` and `FormData`, but not the parts the
 * user agent itself performs — leaving a disabled control out of submission,
 * and calling `formResetCallback` on reset. Those are covered in the browser,
 * in `slide-picker.stories.ts`.
 */
describe('craft-slide-picker form association', () => {
  async function createInForm(
    props: Partial<Pick<CraftSlidePicker, 'name' | 'value'>> = {}
  ): Promise<{form: HTMLFormElement; element: CraftSlidePicker}> {
    const form = document.createElement('form');
    const element = document.createElement('craft-slide-picker');
    Object.assign(element, {min: 0, max: 100, step: 10, value: 0, ...props});
    form.append(element);
    document.body.append(form);
    await element.updateComplete;

    return {form, element};
  }

  it('posts its value under its name', async () => {
    const {form} = await createInForm({name: 'columns', value: 30});

    expect(new FormData(form).get('columns')).toBe('30');
  });

  it('posts the updated value after user input', async () => {
    const {form, element} = await createInForm({name: 'columns', value: 0});

    segments(element)[5]!.click(); // -> 50
    await element.updateComplete;

    expect(new FormData(form).get('columns')).toBe('50');
  });

  it('posts nothing without a name', async () => {
    const {form} = await createInForm({value: 30});

    expect([...new FormData(form).keys()]).toEqual([]);
  });

  it('restores the value it was given back', async () => {
    const {element} = await createInForm({name: 'columns', value: 30});

    segments(element)[8]!.click(); // -> 80
    await element.updateComplete;
    expect(element.value).toBe(80);

    // What the user agent hands back on `form.reset()`.
    element._restoreFormValue('30');
    await element.updateComplete;

    expect(element.value).toBe(30);
  });
});
