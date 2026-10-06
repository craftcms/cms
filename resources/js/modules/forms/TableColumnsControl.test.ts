import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftDialog from '@craftcms/ui/components/dialog/dialog';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormRenderer from './FormRenderer.vue';
import {registerFormComponents} from './register';
import {columnMetadata} from './table.test-support';
import type {FormPayload, FormValues} from './types';

const cellTypes = [
  {label: 'Text', value: 'singleline'},
  {label: 'Plugin select', value: 'plugin:select'},
];

let app: App | undefined;
let form: HTMLFormElement;

afterEach(() => {
  app?.unmount();
  form?.remove();
});

async function settle() {
  await nextTick();
  for (const element of form.querySelectorAll('*')) {
    if ('updateComplete' in element) await element.updateComplete;
  }
  await nextTick();
}

async function mountColumns(
  values: FormValues,
  rendererProps: {
    refresh?: (values: FormValues) => Promise<FormPayload>;
    'onUpdate:mutation'?: (values: FormValues) => void;
  } = {}
): Promise<void> {
  form = document.createElement('form');
  const host = document.createElement('div');
  form.append(host);
  document.body.append(form);
  app = createApp({
    render: () => h(FormRenderer, {payload: payload(values), ...rendererProps}),
  });
  const registry = createCpComponentRegistry();
  registerFormComponents(registry);
  registry.install(app);
  app.mount(host);
  await settle();
}

function payload(values: FormValues): FormPayload {
  const columns = values.columns as FormValues;
  const config = (columns.col1 ?? {}) as FormValues;
  const select = config.type === 'plugin:select';
  return {
    scope: [],
    refreshable: true,
    values,
    errors: [],
    globalErrors: [],
    nodes: [
      {
        type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Columns'},
        control: {
          type: 'CraftCms\\Cms\\Form\\Controls\\TableColumns',
          component: 'craft:table-columns',
          mode: 'editable',
          path: ['columns'],
          deltaGroup: ['columns'],
          reactive: true,
          props: {
            cellTypes,
            rowTemplate: {
              scope: [],
              refreshable: true,
              nodes: columnMetadata([], cellTypes, ['columns']),
            },
          },
          forms: Object.keys(columns).map((key) => ({
            scope: ['columns', key],
            refreshable: true,
            nodes: [
              ...columnMetadata(['columns', key], cellTypes, ['columns']),
              ...(select && key === 'col1'
                ? [
                    {
                      type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
                      component: 'craft:field',
                      props: {label: 'Empty option label'},
                      control: {
                        type: 'CraftCms\\Cms\\Form\\Controls\\Text',
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
  it('refreshes cell settings after a type change and preserves configured values when the dialog closes', async () => {
    const refresh = vi.fn(async (values: FormValues) => payload(values));
    const initial = {
      columns: {
        col1: {
          heading: 'Status',
          handle: 'status',
          width: '',
          type: 'singleline',
          emptyLabel: 'Choose a status',
          privateSetting: 'retain',
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
    form
      .querySelector<HTMLElement>(
        'craft-button[aria-label="Configure column Status"]'
      )!
      .click();
    await settle();

    const input = form.querySelector<HTMLElement & {modelValue: string}>(
      'craft-input[name="columns[col1][emptyLabel]"]'
    )!;
    expect(input).not.toBeNull();
    expect(input.modelValue).toBe('Choose a status');
    input.modelValue = 'No status';
    input.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await settle();
    [...form.querySelectorAll<HTMLElement>('craft-button')]
      .find((button) => button.textContent?.trim() === 'Done')!
      .click();
    await settle();

    expect(new FormData(form).get('columns[col1][emptyLabel]')).toBe(
      'No status'
    );
    expect(new FormData(form).get('columns[col1][privateSetting]')).toBe(
      'retain'
    );
    expect(form.querySelector<CraftDialog>('craft-dialog')?.opened).toBe(false);
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
    let mutation: FormValues = {};
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

    expect(Object.keys(mutation.columns as FormValues)).toEqual([
      'col2',
      'col1',
    ]);
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
