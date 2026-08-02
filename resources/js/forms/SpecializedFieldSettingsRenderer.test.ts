import {createApp} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import type CraftEditableTable from '@/modules/editable-table/editable-table.ce';
import EditableTableValueAdapter from './EditableTableValueAdapter.vue';
import FormRenderer from './FormRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

it('isolates editable table column coordination by Form', () => {
  const first = mount();
  const second = mount();
  const firstTable = first.querySelector<CraftEditableTable>(
    'craft-editable-table'
  )!;
  const secondTable = second.querySelector<CraftEditableTable>(
    'craft-editable-table'
  )!;
  const firstSetColumns = vi
    .spyOn(firstTable, 'setColumns')
    .mockImplementation(() => {});
  const secondSetColumns = vi
    .spyOn(secondTable, 'setColumns')
    .mockImplementation(() => {});

  window.dispatchEvent(
    new CustomEvent('craft:editable-table-columns-changed', {
      detail: {
        scope: first.querySelector('[data-form-root]'),
        name: 'columns',
        columns: {title: {heading: 'Title', type: 'singleline'}},
      },
    })
  );

  expect(firstSetColumns).toHaveBeenCalledOnce();
  expect(secondSetColumns).not.toHaveBeenCalled();
});

function mount(): HTMLElement {
  const registry = createCpComponentRegistry();
  const container = document.createElement('div');

  registry.register('craft:editable-table-input', EditableTableValueAdapter);
  (window as any).Cp = {$formElements: registry};
  document.body.appendChild(container);
  const app = createApp(FormRenderer, {
    form: {
      elements: [
        {
          type: 'craft:field',
          children: [
            {
              type: 'craft:editable-table-input',
              name: 'defaults',
              props: {
                tableHtml: '<craft-editable-table></craft-editable-table>',
                columnsFrom: 'columns',
              },
            },
          ],
        },
      ],
    },
    bindingScope: 'settings',
    values: {settings: {defaults: []}},
    errors: {},
  });

  mountedApps.push(app);
  app.mount(container);

  return container;
}
