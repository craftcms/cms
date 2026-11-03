import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {ImportStep} from '@/modules/import/mapping/types';

const state = vi.hoisted(() => ({
  post: vi.fn(),
  visit: vi.fn(),
  validateStep: vi.fn(),
  details: {} as Record<string, unknown>,
  detailErrors: [] as any[],
  editors: [] as Array<{
    props: any;
    emit: (event: string, value: unknown) => void;
  }>,
  readable: null as null | ((readable: boolean) => void),
}));

vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@craftcms/ui')>()),
  actionClient: {post: state.post},
}));

vi.mock('@inertiajs/vue3', () => ({
  router: {visit: state.visit},
}));

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: () => {},
}));

vi.mock('@/modules/import/steps/step-slideout', () => ({
  validateStep: state.validateStep,
}));

vi.mock('@/modules/ui/UiRenderer.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      props: ['payload', 'errors'],
      setup(props, {expose}) {
        expose({currentValues: () => state.details});

        return () => {
          state.detailErrors = props.errors;

          return h('div', {class: 'details-stub'});
        };
      },
    }),
  };
});

// stands in for the step editor, so a test can report what it would
vi.mock('@/modules/import/steps/StepEditor.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      props: ['step', 'part', 'errors', 'editable'],
      emits: ['update:step', 'update:canMap'],
      setup(props, {emit}) {
        state.editors.push({
          props,
          emit: (event, value) => emit(event as any, value),
        });

        return () =>
          h('div', {class: `editor-stub editor-stub--${props.part}`});
      },
    }),
  };
});

vi.mock('@/modules/import/steps/SourcePreview.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      props: ['step'],
      emits: ['update:readable'],
      setup(_, {emit}) {
        state.readable = (readable) => emit('update:readable', readable);

        return () => h('div', {class: 'preview-stub'});
      },
    }),
  };
});

vi.mock('@/modules/import/ImportPlanReview.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      props: ['steps', 'planUid'],
      setup: () => () => h('div', {class: 'review-stub'}),
    }),
  };
});

const {default: Create} = await import('./Create.vue');

const ENTRIES = 'CraftCms\\Cms\\Entry\\Import\\EntryImporter';
const USERS = 'CraftCms\\Cms\\User\\Import\\UserImporter';

let app: ReturnType<typeof createApp> | null = null;
let container: HTMLElement;

