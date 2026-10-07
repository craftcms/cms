import {nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {renderUi} from '@/actions/CraftCms/Cms/Http/Controllers/FieldsController';
import {defineFieldSettingsUiHost} from './field-settings-ui-host';
import {registerUiComponents} from './register';
import {columnMetadata} from './table.test-support';
import type {UiNodePayload, UiPayload} from './types';

const cellTypes = [
  {label: 'Text', value: 'singleline'},
  {label: 'Select', value: 'select'},
];

afterEach(() => {
  document.body.replaceChildren();
  vi.restoreAllMocks();
});

function settingsPayload(select = false): UiPayload {
  const scope = select ? ['settings'] : [];
  const path = [...scope, 'columns'];
  const settings: UiNodePayload = {
    type: 'Field',
    component: 'craft:field',
    props: {label: 'Columns'},
    control: {
      type: 'TableColumns',
      component: 'craft:table-columns',
      mode: 'editable',
      path,
      deltaGroup: path,
      reactive: true,
      props: {
        cellTypes,
      },
      uis: [
        {
          scope: [...path, 'col1'],
          refreshable: false,
          nodes: [
            ...columnMetadata([...path, 'col1'], cellTypes, path),
            ...(select
              ? [
                  {
                    type: 'Field',
                    component: 'craft:field',
                    props: {label: 'Empty label'},
                    control: {
                      type: 'Text',
                      component: 'craft:text',
                      mode: 'editable' as const,
                      props: {},
                      path: [...path, 'col1', 'emptyLabel'],
                      deltaGroup: path,
                    },
                  },
                ]
              : []),
          ],
        },
      ],
    },
  };
  const defaultsPath = [...scope, 'defaults'];
  const cell: UiNodePayload = {
    type: 'Field',
    component: 'craft:field',
    props: {label: 'Status'},
    control: {
      type: select ? 'Choice' : 'Text',
      component: select ? 'craft:choice' : 'craft:text',
      mode: 'editable',
      path: [...defaultsPath, '0', 'col1'],
      deltaGroup: defaultsPath,
      props: select
        ? {
            options: [
              {label: 'Draft', value: 'draft'},
              {label: 'Published', value: 'published'},
            ],
            presentation: 'select',
          }
        : {},
    },
  };
  const values = {
    columns: {
      col1: {
        heading: 'Status',
        handle: 'status',
        type: select ? 'select' : 'singleline',
        emptyLabel: 'Choose',
        privateSetting: 'retain',
      },
    },
    defaults: [{col1: 'draft'}],
  };
  return {
    scope,
    refreshable: true,
    errors: [],
    globalErrors: [],
    values: select ? {settings: values} : values,
    nodes: [
      settings,
      {
        type: 'Field',
        component: 'craft:field',
        props: {label: 'Defaults'},
        control: {
          type: 'Table',
          component: 'craft:table',
          mode: 'editable',
          path: defaultsPath,
          deltaGroup: defaultsPath,
          props: {
            columns: {
              col1: {heading: 'Status', type: select ? 'select' : 'singleline'},
            },
          },
          uis: [
            {scope: [...defaultsPath, '0'], refreshable: false, nodes: [cell]},
          ],
        },
      },
    ],
  };
}

async function settle(form: HTMLFormElement): Promise<void> {
  await nextTick();
  for (const element of form.querySelectorAll('*')) {
    if ('updateComplete' in element) await element.updateComplete;
  }
  await nextTick();
}

it('refreshes the complete namespaced legacy settings UI and retains unsaved settings for native submission', async () => {
  const request = vi
    .spyOn(actionClient, 'post')
    .mockResolvedValue({data: {ui: settingsPayload(true)}});
  const components = createCpComponentRegistry();
  registerUiComponents(components);
  defineFieldSettingsUiHost(components);
  const form = document.createElement('form');
  const host = document.createElement('craft-field-settings-ui');
  host.setAttribute('name', 'fields[new][settings][__fieldSettings]');
  host.dataset.fieldType = 'CraftCms\\Cms\\Field\\Table';
  host.dataset.payload = JSON.stringify(settingsPayload());
  form.append(host);
  document.body.append(form);
  await settle(form);

  const heading = form.querySelector<HTMLElement & {modelValue: string}>(
    'craft-input[name="fields[new][settings][columns][col1][heading]"]'
  )!;
  heading.modelValue = 'Publication status';
  heading.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true})
  );
  const type = form.querySelector<HTMLSelectElement>(
    'select[name="fields[new][settings][columns][col1][type]"]'
  )!;
  type.value = 'select';
  type.dispatchEvent(new Event('change', {bubbles: true}));
  await vi.waitFor(() => expect(request).toHaveBeenCalledTimes(1));
  await settle(form);

  expect(request).toHaveBeenCalledWith(
    renderUi.url(),
    expect.objectContaining({
      settingsOnly: true,
      scope: [],
      values: expect.objectContaining({
        type: 'CraftCms\\Cms\\Field\\Table',
        settings: expect.objectContaining({
          columns: expect.objectContaining({
            col1: expect.objectContaining({
              heading: 'Publication status',
              type: 'select',
              privateSetting: 'retain',
            }),
          }),
        }),
      }),
    })
  );
  await vi.waitFor(() =>
    expect(
      form.querySelector(
        'select[name="fields[new][settings][defaults][0][col1]"]'
      )
    ).not.toBeNull()
  );
  const defaultChoice = form.querySelector<HTMLSelectElement>(
    'select[name="fields[new][settings][defaults][0][col1]"]'
  );
  expect(defaultChoice?.value).toBe('draft');
  expect(
    [...defaultChoice!.options].map((option) => option.textContent)
  ).toContain('Published');
  expect(
    new FormData(form).get('fields[new][settings][columns][col1][heading]')
  ).toBe('Publication status');
  expect(
    new FormData(form).get(
      'fields[new][settings][columns][col1][privateSetting]'
    )
  ).toBe('retain');
});
