import {afterEach, describe, expect, it} from 'vite-plus/test';

import {Select} from '../src/select';

// happy-dom has no layout, so the 2D-geometry navigation (`getClosestItem`,
// which reads `getBoundingClientRect`/`offset*`) isn't exercised here — these
// tests cover the pointer-driven selection model (click / shift / ctrl), item
// membership, filtering, and teardown, which is what `<craft-component-select>`
// relies on.

function makeItem(): HTMLElement {
  const el = document.createElement('div');
  document.body.appendChild(el);
  return el;
}

function fireMouse(
  el: EventTarget,
  type: string,
  opts: MouseEventInit = {}
): void {
  el.dispatchEvent(new MouseEvent(type, {bubbles: true, button: 0, ...opts}));
}

/** A plain click = mousedown then mouseup (Select resolves clicks across both). */
function click(el: HTMLElement, opts: MouseEventInit = {}): void {
  fireMouse(el, 'mousedown', opts);
  fireMouse(el, 'mouseup', opts);
}

describe('Select', () => {
  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('selects an item on click', () => {
    const a = makeItem();
    const select = new Select({multi: true});
    select.addItems([a]);

    click(a);

    expect(a.classList.contains('sel')).toBe(true);
    expect(select.getSelectedItems()).toEqual([a]);

    select.destroy();
  });

  it('extends a contiguous range with shift-click (multi)', () => {
    const a = makeItem();
    const b = makeItem();
    const c = makeItem();
    const select = new Select({multi: true});
    select.addItems([a, b, c]);

    click(a);
    click(c, {shiftKey: true});

    expect(select.getSelectedItems()).toEqual([a, b, c]);

    select.destroy();
  });

  it('toggles individual items with ⌘/ctrl-click (multi)', () => {
    const a = makeItem();
    const b = makeItem();
    const select = new Select({multi: true});
    select.addItems([a, b]);

    click(a);
    click(b, {metaKey: true, ctrlKey: true});
    expect(select.getSelectedItems()).toEqual([a, b]);

    click(b, {metaKey: true, ctrlKey: true});
    expect(select.getSelectedItems()).toEqual([a]);

    select.destroy();
  });

  it('replaces the selection when multi is off', () => {
    const a = makeItem();
    const b = makeItem();
    const select = new Select({multi: false});
    select.addItems([a, b]);

    click(a);
    click(b);

    expect(select.getSelectedItems()).toEqual([b]);
    expect(a.classList.contains('sel')).toBe(false);

    select.destroy();
  });

  it('drops removed items from the selection and item list', () => {
    const a = makeItem();
    const b = makeItem();
    const select = new Select({multi: true});
    select.addItems([a, b]);

    click(a);
    click(b, {shiftKey: true});
    expect(select.getSelectedItems()).toEqual([a, b]);

    select.removeItems(b);

    expect(select.getSelectedItems()).toEqual([a]);
    expect(select.$items).toEqual([a]);

    select.destroy();
  });

  it('honors a function filter (clicks on excluded targets do not select)', () => {
    const a = makeItem();
    const btn = document.createElement('button');
    a.appendChild(btn);

    const select = new Select({
      multi: true,
      filter: (target) =>
        !(target instanceof Element && target.closest('button')),
    });
    select.addItems([a]);

    // A click originating on the button is filtered out.
    click(btn);
    expect(select.getSelectedItems()).toEqual([]);

    // A click on the item body selects it.
    click(a);
    expect(select.getSelectedItems()).toEqual([a]);

    select.destroy();
  });

  it('deselectAll clears the selection and its classes', () => {
    const a = makeItem();
    const b = makeItem();
    const select = new Select({multi: true});
    select.addItems([a, b]);

    click(a);
    click(b, {shiftKey: true});

    select.deselectAll();

    expect(select.getSelectedItems()).toEqual([]);
    expect(a.classList.contains('sel')).toBe(false);
    expect(b.classList.contains('sel')).toBe(false);

    select.destroy();
  });

  it('does not re-add an item this select already owns', () => {
    const a = makeItem();
    const select = new Select({multi: true});
    select.addItems([a]);
    select.addItems([a]);

    expect(select.$items).toEqual([a]);

    select.destroy();
  });
});
