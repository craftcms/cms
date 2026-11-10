import type {Meta, StoryObj} from '@storybook/vue3-vite';
import CpContainer from './CpContainer.vue';

const meta = {
  title: 'CP/CpContainer',
  component: CpContainer,
  parameters: {
    docs: {
      description: {
        component:
          'Pads page content in from the edges by the control panel’s ' +
          'standard gutter, `--cp-container-padding`, so it lines up with ' +
          'the page header and the rest of the CP. Each direct child sits in ' +
          'the padded column. Put content in the `full` slot, or give a ' +
          'direct child the `cp-container__full` class, to run it edge to ' +
          'edge, such as a flush table or a divider.\n\n' +
          "```ts\nimport {CpContainer} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof CpContainer>;

export default meta;
type Story = StoryObj<typeof meta>;

const frame =
  'border: 1px dashed var(--c-color-neutral-border-quiet); padding-block: var(--c-spacing-lg);';

/** Content padded in from both edges. */
export const Default: Story = {
  render: () => ({
    components: {CpContainer},
    setup: () => ({frame}),
    template: `
      <CpContainer :style="frame">
        <h2>Webhooks</h2>
        <p>
          Craft sends a POST request to each webhook when an entry is saved,
          deleted or restored.
        </p>
      </CpContainer>
    `,
  }),
};

/** The `full` slot runs past the padding to both edges. */
export const FullWidthSlot: Story = {
  render: () => ({
    components: {CpContainer},
    setup: () => ({frame}),
    template: `
      <CpContainer :style="frame">
        <h2>Recent deliveries</h2>
        <p>The last 50 requests sent to this webhook.</p>
        <template #full>
          <hr style="margin: 0" />
        </template>
      </CpContainer>
    `,
  }),
};

/**
 * A direct child with the `cp-container__full` class also spans the full
 * width, which keeps it in place among the padded children.
 */
export const FullWidthChild: Story = {
  render: () => ({
    components: {CpContainer},
    setup: () => ({frame}),
    template: `
      <CpContainer :style="frame">
        <p>Deliveries that failed are retried up to three times.</p>
        <craft-callout class="cp-container__full" variant="warning">
          Two deliveries failed in the last hour.
        </craft-callout>
        <p>Retries happen one, five and thirty minutes after a failure.</p>
      </CpContainer>
    `,
  }),
};
