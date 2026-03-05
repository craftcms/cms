import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import '../../components/chip/chip';

const meta: Meta = {
  title: 'Tokens/Colorable',
  parameters: {
    layout: 'centerd',
  },
};

export default meta;
type Story = StoryObj;

const Color = {
  Red: 'red',
  Orange: 'orange',
  Amber: 'amber',
  Yellow: 'yellow',
  Lime: 'lime',
  Green: 'green',
  Emerald: 'emerald',
  Teal: 'teal',
  Cyan: 'cyan',
  Sky: 'sky',
  Blue: 'blue',
  Indigo: 'indigo',
  Violet: 'violet',
  Purple: 'purple',
  Fuchsia: 'fuchsia',
  Pink: 'pink',
  Rose: 'rose',
  White: 'white',
  Gray: 'gray',
  Black: 'black',
};

export const Default: Story = {
  render: () => html`
    <div class="stage">
      <div
        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem"
      >
        ${Object.entries(Color).map(
          ([name, value]) =>
            html`<craft-chip data-color="${value}"> ${name} </craft-chip>`
        )}
      </div>
    </div>
  `,
};
