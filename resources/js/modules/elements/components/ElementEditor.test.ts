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

vi.mock('@/modules/elements/live-preview/LivePreview.vue', () => ({
  default: {render: () => null},
}));

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

function mount(mode: ScreenMode): void {
  const Root = defineComponent({
    setup: () => {
      provide(ScreenContextKey, {mode});
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

it.each<ScreenMode>(['page', 'slideout'])(
  'links to the element on the front end (%s)',
  async (mode) => {
    state.editor.props.previewTargets = [
      {label: 'Entry page', url: '/actions/preview/create-token?redirect=x'},
    ];

    mount(mode);
    await nextTick();

    expect(
      container.querySelector('craft-button[href]')?.getAttribute('href')
    ).toBe('/actions/preview/create-token?redirect=x');
  }
);

it.each<[ScreenMode, boolean]>([
  ['page', true],
  ['slideout', true],
  ['slideout', false],
])(
  'offers Live Preview (%s, with no preview open: %s)',
  async (mode, noPreviewOpen) => {
    state.activePreview = noPreviewOpen ? null : {isOpen: {value: true}};
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
      iframeResizerOptions: false,
    };

    mount(mode);
    await nextTick();

    expect(findButton('Preview') !== undefined).toBe(noPreviewOpen);
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
