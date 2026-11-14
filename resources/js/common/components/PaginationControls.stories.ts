import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {computed, ref} from 'vue';
import PaginationControls from './PaginationControls.vue';

interface PaginationControlsStoryArgs {
  total: number;
  disabled?: boolean;
  enableAdjustPageSize?: boolean;
  pageSizeOptions?: number[];
  controlsOnly?: boolean;
  itemLabel?: string;
  itemsLabel?: string;
  pageSizeLabel?: string;
}

const meta = {
  title: 'CP/PaginationControls',
  component: PaginationControls,
  args: {
    total: 573,
    disabled: false,
    enableAdjustPageSize: false,
    controlsOnly: false,
  },
  parameters: {
    docs: {
      description: {
        component:
          'Previous and next buttons, a page number input, the “1 – 50 of ' +
          '573 items” count and, optionally, a page size menu. It holds no ' +
          'state of its own. Pass the current `pageIndex` (counted from 0), ' +
          '`pageSize`, `pageCount` and the count’s `from`, `to` and `total`, ' +
          'and update them when it emits `page-change` or `page-size-change`.\n\n' +
          '`AdminTable` already includes it. Use it directly for a list that ' +
          'isn’t an `AdminTable`.\n\n' +
          "Plugins import it from the control panel: `import {PaginationControls} from '@craftcms/cp';`",
      },
    },
  },
} satisfies Meta<PaginationControlsStoryArgs>;

export default meta;

type Story = StoryObj<PaginationControlsStoryArgs>;

function render(args: PaginationControlsStoryArgs) {
  return {
    components: {PaginationControls},
    setup() {
      const pageIndex = ref(0);
      const pageSize = ref(args.pageSizeOptions?.[0] ?? 50);
      const pageCount = computed(() =>
        Math.max(1, Math.ceil(args.total / pageSize.value))
      );
      const from = computed(() =>
        args.total ? pageIndex.value * pageSize.value + 1 : 0
      );
      const to = computed(() =>
        Math.min(args.total, (pageIndex.value + 1) * pageSize.value)
      );

      function onPageSizeChange(size: number) {
        pageSize.value = size;
        pageIndex.value = 0;
      }

      return {
        args,
        pageIndex,
        pageSize,
        pageCount,
        from,
        to,
        onPageSizeChange,
      };
    },
    template: `
      <PaginationControls
        v-bind="args"
        :page-index="pageIndex"
        :page-size="pageSize"
        :page-count="pageCount"
        :from="from"
        :to="to"
        @page-change="pageIndex = $event"
        @page-size-change="onPageSizeChange"
      />
    `,
  };
}

export const Default: Story = {
  render,
};

/** `enableAdjustPageSize` adds a menu of `pageSizeOptions`. */
export const AdjustablePageSize: Story = {
  args: {
    enableAdjustPageSize: true,
    pageSizeOptions: [25, 50, 100],
  },
  render,
};

/** The count and page size menu can name what’s being listed. */
export const CustomLabels: Story = {
  args: {
    total: 1284,
    enableAdjustPageSize: true,
    itemLabel: 'order',
    itemsLabel: 'orders',
    pageSizeLabel: 'Orders per page',
  },
  render,
};

/** `controlsOnly` leaves out the count and page size menu. */
export const ControlsOnly: Story = {
  args: {controlsOnly: true},
  render,
};
