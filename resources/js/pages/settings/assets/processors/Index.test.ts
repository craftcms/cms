import {FlexRender} from '@tanstack/vue-table';
import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import Index from './Index.vue';

vi.mock('@actions/Settings/AssetProcessorsController', () => ({
  create: () => ({url: '/settings/assets/processors/new'}),
  destroy: ({handle}: {handle: string}) => ({
    url: `/settings/assets/processors/${handle}`,
  }),
  edit: ({handle}: {handle: string}) => ({
    url: `/settings/assets/processors/${handle}`,
  }),
}));

vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: defineComponent({render: () => h('div')}),
}));

vi.mock('@/modules/admin-table/components/DeleteButton.vue', () => ({
  default: defineComponent({render: () => h('button')}),
}));

vi.mock('@/modules/admin-table/components/AdminTable.vue', () => ({
  default: defineComponent({
    props: ['table'],
    render() {
      const nameCell = this.table
        .getRowModel()
        .rows[0].getVisibleCells()
        .find((cell: any) => cell.column.id === 'name');

      return h(FlexRender, {
        render: nameCell.column.columnDef.cell,
        props: nameCell.getContext(),
      });
    },
  }),
}));

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('links the default Craft processor to its edit screen', async () => {
  app = createApp(Index, {
    processors: [
      {
        uid: 'craft-uid',
        name: 'Craft',
        handle: 'craft',
        driver: 'Craft',
        isDefault: true,
        canDelete: false,
      },
    ],
    readOnly: false,
  });
  app.mount(container);
  await nextTick();

  const link = container.querySelector('a');

  expect(link?.textContent).toBe('Craft (Default)');
  expect(link?.getAttribute('href')).toBe('/settings/assets/processors/craft');
});
