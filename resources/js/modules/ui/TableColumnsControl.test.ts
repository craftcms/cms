import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {
  openColumnSettings,
  type OpenColumnSettingsOptions,
} from './table/columnSettingsSlideout';
import UiRenderer from './UiRenderer.vue';
import {registerUiComponents} from './register';
import {columnMetadata} from './table.test-support';
import type {UiPayload, UiValues} from './types';

const cellTypes = [
  {label: 'Text', value: 'singleline'},
  {label: 'Plugin select', value: 'plugin:select'},
];

const slideout = vi.hoisted(() => ({
  options: undefined as OpenColumnSettingsOptions | undefined,
}));

// The slideout itself is only chrome around the settings, which the control
// teleports into it.
vi.mock('./table/columnSettingsSlideout', () => ({
  openColumnSettings: vi.fn(async (options: OpenColumnSettingsOptions) => {
    slideout.options = options;
    return {id: 'slideout-1'};
  }),
}));

let app: App | undefined;
let form: HTMLFormElement;
let panel: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  form?.remove();
  panel?.remove();
  slideout.options = undefined;
  vi.mocked(openColumnSettings).mockClear();
});

async function settle() {
  await nextTick();
  for (const element of document.body.querySelectorAll('*')) {
    if ('updateComplete' in element) await element.updateComplete;
  }
  await nextTick();
}

/** Opens a column's settings, and renders them into a stand-in panel. */
async function configureColumn(heading: string): Promise<HTMLElement> {
  form
    .querySelector<HTMLElement>(
      `craft-button[aria-label="Configure column ${heading}"]`
    )!
    .click();
  await vi.waitFor(() => expect(slideout.options).toBeDefined());

  panel = document.createElement('div');
  document.body.append(panel);
  slideout.options!.mount(panel);
  await settle();

  return panel;
}

async function changeEmptyLabel(
  panel: HTMLElement,
  value: string
): Promise<void> {
  const input = panel.querySelector<HTMLElement & {modelValue: string}>(
    'craft-input[name="columns[col1][emptyLabel]"]'
  )!;
  input.modelValue = value;
  input.dispatchEvent(new CustomEvent('model-value-changed', {bubbles: true}));
  await settle();
}

const statusColumn = {
  columns: {
    col1: {
      heading: 'Status',
      handle: 'status',
      width: '',
      type: 'plugin:select',
      emptyLabel: 'Choose a status',
      privateSetting: 'retain',
    },
  },
};

async function mountColumns(
  values: UiValues,
  rendererProps: {
    refresh?: (values: UiValues) => Promise<UiPayload>;
    'onUpdate:mutation'?: (values: UiValues) => void;
  } = {},
  columnErrors?: Record<string, Record<string, true>>
): Promise<void> {
  form = document.createElement('form');
  const host = document.createElement('div');
  form.append(host);
  document.body.append(form);
  app = createApp({
    render: () =>
      h(UiRenderer, {payload: payload(values, columnErrors), ...rendererProps}),
  });
  const registry = createCpComponentRegistry();
  registerUiComponents(registry);
  registry.install(app);
  app.mount(host);
  await settle();
}

function payload(
  values: UiValues,
  columnErrors?: Record<string, Record<string, true>>
): UiPayload {
  const columns = values.columns as UiValues;
  const config = (columns.col1 ?? {}) as UiValues;
  const select = config.type === 'plugin:select';
  return {
    scope: [],
    refreshable: true,
    values,
    errors: [],
    globalErrors: [],
    nodes: [
      {
        type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Columns'},
        control: {
          type: 'CraftCms\\Cms\\Ui\\Controls\\TableColumns',
          component: 'craft:table-columns',
          mode: 'editable',
          path: ['columns'],
          deltaGroup: ['columns'],
          reactive: true,
          props: {
            cellTypes,
            ...(columnErrors ? {errors: columnErrors} : {}),
            rowTemplate: {
              scope: [],
              refreshable: true,
              nodes: columnMetadata([], cellTypes, ['columns']),
            },
          },
          uis: Object.keys(columns).map((key) => ({
            scope: ['columns', key],
            refreshable: true,
            nodes: [
              ...columnMetadata(['columns', key], cellTypes, ['columns']),
              ...(select && key === 'col1'
                ? [
                    {
                      type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
                      component: 'craft:field',
                      props: {label: 'Empty option label'},
                      control: {
                        type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
                        component: 'craft:text',
                        path: ['columns', 'col1', 'emptyLabel'],
                        deltaGroup: ['columns'],
                        mode: 'editable' as const,
                        props: {},
                      },
                    },
                  ]
                : []),
            ],
          })),
        },
      },
    ],
  };
}

