import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftSlidePicker from './slide-picker.js';
import './slide-picker.js';

async function createSlidePicker(
  props: Partial<
    Pick<CraftSlidePicker, 'min' | 'max' | 'step' | 'value' | 'name'>
  > = {},
  parent: HTMLElement = document.body
): Promise<CraftSlidePicker> {
  const element = document.createElement('craft-slide-picker');
  Object.assign(element, {min: 0, max: 100, step: 10, value: 0, ...props});
  parent.append(element);
  await element.updateComplete;
  await slider(element).updateComplete;

  return element;
}

function slider(element: CraftSlidePicker) {
  return element.querySelector('craft-slide-picker-slider')!;
}

function segments(element: CraftSlidePicker): HTMLElement[] {
  return [
    ...slider(element).shadowRoot!.querySelectorAll('.slide-picker__segment'),
  ] as HTMLElement[];
}

async function settle(element: CraftSlidePicker) {
  await element.updateComplete;
  await slider(element).updateComplete;
}

function press(element: CraftSlidePicker, key: string) {
  slider(element).dispatchEvent(
    new KeyboardEvent('keydown', {key, bubbles: true})
  );
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
    const element = await createSlidePicker();
    const order: string[] = [];
    element.addEventListener('input', (event) => {
      if (event.target === element) order.push('input');
    });
    element.addEventListener('change', (event) => {
      if (event.target === element) order.push('change');
    });

    segments(element)[3]!.click(); // 0, 10, 20, 30 -> 30
    await settle(element);

    expect(element.value).toBe(30);
    expect(order).toEqual(['input', 'change']);
  });

  it('does not emit when the value is set programmatically', async () => {
    const element = await createSlidePicker();
    const handler = vi.fn();
    element.addEventListener('input', handler as EventListener);
    element.addEventListener('change', handler as EventListener);

    element.value = 40;
    await settle(element);

    expect(handler).not.toHaveBeenCalled();
    expect(slider(element).getAttribute('aria-valuenow')).toBe('40');
  });

  it('starts at the minimum when it is given no value', async () => {
    const element = document.createElement('craft-slide-picker');
    Object.assign(element, {min: 20, max: 100, step: 20});
    document.body.append(element);
    await settle(element);

    expect(element.value).toBe(20);
  });

  it('starts at the minimum when its value isn’t a number', async () => {
    document.body.innerHTML = `<craft-slide-picker min="20" max="100" step="20" value="wide"></craft-slide-picker>`;
    const element = document.querySelector('craft-slide-picker')!;
    await settle(element);

    expect(element.value).toBe(20);
    expect(slider(element).getAttribute('aria-valuenow')).toBe('20');
  });

  it('leaves the keys alone while read-only, so they still scroll the page', async () => {
    const element = await createSlidePicker({value: 50});
    element.readOnly = true;
    await settle(element);

    const event = new KeyboardEvent('keydown', {
      key: 'ArrowDown',
      bubbles: true,
      cancelable: true,
    });
    slider(element).dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
  });

  it('clamps out-of-range values and snaps off-step values', async () => {
    const clamped = await createSlidePicker({value: 250});
    expect(clamped.value).toBe(100);

    const snapped = await createSlidePicker({value: 23});
    expect(snapped.value).toBe(20);
  });

  it('moves the value with the keyboard and emits each change', async () => {
    const element = await createSlidePicker({value: 50});
    const handler = vi.fn();
    element.addEventListener('change', handler as EventListener);

    press(element, 'ArrowUp');
    await settle(element);
    expect(element.value).toBe(60);

    press(element, 'Home');
    await settle(element);
    expect(element.value).toBe(0);

    press(element, 'End');
    await settle(element);
    expect(element.value).toBe(100);

    expect(handler).toHaveBeenCalledTimes(3);
  });

  it('ignores input while read-only, but stays in the Tab order', async () => {
    const element = await createSlidePicker({value: 50});
    element.readOnly = true;
    await settle(element);

    const handler = vi.fn();
    element.addEventListener('change', handler as EventListener);

    segments(element)[0]!.click();
    press(element, 'ArrowUp');
    await settle(element);

    expect(element.value).toBe(50);
    expect(handler).not.toHaveBeenCalled();
    expect(slider(element).getAttribute('tabindex')).toBe('0');
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
    await settle(element);

    const handler = vi.fn();
    element.addEventListener('change', handler as EventListener);

    segments(element)[0]!.click();
    await settle(element);

    expect(element.value).toBe(50);
    expect(handler).not.toHaveBeenCalled();
    expect(slider(element).getAttribute('tabindex')).toBe('-1');
    expect(slider(element).getAttribute('aria-disabled')).toBe('true');
  });

  it.each([
    ['columns', '50 columns'],
    [' columns', '50 columns'],
    ['%', '50%'],
  ])('announces value-unit %j as %j', async (valueUnit, expected) => {
    const element = await createSlidePicker({value: 50});
    element.valueUnit = valueUnit;
    await settle(element);

    expect(slider(element).getAttribute('aria-valuetext')).toBe(expected);
  });

  it('announces the value as valueLabel formats it', async () => {
    const element = await createSlidePicker({value: 50});
    element.valueLabel = (value) => `${value} columns`;
    await settle(element);

    expect(slider(element).getAttribute('aria-valuetext')).toBe('50 columns');
  });
});

describe('craft-slide-picker in a form', () => {
  async function createInForm(
    props: Partial<Pick<CraftSlidePicker, 'name' | 'value'>> = {}
  ): Promise<{form: HTMLFormElement; element: CraftSlidePicker}> {
    const form = document.createElement('form');
    document.body.append(form);
    const element = await createSlidePicker(props, form);

    return {form, element};
  }

  it('posts its value under its name', async () => {
    const {form} = await createInForm({name: 'columns', value: 30});

    expect(new FormData(form).get('columns')).toBe('30');
  });

  it('posts the updated value after user input', async () => {
    const {form, element} = await createInForm({name: 'columns'});

    segments(element)[5]!.click(); // -> 50
    await settle(element);

    expect(new FormData(form).get('columns')).toBe('50');
  });

  it('posts nothing without a name', async () => {
    const {form} = await createInForm({value: 30});

    expect([...new FormData(form).keys()]).toEqual([]);
  });

  it('posts nothing while disabled', async () => {
    const {form, element} = await createInForm({
      name: 'columns',
      value: 30,
    });
    element.disabled = true;
    await settle(element);

    expect(new FormData(form).get('columns')).toBeNull();
  });

  it('puts back the value it started with when the form is reset', async () => {
    const {form, element} = await createInForm({
      name: 'columns',
      value: 30,
    });

    segments(element)[8]!.click(); // -> 80
    await settle(element);
    expect(element.value).toBe(80);

    form.reset();
    await settle(element);

    expect(element.value).toBe(30);
    expect(new FormData(form).get('columns')).toBe('30');
  });
});
