import {createApp, h, nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import RunCallout from './RunCallout.vue';
import type {RunState, StepRunState} from './run-state';

const container = document.createElement('div');
let app: ReturnType<typeof createApp>;

afterEach(() => {
  app.unmount();
  container.remove();
});

function stepRun(overrides: Partial<StepRunState> = {}): StepRunState {
  return {
    status: 'completed',
    progress: 100,
    errors: [],
    errorCount: 0,
    ...overrides,
  };
}

async function mount(run: RunState): Promise<void> {
  document.body.append(container);
  app = createApp({
    render: () => h(RunCallout, {run, stepUids: ['a', 'b', 'c']}),
  });
  app.mount(container);
  await nextTick();
}

function text(): string {
  return container.textContent?.replace(/\s+/g, ' ').trim() ?? '';
}

function link(): string | null {
  return container.querySelector('a')?.getAttribute('href') ?? null;
}

it('says how far a running import has got, and links to its first step', async () => {
  await mount({
    status: 'running',
    progress: 40,
    steps: {b: stepRun({status: 'running', progress: 40}), c: stepRun()},
  });

  expect(text()).toContain('The import is running: 40% done.');
  expect(link()).toBe('#step-run-b');
});

it('counts the items a failed run couldn’t import, and links to the first step that failed', async () => {
  await mount({
    status: 'failed',
    progress: 100,
    steps: {
      a: stepRun(),
      b: stepRun({status: 'failed', errorCount: 2}),
      c: stepRun({status: 'failed', errorCount: 3}),
    },
  });

  expect(text()).toContain('The last run finished with errors.');
  expect(text()).toContain('5 items couldn’t be imported.');
  expect(link()).toBe('#step-run-b');
});

it('says when a failed run stopped before any item failed', async () => {
  await mount({
    status: 'failed',
    progress: 30,
    steps: {a: stepRun({status: 'failed', progress: 30})},
  });

  expect(text()).toContain('It stopped before it finished.');
});
