import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

import {Listbox} from '../src/listbox';

/**
 * Build a listbox container with `count` `<button type="button">` options.
 * If `pressedIndex` is given, that option starts with `aria-pressed="true"`.
 */
function makeContainer(count = 3, pressedIndex?: number): HTMLElement {
  const container = document.createElement('div');
  container.className = 'listbox';
  for (let i = 0; i < count; i++) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = `Option ${i}`;
    if (i === pressedIndex) {
      btn.setAttribute('aria-pressed', 'true');
    }
    container.appendChild(btn);
  }
  document.body.appendChild(container);
  return container;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe('Listbox construction', () => {
  it('applies defaults merged with passed settings', () => {
    const listbox = new Listbox(makeContainer(), {selectedClass: 'chosen'});
    expect(listbox.settings!.selectedClass).toBe('chosen');
    expect(listbox.settings!.focusClass).toBe('focus');
    expect(listbox.settings!.readOnly).toBe(false);
  });

  it('uses "active" as the default selectedClass (legacy parity)', () => {
    const listbox = new Listbox(makeContainer());
    expect(listbox.settings!.selectedClass).toBe('active');
  });

  it('supports the param-shift form: new Listbox(settings)', () => {
    const listbox = new Listbox({selectedClass: 'x'});
    expect(listbox.settings!.selectedClass).toBe('x');
    expect(listbox.$container).toBeNull();
    expect(listbox.$options).toEqual([]);
  });

  it('stores the container element', () => {
    const container = makeContainer();
    const listbox = new Listbox(container);
    expect(listbox.$container).toBe(container);
  });
});

describe('Listbox option discovery', () => {
  it('discovers button + [type=button] options', () => {
    const container = document.createElement('div');
    const a = document.createElement('button');
    a.type = 'button';
    const b = document.createElement('input');
    b.type = 'button';
    const c = document.createElement('div'); // not an option
    container.append(a, b, c);
    document.body.appendChild(container);

    const listbox = new Listbox(container);
    expect(listbox.$options).toHaveLength(2);
    expect(listbox.$options).toContain(a);
    expect(listbox.$options).toContain(b);
  });

  it('discovers craft-button options alongside native buttons', () => {
    const container = document.createElement('div');
    const native = document.createElement('button');
    native.type = 'button';
    const craft = document.createElement('craft-button');
    const other = document.createElement('div'); // not an option
    container.append(native, craft, other);
    document.body.appendChild(container);

    const listbox = new Listbox(container);
    expect(listbox.$options).toHaveLength(2);
    expect(listbox.$options).toContain(native);
    expect(listbox.$options).toContain(craft);
  });

  it('selects a craft-button option on click', () => {
    const container = document.createElement('div');
    const a = document.createElement('craft-button');
    const b = document.createElement('craft-button');
    container.append(a, b);
    document.body.appendChild(container);

    const onChange = vi.fn();
    const listbox = new Listbox(container, {onChange});
    b.dispatchEvent(new MouseEvent('click', {bubbles: true}));

    expect(b.getAttribute('aria-pressed')).toBe('true');
    expect(listbox.$selectedOption).toBe(b);
    expect(onChange).toHaveBeenCalledWith(b, 1);
  });

  it('finds the initially-pressed option on init', () => {
    const listbox = new Listbox(makeContainer(3, 1));
    expect(listbox.selectedOptionIndex).toBe(1);
    expect(listbox.$selectedOption).toBe(listbox.$options[1]);
  });

  it('leaves selection empty when no option is pressed', () => {
    const listbox = new Listbox(makeContainer(3));
    expect(listbox.selectedOptionIndex).toBeNull();
    expect(listbox.$selectedOption).toBeNull();
  });
});

