import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {onBeforeUnmount, ref} from 'vue';
import RunButton from './RunButton.vue';
import RunIndicator from './RunIndicator.vue';
import type {RunStatus} from './run-state';

interface RunIndicatorArgs {
  status: RunStatus;
  progress: number;
  name?: string;
}

const meta = {
  title: 'Import/RunIndicator',
  component: RunIndicator,
  argTypes: {
    status: {
      control: 'inline-radio',
      options: ['pending', 'running', 'successful', 'failed'],
    },
    progress: {control: {type: 'range', min: -1, max: 100, step: 1}},
  },
  args: {status: 'running', progress: 40},
  parameters: {
    docs: {
      description: {
        component:
          'Where an import run, or one of its steps, has got. A grey outline while ' +
          'it waits to run, a spinner while its progress isn’t known yet, a ' +
          'progress ring while it runs, then a check or a warning. It’s the size of a small ' +
          'control with a 2px ring, so it can take the place of `RunButton`. ' +
          'The status is in its tooltip; `name` prefixes it for screen readers.',
      },
    },
  },
} satisfies Meta<RunIndicatorArgs>;

export default meta;
type Story = StoryObj<RunIndicatorArgs>;

export const Default: Story = {};

/** Every status. */
export const States: Story = {
  render: () => ({
    components: {RunIndicator},
    template: `
      <table class="text-sm" style="border-spacing: 1.5rem 0.75rem; border-collapse: separate">
        <tbody>
          <tr>
            <th class="text-start">Pending</th>
            <td><RunIndicator status="pending" :progress="0" /></td>
          </tr>
          <tr>
            <th class="text-start">Running, progress unknown</th>
            <td><RunIndicator status="running" :progress="-1" /></td>
          </tr>
          <tr>
            <th class="text-start">Running, 65%</th>
            <td><RunIndicator status="running" :progress="65" /></td>
          </tr>
          <tr>
            <th class="text-start">Completed</th>
            <td><RunIndicator status="successful" :progress="100" /></td>
          </tr>
          <tr>
            <th class="text-start">Failed</th>
            <td><RunIndicator status="failed" :progress="100" /></td>
          </tr>
        </tbody>
      </table>
    `,
  }),
};

/** `RunButton` runs a plan, then gives way to its indicator, the outline turning into the ring. */
export const WithRunButton: Story = {
  render: () => ({
    components: {RunButton},
    setup() {
      const running = ref(false);
      const status = ref<RunStatus>('pending');
      const progress = ref(-1);
      let timer: ReturnType<typeof setInterval> | undefined;

      function stop(): void {
        clearInterval(timer);
        timer = undefined;
      }

      // waits a moment to start, then counts up to done
      function run(): void {
        running.value = true;
        status.value = 'pending';
        progress.value = -1;
        let ticks = 0;

        timer = setInterval(() => {
          ticks++;

          if (ticks < 4) {
            return;
          }

          status.value = 'running';
          progress.value = Math.min(100, (ticks - 4) * 10);

          if (progress.value === 100) {
            stop();
            running.value = false;
          }
        }, 300);
      }

      onBeforeUnmount(stop);

      return {running, status, progress, run};
    },
    template: `
      <RunButton
        name="Guides"
        :running="running"
        :status="status"
        :progress="progress"
        @run="run"
      />
    `,
  }),
};
