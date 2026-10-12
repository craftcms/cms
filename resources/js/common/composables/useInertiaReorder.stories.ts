import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {defineComponent, onBeforeUnmount, ref} from 'vue';
import {router, usePage} from '@inertiajs/vue3';
import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
import {useCraftTable} from '@/common/table/craftTable';
import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
import {useInertiaReorder} from './useInertiaReorder';

interface Category {
  id: number;
  name: string;
  handle: string;
}

const categories: Category[] = [
  'News',
  'Reviews',
  'Tutorials',
  'Interviews',
  'Opinion',
].map((name, i) => ({id: i + 1, name, handle: name.toLowerCase()}));

const columnHelper = createCraftColumnHelper<Category>();
const columns = columnHelper.columns([
  columnHelper.accessor('name', {header: 'Name'}),
  columnHelper.handle('handle'),
]);

/**
 * A reorderable AdminTable whose rows are the `categories` page prop, next to
 * the visit `useInertiaReorder` makes. Stories run on a mocked router, so this
 * applies the optimistic update itself and, with `failSave`, puts the prop back
 * the way Inertia does when the save fails.
 */
const ReorderTable = defineComponent({
  components: {AdminTable},
  props: {
    failSave: {type: Boolean, default: false},
  },
  setup(props) {
    const page = usePage<{categories: Category[]}>();
    page.props.categories = [...categories];

    const lastVisit = ref<{url: unknown; data: unknown} | null>(null);
    const originalVisit = router.visit.bind(router);
    router.visit = ((url, options = {}) => {
      lastVisit.value = {url, data: options.data};
      const previous = page.props.categories;
      Object.assign(page.props, options.optimistic?.(page.props));
      if (props.failSave) {
        setTimeout(() => (page.props.categories = previous), 800);
      }
    }) as typeof router.visit;
    onBeforeUnmount(() => (router.visit = originalVisit));

    const onReorder = useInertiaReorder({
      url: '/admin/categories/reorder',
      prop: 'categories',
    });

    const table = useCraftTable({
      get data() {
        return page.props.categories;
      },
      columns,
      getRowId: (row) => String(row.id),
    });

    return {table, onReorder, lastVisit};
  },
  template: `
    <div class="flex flex-col gap-4">
      <AdminTable :table="table" title="Categories" reorderable @reorder="onReorder" />
      <div class="text-xs">
        <p v-if="!lastVisit">Drag a row, or use its move buttons, to see the visit.</p>
        <template v-else>
          <strong>Visit</strong>
          <pre>POST {{ lastVisit.url }}\n{{ lastVisit.data }}</pre>
        </template>
      </div>
    </div>
  `,
});

const meta = {
  title: 'Composables/useInertiaReorder',
  component: ReorderTable,
  parameters: {
    docs: {
      description: {
        component:
          'Returns an `AdminTable` `reorder` handler. It moves the row in its ' +
          'page prop straight away and posts the new id order to `url`, and ' +
          'Inertia puts the prop back if the save fails. Options: `url`, ' +
          '`prop`, `key` (default `id`) and `param` (default `ids`). See the ' +
          'AdminTable Guide.',
      },
    },
  },
} satisfies Meta<typeof ReorderTable>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {};

/** When the save fails, the moved row goes back where it was. */
export const FailedSave: Story = {
  args: {failSave: true},
};
