import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, type VNodeChild} from 'vue';
import {createCraftColumnHelper} from './createCraftColumnHelper';

interface Row {
  name: string;
  status: string;
  enabled: boolean;
  locked?: boolean;
}

const columnHelper = createCraftColumnHelper<Row>();
const options = [
  {label: 'Live', value: 'live', hint: 'Shown on the site'},
  {label: 'Draft', value: 'draft'},
];

let container: HTMLElement;
let app: ReturnType<typeof createApp> | null = null;

/** Renders one column's cell for a row, as the table would. */
function renderCell(column: {cell?: unknown}, row: Row): HTMLElement {
  const cell = column.cell as (context: {row: {original: Row}}) => VNodeChild;
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({render: () => cell({row: {original: row}})});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);

  return container;
}

/** A registered control takes `label` as a property; a stand-in, as an attribute. */
function labelOf(element: Element): string | null {
  return (
    element.getAttribute('label') ??
    (element as Element & {label?: string}).label ??
    null
  );
}

function change(element: Element, property: string, value: unknown): void {
  Object.assign(element, {[property]: value});
  element.dispatchEvent(new Event('change', {bubbles: true}));
}

afterEach(() => {
  app?.unmount();
  container?.remove();
  app = null;
});

const row: Row = {name: 'Home', status: 'live', enabled: true};

it.each([
  ['select', 'craft-select'],
  ['selectRich', 'craft-select-rich'],
] as const)(
  'renders a %s column as a labeled %s and reports the chosen value',
  (method, tag) => {
    const onChange = vi.fn();
    const cell = renderCell(
      columnHelper[method]({
        id: 'status',
        options,
        value: (r) => r.status,
        label: (r) => `Status of ${r.name}`,
        onChange,
      }),
      row
    );
    const select = cell.querySelector(tag)!;

    expect(labelOf(select)).toBe('Status of Home');
    expect((select as unknown as {value: string}).value).toBe('live');

    change(select, 'value', 'draft');

    expect(onChange).toHaveBeenCalledWith(row, 'draft');
  }
);

it('gives a rich select’s options their hints, which a native select leaves out', () => {
  const column = {
    id: 'status',
    options,
    value: (r: Row) => r.status,
    label: () => 'Status',
    onChange: () => {},
  };

  const rich = renderCell(columnHelper.selectRich(column), row);
  expect(
    (rich.querySelector('craft-option') as unknown as {hint: string}).hint
  ).toBe('Shown on the site');
  app!.unmount();
  container.remove();

  const native = renderCell(columnHelper.select(column), row);
  expect(native.querySelector('option')!.textContent!.trim()).toBe('Live');
});

it('reports a checkbox column’s new state, named by its label', () => {
  const onChange = vi.fn();
  const cell = renderCell(
    columnHelper.checkbox({
      id: 'enabled',
      value: (r) => r.enabled,
      label: (r) => `Enable ${r.name}`,
      onChange,
    }),
    row
  );
  const checkbox = cell.querySelector('craft-checkbox')!;

  expect(checkbox.querySelector('[slot="label"]')!.textContent).toBe(
    'Enable Home'
  );

  change(checkbox, 'checked', false);

  expect(onChange).toHaveBeenCalledWith(row, false);
});

it('disables a control for the rows that say so, and leaves hidden rows empty', () => {
  const column = columnHelper.checkbox({
    id: 'enabled',
    value: (r) => r.enabled,
    label: () => 'Enabled',
    onChange: () => {},
    disabled: (r) => Boolean(r.locked),
    hidden: (r) => r.name === 'Hidden',
  });

  const locked = renderCell(column, {...row, locked: true});
  expect(
    (locked.querySelector('craft-checkbox') as unknown as {disabled: boolean})
      .disabled
  ).toBe(true);
  app!.unmount();
  container.remove();

  const hidden = renderCell(column, {...row, name: 'Hidden'});
  expect(hidden.querySelector('craft-checkbox')).toBeNull();
});
