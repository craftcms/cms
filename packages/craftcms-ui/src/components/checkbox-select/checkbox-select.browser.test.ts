import {beforeEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import '../../styles/cp.css';
import './checkbox-select.js';
import type CraftCheckbox from '../checkbox/checkbox.js';
import type CraftCheckboxSelect from './checkbox-select.js';
import type {CheckboxSelectOption} from './checkbox-select.js';

const options: CheckboxSelectOption[] = [
  {value: 'title', label: 'Title'},
  {value: 'author', label: 'Author'},
  {value: 'postDate', label: 'Post Date'},
];

beforeEach(() => {
  document.body.innerHTML = '';
});

const settle = async (element: CraftCheckboxSelect) => {
  await element.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  await element.updateComplete;
};

async function create(
  attributes: Record<string, string | boolean> = {},
  initialOptions: CheckboxSelectOption[] = options
): Promise<{element: CraftCheckboxSelect; form: HTMLFormElement}> {
  const form = document.createElement('form');
  const element = document.createElement('craft-checkbox-select');
  for (const [name, value] of Object.entries(attributes)) {
    if (value === true) {
      element.setAttribute(name, '');
    } else if (value !== false) {
      element.setAttribute(name, value);
    }
  }
  form.append(element);
  document.body.append(form);
  element.options = initialOptions;
  await settle(element);

  return {element, form};
}

const posted = (form: HTMLFormElement, name: string) =>
  new FormData(form).getAll(name);

const shadow = (element: CraftCheckboxSelect) => element.shadowRoot!;

const input = (element: CraftCheckboxSelect, value: string) =>
  element.querySelector<HTMLInputElement>(
    `.cp-checkbox-select__item input[value="${value}"]`
  )!;

it('adopts server-rendered items, posting checked values in display order', async () => {
  document.body.innerHTML = `
    <form>
      <craft-checkbox-select name="columns">
        <input type="hidden" name="columns" value="" data-empty>
        <div class="cp-checkbox-select__item">
          <craft-checkbox><input type="checkbox" slot="input" id="c-1" name="columns[]" value="postDate" checked><label slot="label" for="c-1">Post Date</label></craft-checkbox>
        </div>
        <div class="cp-checkbox-select__item">
          <craft-checkbox><input type="checkbox" slot="input" id="c-2" name="columns[]" value="title"><label slot="label" for="c-2">Title</label></craft-checkbox>
        </div>
        <div class="cp-checkbox-select__item">
          <craft-checkbox><input type="checkbox" slot="input" id="c-3" name="columns[]" value="author" checked><label slot="label" for="c-3">Author</label></craft-checkbox>
        </div>
      </craft-checkbox-select>
    </form>`;
  const element = document.querySelector('craft-checkbox-select')!;
  await settle(element);

  expect(element.modelValue).toEqual(['postDate', 'author']);
  expect(element.options.map((option) => option.label)).toEqual([
    'Post Date',
    'Title',
    'Author',
  ]);
  expect(posted(document.querySelector('form')!, 'columns[]')).toEqual([
    'postDate',
    'author',
  ]);
  expect(element.querySelectorAll('input[data-empty]')).toHaveLength(1);
});

it('posts an empty value while nothing is checked', async () => {
  const {form} = await create({name: 'columns'});

  expect(posted(form, 'columns')).toEqual(['']);
  expect(posted(form, 'columns[]')).toEqual([]);
});

it('changes its options at runtime, keeping what is checked', async () => {
  const {element} = await create({name: 'columns'});
  element.modelValue = ['author'];

  element.options = [
    {value: 'postDate', label: 'Published'},
    {value: 'author', label: 'Author'},
  ];
  await settle(element);

  expect(element.options).toMatchObject([
    {value: 'postDate', label: 'Published', checked: false},
    {value: 'author', label: 'Author', checked: true},
  ]);

  element.addOption({value: 'slug', label: 'Slug', checked: true}, 1);
  element.updateOption('postDate', {label: 'Post Date', checked: true});
  element.removeOption('author');
  await settle(element);

  expect(element.options.map((option) => option.value)).toEqual([
    'postDate',
    'slug',
  ]);
  expect(element.modelValue).toEqual(['postDate', 'slug']);
  expect(shadow(element).querySelectorAll('.row').length).toBe(2);
});

it('picks up items added to its markup directly', async () => {
  const {element} = await create();
  const item = document.createElement('div');
  item.className = 'cp-checkbox-select__item';
  item.innerHTML =
    '<craft-checkbox><input type="checkbox" slot="input" value="slug" checked><label slot="label">Slug</label></craft-checkbox>';
  element.append(item);
  await settle(element);

  expect(shadow(element).querySelectorAll('.row')).toHaveLength(4);
  expect(element.modelValue).toEqual(['slug']);
});

it('posts only the “All” value while “All” is checked in single-value mode', async () => {
  const {element, form} = await create({
    name: 'sources',
    'all-label': 'All',
  });
  const all = element.querySelector<HTMLInputElement>('.all input')!;

  await userEvent.click(all);
  await settle(element);

  expect(element.options.every((option) => option.checked)).toBe(true);
  expect(posted(form, 'sources')).toEqual(['']);
  expect(posted(form, 'sources[]')).toEqual(['*']);
  expect(element.modelValue).toEqual(['*']);

  await userEvent.click(input(element, 'author'));
  await settle(element);

  expect(all.checked).toBe(false);
  expect(posted(form, 'sources[]')).toEqual(['title', 'postDate']);
  expect(element.modelValue).toEqual(['title', 'postDate']);
});

it('takes the “All” value as its model value before its first render', async () => {
  const element = document.createElement('craft-checkbox-select');
  element.setAttribute('all-label', 'All');
  element.options = options;
  element.modelValue = ['*'];
  document.body.append(element);
  await settle(element);

  expect(element.querySelector<HTMLInputElement>('.all input')!.checked).toBe(
    true
  );
  expect(element.modelValue).toEqual(['*']);
});

it('checks every option from “All” in each-value mode, posting their values', async () => {
  const {element, form} = await create({
    name: 'caches',
    'all-label': 'All',
    'all-mode': 'each-value',
  });
  const allCheckbox = element.querySelector<CraftCheckbox>(
    '.all craft-checkbox'
  )!;

  await userEvent.click(input(element, 'title'));
  await settle(element);
  expect(allCheckbox.indeterminate).toBe(true);

  await userEvent.click(element.querySelector<HTMLInputElement>('.all input')!);
  await settle(element);

  expect(posted(form, 'caches[]')).toEqual(['title', 'author', 'postDate']);
  expect(element.modelValue).toEqual(['title', 'author', 'postDate']);
  expect(allCheckbox.indeterminate).toBe(false);
});

it('moves options with their reorder buttons, posting the new order', async () => {
  const {element, form} = await create({name: 'columns', sortable: true});
  element.modelValue = ['title', 'author'];
  const onOrder = vi.fn();
  element.addEventListener('craft-order-change', onOrder);
  await settle(element);

  const buttons = shadow(element).querySelectorAll('craft-reorder-button');
  expect([...buttons].map((button) => button.getAttribute('position'))).toEqual(
    ['first', 'middle', 'last']
  );

  buttons[0]!.dispatchEvent(
    new CustomEvent('craft-reorder', {detail: {direction: 'down'}})
  );
  await settle(element);

  expect(posted(form, 'columns[]')).toEqual(['author', 'title']);
  expect(onOrder).toHaveBeenCalledWith(
    expect.objectContaining({detail: {value: 'title', index: 1}})
  );
  expect(shadow(element).querySelector('[role="status"]')!.textContent).toBe(
    'Moved Title to position 2'
  );
});

it('keeps “All” alongside sortable options', async () => {
  const {element, form} = await create({
    name: 'sources',
    'all-label': 'All',
    sortable: true,
  });

  await userEvent.click(element.querySelector<HTMLInputElement>('.all input')!);
  await settle(element);
  shadow(element)
    .querySelectorAll('craft-reorder-button')[2]!
    .dispatchEvent(
      new CustomEvent('craft-reorder', {detail: {direction: 'up'}})
    );
  await settle(element);

  expect(element.options.map((option) => option.value)).toEqual([
    'title',
    'postDate',
    'author',
  ]);
  expect(posted(form, 'sources[]')).toEqual(['*']);
});

it('moves options by dragging them by their reorder buttons', async () => {
  const {page} = await import('vite-plus/test/browser');
  const {element} = await create({sortable: true});
  const rows = shadow(element).querySelectorAll<HTMLElement>('.row');
  for (const row of rows) {
    row.style.height = '60px';
  }

  await page
    .elementLocator(rows[0]!.querySelector('craft-reorder-button')!)
    .dropTo(page.elementLocator(rows[2]!), {
      targetPosition: {x: 10, y: 50},
    });
  await settle(element);

  expect(element.options.map((option) => option.value)).toEqual([
    'author',
    'postDate',
    'title',
  ]);
});

it('lists only checked options in add-menu, offering the rest to add', async () => {
  const {element, form} = await create({
    name: 'columns',
    presentation: 'add-menu',
  });
  element.modelValue = ['author'];
  await settle(element);

  expect(shadow(element).querySelectorAll('.row')).toHaveLength(1);
  const menu = shadow(element).querySelector('craft-action-menu')!;
  const actions = (
    menu.actions as () => Array<{label: string; onClick: () => void}>
  )();
  expect(actions.map((action) => action.label)).toEqual(['Title', 'Post Date']);

  actions[1]!.onClick();
  await settle(element);

  expect(posted(form, 'columns[]')).toEqual(['author', 'postDate']);
  expect(
    [...shadow(element).querySelectorAll('.row__label')].map((label) =>
      label.textContent!.trim()
    )
  ).toEqual(['Author', 'Post Date']);
  expect(shadow(element).querySelector('[role="status"]')!.textContent).toBe(
    'Added Post Date'
  );
});

it('lists no options in add-menu while “All” is checked', async () => {
  const {element, form} = await create({
    name: 'sources',
    presentation: 'add-menu',
    'all-label': 'All',
  });
  const all = element.querySelector<HTMLInputElement>('.all input')!;

  await userEvent.click(all);
  await settle(element);

  expect(posted(form, 'sources[]')).toEqual(['*']);
  expect(shadow(element).querySelectorAll('.row')).toHaveLength(0);
  expect(shadow(element).querySelector('craft-action-menu')).toBeNull();

  await userEvent.click(all);
  await settle(element);

  expect(shadow(element).querySelector('craft-action-menu')).not.toBeNull();
});

it('removes an option in add-menu, moving focus to the next one', async () => {
  const {element, form} = await create({
    name: 'columns',
    presentation: 'add-menu',
  });
  element.modelValue = ['title', 'author'];
  await settle(element);

  const [first] = shadow(element).querySelectorAll<HTMLElement>('.remove');
  expect(first!.getAttribute('aria-label')).toBe('Remove Title');
  first!.click();
  await settle(element);

  expect(posted(form, 'columns[]')).toEqual(['author']);
  expect(shadow(element).activeElement).toBe(
    shadow(element).querySelector('.remove')
  );
  expect(shadow(element).querySelector('[role="status"]')!.textContent).toBe(
    'Removed Title'
  );
});

it('moves focus to the new option once nothing is left to add', async () => {
  const {element} = await create({presentation: 'add-menu'});
  element.modelValue = ['title', 'author'];
  await settle(element);

  const menu = shadow(element).querySelector('craft-action-menu')!;
  (menu.actions as () => Array<{onClick: () => void}>)()[0]!.onClick();
  await settle(element);

  const rows = shadow(element).querySelectorAll('.row');
  expect(shadow(element).activeElement).toBe(rows[2]!.querySelector('.remove'));
});

it('drops the “Add item” menu once everything is checked, and when disabled', async () => {
  const {element} = await create({presentation: 'add-menu'});
  element.modelValue = ['title', 'author', 'postDate'];
  await settle(element);
  expect(shadow(element).querySelector('craft-action-menu')).toBeNull();

  element.modelValue = ['title'];
  element.disabled = true;
  await settle(element);
  expect(shadow(element).querySelector('craft-action-menu')).toBeNull();
  expect(shadow(element).querySelector('.remove')).toBeNull();
  expect(input(element, 'author').disabled).toBe(true);

  element.disabled = false;
  await settle(element);
  expect(input(element, 'author').disabled).toBe(false);
});

it('keeps disabled options checked and in place', async () => {
  const {element} = await create({sortable: true, presentation: 'add-menu'}, [
    {value: 'title', label: 'Title', checked: true, disabled: true},
    ...options.slice(1),
  ]);
  element.modelValue = ['author', 'postDate'];
  await settle(element);

  expect(element.modelValue).toEqual(['title', 'author', 'postDate']);

  const rows = shadow(element).querySelectorAll('.row');
  expect(rows[0]!.querySelector('.remove')).toBeNull();
  expect(rows[0]!.querySelector('craft-reorder-button')!.disabled).toBe(true);

  rows[1]!
    .querySelector('craft-reorder-button')!
    .dispatchEvent(
      new CustomEvent('craft-reorder', {detail: {direction: 'up'}})
    );
  await settle(element);

  expect(element.modelValue).toEqual(['title', 'author', 'postDate']);
});

it('follows a renamed option in its label and its Remove button', async () => {
  const {element} = await create({presentation: 'add-menu'});
  element.modelValue = ['postDate'];
  await settle(element);

  element.updateOption('postDate', {label: 'Published'});
  await settle(element);

  const row = shadow(element).querySelector('.row')!;
  expect(row.querySelector('.row__label')!.textContent!.trim()).toBe(
    'Published'
  );
  expect(row.querySelector('.remove')!.getAttribute('aria-label')).toBe(
    'Remove Published'
  );
});

it('names its group with its label', async () => {
  const {element} = await create({label: 'Columns'});
  const group = shadow(element).querySelector('[role="group"]')!;

  expect(
    shadow(element).getElementById(group.getAttribute('aria-labelledby')!)!
      .textContent
  ).toBe('Columns');
  expect(group.querySelector('slot[name="all"]')).not.toBeNull();
});

it('keeps focus on an option’s reorder button after moving it', async () => {
  const {element} = await create({sortable: true});
  const button = shadow(element).querySelectorAll('craft-reorder-button')[0]!;
  button.focus();

  button.dispatchEvent(
    new CustomEvent('craft-reorder', {detail: {direction: 'down'}})
  );
  await settle(element);

  const rows = shadow(element).querySelectorAll('.row');
  expect(shadow(element).activeElement).toBe(
    rows[1]!.querySelector('craft-reorder-button')
  );
});

it('fires model-value-changed for its own value, not its checkboxes’', async () => {
  const {element} = await create();
  const onValue = vi.fn((event: Event) => event.target);
  element.addEventListener('model-value-changed', onValue);

  await userEvent.click(input(element, 'author'));
  await settle(element);

  expect(onValue.mock.results.map((result) => result.value)).toEqual([element]);
  expect(element.modelValue).toEqual(['author']);
});
