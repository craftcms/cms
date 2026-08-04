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

export const Indeterminate: Story = {
  args: {
    progress: -1,
    label: 'Loading...',
  },
};

export const CustomSize: Story = {
  render: () => html`
    <div style="display: flex; gap: 24px; align-items: center;">
      <div style="text-align: center;">
        <craft-progress
          progress="60"
          style="--craft-progress-size: 16px; --craft-progress-stroke-width: 2px;"
        ></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">Small (16px)</div>
      </div>
      <div style="text-align: center;">
        <craft-progress progress="60"></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">Default (18px)</div>
      </div>
      <div style="text-align: center;">
        <craft-progress
          progress="60"
          style="--craft-progress-size: 32px; --craft-progress-stroke-width: 4px;"
        ></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">Medium (32px)</div>
      </div>
      <div style="text-align: center;">
        <craft-progress
          progress="60"
          style="--craft-progress-size: 64px; --craft-progress-stroke-width: 6px;"
        ></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">Large (64px)</div>
      </div>
    </div>
  `,
};

export const IndeterminateSizes: Story = {
  render: () => html`
    <div style="display: flex; gap: 24px; align-items: center;">
      <div style="text-align: center;">
        <craft-progress
          progress="-1"
          style="--craft-progress-size: 16px; --craft-progress-stroke-width: 2px;"
        ></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">Small</div>
      </div>
      <div style="text-align: center;">
        <craft-progress progress="-1"></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">Default</div>
      </div>
      <div style="text-align: center;">
        <craft-progress
          progress="-1"
          style="--craft-progress-size: 32px; --craft-progress-stroke-width: 4px;"
        ></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">Medium</div>
      </div>
      <div style="text-align: center;">
        <craft-progress
          progress="-1"
          style="--craft-progress-size: 64px; --craft-progress-stroke-width: 6px;"
        ></craft-progress>
        <div style="font-size: 12px; margin-top: 4px;">Large</div>
      </div>
    </div>
  `,
};

export const CompleteAnimation: Story = {
  render: () => {
    const handleClick = () => {
      const el = document.querySelector(
        '#complete-demo'
      ) as CraftProgress | null;
      if (el) {
        el.progress = 75;
        el.complete().then(() => {
          console.log('Complete event fired!');
        });
      }
    };

    return html`
      <div style="display: flex; gap: 16px; align-items: center;">
        <craft-progress
          id="complete-demo"
          progress="75"
          @complete=${() => console.log('Complete event received!')}
        ></craft-progress>
        <button @click=${handleClick}>Trigger Complete Animation</button>
      </div>
    `;
  },
};

export const AutoComplete: Story = {
  render: () => {
    let timeout: ReturnType<typeof setTimeout> | null = null;

    function replay() {
      let progress = 0;
      if (timeout) {
        clearTimeout(timeout);
      }
      const el = document.querySelector(
        '#auto-complete-demo'
      ) as CraftProgress | null;
      if (el) {
        const canvas = el.shadowRoot?.querySelector('canvas');
        if (canvas) {
          canvas.style.transition = '';
          canvas.style.opacity = '1';
        }
      }
      const updateProgress = () => {
        const el = document.querySelector(
          '#auto-complete-demo'
        ) as CraftProgress | null;
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
        <craft-progress
          id="auto-complete-demo"
          progress="0"
          auto-complete
          @complete=${() => console.log('Auto-complete triggered!')}
        ></craft-progress>
        <div>
          <button type="button" @click="${replay}">Replay</button>
        </div>
        <p style="font-size: 12px; color: #666;">
          With <code>auto-complete</code>, the component automatically runs the
          complete animation when progress reaches 100.
        </p>
      </div>
    `;
  },
};
