import type {Meta, StoryObj} from '@storybook/web-components-vite';
import './info-icon';

const meta: Meta = {
  title: 'Components/Info Icon',
  tags: ['autodocs'],
  args: {},
  render: (args) => {
    return `
    <craft-info-icon>
      This is the content for the tooltip
    </craft-info-icon>`;
  },
};

export default meta;

type Story = StoryObj<any>;

export const Default: Story = {
  args: {
    label: 'More Info',
    icon: 'circle-info',
  },
};