describe('Listbox select', () => {
  it('marks the new option pressed with the selectedClass + aria-pressed', () => {
    const listbox = new Listbox(makeContainer(3));
    listbox.select(2);
    const opt = listbox.$options[2]!;
    expect(opt.classList.contains('active')).toBe(true);
    expect(opt.getAttribute('aria-pressed')).toBe('true');
    expect(listbox.selectedOptionIndex).toBe(2);
    expect(listbox.$selectedOption).toBe(opt);
  });

  it('clears the previously-selected option', () => {
    const listbox = new Listbox(makeContainer(3, 0));
    listbox.select(2);
    const prev = listbox.$options[0]!;
    expect(prev.classList.contains('active')).toBe(false);
    expect(prev.getAttribute('aria-pressed')).toBe('false');
  });

  it('calls onChange with the option element and index', () => {
    const onChange = vi.fn();
    const listbox = new Listbox(makeContainer(3), {onChange});
    listbox.select(1);
    expect(onChange).toHaveBeenCalledOnce();
    expect(onChange).toHaveBeenCalledWith(listbox.$options[1], 1);
  });

  it('fires a change event with the selected option + index', () => {
    const listbox = new Listbox(makeContainer(3));
    const handler = vi.fn();
    listbox.on('change', handler);
    listbox.select(1);
    expect(handler).toHaveBeenCalledOnce();
    const ev = handler.mock.calls[0]![0];
    expect(ev.$selectedOption).toBe(listbox.$options[1]);
    expect(ev.selectedOptionIndex).toBe(1);
  });

  it('is a no-op when index is out of range', () => {
    const onChange = vi.fn();
    const listbox = new Listbox(makeContainer(3), {onChange});
    listbox.select(-1);
    listbox.select(99);
    expect(onChange).not.toHaveBeenCalled();
    expect(listbox.selectedOptionIndex).toBeNull();
  });

  it('is a no-op when the index is already selected', () => {
    const onChange = vi.fn();
    const listbox = new Listbox(makeContainer(3, 1), {onChange});
    listbox.select(1);
    expect(onChange).not.toHaveBeenCalled();
  });

  it('selects an option on click', () => {
    const onChange = vi.fn();
    const listbox = new Listbox(makeContainer(3), {onChange});
    listbox.$options[2]!.dispatchEvent(
      new MouseEvent('click', {bubbles: true})
    );
    expect(listbox.selectedOptionIndex).toBe(2);
    expect(onChange).toHaveBeenCalledOnce();
  });
});

describe('Listbox readOnly', () => {
  it('does not bind clicks and removes options from the tab order', () => {
    const onChange = vi.fn();
    const listbox = new Listbox(makeContainer(3), {readOnly: true, onChange});
    listbox.$options[1]!.dispatchEvent(
      new MouseEvent('click', {bubbles: true})
    );
    expect(onChange).not.toHaveBeenCalled();
    for (const option of listbox.$options) {
      expect(option.getAttribute('tabindex')).toBe('-1');
    }
  });
});

describe('Listbox disable/enable', () => {
  it('toggles aria-disabled on the container', () => {
    const container = makeContainer(3);
    const listbox = new Listbox(container);
    listbox.disable();
    expect(listbox.disabled).toBe(true);
    expect(container.getAttribute('aria-disabled')).toBe('true');
    listbox.enable();
    expect(listbox.disabled).toBe(false);
    expect(container.hasAttribute('aria-disabled')).toBe(false);
  });
});

describe('Listbox double-instantiation guard', () => {
  it('destroys the prior listbox on the same element', () => {
    const container = makeContainer(3);
    const first = new Listbox(container);
    const onDestroy = vi.fn();
    first.on('destroy', onDestroy);
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

    const second = new Listbox(container);
    expect(onDestroy).toHaveBeenCalledOnce();
    expect(warn).toHaveBeenCalled();
    expect(second.$container).toBe(container);
  });
});

describe('Listbox destroy', () => {
  it('fires destroy and drops the click binding', () => {
    const onChange = vi.fn();
    const container = makeContainer(3);
    const listbox = new Listbox(container, {onChange});
    const onDestroy = vi.fn();
    listbox.on('destroy', onDestroy);

    listbox.destroy();
    expect(onDestroy).toHaveBeenCalledOnce();

    // Listeners are torn down: a click no longer selects.
    container
      .querySelectorAll('button')[1]!
      .dispatchEvent(new MouseEvent('click', {bubbles: true}));
    expect(onChange).not.toHaveBeenCalled();
  });
});
