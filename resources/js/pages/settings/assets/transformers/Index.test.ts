import {FlexRender} from '@tanstack/vue-table';
import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import Index from './Index.vue';

vi.mock('@actions/Settings/AssetTransformersController', () => ({
  create: () => ({url: '/settings/assets/transformers/new'}),
  destroy: ({handle}: {handle: string}) => ({
    url: `/settings/assets/transformers/${handle}`,
  }),
  edit: ({handle}: {handle: string}) => ({
    url: `/settings/assets/transformers/${handle}`,
  }),
}));

vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: defineComponent({render: () => h('div')}),
}));

vi.mock('@/modules/admin-table/components/DeleteButton.vue', () => ({
  default: defineComponent({
    inheritAttrs: false,
    props: ['disabled'],
    render() {
      return h('button', {
        'aria-disabled': this.disabled ? 'true' : undefined,
        ...this.$attrs,
      });
    },
  }),
}));

vi.mock('@/modules/admin-table/components/AdminTable.vue', () => ({
  default: defineComponent({
    props: ['table'],
    render() {
      return this.table
        .getRowModel()
        .rows[0].getVisibleCells()
        .filter((cell: any) => ['name', 'actions'].includes(cell.column.id))
        .map((cell: any) =>
          h(FlexRender, {
            render: cell.column.columnDef.cell,
            props: cell.getContext(),
          })
        );
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

it('links the default Craft transformer to its edit screen', async () => {
  app = createApp(Index, {
    transformers: [
      {
        uid: 'craft-uid',
        name: 'Craft',
        handle: 'craft',
        driver: 'Craft',
        isDefault: true,
        deleteDisabledReason: 'The Craft Asset Transformer cannot be deleted.',
      },
    ],
    readOnly: false,
  });
  app.mount(container);
  await nextTick();

  const link = container.querySelector('a');

  expect(link?.textContent).toBe('Craft (Default)');
  expect(link?.getAttribute('href')).toBe(
    '/settings/assets/transformers/craft'
  );
  expect(container.querySelector('button')?.disabled).toBe(false);
  expect(container.querySelector('button')?.getAttribute('aria-disabled')).toBe(
    'true'
  );
  expect(
    container
      .querySelector('#delete-asset-transformer-craft-uid')
      ?.hasAttribute('tabindex')
  ).toBe(false);
  expect(container.querySelector('craft-tooltip')?.textContent).toBe(
    'The Craft Asset Transformer cannot be deleted.'
  );
});
