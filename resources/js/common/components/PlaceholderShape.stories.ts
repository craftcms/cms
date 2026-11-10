import type {Meta, StoryObj} from '@storybook/vue3-vite';
import PlaceholderShape from './PlaceholderShape.vue';

const meta = {
  title: 'CP/PlaceholderShape',
  component: PlaceholderShape,
  parameters: {
    docs: {
      description: {
        component:
          'A pulsing block that holds the place of content still loading. ' +
          'It’s one line of text tall and fills its container’s width; size ' +
          'it with classes or styles to match what it stands in for. Screen ' +
          'readers skip it, so mark the region it sits in with ' +
          '`aria-busy="true"` while it loads.\n\n' +
          "```ts\nimport {PlaceholderShape} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof PlaceholderShape>;

export default meta;
type Story = StoryObj<typeof meta>;

/** One line of text. */
export const Default: Story = {
  render: () => ({
    components: {PlaceholderShape},
    template: '<PlaceholderShape />',
  }),
};

/** Shapes sized to stand in for a card while its data loads. */
export const LoadingCard: Story = {
  render: () => ({
    components: {PlaceholderShape},
    template: `
      <div
        aria-busy="true"
        style="display: flex; gap: var(--c-spacing-md); max-inline-size: 24rem"
      >
        <PlaceholderShape style="inline-size: 3rem; block-size: 3rem; flex: none" />
        <div style="display: grid; gap: var(--c-spacing-sm); flex: 1">
          <PlaceholderShape style="inline-size: 60%" />
          <PlaceholderShape />
          <PlaceholderShape style="inline-size: 80%" />
        </div>
      </div>
    `,
  }),
};
