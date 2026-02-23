import type {Meta, StoryObj} from '@storybook/vue3-vite';

import TransitionFade from './TransitionFade.vue';

const meta = {
  title: 'Components/TransitionFade',
  component: TransitionFade,
  tags: ['autodocs'],
} satisfies Meta<typeof TransitionFade>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: () => ({
    components: {TransitionFade},
    data() {
      return {visible: true};
    },
    template: `
      <div>
        <craft-button type="button" @click="visible = !visible" style="margin-bottom: 1rem;">
          Toggle
        </craft-button>
        <TransitionFade>
          <p v-if="visible">This content fades in and out.</p>
        </TransitionFade>
      </div>
    `,
  }),
};
