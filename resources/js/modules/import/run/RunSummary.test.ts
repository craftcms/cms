import {createApp, h, nextTick, shallowRef} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {RunState} from './run-state';
import RunSummary from './RunSummary.vue';

beforeEach(() => {
  vi.useFakeTimers();
  vi.setSystemTime(new Date('2026-11-23T12:00:10Z'));
});

afterEach(() => {
  vi.useRealTimers();
  document.body.innerHTML = '';
});

it('counts a run’s duration up while it’s going, and stops once it finishes', async () => {
  const run = shallowRef<RunState>({
    id: 'run-1',
    url: '/admin/import/activity/run-1',
    status: 'running',
    progress: 40,
    trigger: 'cp',
    user: 'admin',
    dateCreated: '2026-11-23T12:00:00Z',
    started: {label: 'Today at 12:00 PM', title: ''},
    dateStarted: '2026-11-23T12:00:00Z',
    dateFinished: null,
    steps: {},
  });
  const root = document.createElement('div');
  document.body.append(root);
  const app = createApp({render: () => h(RunSummary, {run: run.value})});
  app.mount(root);
  const duration = () => root.querySelectorAll('dd')[3]!.textContent!.trim();

  expect(duration()).toBe('10s');

  await vi.advanceTimersByTimeAsync(5000);
  expect(duration()).toBe('15s');

  run.value = {
    ...run.value,
    status: 'successful',
    dateFinished: '2026-11-23T12:00:16Z',
  };
  await nextTick();
  await vi.advanceTimersByTimeAsync(5000);
  expect(duration()).toBe('16s');

  app.unmount();
});