async function mount(
  runUrl: string | null = '/actions/import/run'
): Promise<void> {
  app = createApp({
    render: () =>
      h(Create, {
        title: 'Create a new import plan',
        ui: {nodes: [], values: {}} as never,
        importerTypes: [
          {value: ENTRIES, label: 'Entries'},
          {value: USERS, label: 'Users'},
        ],
        submit: {method: 'post', url: '/actions/import/save'},
        runUrl,
        editUrl: '/admin/import/__handle__',
        indexUrl: '/admin/import',
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  await flush();
}

async function flush(): Promise<void> {
  for (let i = 0; i < 4; i++) {
    await Promise.resolve();
    await nextTick();
  }
}

function section(key: string): HTMLElement {
  return container.querySelector<HTMLElement>(`[data-wizard-key="${key}"]`)!;
}

function openSection(): HTMLElement {
  return container.querySelector<HTMLElement>('.import-wizard-section--open')!;
}

async function clickButton(scope: HTMLElement, label: string): Promise<void> {
  [...scope.querySelectorAll<HTMLElement>('craft-button')]
    .find((button) => button.textContent!.trim() === label)!
    .dispatchEvent(new MouseEvent('click', {bubbles: true}));
  await flush();
}

/** Names the plan. */
async function nameThePlan(): Promise<void> {
  state.details = {name: 'Guides', handle: 'guides', description: ''};
  await clickButton(section('details'), 'Continue');
}

function lastEditor() {
  return state.editors.at(-1)!;
}

/** The latest editor of one part of a step. */
function editor(part: string) {
  return state.editors.filter((e) => e.props.part === part).at(-1)!;
}

/** Names the plan, which starts its first step at the data source. */
async function startAStep(): Promise<void> {
  await nameThePlan();
}

/** Takes a new step through its data source and destination, ending on its fields. */
async function reachTheFields(): Promise<ImportStep> {
  await startAStep();
  const step = {...lastEditor().props.step, source: 'guides.json'};
  lastEditor().emit('update:step', step);
  state.readable!(true);
  await flush();
  await clickButton(openSection(), 'Continue');
  lastEditor().emit('update:canMap', true);
  await flush();
  await clickButton(openSection(), 'Continue');

  return step;
}

/** Takes a new step through all its sections, ending on the review. */
async function finishTheStep(): Promise<ImportStep> {
  const step = await reachTheFields();
  await clickButton(openSection(), 'Continue');
  await clickButton(openSection(), 'Continue');

  return step;
}

beforeEach(() => {
  state.post.mockReset();
  state.visit.mockReset();
  state.validateStep.mockReset().mockResolvedValue(undefined);
  state.editors = [];
  state.details = {};
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
  vi.stubGlobal('Craft', {cp: {displayNotice: vi.fn(), displayError: vi.fn()}});
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app?.unmount();
  app = null;
  container.remove();
  vi.unstubAllGlobals();
});

it('starts by naming the plan, and won’t go on without a name and handle', async () => {
  await mount();

  expect(section('details').classList).toContain('import-wizard-section--open');
  expect(section('review').classList).toContain(
    'import-wizard-section--locked'
  );

  await clickButton(section('details'), 'Continue');

  expect(state.detailErrors.map((error) => error.path[0])).toEqual([
    'name',
    'handle',
  ]);
  expect(section('details').classList).toContain('import-wizard-section--open');
});

it('shows the whole journey from the start, with steps to add', async () => {
  await mount();

  const titles = [
    ...container.querySelectorAll('.import-wizard-section__title'),
  ].map((el) => el.textContent!.trim());

  expect(titles).toEqual(['Name the plan', 'Review and run']);
  expect(section('review').classList).toContain(
    'import-wizard-section--locked'
  );
  expect(section('add').textContent).toContain('Add step');
});

it('collapses the name to a summary, and starts the first step at its data source', async () => {
  await mount();
  await nameThePlan();

  const titles = [
    ...container.querySelectorAll('.import-wizard-section__title'),
  ].map((el) => el.textContent!.trim());

  expect(section('details').classList).toContain('import-wizard-section--done');
  expect(section('details').textContent).toContain('Guides · guides');
  expect(titles).toEqual([
    'Name the plan',
    'Data source',
    'Destination',
    'Fields',
    'Matching',
    'Review and run',
  ]);
  expect(container.textContent).toContain('Step 1 · Entries');
  expect(openSection().textContent).toContain('Data source');
  expect(editor('source').props).toMatchObject({step: {type: ENTRIES}});
  expect(editor('advanced').props.step.uid).toBe(
    editor('source').props.step.uid
  );
});

it('adds another step, starting at its data source', async () => {
  await mount();
  await nameThePlan();

  await clickButton(section('add'), 'Add step');

  expect(container.textContent).toContain('Step 2 · Entries');
  expect(openSection().textContent).toContain('Data source');
  expect(container.querySelectorAll('.import-wizard__step')).toHaveLength(2);
});

it('only moves on from a data source that can be read', async () => {
  await mount();
  await startAStep();

  await clickButton(openSection(), 'Continue');
  expect(openSection().textContent).toContain(
    'Choose a data source that can be read.'
  );

  state.readable!(true);
  await flush();
  await clickButton(openSection(), 'Continue');

  expect(openSection().textContent).toContain('Destination');
  expect(lastEditor().props.part).toBe('settings');
});

it('only moves on from a destination once the step can be mapped', async () => {
  await mount();
  await startAStep();
  state.readable!(true);
  await flush();
  await clickButton(openSection(), 'Continue');

  await clickButton(openSection(), 'Continue');
  expect(openSection().textContent).toContain(
    'Choose what this step imports into.'
  );

  lastEditor().emit('update:canMap', true);
  await flush();
  await clickButton(openSection(), 'Continue');

  expect(openSection().textContent).toContain('Fields');
  expect(lastEditor().props.part).toBe('mapping');
});

it('checks a step’s fields with the server before moving on to the review', async () => {
  await mount();
  const step = await finishTheStep();

  expect(state.validateStep).toHaveBeenCalledWith(
    expect.objectContaining({uid: step.uid, source: 'guides.json'})
  );
  expect(section('review').classList).toContain('import-wizard-section--open');
});

it('opens a step’s data source again when the server finds a problem with it', async () => {
  await mount();
  await finishTheStep();
  state.validateStep.mockRejectedValue({
    response: {data: {errors: {source: ['The file doesn’t exist.']}}},
  });

  section(`${lastEditor().props.step.uid}:fields`)
    .querySelector('button')!
    .click();
  await flush();
  await clickButton(openSection(), 'Continue');

  expect(openSection().textContent).toContain('Data source');
  expect(editor('source').props.errors).toEqual([
    {path: ['source'], messages: ['The file doesn’t exist.']},
  ]);
});

it('moves on from the fields to matching', async () => {
  await mount();
  await reachTheFields();
  await clickButton(openSection(), 'Continue');

  expect(openSection().textContent).toContain('Matching');
  expect(editor('matching').props.step.source).toBe('guides.json');
});

it('shows the fields’ problems on the fields, and stays there', async () => {
  state.validateStep.mockRejectedValue({
    response: {data: {errors: {'settings.map': ['Title must be mapped.']}}},
  });
  await mount();
  await reachTheFields();
  await clickButton(openSection(), 'Continue');

  expect(openSection().textContent).toContain('Fields');
  expect(openSection().querySelector('.error-list')!.textContent).toContain(
    'Title must be mapped.'
  );
});

it('shows why the rows can’t be read with the mapping on the fields', async () => {
  state.post.mockResolvedValue({
    data: {steps: [{preview: {error: 'The template has a syntax error.'}}]},
  });
  await mount();
  await reachTheFields();
  await clickButton(openSection(), 'Continue');

  expect(openSection().textContent).toContain('Fields');
  expect(openSection().querySelector('.error-list')!.textContent).toContain(
    'The template has a syntax error.'
  );
});

it('sums up the matching once it’s done', async () => {
  await mount();
  const step = await reachTheFields();
  editor('mapping').emit('update:step', {
    ...step,
    settings: {matchMode: 'updateOnly', matchCriteria: {slug: '1'}},
  });
  await clickButton(openSection(), 'Continue');
  await clickButton(openSection(), 'Continue');

  expect(section(`${step.uid}:matching`).textContent).toContain(
    'Only update · matching on 1 field'
  );
});

it('opens a finished section again to change it', async () => {
  await mount();
  await finishTheStep();

  section('details').querySelector('button')!.click();
  await flush();

  expect(section('details').classList).toContain('import-wizard-section--open');
});

it('adds another step after the first', async () => {
  await mount();
  await finishTheStep();

  await clickButton(section('add'), 'Add step');

  expect(openSection().textContent).toContain('Data source');
  expect(container.textContent).toContain('Step 2 · Entries');
});

it('opens any section that can be used yet, in whatever order', async () => {
  await mount();
  await nameThePlan();
  const uid = editor('source').props.step.uid;

  for (const key of [`${uid}:matching`, 'review', 'details']) {
    section(key).querySelector('button')!.click();
    await flush();

    expect(section(key).classList).toContain('import-wizard-section--open');
  }
});

it('waits for a readable data source before the destination, and a destination before the fields', async () => {
  await mount();
  await nameThePlan();
  const uid = editor('source').props.step.uid;
  const stateOf = (part: string) =>
    [...section(`${uid}:${part}`).classList].find((c) =>
      c.startsWith('import-wizard-section--')
    );

  expect(stateOf('destination')).toBe('import-wizard-section--locked');
  expect(section(`${uid}:destination`).textContent).toContain(
    'Available once the data source can be read.'
  );
  expect(stateOf('fields')).toBe('import-wizard-section--locked');

  state.readable!(true);
  await flush();

  expect(stateOf('destination')).toBe('import-wizard-section--todo');
  expect(section(`${uid}:fields`).textContent).toContain(
    'Available once the destination is chosen.'
  );

  await clickButton(openSection(), 'Continue');
  lastEditor().emit('update:canMap', true);
  await flush();

  expect(stateOf('fields')).toBe('import-wizard-section--todo');
});

it('explains the open section in its header, and moves on from its footer', async () => {
  await mount();
  await nameThePlan();

  const open = openSection();

  expect(
    open.querySelector('.import-wizard-section__description')!.textContent
  ).toContain('Where the data comes from');
  expect(
    open.querySelector('[slot="footer"] craft-button')!.textContent!.trim()
  ).toBe('Continue');
});

it('posts the whole plan, then goes to its page', async () => {
  state.post.mockResolvedValue({
    data: {import: {uid: 'plan-uid', handle: 'guides'}},
  });
  await mount();
  const step = await finishTheStep();

  await clickButton(section('review'), 'Create import plan');

  expect(state.post).toHaveBeenCalledWith('/actions/import/save', {
    name: 'Guides',
    handle: 'guides',
    description: '',
    steps: [expect.objectContaining({uid: step.uid, type: ENTRIES})],
  });
  expect(state.visit).toHaveBeenCalledWith(
    '/admin/import/guides',
    expect.anything()
  );
});

it('runs the plan once it’s saved, and goes to the index to follow it', async () => {
  state.post.mockResolvedValue({
    data: {import: {uid: 'plan-uid', handle: 'guides'}},
  });
  await mount();
  await finishTheStep();

  await clickButton(section('review'), 'Create and run');

  expect(state.post).toHaveBeenLastCalledWith('/actions/import/run', {
    uid: 'plan-uid',
  });
  expect(state.visit).toHaveBeenCalledWith('/admin/import', expect.anything());
});

it('leaves running out for a user who can’t run plans', async () => {
  await mount(null);
  await finishTheStep();

  expect(section('review').textContent).not.toContain('Create and run');
});

it('opens the section the server’s errors are about', async () => {
  state.post.mockRejectedValue({
    response: {
      data: {errors: {handle: ['Handle “guides” has already been taken.']}},
    },
  });
  await mount();
  await finishTheStep();

  await clickButton(section('review'), 'Create import plan');

  expect(section('details').classList).toContain('import-wizard-section--open');
  expect(state.detailErrors).toEqual([
    {path: ['handle'], messages: ['Handle “guides” has already been taken.']},
  ]);
  expect(state.visit).not.toHaveBeenCalled();
});
