import {createApp, h, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import type {ImportStep} from '@/modules/import/mapping/types';
import type {StepPreview} from './plan-preview';

const fetchStepPreview = vi.hoisted(() => vi.fn());
vi.mock('./plan-preview', async (importOriginal) => ({
  ...(await importOriginal<typeof import('./plan-preview')>()),
  fetchStepPreview,
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
  usePage: () => ({props: {}}),
}));

const {default: PlanPreview} = await import('./PlanPreview.vue');

function preview(total: number, pending: number): StepPreview {
  return {
    error: null,
    total,
    itemCount: total + pending,
    pending,
    counts: {new: total, update: 0, unchanged: 0, skip: 0, problem: 0},
    page: 0,
    pageSize: 10,
    previewable: total,
    previewableBefore: 0,
    matchColumns: [],
    items: Array.from({length: Math.min(total, 10)}, (_, index) => ({
      index,
      result: 'new' as const,
      title: `Guide ${index + 1}`,
      match: {},
      errors: [],
    })),
  };
}

afterEach(() => {
  document.body.innerHTML = '';
  fetchStepPreview.mockReset();
});

it('keeps previewing a step a request at a time until none of its items are left', async () => {
  const step = {uid: 'step-1', type: 'Entries', enabled: true} as ImportStep;
  fetchStepPreview
    .mockResolvedValueOnce(preview(10, 40))
    .mockResolvedValueOnce(preview(30, 20))
    .mockResolvedValueOnce(preview(50, 0));
  const root = document.createElement('div');
  document.body.append(root);
  const app = createApp({
    render: () =>
      h(PlanPreview, {
        steps: [step],
        importerTypes: [{value: 'Entries', label: 'Entries'}],
      }),
  });
  app.mount(root);

  await vi.waitFor(() => expect(fetchStepPreview).toHaveBeenCalledTimes(1));
  await nextTick();
  expect(root.textContent).toContain('Previewing 10 of 50 items…');

  await vi.waitFor(() => expect(fetchStepPreview).toHaveBeenCalledTimes(3));
  await nextTick();
  expect(root.textContent).not.toContain('Previewing');
  expect(fetchStepPreview).toHaveBeenCalledTimes(3);

  app.unmount();
});
