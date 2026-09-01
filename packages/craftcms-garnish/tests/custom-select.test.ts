import {beforeEach, describe, expect, it, vi} from 'vitest';

import {CustomSelect} from '../src/custom-select';

function buildMenu(labels = ['Apple', 'Banana', 'Cherry']): HTMLElement {
  const menu = document.createElement('div');
  const ul = document.createElement('ul');
  labels.forEach((label) => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.textContent = label;
    li.appendChild(a);
    ul.appendChild(li);
  });
  menu.appendChild(ul);
  document.body.appendChild(menu);
  return menu;
}

describe('CustomSelect init / ARIA', () => {
  let menu: HTMLElement;
  beforeEach(() => {
    document.body.innerHTML = '';
    menu = buildMenu();
  });

  it('marks the container as a listbox with an id', () => {
    const select = new CustomSelect(menu);
    expect(menu.getAttribute('role')).toBe('listbox');
    expect(menu.getAttribute('id')).toBe(select.menuId);
  });

  it('marks nested <ul> as a group', () => {
    new CustomSelect(menu);
    expect(menu.querySelector('ul')!.getAttribute('role')).toBe('group');
  });

  it('registers every option and its <li> as an ARIA option', () => {
    const select = new CustomSelect(menu);
    expect(select.$options).toHaveLength(3);
    expect(select.$ariaOptions).toHaveLength(3);
    select.$options.forEach((o) => {
      expect(o.getAttribute('tabindex')).toBe('-1');
      expect(o.getAttribute('id')).toBeTruthy();
      expect(o.parentElement!.getAttribute('role')).toBe('option');
    });
  });

  it('seeds aria-selected from the .sel class', () => {
    menu.querySelector('a')!.classList.add('sel');
    const select = new CustomSelect(menu);
    expect(select.$ariaOptions[0]!.getAttribute('aria-selected')).toBe('true');
    expect(select.$ariaOptions[1]!.getAttribute('aria-selected')).toBe('false');
  });
});

describe('CustomSelect selection', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('fires onOptionSelect + optionselect on click', () => {
    const menu = buildMenu();
    const onOptionSelect = vi.fn();
    const onEvent = vi.fn();
    const select = new CustomSelect(menu, {onOptionSelect});
    select.on('optionselect', onEvent);

    const first = select.$options[0]!;
    first.dispatchEvent(new MouseEvent('click', {bubbles: true}));

    expect(onOptionSelect).toHaveBeenCalledWith(first);
    expect(onEvent).toHaveBeenCalledOnce();
  });
});

describe('CustomSelect show/hide', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('toggles visibility and emits show/hide (no anchor → no layout)', () => {
    const menu = buildMenu();
    const select = new CustomSelect(menu);
    const onShow = vi.fn();
    const onHide = vi.fn();
    select.on('show', onShow);
    select.on('hide', onHide);

    select.show();
    expect(select.visible).toBe(true);
    expect(menu.classList.contains('visible')).toBe(true);
    expect(onShow).toHaveBeenCalledOnce();

    select.hide();
    expect(select.visible).toBe(false);
    expect(onHide).toHaveBeenCalledOnce();
  });

  it('show() is a no-op when already visible', () => {
    const select = new CustomSelect(buildMenu());
    const onShow = vi.fn();
    select.on('show', onShow);
    select.show();
    select.show();
    expect(onShow).toHaveBeenCalledOnce();
  });
});

describe('CustomSelect type-ahead search text', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('lowercases and strips nested SVGs', () => {
    const menu = document.createElement('div');
    const ul = document.createElement('ul');
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.innerHTML = '<svg><path/></svg> Transform';
    li.appendChild(a);
    ul.appendChild(li);
    menu.appendChild(ul);
    document.body.appendChild(menu);

    const select = new CustomSelect(menu);
    expect(select.getOptionSearchText(select.$options[0]!)).toBe('transform');
  });
});
