import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftProgressBar from './progress-bar.js';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './progress-bar.js';

const {events, args, argTypes, template} =
  getStorybookHelpers('craft-progress-bar');

const meta: Meta<CraftProgressBar> = {
  title: 'Components/Progress Bar',
  component: 'craft-progress-bar',
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
type Story = StoryObj<CraftProgressBar & typeof args>;

/**
 * The default progress bar showing various progress states.
 */
export const ProgressStates: Story = {
  render: () => html`
    <div style="display: grid; gap: 24px; max-width: 400px;">
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">0%</div>
        <craft-progress-bar progress="0"></craft-progress-bar>
      </div>
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">25%</div>
        <craft-progress-bar progress="25"></craft-progress-bar>
      </div>
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">50%</div>
        <craft-progress-bar progress="50"></craft-progress-bar>
      </div>
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">75%</div>
        <craft-progress-bar progress="75"></craft-progress-bar>
      </div>
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">100%</div>
        <craft-progress-bar progress="100"></craft-progress-bar>
      </div>
    </div>
  `,
};

/**
 * The pending state shows an animated striped pattern to indicate
 * indeterminate progress (when the total is unknown).
 */
export const Pending: Story = {
  args: {
    pending: true,
    label: 'Loading...',
  },
};

/**
 * Use `show-status` to display the progress count or percentage below the bar.
 */
export const WithStatus: Story = {
  render: () => html`
    <div style="display: grid; gap: 24px; max-width: 400px;">
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">
          With percentage status
        </div>
        <craft-progress-bar progress="60" show-status></craft-progress-bar>
      </div>
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">
          With item count status
        </div>
        <craft-progress-bar
          total="150"
          processed="45"
          show-status
        ></craft-progress-bar>
      </div>
    </div>
  `,
};

/**
 * Using `total` and `processed` properties automatically calculates
 * the progress percentage.
 */
export const ItemCountMode: Story = {
  args: {
    total: 100,
    processed: 35,
    'show-status': true,
  },
};

/**
 * Enable `animate` to smoothly transition between progress values.
 */
export const AnimatedProgress: Story = {
  render: () => {
    let timeout: ReturnType<typeof setTimeout> | null = null;

    function replay() {
      let processed = 0;
      const total = 100;
      if (timeout) {
        clearTimeout(timeout);
      }
      const el = document.querySelector(
        '#animated-progress-bar'
      ) as CraftProgressBar;
      if (el) {
        el.processed = 0;
        el.pending = false;
      }

      const updateProgress = () => {
        const el = document.querySelector(
          '#animated-progress-bar'
        ) as CraftProgressBar;
        if (el && processed <= total) {
          el.processed = processed;
          processed += 10;
          if (processed <= total) {
            timeout = setTimeout(updateProgress, 500);
          }
        }
      };
      updateProgress();
    }

    setTimeout(replay, 100);

    return html`
      <div style="max-width: 400px;">
        <craft-progress-bar
          id="animated-progress-bar"
          total="100"
          processed="0"
          show-status
          smooth
        ></craft-progress-bar>
        <div style="margin-top: 16px;">
          <button type="button" @click="${replay}">Replay</button>
        </div>
      </div>
    `;
  },
};

/**
 * The progress bar can be customized using CSS custom properties.
 */
export const CustomStyles: Story = {
  render: () => html`
    <div style="display: grid; gap: 24px; max-width: 400px;">
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">
          Success color (green)
        </div>
        <craft-progress-bar
          progress="75"
          style="--c-progress-bar-fill-color: #22c55e;"
        ></craft-progress-bar>
      </div>
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">
          Warning color (amber)
        </div>
        <craft-progress-bar
          progress="45"
          style="--c-progress-bar-fill-color: #f59e0b;"
        ></craft-progress-bar>
      </div>
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">
          Danger color (red)
        </div>
        <craft-progress-bar
          progress="25"
          style="--c-progress-bar-fill-color: #ef4444;"
        ></craft-progress-bar>
      </div>
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">Taller bar</div>
        <craft-progress-bar
          progress="60"
          style="--c-progress-bar-height: 1rem;"
        ></craft-progress-bar>
      </div>
      <div>
        <div style="font-size: 12px; margin-bottom: 4px;">Rounded corners</div>
        <craft-progress-bar
          progress="60"
          style="--c-progress-bar-height: 1rem; --c-progress-bar-radius: 0.5rem;"
        ></craft-progress-bar>
      </div>
    </div>
  `,
};

/**
 * Demonstrates the complete event fired when progress reaches 100%.
 */
export const CompleteEvent: Story = {
  render: () => {
    let timeout: ReturnType<typeof setTimeout> | null = null;

    function start() {
      let processed = 0;
      const total = 50;
      if (timeout) {
        clearTimeout(timeout);
      }
      const el = document.querySelector(
        '#complete-demo-bar'
      ) as CraftProgressBar;
      if (el) {
        el.processed = 0;
      }

      const updateProgress = () => {
        const el = document.querySelector(
          '#complete-demo-bar'
        ) as CraftProgressBar;
        if (el && processed <= total) {
          el.processed = processed;
          processed += 10;
          if (processed <= total) {
            timeout = setTimeout(updateProgress, 300);
          }
        }
      };
      updateProgress();
    }

    return html`
      <div style="max-width: 400px;">
        <craft-progress-bar
          id="complete-demo-bar"
          total="50"
          processed="0"
          show-status
          smooth
          @complete=${() => {
            const output = document.querySelector('#complete-output');
            if (output) {
              output.textContent = 'Complete event fired!';
            }
          }}
        ></craft-progress-bar>
        <div
          style="margin-top: 16px; display: flex; gap: 8px; align-items: center;"
        >
          <button type="button" @click="${start}">Start Progress</button>
          <span
            id="complete-output"
            style="font-size: 14px; color: #22c55e;"
          ></span>
        </div>
      </div>
    `;
  },
};

/**
 * Shows how to use the reset() method to return the bar to its initial state.
 */
export const ResetMethod: Story = {
  render: () => {
    function setProgress(value: number) {
      const el = document.querySelector('#reset-demo-bar') as CraftProgressBar;
      if (el) {
        el.processed = value;
      }
    }

    function reset() {
      const el = document.querySelector('#reset-demo-bar') as CraftProgressBar;
      if (el) {
        el.reset();
      }
    }

    return html`
      <div style="max-width: 400px;">
        <craft-progress-bar
          id="reset-demo-bar"
          total="100"
          processed="0"
          show-status
          smooth
          pending
        ></craft-progress-bar>
        <div style="margin-top: 16px; display: flex; gap: 8px;">
          <button type="button" @click="${() => setProgress(25)}">25%</button>
          <button type="button" @click="${() => setProgress(50)}">50%</button>
          <button type="button" @click="${() => setProgress(75)}">75%</button>
          <button type="button" @click="${() => setProgress(100)}">100%</button>
          <button type="button" @click="${reset}">Reset</button>
        </div>
      </div>
    `;
  },
};

/**
 * Interactive playground with all properties.
 */
export const Playground: Story = {
  args: {
    progress: 50,
    'show-status': true,
    smooth: true,
    pending: false,
    label: 'File upload progress',
  },
};
