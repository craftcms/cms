import {http} from '@inertiajs/vue3';
import {createApp, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
import WorkflowEditLock from './WorkflowEditLock.vue';

vi.mock('@/modules/messages/useMessages', () => ({
  useMessages: () => ({
    showMessage: vi.fn(),
    notice: vi.fn(),
    success: vi.fn(),
    error: vi.fn(),
  }),
}));

const requestSpy = vi.spyOn(http.getClient(), 'request');

let app: ReturnType<typeof createApp> | undefined;
let container: HTMLElement;

beforeEach(() => {
  requestSpy.mockReset();
  requestSpy.mockResolvedValue({
    status: 200,
    headers: {},
    data: JSON.stringify({
      workflowReview: {status: 'withdrawn'},
      editorActions: {},
    }),
  });
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
});

afterEach(() => {
  app?.unmount();
  container.remove();
  vi.unstubAllGlobals();
});

function mount(releaseStatus: 'scheduled' | 'unscheduled', blockers: string[]) {
  const startEditing = vi.fn<() => void>();
  container = document.createElement('div');
  document.body.append(container);
  app = createApp(WorkflowEditLock, {
    payload: {
      elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
      canonicalId: 12,
      draftId: 7,
      siteId: 1,
      release: {id: 3, name: 'Spring launch', status: releaseStatus, blockers},
      workflow: {
        current: {status: 'approved', runId: 41, isReviewer: false},
        draftReviews: [],
      },
    } as unknown as ElementEditPayload,
    updatePayload: vi.fn(),
    onStartEditing: startEditing,
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);

  return startEditing;
}

const button = (label: string): HTMLElement =>
  [...container.querySelectorAll<HTMLElement>('craft-button')].find(
    (element) => element.textContent?.trim() === label
  )!;
const dialog = () =>
  container.querySelector<HTMLElement & {open?: boolean}>('craft-dialog')!;

it('asks before reopening a draft that’s ready for its scheduled release, and leaves it locked if told not to', async () => {
  const startEditing = mount('scheduled', []);

  button('Start editing').click();
  await nextTick();

  expect(dialog().open).toBe(true);
  expect(dialog().textContent).toContain('“Spring launch”');

  button('Cancel').click();
  await nextTick();

  expect(dialog().open).toBe(false);
  expect(requestSpy).not.toHaveBeenCalled();
  expect(startEditing).not.toHaveBeenCalled();
});

it('reopens the draft once told to edit anyway', async () => {
  const startEditing = mount('scheduled', []);

  button('Start editing').click();
  await nextTick();
  button('Edit anyway').click();

  await vi.waitFor(() => expect(startEditing).toHaveBeenCalledOnce());
  expect(requestSpy).toHaveBeenCalledOnce();
});

it.each([
  ['an unscheduled release', 'unscheduled', []],
  [
    'a scheduled release it’s already blocking',
    'scheduled',
    ['Missing alt text'],
  ],
] as const)('doesn’t ask about a draft in %s', async (_, status, blockers) => {
  const startEditing = mount(status, [...blockers]);

  button('Start editing').click();

  await vi.waitFor(() => expect(startEditing).toHaveBeenCalledOnce());
  expect(dialog().open).toBe(false);
});
