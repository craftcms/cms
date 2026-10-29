import {afterEach, expect, it, vi} from 'vite-plus/test';
import {addActionsToChip} from './chip-actions';

afterEach(() => {
  document.body.replaceChildren();
});

function chip(suffix: string): HTMLElement {
  const el = document.createElement('craft-chip');
  el.innerHTML = `<div slot="suffix">${suffix}</div>`;
  document.body.append(el);

  return el;
}

const itemLabels = (menu: Element) =>
  [...menu.querySelectorAll('[slot="content"] > *')].map((node) =>
    node.tagName === 'HR' ? '---' : node.textContent
  );

it('adds actions to the chip’s action menu, destructive ones last', async () => {
  const el = chip(
    '<craft-action-menu><div slot="content"><craft-action-item>Edit</craft-action-item></div></craft-action-menu>'
  );
  const onRemove = vi.fn();

  addActionsToChip(el, [
    {label: 'Remove', destructive: true, onActivate: onRemove},
    {label: 'Replace', icon: 'arrows-rotate'},
  ]);
  const menu = el.querySelector('craft-action-menu')!;
  await vi.waitFor(() => expect(itemLabels(menu)).toHaveLength(4));

  expect(itemLabels(menu)).toEqual(['Edit', 'Replace', '---', 'Remove']);
  expect(
    menu.querySelectorAll('craft-action-item')[1]!.getAttribute('icon')
  ).toBe('arrows-rotate');

  const remove = menu.querySelector<HTMLElement>('[variant="danger"]')!;
  const closed = vi.fn();
  menu.addEventListener('close-overlay', closed);
  remove.click();

  expect(onRemove).toHaveBeenCalledWith(remove);
  expect(closed).toHaveBeenCalled();
});

it('builds an action menu when the chip has a container but no menu', async () => {
  const el = chip('');

  addActionsToChip([el], [{label: 'Replace'}]);

  const menu = el.querySelector('[slot="suffix"] > craft-action-menu')!;
  expect(
    menu.querySelector('[slot="invoker"]')!.getAttribute('aria-label')
  ).toBe('Actions');
  await vi.waitFor(() => expect(itemLabels(menu)).toEqual(['Replace']));
});

it('puts prepended actions ahead of the existing ones', async () => {
  const el = chip(
    '<craft-action-menu><div slot="content"><craft-action-item>Edit</craft-action-item></div></craft-action-menu>'
  );

  addActionsToChip(el, [{label: 'Replace'}], true);

  const menu = el.querySelector('craft-action-menu')!;
  await vi.waitFor(() => expect(itemLabels(menu)).toEqual(['Replace', 'Edit']));
});
