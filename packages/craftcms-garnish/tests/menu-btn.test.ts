import { beforeEach, describe, expect, it, vi } from 'vitest';

import { MenuBtn } from '../src/menu-btn';

function buildMenuBtn(labels = ['Apple', 'Banana', 'Cherry']): {
  btn: HTMLButtonElement;
  wrapper: HTMLElement;
} {
  const wrapper = document.createElement('div');
  const btn = document.createElement('button');
  btn.type = 'button';
  const menu = document.createElement('div');
  menu.className = 'menu';
  const ul = document.createElement('ul');
  labels.forEach((label) => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.textContent = label;
    li.appendChild(a);
    ul.appendChild(li);
  });
  menu.appendChild(ul);
  wrapper.appendChild(btn);
  wrapper.appendChild(menu);
  document.body.appendChild(wrapper);
  return { btn, wrapper };
}

describe('MenuBtn init / ARIA', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('adopts the sibling .menu and wires combobox ARIA', () => {
    const { btn } = buildMenuBtn();
    const menuBtn = new MenuBtn(btn);

    expect(btn.getAttribute('role')).toBe('combobox');
    expect(btn.getAttribute('aria-haspopup')).toBe('listbox');
    expect(btn.getAttribute('aria-expanded')).toBe('false');
    expect(btn.getAttribute('aria-controls')).toBe(menuBtn.menu.menuId);
    expect(menuBtn.menu.$options).toHaveLength(3);
  });

  it('warns and no-ops without a DOM element', () => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
    const menuBtn = new MenuBtn(null);
    expect(warn).toHaveBeenCalled();
    expect(menuBtn.$btn).toBeFalsy();
    warn.mockRestore();
  });
});

describe('MenuBtn disabled state', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('reads the initial disabled attribute', () => {
    const { btn } = buildMenuBtn();
    btn.setAttribute('disabled', 'disabled');
    const menuBtn = new MenuBtn(btn);
    expect(menuBtn.disabled).toBe(true);
    expect(btn.classList.contains('disabled')).toBe(true);
  });

  it('treats a bare button as enabled', () => {
    const { btn } = buildMenuBtn();
    const menuBtn = new MenuBtn(btn);
    expect(menuBtn.disabled).toBe(false);
  });

  it('enable()/disable() toggle the button attribute', () => {
    const { btn } = buildMenuBtn();
    const menuBtn = new MenuBtn(btn);
    menuBtn.disable();
    expect(btn.hasAttribute('disabled')).toBe(true);
    menuBtn.enable();
    expect(btn.hasAttribute('disabled')).toBe(false);
  });
});

describe('MenuBtn keyboard focus', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('focusFirstOption sets .hover + aria-activedescendant', () => {
    const { btn } = buildMenuBtn();
    const menuBtn = new MenuBtn(btn);
    menuBtn.focusFirstOption();
    const first = menuBtn.menu.$options[0]!;
    expect(first.classList.contains('hover')).toBe(true);
    expect(btn.getAttribute('aria-activedescendant')).toBe(first.parentElement!.getAttribute('id'));
  });

  it('moveFocusDown advances to the next option', () => {
    const { btn } = buildMenuBtn();
    const menuBtn = new MenuBtn(btn);
    menuBtn.focusFirstOption();
    menuBtn.moveFocusDown(1);
    expect(menuBtn.menu.$options[0]!.classList.contains('hover')).toBe(false);
    expect(menuBtn.menu.$options[1]!.classList.contains('hover')).toBe(true);
  });

  it('moveFocusUp from the first option stays clamped', () => {
    const { btn } = buildMenuBtn();
    const menuBtn = new MenuBtn(btn);
    menuBtn.focusFirstOption();
    menuBtn.moveFocusUp(1);
    expect(menuBtn.menu.$options[0]!.classList.contains('hover')).toBe(true);
  });
});

describe('MenuBtn option selection', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('propagates the menu optionselect to onOptionSelect + optionSelect', () => {
    const { btn } = buildMenuBtn();
    const onOptionSelect = vi.fn();
    const menuBtn = new MenuBtn(btn, { onOptionSelect });
    const onEvent = vi.fn();
    menuBtn.on('optionSelect', onEvent);

    const option = menuBtn.menu.$options[1]!;
    menuBtn.menu.selectOption(option);

    expect(onOptionSelect).toHaveBeenCalledWith(option);
    expect(onEvent).toHaveBeenCalledOnce();
  });
});
