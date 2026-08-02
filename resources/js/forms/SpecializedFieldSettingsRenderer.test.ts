import {createApp, nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import CraftEditableTable from '@craftcms/ui/vue/CraftEditableTable.vue';
import '@craftcms/ui/components/editable-table/editable-table';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormRenderer from './FormRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

it('isolates editable table column coordination by Form', async () => {
  const first = mount({
    columns: {first: {heading: 'First heading', type: 'singleline'}},
    defaults: [{rowId: 'first-row', first: 'First value'}],
  });
  const second = mount({
    columns: {second: {heading: 'Second heading', type: 'singleline'}},
    defaults: [{rowId: 'second-row', second: 'Second value'}],
  });
  const firstTables = Array.from(
    first.querySelectorAll<HTMLElementTagNameMap['craft-editable-table']>(
      'craft-editable-table'
    )
  );
  const secondTables = Array.from(
    second.querySelectorAll<HTMLElementTagNameMap['craft-editable-table']>(
      'craft-editable-table'
    )
  );

  await nextTick();
  await Promise.all(
    [...firstTables, ...secondTables].map((table) => table.updateComplete)
  );

  expect(
    firstTables[1]!.shadowRoot!.querySelector('th')?.textContent
  ).toContain('First heading');
  expect(
    secondTables[1]!.shadowRoot!.querySelector('th')?.textContent
  ).toContain('Second heading');
});

function mount(settings: Record<string, unknown>): HTMLElement {
  const registry = createCpComponentRegistry();
  const container = document.createElement('div');

  registry.register('craft:editable-table-input', CraftEditableTable);
  (window as any).Cp = {$formElements: registry};
  document.body.appendChild(container);
  const app = createApp(FormRenderer, {
    form,
    bindingScope: 'settings',
    values: {settings},
    errors: {},
  });

  mountedApps.push(app);
  app.mount(container);

  return container;
}

const form = {
  elements: [
    field('columns', {
      columns: [
        {key: 'heading', label: 'Column Heading', type: 'text'},
        {key: 'type', label: 'Type', type: 'text'},
      ],
      keyed: true,
      definesColumns: true,
      sourceName: 'columns',
    }),
    field('defaults', {
      columns: [{key: 'placeholder', label: 'Placeholder', type: 'text'}],
      includeRowId: true,
      columnsFrom: 'columns',
    }),
  ],
} satisfies CraftCms.Cms.Cp.Forms.Data.FormPayload;

function field(
  name: string,
  props: Record<string, CraftCms.Cms.Cp.Forms.Data.JsonValue>
) {
  return {
    type: 'craft:field',
    children: [{type: 'craft:editable-table-input', name, props}],
  };
}
