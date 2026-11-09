import {
  computed,
  createApp,
  defineComponent,
  h,
  nextTick,
  provide,
  reactive,
  shallowRef,
} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {router} from '@inertiajs/vue3';
import {
  createScreenPropsStore,
  ScreenContextKey,
  ScreenPropsStoreKey,
  type ScreenMode,
} from '@/common/composables/screen';
import {provideLayoutSlotRegistry} from '@/common/composables/layoutSlots';
import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
import ContentFooter from '@/common/layouts/screens/page/ContentFooter.vue';
import ErrorSummary from '@/common/form/ErrorSummary.vue';
import {SlideoutControllerKey} from '@/common/slideouts/types';
import {useReleasePreviewScope} from '@/modules/releases/releaseEditor';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {registerUiComponents} from '@/modules/ui/register';
import ElementEditor from './ElementEditor.vue';

const state = vi.hoisted(() => ({
  editor: null as any,
  activePreview: null as unknown,
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
  ...(await importOriginal<Record<string, unknown>>()),
  usePage: () => ({props: {}}),
}));

vi.mock('@/modules/elements/composables/useElementEditor', () => ({
  useElementEditor: () => state.editor,
}));

vi.mock('@/modules/elements/live-preview/previews', async (importOriginal) => ({
  ...(await importOriginal<Record<string, unknown>>()),
  getActivePreview: () => state.activePreview,
}));

vi.mock('@/modules/elements/live-preview/LivePreview.vue', async () => {
  const {defineComponent, h, ref} = await import('vue');

  return {
    default: defineComponent({
      setup(_, {expose, slots}) {
        const editorTarget = ref<HTMLElement | null>(null);
        const noticesTarget = ref<HTMLElement | null>(null);
        expose({editorTarget, noticesTarget, holdsEditor: true});

        return () =>
          h('section', {'data-live-preview': ''}, [
            h('div', {ref: editorTarget}),
            h('div', {ref: noticesTarget, 'data-preview-notices': ''}),
            slots.footer?.(),
          ]);
      },
    }),
  };
});

vi.mock('@/bootstrap/element-details-panels', () => ({
  elementDetailsPanelRegistry: {hasVisible: () => false},
}));

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  state.editor = {
    activity: {
      activity: shallowRef([]),
      isStale: shallowRef(false),
      staleType: shallowRef(null),
      rebase: vi.fn(),
    },
    activityTimelineVersion: shallowRef(0),
    autosave: {
      error: shallowRef(null),
      httpStatus: shallowRef(null),
      modified: shallowRef([]),
      savedAt: shallowRef(null),
      status: shallowRef('idle'),
    },
    addToRelease: vi.fn(),
    discardDraft: vi.fn(),
    errors: computed(() => ({})),
    form: reactive({typeId: null, processing: false}),
    uiPayload: computed(() => null),
    onMutation: vi.fn(),
    onSidebarMutation: vi.fn(),
    props: reactive({
      actionMenu: [],
      additionalButtons: [],
      activityPageUrl: null,
      activityTimelineUrl: null,
      aiAssistant: null,
      canAddToRelease: true,
      canCreateRelease: false,
      release: null,
      releaseOptions: [
        {id: 4, name: 'Launch', status: 'draft', targetDate: null},
      ],
      releaseTimeZone: 'UTC',
      contextMenu: null,
      applyDraftUrl: '/actions/elements/apply-draft',
      editorActions: {
        primary: {label: 'Save'},
        menu: [],
        buttons: [
          {
            label: 'Apply draft',
            actionUrl: '/actions/elements/apply-draft',
            variant: 'primary',
          },
        ],
      },
      elementDisplayName: 'entry',
      isProvisionalDraft: false,
      mergeNotice: null,
      metadataHtml: null,
      notice: null,
      previewTargets: [],
      readOnly: false,
      statusLabelHtml: null,
      title: 'Reviewed draft',
      workflow: {
        convertedToDraft: false,
        current: {status: 'approved'},
        draftReviews: [],
      },
    }),
    renderer: shallowRef(null),
    save: vi.fn(),
    sidebarErrors: computed(() => ({})),
    sidebarPayload: computed(() => null),
    sidebarRenderer: shallowRef(null),
    startEditingDraft: vi.fn(),
    releaseDraftLocked: computed(() => false),
    submitAction: vi.fn(),
    updatePayload: vi.fn(),
    workflowReviewLocked: computed(() => true),
  };

  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
  state.activePreview = null;
});

