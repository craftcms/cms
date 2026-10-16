import '@craftcms/ui';
import $ from 'jquery';
import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {SortableCheckboxSelect} from './sortable-checkbox-select';

let select: SortableCheckboxSelect | undefined;

beforeEach(() => {
  window.$ = $;
  window.Craft = Object.assign(Object.create(null), {
    t: (_category: string, message: string, params?: Record<string, string>) =>
      message.replace(/\{(\w+)\}/g, (_match, key) => params?.[key] ?? ''),
    hasMousePointerEvents: () => false,
  });
});

afterEach(() => {
  select?.destroy();
  select = undefined;
  document.body.innerHTML = '';
  delete window.Craft;
  delete window.$;
});

function render(checked: string[]): HTMLElement {
  const container = document.createElement('fieldset');
  container.className = 'cp-checkbox-select';
  container.innerHTML = ['one', 'two', 'three']
    .map(
      (value) => `
        <div class="cp-checkbox-select__item">
          <input type="checkbox" id="${value}" value="${value}"${
            checked.includes(value) ? ' checked' : ''
          }>
          <label for="${value}">${value.toUpperCase()}</label>
        </div>`
    )
    .join('');
  document.body.append(container);
  select = new SortableCheckboxSelect(container);
  return container;
}

function visibleLabels(container: HTMLElement): string[] {
  return [
    ...container.querySelectorAll<HTMLElement>('.cp-checkbox-select__item'),
  ]
    .filter((item) => !item.hidden)
    .map(
      (item) => item.querySelector('.cp-checkbox-select__label')!.textContent!
    );
}

function addMenuLabels(): string[] {
  const menu = document.querySelector('craft-action-menu')!;
  return ((menu as any).actions() as Array<{label: string}>).map(
    (action) => action.label
  );
}

it('shows only the checked items, offering the rest to add', () => {
  const container = render(['two']);

  expect(visibleLabels(container)).toEqual(['TWO']);
  expect(addMenuLabels()).toEqual(['ONE', 'THREE']);
  expect(
    container.querySelector<HTMLElement>('.cp-checkbox-select__add')!.hidden
  ).toBe(false);
});

it('adds an item to the end of the checked ones', () => {
  const container = render(['two']);
  const changes: string[] = [];
  container.addEventListener('change', (event) =>
    changes.push((event.target as HTMLInputElement).value)
  );

  const menu = document.querySelector('craft-action-menu')!;
  const actions = (menu as any).actions() as Array<{onClick: () => void}>;
  actions[0]!.onClick();

  expect(visibleLabels(container)).toEqual(['TWO', 'ONE']);
  expect(changes).toEqual(['one']);
});

it('removes an item and hides the add menu only when nothing is left', () => {
  const container = render(['one', 'two', 'three']);

  expect(
    container.querySelector<HTMLElement>('.cp-checkbox-select__add')!.hidden
  ).toBe(true);

  container
    .querySelector<HTMLInputElement>('input[value="one"]')!
    .closest('.cp-checkbox-select__item')!
    .querySelector<HTMLElement>('.cp-checkbox-select__remove')!
    .click();

  expect(visibleLabels(container)).toEqual(['TWO', 'THREE']);
  expect(
    container.querySelector<HTMLInputElement>('input[value="one"]')!.checked
  ).toBe(false);
  expect(
    container.querySelector<HTMLElement>('.cp-checkbox-select__add')!.hidden
  ).toBe(false);
});

it('marks each item so detached drag helpers hide its checkbox too', () => {
  const container = render(['one']);
  const helper = container
    .querySelector('.cp-checkbox-select__item')!
    .cloneNode(true) as HTMLElement;

  expect(
    helper.classList.contains('cp-checkbox-select__item--selected-only')
  ).toBe(true);
});

it('labels remove buttons and follows label renames', async () => {
  const container = render(['one']);
  const item = container.querySelector('.cp-checkbox-select__item')!;

  item.querySelector('label')!.textContent = 'Renamed';
  await Promise.resolve();

  expect(item.querySelector('.cp-checkbox-select__label')!.textContent).toBe(
    'Renamed'
  );
  expect(
    item
      .querySelector('.cp-checkbox-select__remove')!
      .getAttribute('aria-label')
  ).toBe('Remove Renamed');
});
