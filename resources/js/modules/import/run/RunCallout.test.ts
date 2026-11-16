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

function stepRun(
  overrides: Partial<StepRunState> = {},
  failed = 0
): StepRunState {
  return {
    label: 'Entries',
    status: 'successful',
    progress: 100,
    total: 10,
    source: {name: 'people.csv', size: 1024},
    counts: {created: 0, updated: 0, unchanged: 0, skipped: 0, failed},
    failures: [],
    ...overrides,
  };
}

function makeRun(
  overrides: Pick<RunState, 'status' | 'progress' | 'steps'>
): RunState {
  return {
    id: 'run',
    trigger: 'cp',
    user: 'admin',
    dateCreated: '2026-11-16T10:00:00+00:00',
    dateStarted: null,
    dateFinished: null,
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
  await mount(
    makeRun({
      status: 'running',
      progress: 40,
      steps: {b: stepRun({status: 'running', progress: 40}), c: stepRun()},
    })
  );

  expect(text()).toContain('The import is running: 40% done.');
  expect(link()).toBe('#step-run-b');
});

it('counts the items a failed run couldn’t import, and links to the first step that failed', async () => {
  await mount(
    makeRun({
      status: 'failed',
      progress: 100,
      steps: {
        a: stepRun(),
        b: stepRun({status: 'failed'}, 2),
        c: stepRun({status: 'failed'}, 3),
      },
    })
  );

  expect(text()).toContain('The last run finished with errors.');
  expect(text()).toContain('5 items couldn’t be imported.');
  expect(link()).toBe('#step-run-b');
});

it('says when a failed run stopped before any item failed', async () => {
  await mount(
    makeRun({
      status: 'failed',
      progress: 30,
      steps: {a: stepRun({status: 'failed', progress: 30})},
    })
  );

  expect(text()).toContain('It stopped before it finished.');
});
