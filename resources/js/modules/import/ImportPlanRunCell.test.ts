import {createApp, h} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import ImportPlanRunCell from './ImportPlanRunCell.vue';

type ImportRow = CraftCms.Cms.Import.Data.ImportPlanIndexData;

function plan(overrides: Partial<ImportRow> = {}): ImportRow {
  return {
    uid: 'plan-uid',
    name: 'Blog',
    handle: 'blog',
    description: null,
    stepCount: 1,
    steps: [{label: 'Entries', source: 'people.csv', enabled: true, url: null}],
    editable: true,
    running: false,
    runningJobUrl: null,
    lastRunFailed: false,
    ...overrides,
  };
}

let app: ReturnType<typeof createApp>;
let container: HTMLElement;
let runs: number;

function mount(row: ImportRow, canRun = true): void {
  app = createApp({
    render: () =>
      h(ImportPlanRunCell, {plan: row, canRun, onRun: () => runs++}),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

function button() {
  return container.querySelector('craft-button');
}

beforeEach(() => {
  runs = 0;
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
  vi.unstubAllGlobals();
});

it('offers to run a plan that isn’t running', () => {
  mount(plan());

  expect(button()!.icon ?? button()!.getAttribute('icon')).toBe('play');
  expect(button()!.getAttribute('aria-label')).toBe('Run “Blog”');

  button()!.dispatchEvent(new Event('click'));

  expect(runs).toBe(1);
});

it('links a running plan’s spinner to its job in the queue manager', () => {
  mount(
    plan({
      running: true,
      runningJobUrl: '/admin/utilities/queue-manager/job-uuid',
    })
  );

  const link = container.querySelector('a')!;

  expect(button()).toBeNull();
  expect(link.getAttribute('href')).toBe(
    '/admin/utilities/queue-manager/job-uuid'
  );
  expect(link.querySelector('craft-spinner')).not.toBeNull();
  expect(link.getAttribute('aria-label')).toBe(
    '“Blog” is running. View it in the queue manager'
  );
});

it('warns that the last run failed, and still runs the plan again', () => {
  mount(plan({lastRunFailed: true}));

  expect(button()!.icon ?? button()!.getAttribute('icon')).toBe(
    'triangle-exclamation'
  );
  expect(button()!.getAttribute('aria-label')).toBe(
    'The last run of “Blog” had errors. Run it again'
  );

  button()!.dispatchEvent(new Event('click'));

  expect(runs).toBe(1);
});

it('can’t run a plan without steps', () => {
  mount(plan({stepCount: 0}));

  expect(button()!.disabled).toBe(true);
});

it('only shows the warning to someone who can’t run plans', () => {
  mount(plan({lastRunFailed: true}), false);

  expect(button()).toBeNull();
  const icon = container.querySelector('craft-icon') as HTMLElement & {
    name?: string;
  };

  expect(icon.name ?? icon.getAttribute('name')).toBe('triangle-exclamation');
});