describe('Table column settings', () => {
  it('refreshes cell settings after a type change and submits settings applied in the slideout', async () => {
    const refresh = vi.fn(async (values: UiValues) => payload(values));
    const initial = {
      columns: {
        col1: {
          heading: 'Status',
          handle: 'status',
          width: '',
          type: 'singleline',
          emptyLabel: 'Choose a status',
          privateSetting: 'retain',
          emptyList: [],
        },
      },
    };
    await mountColumns(initial, {refresh});
    expect(
      form.querySelector('craft-button[aria-label="Configure column Status"]')
    ).toBeNull();

    const type = form.querySelector<HTMLSelectElement>(
      'select[name="columns[col1][type]"]'
    )!;
    type.value = 'plugin:select';
    type.dispatchEvent(new Event('change', {bubbles: true}));
    await vi.waitFor(() => expect(refresh).toHaveBeenCalledTimes(1));
    await settle();
    const settings = await configureColumn('Status');

    expect(
      settings.querySelector<HTMLElement & {modelValue: string}>(
        'craft-input[name="columns[col1][emptyLabel]"]'
      )?.modelValue
    ).toBe('Choose a status');
    await changeEmptyLabel(settings, 'No status');
    slideout.options!.apply();
    slideout.options!.onClosed();
    await settle();

    expect(new FormData(form).get('columns[col1][emptyLabel]')).toBe(
      'No status'
    );
    expect(new FormData(form).get('columns[col1][privateSetting]')).toBe(
      'retain'
    );
    expect(new FormData(form).getAll('columns[col1][emptyList]')).toEqual(['']);
  });

  it('flags columns whose settings are invalid', async () => {
    await mountColumns(
      statusColumn,
      {refresh: async (values) => payload(values)},
      {col1: {emptyLabel: true}}
    );

    expect(
      form.querySelector(
        'craft-button[aria-label="Configure column Status (contains errors)"]'
      )
    ).not.toBeNull();
  });

  it('opens one slideout at a time', async () => {
    await mountColumns(statusColumn, {
      refresh: async (values) => payload(values),
    });
    const button = form.querySelector<HTMLElement>(
      'craft-button[aria-label="Configure column Status"]'
    )!;

    button.click();
    button.click();
    await settle();

    expect(openColumnSettings).toHaveBeenCalledTimes(1);
  });

  it('leaves the columns alone when the slideout closes without changes', async () => {
    const refresh = vi.fn(async (values: UiValues) => payload(values));
    await mountColumns(statusColumn, {refresh});

    await configureColumn('Status');
    slideout.options!.onClosed();
    await settle();

    expect(refresh).not.toHaveBeenCalled();
  });

  it('restores the column settings when the slideout closes without applying them', async () => {
    await mountColumns(statusColumn, {
      refresh: async (values) => payload(values),
    });

    const settings = await configureColumn('Status');
    await changeEmptyLabel(settings, 'No status');
    expect(slideout.options!.settings()).toContain('No status');
    slideout.options!.onClosed();
    await settle();

    expect(new FormData(form).get('columns[col1][emptyLabel]')).toBe(
      'Choose a status'
    );
  });

  it('adds a column to an empty table, generates its handle, and retains a manually edited handle', async () => {
    await mountColumns(
      {columns: {}},
      {refresh: async (values) => payload(values)}
    );

    const addButton = [
      ...form.querySelectorAll<HTMLElement>('craft-button'),
    ].find((button) => button.textContent?.trim() === 'Add a column')!;
    addButton.focus();
    addButton.click();
    await settle();
    const heading = form.querySelector<HTMLElement & {modelValue: string}>(
      'craft-input[name="columns[col1][heading]"]'
    )!;
    expect(heading).not.toBeNull();
    expect(document.activeElement).toBe(heading.querySelector('input'));
    heading.modelValue = 'Publication status';
    heading.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await settle();
    expect(new FormData(form).get('columns[col1][handle]')).toBe(
      'publicationStatus'
    );
    expect(new FormData(form).get('columns[col1][type]')).toBe('singleline');

    const handle = form.querySelector<HTMLElement & {modelValue: string}>(
      'craft-input-handle[name="columns[col1][handle]"]'
    )!;
    handle.modelValue = 'custom';
    handle.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await settle();
    heading.modelValue = 'Changed';
    heading.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await settle();
    expect(new FormData(form).get('columns[col1][handle]')).toBe('custom');

    form
      .querySelector<HTMLElement>('craft-button[aria-label="Delete row 1"]')!
      .click();
    await settle();
    expect(new FormData(form).has('columns[col1][heading]')).toBe(false);
    [...form.querySelectorAll<HTMLElement>('craft-button')]
      .find((button) => button.textContent?.trim() === 'Add a column')!
      .click();
    await settle();
    expect(
      form.querySelector<HTMLInputElement>('input[name$="[heading]"]')?.value
    ).toBe('');
  });

  it('submits a pure column order change and clears the mutation when the original order is restored', async () => {
    const initial = {
      columns: {
        col1: {
          heading: 'First',
          handle: 'first',
          width: '',
          type: 'singleline',
          privateSetting: 'retain',
        },
        col2: {
          heading: 'Second',
          handle: 'second',
          width: '',
          type: 'singleline',
        },
      },
    };
    let mutation: UiValues = {};
    await mountColumns(initial, {
      'onUpdate:mutation': (values) => {
        mutation = values;
      },
    });

    form.querySelector('tbody tr craft-reorder-button')!.dispatchEvent(
      new CustomEvent('craft-reorder', {
        detail: {direction: 'down'},
        bubbles: true,
      })
    );
    await settle();

    expect(Object.keys(mutation.columns as UiValues)).toEqual(['col2', 'col1']);
    expect(mutation.columns).toEqual(initial.columns);
    expect(
      [
        ...form.querySelectorAll<HTMLInputElement>('input[name$="[heading]"]'),
      ].map((input) => input.value)
    ).toEqual(['Second', 'First']);

    form.querySelector('tbody tr craft-reorder-button')!.dispatchEvent(
      new CustomEvent('craft-reorder', {
        detail: {direction: 'down'},
        bubbles: true,
      })
    );
    await settle();

    expect(mutation).toEqual({});
    expect(
      [
        ...form.querySelectorAll<HTMLInputElement>('input[name$="[heading]"]'),
      ].map((input) => input.value)
    ).toEqual(['First', 'Second']);
  });
});