function mount(
  mode: ScreenMode,
  {previewedReleaseId}: {previewedReleaseId?: number} = {}
): void {
  const Root = defineComponent({
    setup: () => {
      provide(ScreenContextKey, {mode});

      // Edited from the release's preview, as its Edit button does.
      if (previewedReleaseId !== undefined) {
        useReleasePreviewScope(() => previewedReleaseId);
        provide(SlideoutControllerKey, {} as never);
      }

      provideLayoutSlotRegistry();
      const screen = createScreenPropsStore();
      provide(ScreenPropsStoreKey, screen);

      return () =>
        h('div', [
          // Falls back to the shell's own summary, as PageScreen does.
          h(LayoutSlotOutlet, {name: 'error-summary'}, () =>
            state.editor.form.hasErrors
              ? h(ErrorSummary, {errors: state.editor.form.errors})
              : null
          ),
          h(LayoutSlotOutlet, {name: 'content-notices'}),
          h(LayoutSlotOutlet, {name: 'content-toolbar-meta'}),
          h(LayoutSlotOutlet, {name: 'content-toolbar-actions'}),
          h(ElementEditor),
          h(ContentFooter, {
            ...screen.props,
            readOnly: false,
            form: state.editor.form,
            defaultFormActions: [],
          }),
        ]);
    },
  });

  app = createApp(Root);
  const components = createCpComponentRegistry();
  registerUiComponents(components);
  components.install(app);
  app.mount(container);
}

function findButton(label: string): HTMLElement | undefined {
  return [...container.querySelectorAll<HTMLElement>('craft-button')].find(
    (button) => button.textContent?.trim() === label
  );
}

it.each<[boolean, ScreenMode]>([
  [true, 'page'],
  [true, 'slideout'],
  [false, 'page'],
  [false, 'slideout'],
])(
  'keeps Apply draft and release selection available (locked: %s, %s)',
  async (locked, mode) => {
    state.editor.workflowReviewLocked = computed(() => locked);

    mount(mode);
    await nextTick();

    findButton('Apply draft')!.click();
    expect(state.editor.submitAction).toHaveBeenCalledWith(
      state.editor.props.editorActions.buttons[0]
    );

    findButton('Add to release')!.click();
    await vi.waitFor(() =>
      expect(container.querySelector('craft-action-item')).not.toBeNull()
    );
    container.querySelector<HTMLElement>('craft-action-item')!.click();

    expect(state.editor.addToRelease).toHaveBeenCalledWith(
      state.editor.props.releaseOptions[0]
    );
  }
);

it.each([null, 'draft'] as const)(
  'keeps Apply draft available when no release can be chosen (release: %s)',
  async (status) => {
    state.editor.props.canAddToRelease = false;
    if (status !== null) {
      state.editor.props.release = {
        id: 6,
        name: 'Launch',
        status,
        targetDate: null,
        canManage: true,
        draftId: 9,
        siteId: 1,
        blockers: [],
      };
    }

    mount('page');
    await nextTick();

    expect(findButton('Add to release')).toBeUndefined();
    findButton('Apply draft')!.click();

    expect(state.editor.submitAction).toHaveBeenCalledWith(
      state.editor.props.editorActions.buttons[0]
    );
  }
);

it('re-baselines the stale notice once Reload brings in the latest version', async () => {
  state.editor.activity.isStale.value = true;
  state.editor.props.updatedTimestamps = {element: 5, canonical: 5};
  const reload = vi
    .spyOn(router, 'reload')
    .mockImplementation((options?: any) => options?.onSuccess?.());

  mount('page');
  await nextTick();
  findButton('Reload')!.click();

  expect(reload).toHaveBeenCalledOnce();
  expect(state.editor.activity.rebase).toHaveBeenCalledWith({
    element: 5,
    canonical: 5,
  });
  reload.mockRestore();
});

it('titles its error summary for the element rather than for settings', async () => {
  Object.assign(state.editor.form, {
    hasErrors: true,
    errors: {title: 'The title field is required.'},
  });

  mount('page');
  await nextTick();

  const summary = container.querySelector('craft-callout[variant="danger"]');
  expect(summary?.textContent).toContain('Couldn’t save changes');
  expect(summary?.textContent).toContain('The title field is required.');
  expect(summary?.textContent).not.toContain('settings');
});

const release = {
  id: 6,
  name: 'Launch',
  status: 'draft',
  targetDate: null,
  canManage: true,
  draftId: 9,
  siteId: 1,
  blockers: [],
};

function withPreviewTargets(): void {
  state.editor.props.previewTargets = [
    {label: 'Entry page', url: '/actions/preview/create-token?redirect=x'},
  ];
  state.editor.props.preview = {
    targets: [{label: 'Entry page', url: '/entry', refresh: true}],
    createTokenUrl: '/actions/preview/create-token',
    tokenParams: {canonicalId: 1, siteId: 1, previewToken: 'encrypted'},
    token: 'token',
    tokenDuration: 86400,
    tokenParam: 'token',
    paramValue: 'param',
    siteTokenParam: 'siteToken',
    siteToken: null,
  };
}

