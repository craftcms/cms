import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftProgress from './progress.js';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './progress.js';

const {events, args, argTypes, template} =
  getStorybookHelpers('craft-progress');

const meta: Meta<CraftProgress> = {
  title: 'Components/Progress',
  component: 'craft-progress',
  args,
  argTypes,
  render: (args) => template(args),
  parameters: {
    actions: {
      handles: events,
    },
  },
};

export default meta;
type Story = StoryObj<CraftProgress & typeof args>;

export const ProgressStates: Story = {
  render: () => html`
    <div style="display: flex; gap: 16px; align-items: center;">
      <div style="text-align: center;">
        <craft-progress progress="0"></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">0%</div>
      </div>
      <div style="text-align: center;">
        <craft-progress progress="25"></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">25%</div>
      </div>
      <div style="text-align: center;">
        <craft-progress progress="50"></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">50%</div>
      </div>
      <div style="text-align: center;">
        <craft-progress progress="75"></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">75%</div>
      </div>
      <div style="text-align: center;">
        <craft-progress progress="100"></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">100%</div>
      </div>
      <div style="text-align: center;">
        <craft-progress failed></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">Failed</div>
      </div>
    </div>
  `,
};

export const AnimatedProgress: Story = {
  render: () => {
    let timeout: NodeJS.Timeout | null = null;

    function replay() {
      let progress = 0;
      if (timeout) {
        clearTimeout(timeout);
      }
      const updateProgress = () => {
        const el = document.querySelector(
          '#animated-progress'
        ) as CraftProgress;
        if (el && progress <= 100) {
          el.progress = progress;
          progress += 10;
          if (progress <= 100) {
            timeout = setTimeout(updateProgress, 500);
          }
        }
      };
      updateProgress();
    }

    replay();

    return html`
      <div style="display: grid; gap: 1em; justify-items: center;">
        <craft-progress id="animated-progress" progress="0"></craft-progress>
        <div>
          <button type="button" @click="${replay}">Replay</button>
        </div>
      </div>
    `;
  },
};

export const CustomColors: Story = {
  args: {
    progress: 60,
    color: '#22c55e',
    'bg-color': '#dcfce7',
  },
};
