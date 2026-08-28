import type {Meta, StoryObj} from '@storybook/vue3-vite';
import ElementStatus from './ElementStatus.vue';

interface ElementStatusArgs {
  label?: string;
  value: string | number;
  mode?: 'badge' | 'inline';
  color?: string | {value: string};
}

const meta = {
  title: 'Elements/ElementStatus',
  component: ElementStatus,
  parameters: {
    docs: {
      description: {
        component:
          'An element status — a colored indicator dot with a label. Known ' +
          'statuses (`live`, `pending`, `expired`, `disabled`, `enabled`, …) ' +
          'map to semantic colors automatically; pass `color` for anything ' +
          'custom. The label defaults to the capitalized value.',
      },
    },
  },
} satisfies Meta<ElementStatusArgs>;

export default meta;
type Story = StoryObj<ElementStatusArgs>;

export const Default: Story = {
  args: {value: 'live'},
};

export const KnownStatuses: Story = {
  args: {value: 'live'},
  render: () => ({
    components: {ElementStatus},
    template: `
      <div class="cp:flex cp:gap-4 cp:flex-wrap">
        <ElementStatus value="live" />
        <ElementStatus value="enabled" />
        <ElementStatus value="pending" />
        <ElementStatus value="expired" />
        <ElementStatus value="suspended" />
        <ElementStatus value="disabled" />
      </div>
    `,
  }),
};

/** `mode="badge"` wraps the status in a tinted pill. */
export const Badge: Story = {
  args: {value: 'live'},
  render: () => ({
    components: {ElementStatus},
    template: `
      <div class="cp:flex cp:gap-4 cp:flex-wrap">
        <ElementStatus value="live" mode="badge" />
        <ElementStatus value="pending" mode="badge" />
        <ElementStatus value="disabled" mode="badge" />
      </div>
    `,
  }),
};

/** Custom colors are handy for user-defined statuses. */
export const CustomColor: Story = {
  args: {value: 'archived', label: 'Archived', color: 'purple'},
};