it.each<[ScreenMode, boolean]>([
  ['page', false],
  ['slideout', false],
  ['page', true],
  ['slideout', true],
])(
  'links to the element on the front end (%s, in a release: %s)',
  async (mode, inRelease) => {
    withPreviewTargets();
    state.editor.props.release = inRelease ? release : null;

    mount(mode);
    await nextTick();

    expect(
      container.querySelector('craft-button[href]')?.getAttribute('href')
    ).toBe('/actions/preview/create-token?redirect=x');
  }
);

it.each<[ScreenMode, boolean, boolean]>([
  ['page', true, false],
  ['slideout', true, false],
  ['slideout', false, false],
  ['page', true, true],
  ['slideout', true, true],
])(
  'offers Live Preview (%s, with no preview open: %s, in a release: %s)',
  async (mode, noPreviewOpen, inRelease) => {
    state.activePreview = noPreviewOpen ? null : {isOpen: {value: true}};
    withPreviewTargets();
    state.editor.props.release = inRelease ? release : null;

    mount(mode);
    await nextTick();

    expect(findButton('Preview') !== undefined).toBe(noPreviewOpen);
  }
);

it.each<ScreenMode>(['page', 'slideout'])(
  'shows the action menu for a draft in a release (%s)',
  async (mode) => {
    state.editor.props.release = release;
    state.editor.props.actionMenu = [
      {label: 'Copy entry', behavior: {type: 'clipboard', value: 'x'}},
    ];

    mount(mode);
    await nextTick();

    expect(
      container.querySelector('[data-focus-key="element-actions"]')
    ).not.toBeNull();
  }
);

it.each([true, false])(
  'keeps the editor’s notices and lock in Live Preview (locked: %s)',
  async (locked) => {
    state.editor.workflowReviewLocked = computed(() => false);
    Object.assign(state.editor.props, {
      readOnly: locked,
      notice: 'You’re viewing a draft.',
    });
    withPreviewTargets();

    mount('page');
    await nextTick();
    await nextTick();

    const pane = container.querySelector('[data-live-preview]')!;
    expect(pane.querySelector('[data-preview-notices]')?.textContent).toContain(
      'You’re viewing a draft.'
    );
    expect(
      [...pane.querySelectorAll('craft-button')].some(
        (button) => button.textContent?.trim() === 'Save'
      )
    ).toBe(!locked);
  }
);

it('describes a revision with its own notice alone', async () => {
  Object.assign(state.editor.props, {
    readOnly: true,
    notice: 'You’re viewing a revision.',
  });

  mount('page');
  await nextTick();

  expect(container.textContent).toContain('You’re viewing a revision.');
  expect(container.textContent).not.toContain('This is a read-only view.');
});

it.each([
  ['a revision', 'readOnly', false],
  ['a draft locked for review', 'editable', true],
] as const)(
  'renders the fields of %s read-only, keeping them legible',
  async (_, controlMode, reviewLocked) => {
    state.editor.workflowReviewLocked = computed(() => reviewLocked);
    state.editor.props.readOnly = controlMode === 'readOnly';
    state.editor.errors = computed(() => []);
    state.editor.uiPayload = computed(() => ({
      scope: [],
      refreshable: false,
      values: {title: 'Hello'},
      errors: [],
      globalErrors: [],
      nodes: [
        {
          type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
          component: 'craft:field',
          props: {label: 'Title'},
          control: {
            type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
            component: 'craft:text',
            props: {},
            path: ['title'],
            mode: controlMode,
            deltaGroup: ['title'],
          },
        },
      ],
    }));

    mount('page');
    await nextTick();

    const field = container.querySelector<
      HTMLElement & {readOnly: boolean; disabled: boolean}
    >('craft-field')!;
    expect({readOnly: field.readOnly, disabled: field.disabled}).toEqual({
      readOnly: true,
      disabled: false,
    });
  }
);

it('points a withdrawn draft back to the workflow panel to resubmit it', async () => {
  state.editor.workflowReviewLocked = computed(() => false);
  state.editor.props.workflow.current = {
    status: 'withdrawn',
    canSubmit: true,
    submitLabel: 'Resubmit for review',
  };

  mount('page');
  await nextTick();

  expect(container.textContent).toContain(
    'This draft was withdrawn from review. Resubmit it when your edits are ready.'
  );
  expect(findButton('Resubmit for review')).toBeDefined();
});

