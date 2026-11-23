import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {RunState} from './run-state';

const mocks = vi.hoisted(() => ({
  on: vi.fn(),
  visit: vi.fn(),
  get: vi.fn(),
  showPersistentNotice: vi.fn(),
  showSuccess: vi.fn(),
  showError: vi.fn(),
  registerMessageOutlet: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
  router: {on: mocks.on, visit: mocks.visit},
}));
vi.mock('@craftcms/ui', () => ({
  t: (message: string, params: Record<string, string> = {}) =>
    message.replace(/\{(\w+)\}/g, (_, key) => params[key] ?? `{${key}}`),
  actionClient: {get: mocks.get},
}));
vi.mock('@actions/Import/ImportPlansController', () => ({
  runState: {url: () => '/actions/import/run-state'},
}));
vi.mock('@/modules/messages/messages', () => ({
  showPersistentNotice: mocks.showPersistentNotice,
  showSuccess: mocks.showSuccess,
  showError: mocks.showError,
  registerMessageOutlet: mocks.registerMessageOutlet,
}));

function run(overrides: Partial<RunState> = {}): RunState {
  return {
    id: 'run-1',
    url: 'http://localhost/admin/import/activity/run-1',
    status: 'running',
    progress: 40,
    trigger: 'cp',
    user: 'admin',
    dateCreated: '2026-11-23T10:00:00Z',
    started: {label: 'Today at 10:00 AM', title: ''},
    dateStarted: null,
    dateFinished: null,
    steps: {},
    ...overrides,
  };
}

/** Sets the notifier up afresh, as a page load does. */
async function setUp() {
  vi.resetModules();
  const {setUpImportRunNotifications} = await import('./run-notifications');
  setUpImportRunNotifications();

  return {
    /** An Inertia page arriving, with the imports it reports running. */
    visit: (running: unknown[]) => {
      const navigate = mocks.on.mock.calls.find(
        ([name]) => name === 'navigate'
      )![1];
      navigate({detail: {page: {props: {runningImports: running}}}});
    },
    outlet: mocks.registerMessageOutlet.mock.calls[0]![1] as (
      message: unknown
    ) => void,
  };
}

const closeNotice = vi.fn();
const guides = {handle: 'guides', name: 'Guides'};

beforeEach(() => {
  vi.useFakeTimers();
  vi.clearAllMocks();
  sessionStorage.clear();
  history.replaceState(null, '', '/admin/dashboard');
  mocks.showPersistentNotice.mockReturnValue(closeNotice);
});

afterEach(() => {
  vi.useRealTimers();
});

it('shows a running import’s progress, then how it went once it finishes', async () => {
  const {visit} = await setUp();
  mocks.get.mockResolvedValue({
    data: {runState: run({status: 'successful', progress: 100})},
  });

  visit([{...guides, run: run()}]);

  expect(mocks.showPersistentNotice).toHaveBeenCalledOnce();
  const [message, details] = mocks.showPersistentNotice.mock.calls[0]!;
  expect(message).toBe('Importing “Guides” data');
  expect(details.querySelector('craft-progress-bar').progress).toBe(40);
  expect(details.textContent).toContain('View run');

  await vi.advanceTimersByTimeAsync(2000);

  expect(closeNotice).toHaveBeenCalledOnce();
  expect(mocks.showSuccess).toHaveBeenCalledWith(
    '“Guides” finished importing.',
    expect.anything()
  );
});

it('leaves a run to its own page', async () => {
  const {visit} = await setUp();
  history.replaceState(null, '', '/admin/import/activity/run-1');

  visit([{...guides, run: run()}]);

  expect(mocks.showPersistentNotice).not.toHaveBeenCalled();
});

it('follows a run started in the background from its “Import started.” message', async () => {
  const {outlet} = await setUp();
  mocks.get.mockResolvedValue({
    data: {runState: run({status: 'pending', progress: 0})},
  });

  outlet({
    type: 'success',
    message: 'Import started.',
    settings: {importPlan: guides},
  });
  await vi.advanceTimersByTimeAsync(0);

  expect(mocks.get).toHaveBeenCalledWith('/actions/import/run-state', {
    params: {handle: 'guides'},
  });
  expect(mocks.showPersistentNotice).toHaveBeenCalledOnce();
  expect(mocks.showPersistentNotice.mock.calls[0]![1].textContent).toContain(
    'Waiting to run'
  );
});

it('doesn’t show a run again once it’s been dismissed', async () => {
  const {visit} = await setUp();
  visit([{...guides, run: run()}]);

  const onClose = mocks.showPersistentNotice.mock.calls[0]![2] as () => void;
  onClose();
  visit([{...guides, run: run()}]);

  expect(mocks.showPersistentNotice).toHaveBeenCalledOnce();
});
