import {createApp, defineComponent, h, provide} from 'vue';
import {router} from '@inertiajs/vue3';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {ScreenPagePropsKey} from '@/common/composables/screen';
import {SlideoutControllerKey} from '@/common/slideouts/types';
import {useElementEditPage} from './useElementEditPage';

/** Enough of the shared payload for the composable to boot. */
function payload(overrides: Record<string, unknown> = {}) {
  return {
    elementId: 12,
    canonicalId: 12,
    elementType: 'craft\\elements\\Entry',
    siteId: 1,
    draftId: null,
    isProvisionalDraft: false,
    canAutosave: false,
    form: null,
    sidebarForm: null,
    saveUrl: '/actions/entries/save-entry',
    applyDraftUrl: '/actions/elements/apply-draft',
    autosaveUrl: '/actions/elements/save-draft',
    discardDraftUrl: '/actions/elements/delete-draft',
    activityUrl: null,
    updatedTimestamps: {element: 1, canonical: 1},
    formActions: [],
    headerActions: [],
    actionMenu: [],
    previewTargets: [],
    ...overrides,
  };
}

/** Only what the composable reaches for. */
function slideoutController() {
  return {
    instance: {id: 'slideout-1', containerId: 'container-1'},
    close: vi.fn(),
    reload: vi.fn().mockResolvedValue(undefined),
    saved: vi.fn().mockReturnValue(false),
  };
}

describe('useElementEditPage', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  /**
   * Mount the composable under a shell that provides the screen's own props,
   * the way `SlideoutPanel` does — and, in a panel, the slideout controller.
   */
  function mount(
    screenProps: Record<string, unknown>,
    slideout: ReturnType<typeof slideoutController> | null = null
  ) {
    let editor!: ReturnType<typeof useElementEditPage>;

    const Editor = defineComponent({
      setup() {
        editor = useElementEditPage();

        return () => null;
      },
    });

    const Shell = defineComponent({
      setup() {
        provide(ScreenPagePropsKey, () => screenProps);

        if (slideout) {
          provide(SlideoutControllerKey, slideout as any);
        }

        return () => h(Editor);
      },
    });

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(Shell);
    app.mount(container);

    return editor;
  }

  it('reads the panel’s own props inside a slideout, not the page behind it', () => {
    const editor = mount(
      payload({elementId: 99, canonicalId: 99}),
      slideoutController()
    );

    expect(editor.props.elementId).toBe(99);
    expect(editor.props.saveUrl).toBe('/actions/entries/save-entry');
  });

  it('leaves the base page’s navigation to itself inside a slideout', () => {
    const on = vi.spyOn(router, 'on');

    mount(payload(), slideoutController());

    // The panel's unsaved changes are the slideout store's to guard, and a
    // guard here would also fire on the reload that follows saving from it.
    expect(on).not.toHaveBeenCalledWith('before', expect.anything());

    on.mockRestore();
  });

  it('guards navigation away from a full page', () => {
    const on = vi.spyOn(router, 'on');

    mount(payload());

    expect(on).toHaveBeenCalledWith('before', expect.anything());

    on.mockRestore();
  });

  it('autosaves on a difference from the server, not on being told of a change', () => {
    const editor = mount(payload({canAutosave: true}));
    const schedule = vi.spyOn(editor.autosave, 'schedule');

    // The renderers hand over the difference against the server's values, so an
    // empty one means a control announced itself without changing anything —
    // populating a field on load, say. That must not create a draft.
    editor.onMutation({});
    editor.onSidebarMutation({});

    expect(schedule).not.toHaveBeenCalled();

    editor.onMutation({fields: {money: {value: '12', locale: 'en-US'}}});

    expect(schedule).toHaveBeenCalledTimes(1);

    editor.onSidebarMutation({slug: 'changed'});

    expect(schedule).toHaveBeenCalledTimes(2);
  });
});