it('says a withdrawn draft is out of review to someone who can’t resubmit it', async () => {
  state.editor.workflowReviewLocked = computed(() => false);
  state.editor.props.workflow.current = {
    status: 'withdrawn',
    canSubmit: false,
    submitLabel: 'Resubmit for review',
  };

  mount('page');
  await nextTick();

  expect(container.textContent).toContain(
    'This draft was withdrawn from review.'
  );
  expect(container.textContent).not.toContain('Resubmit it when');
  expect(findButton('Resubmit for review')).toBeUndefined();
});

it('tells a reviewer the draft is waiting on them until they dismiss it', async () => {
  state.editor.workflowReviewLocked = computed(() => false);
  state.editor.props.workflow.current = {
    status: 'pending',
    isReviewer: true,
    hasApproved: false,
  };

  mount('page');
  await nextTick();

  expect(container.textContent).toContain(
    'This draft is waiting on your approval.'
  );

  container
    .querySelector<HTMLElement>('craft-button[aria-label="Dismiss"]')!
    .click();
  await nextTick();

  expect(container.textContent).not.toContain(
    'This draft is waiting on your approval.'
  );
});

it('shows a dismissed release notice again once the draft blocks the release', async () => {
  state.editor.workflowReviewLocked = computed(() => false);
  state.editor.props.workflow.current = null;
  state.editor.props.canAddToRelease = false;
  state.editor.props.release = {
    id: 6,
    name: 'Spring launch',
    status: 'draft',
    targetDate: null,
    canManage: true,
    draftId: 9,
    siteId: 1,
    blockers: [],
  };

  mount('page');
  await nextTick();

  expect(container.textContent).toContain(
    'This draft belongs to “Spring launch”.'
  );

  container
    .querySelector<HTMLElement>('craft-button[aria-label="Dismiss"]')!
    .click();
  await nextTick();

  expect(container.textContent).not.toContain('Spring launch');

  state.editor.props.release = {...state.editor.props.release, name: 'Renamed'};
  await nextTick();

  expect(container.textContent).not.toContain('Renamed');

  state.editor.props.release = {
    ...state.editor.props.release,
    blockers: ['Title cannot be blank.'],
  };
  await nextTick();

  expect(container.textContent).toContain('This draft is blocking “Renamed”.');
});

it('asks before editing a reviewed draft in a scheduled release', async () => {
  state.editor.props.release = {
    id: 6,
    name: 'Spring launch',
    status: 'scheduled',
    targetDate: null,
    canManage: true,
    draftId: 9,
    siteId: 1,
    blockers: [],
  };

  mount('page');
  await nextTick();

  const dialog = container.querySelector<HTMLElement & {opened?: boolean}>(
    'craft-dialog'
  )!;
  expect(dialog.opened).toBe(false);

  findButton('Start editing')!.click();
  await vi.waitFor(() => expect(dialog.opened).toBe(true));
  expect(dialog.textContent).toContain(
    'Unless it’s approved again before “Spring launch” publishes, the release will fail to publish.'
  );

  findButton('Cancel')!.click();
  await vi.waitFor(() => expect(dialog.opened).toBe(false));
  expect(state.editor.startEditingDraft).not.toHaveBeenCalled();
});

it('doesn’t ask before editing a draft that already blocks its scheduled release', async () => {
  state.editor.props.release = {
    id: 6,
    name: 'Spring launch',
    status: 'scheduled',
    targetDate: null,
    canManage: true,
    draftId: 9,
    siteId: 1,
    blockers: ['The title field is required.'],
  };

  mount('page');
  await nextTick();

  const dialog = container.querySelector<HTMLElement & {opened?: boolean}>(
    'craft-dialog'
  )!;
  findButton('Start editing')!.click();
  await nextTick();
  await nextTick();

  expect(dialog.opened).toBe(false);
});

it.each([
  ['an unscheduled release it isn’t blocking', false, 'draft', [], null],
  ['a scheduled release', true, 'scheduled', [], null],
  [
    'an unscheduled release it’s blocking',
    true,
    'draft',
    ['Title cannot be blank.'],
    null,
  ],
  [
    'an unscheduled release, with an approved review',
    true,
    'draft',
    [],
    'approved',
  ],
])(
  'in its release’s preview, a draft in %s shows the release notice: %s',
  async (_, shown, status, blockers, reviewStatus) => {
    state.editor.workflowReviewLocked = computed(() => false);
    state.editor.props.workflow.current = reviewStatus
      ? {status: reviewStatus}
      : null;
    state.editor.props.canAddToRelease = false;
    state.editor.props.release = {
      id: 6,
      name: 'Spring launch',
      status,
      targetDate: null,
      canManage: true,
      draftId: 9,
      siteId: 1,
      blockers,
    };

    mount('slideout', {previewedReleaseId: 6});
    await nextTick();

    expect(container.textContent?.includes('Spring launch')).toBe(shown);
  }
);
