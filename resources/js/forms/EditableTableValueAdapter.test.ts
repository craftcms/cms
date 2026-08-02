import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import type CraftEditableTable from '@/modules/editable-table/editable-table.ce';
import EditableTableValueAdapter from './EditableTableValueAdapter.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

it('reads values from the existing editable table controller', async () => {
  const value = ref<
    Array<Record<string, unknown>> | Record<string, Record<string, unknown>>
  >([]);
  const container = document.createElement('div');
  const host = defineComponent({
    setup() {
      return () =>
        h(EditableTableValueAdapter, {
          name: 'settings[rows]',
          tableHtml:
            '<craft-editable-table name="rows"></craft-editable-table>',
          'onUpdate:modelValue': (
            nextValue:
              | Array<Record<string, unknown>>
              | Record<string, Record<string, unknown>>
          ) => {
            value.value = nextValue;
          },
        });
    },
  });
  const app = createApp(host);

  document.body.appendChild(container);
  mountedApps.push(app);
  app.mount(container);
  await nextTick();

  const table = container.querySelector<CraftEditableTable>(
    'craft-editable-table'
  )!;
  table.serialize = () => [{title: 'Updated'}];
  table.dispatchEvent(new Event('addRow', {bubbles: true}));
  await nextTick();

  expect(table.getAttribute('name')).toBe('settings[rows]');
  expect(value.value).toEqual([{title: 'Updated'}]);
});

it('publishes initial columns from the model value', async () => {
  const container = document.createElement('div');
  const listener = vi.fn();
  const app = createApp(EditableTableValueAdapter, {
    name: 'columns',
    sourceName: 'columns',
    definesColumns: true,
    modelValue: {
      title: {heading: 'Title', type: 'text'},
    },
    tableHtml: '<craft-editable-table name="columns"></craft-editable-table>',
  });

  window.addEventListener('craft:editable-table-columns-changed', listener, {
    once: true,
  });
  document.body.appendChild(container);
  mountedApps.push(app);
  app.mount(container);
  await nextTick();

  expect(listener).toHaveBeenCalledOnce();
  expect(listener.mock.calls[0]![0].detail.columns).toEqual({
    title: {heading: 'Title', type: 'text'},
  });
});
