import {
  createApp,
  defineComponent,
  h,
  nextTick,
  provide,
  shallowReactive,
} from 'vue';
import {router} from '@inertiajs/vue3';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  ScreenPagePropsKey,
  type ScreenPageProps,
} from '@/common/composables/screen';
import {SlideoutControllerKey} from '@/common/slideouts/types';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormRenderer from '@/modules/forms/FormRenderer.vue';
import {registerFormComponents} from '@/modules/forms/register';
import type {FormPayload} from '@/modules/forms/types';
import {useElementEditor, type ElementEditPayload} from './useElementEditor';

const {postSpy} = vi.hoisted(() => ({postSpy: vi.fn()}));

// Only the action client is stubbed; `t()` and the rest of the package are the
// real thing, the way the composable sees them at runtime.
vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal<Record<string, unknown>>()),
  actionClient: {post: postSpy},
}));

// `usePage()` reads a store Inertia only fills once its own `App` component has
// mounted, and saving reads `redirectUrl` off it. The screen's own props come
// through `ScreenPagePropsKey` either way — see `useScreenPageProps()` — so
// this only stands in for the shared page, and `router` stays the real one so
// the visits can be spied on.
vi.mock('@inertiajs/vue3', async (importOriginal) => ({
  ...(await importOriginal<Record<string, unknown>>()),
  usePage: () => ({props: {}}),
}));

/** Enough of the shared payload for the composable to boot. */
function payload(
  overrides: Partial<ElementEditPayload> = {}
): Partial<ElementEditPayload> {
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

describe('useElementEditor', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;
  const attachInternals = Object.getOwnPropertyDescriptor(
    HTMLElement.prototype,
    'attachInternals'
  );

  beforeEach(() => {
    // The form-associated Controls need it to upgrade at all.
    Object.defineProperty(HTMLElement.prototype, 'attachInternals', {
      configurable: true,
      value: () => ({setFormValue: vi.fn()}),
    });
    postSpy.mockReset();
    postSpy.mockResolvedValue({data: {draftId: 7}});
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();

    if (attachInternals) {
      Object.defineProperty(
        HTMLElement.prototype,
        'attachInternals',
        attachInternals
      );
    } else {
      delete (HTMLElement.prototype as Partial<HTMLElement>).attachInternals;
    }
  });

  /**
   * Mount the composable under a shell that provides the screen's own props,
   * the way `SlideoutPanel` does — and, in a panel, the slideout controller.
   *
   * The props are read through a holder so a test can swap them for a new
   * object, which is what an Inertia visit or a panel reload does.
   */
  function mount(
    screenProps: Partial<ElementEditPayload>,
    slideout: ReturnType<typeof slideoutController> | null = null
  ) {
    // Reactive, the way both real sources are: Inertia's `usePage()` exposes
    // `props` as a computed, and the slideout store's panels are `reactive()`.
    const page = shallowReactive({props: screenProps});
    let editor!: ReturnType<typeof useElementEditor>;

    // The real field layout, wired the way `ElementEditor` wires it — the
    // renderer is what holds the unsaved values, so a screen with a `form`
    // payload can only be reasoned about with one mounted.
    const Editor = defineComponent({
      setup() {
        editor = useElementEditor();

        return () =>
          editor.formPayload.value
            ? h(FormRenderer, {
                ref: editor.renderer as any,
                payload: editor.formPayload.value,
                errors: editor.errors.value,
                'onUpdate:mutation': editor.onMutation,
              })
            : null;
      },
    });

    const Shell = defineComponent({
      setup() {
        provide(ScreenPagePropsKey, () => {
          const props: ScreenPageProps = {};
          Object.assign(props, page.props);
          return props;
        });

        if (slideout) {
          provide(SlideoutControllerKey, slideout as any);
        }

        return () => h(Editor);
      },
    });

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(Shell);
    const components = createCpComponentRegistry();
    registerFormComponents(components);
    components.install(app);
    app.mount(container);

    return {editor, page};
  }

  /** A one-field layout, the smallest thing that can hold an unsaved value. */
  function fieldLayout(title: string): FormPayload {
    return {
      scope: [],
      refreshable: false,
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
          component: 'craft:field',
          props: {label: 'Title', instructions: null, required: false},
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\Text',
            component: 'craft:text',
            props: {inputType: 'text'},
            path: ['title'],
            mode: 'editable',
            deltaGroup: ['title'],
            forms: [],
          },
        },
      ],
      values: {title},
      errors: [],
      globalErrors: [],
    };
  }

  /** The rendered Title input. */
  function titleInput(): HTMLInputElement {
    return container!.querySelector<HTMLInputElement>('input[name="title"]')!;
  }

  /** Types into the Title field the way a user would. */
  async function typeTitle(value: string): Promise<void> {
    // The Controls are custom elements; they render their input on the tick
    // after the Form does.
    await nextTick();
    titleInput().value = value;
    titleInput().dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();
  }

  it('reads the panel’s own props inside a slideout, not the page behind it', () => {
    const {editor} = mount(
      payload({elementId: 99, canonicalId: 99}),
      slideoutController()
    );

    expect(editor.props.elementId).toBe(99);
    expect(editor.props.saveUrl).toBe('/actions/entries/save-entry');
  });

  // Autosaving a canonical element creates a provisional draft, and from that
  // point the screen belongs to the draft. Page props are only replaced by a
  // visit, so what the save returns is read over the top of them.
  it('applies the screen payload an autosave returned, without a visit', async () => {
    postSpy.mockResolvedValue({
      data: {
        draftId: 7,
        elementId: 44,
        screen: {
          elementId: 44,
          draftId: 7,
          isProvisionalDraft: true,
          canDiscardDraft: true,
          notice: 'Showing your unsaved changes.',
          submitButtonLabel: 'Save',
        },
      },
    });

    const {editor} = mount(payload({canAutosave: true}));

    expect(editor.props.notice).toBeUndefined();

    await editor.autosave.save();
    await nextTick();

    expect(editor.props.notice).toBe('Showing your unsaved changes.');
    expect(editor.props.canDiscardDraft).toBe(true);
    expect(editor.props.isProvisionalDraft).toBe(true);
    expect(editor.props.elementId).toBe(44);
    // Untouched keys still read from the page's own props.
    expect(editor.props.saveUrl).toBe('/actions/entries/save-entry');
  });

  it('drops the autosaved screen once a visit brings a newer payload', async () => {
    postSpy.mockResolvedValue({
      data: {
        draftId: 7,
        screen: {notice: 'Showing your unsaved changes.', draftId: 7},
      },
    });

    const {editor, page} = mount(payload({canAutosave: true}));

    await editor.autosave.save();
    await nextTick();

    expect(editor.props.notice).toBe('Showing your unsaved changes.');

    // Discarding the draft reloads the screen; the server's view of it wins.
    page.props = payload({canAutosave: true, notice: null});
    await nextTick();

    expect(editor.props.notice).toBeNull();
    expect(editor.autosave.screen.value).toBeNull();
  });

  /**
   * The reload that follows a discard is a round trip, and the overlay would go
   * on shadowing the page props until it lands — leaving the notice describing
   * a draft the user just deleted.
   */
  it('drops the autosaved screen as soon as the draft is discarded', async () => {
    postSpy.mockResolvedValue({
      data: {
        draftId: 7,
        screen: {
          draftId: 7,
          isProvisionalDraft: true,
          canDiscardDraft: true,
          notice: 'Showing your unsaved changes.',
        },
      },
    });

    const {editor} = mount(payload({canAutosave: true}));

    await editor.autosave.save();
    await nextTick();
    expect(editor.props.notice).toBe('Showing your unsaved changes.');

    const reload = vi.spyOn(router, 'reload').mockImplementation(() => {});

    // Resolves the delete, but nothing replaces the page props — the reload it
    // triggers is still in flight.
    postSpy.mockResolvedValue({data: {}});
    await editor.discardDraft();
    await nextTick();

    expect(editor.props.notice).toBeUndefined();
    expect(editor.props.canDiscardDraft).toBeUndefined();
    expect(editor.autosave.screen.value).toBeNull();
    // The draft it named is gone, so the next save must not target it.
    expect(editor.autosave.draftId.value).toBeNull();
    expect(reload).toHaveBeenCalled();

    reload.mockRestore();
  });

  /**
   * The banner going away isn't the whole of a discard: the values the user
   * threw away live in the renderer, which a reload deliberately merges *under*
   * rather than over — so without an explicit reset they survive it, the form
   * stays dirty, and the next autosave builds the draft again.
   */
  it('reverts the fields and leaves the form clean when the draft is discarded', async () => {
    vi.useFakeTimers();

    const {editor, page} = mount(
      payload({canAutosave: true, form: fieldLayout('Canonical title')})
    );

    await typeTitle('Edited title');
    expect(editor.form.isDirty).toBe(true);

    // The autosave the edit armed creates the provisional draft, and answers
    // with the screen — and the field layout — as the draft's.
    postSpy.mockResolvedValue({
      data: {
        draftId: 7,
        form: fieldLayout('Edited title'),
        screen: {
          draftId: 7,
          isProvisionalDraft: true,
          canDiscardDraft: true,
          notice: 'Showing your unsaved changes.',
        },
      },
    });
    await vi.advanceTimersByTimeAsync(2000);
    await nextTick();

    expect(postSpy).toHaveBeenCalledTimes(1);
    expect(editor.props.notice).toBe('Showing your unsaved changes.');
    expect(titleInput().value).toBe('Edited title');

    postSpy.mockResolvedValue({data: {}});
    const reload = vi
      .spyOn(router, 'reload')
      .mockImplementation((options: any) => {
        // What the visit does: a fresh canonical payload, then the callbacks.
        page.props = payload({
          canAutosave: true,
          form: fieldLayout('Canonical title'),
        });
        void nextTick().then(() => options?.onFinish?.());
      });

    await editor.discardDraft();
    await nextTick();

    expect(reload).toHaveBeenCalled();
    expect(titleInput().value).toBe('Canonical title');
    expect(editor.values.value).toEqual({title: 'Canonical title'});
    expect(editor.form.data()).toEqual({});
    expect(editor.form.isDirty).toBe(false);
    expect(editor.props.notice).toBeUndefined();

    // …and nothing left armed to build the draft straight back.
    await vi.advanceTimersByTimeAsync(5000);
    expect(postSpy).toHaveBeenCalledTimes(2);

    reload.mockRestore();
    vi.useRealTimers();
  });

  /**
   * A panel reloads itself rather than visiting: the store swaps the panel's
   * props, so nothing about the Inertia page changes. (The store also drops the
   * screen for a spinner while it loads, which remounts the editor on top of
   * this — but the revert is what puts the fields back the moment the draft is
   * deleted, rather than a round trip later.)
   */
  it('reverts the fields when the draft is discarded inside a slideout', async () => {
    vi.useFakeTimers();

    const slideout = slideoutController();
    let page!: {props: Record<string, unknown>};
    const mounted = mount(
      payload({canAutosave: true, form: fieldLayout('Canonical title')}),
      slideout
    );
    const editor = mounted.editor;
    page = mounted.page;

    slideout.reload.mockImplementation(async () => {
      page.props = payload({
        canAutosave: true,
        form: fieldLayout('Canonical title'),
      });
      await nextTick();
    });

    await typeTitle('Edited title');

    postSpy.mockResolvedValue({
      data: {
        draftId: 7,
        form: fieldLayout('Edited title'),
        screen: {draftId: 7, notice: 'Showing your unsaved changes.'},
      },
    });
    await vi.advanceTimersByTimeAsync(2000);
    await nextTick();

    expect(titleInput().value).toBe('Edited title');

    postSpy.mockResolvedValue({data: {}});
    await editor.discardDraft();
    await nextTick();

    // The panel reloads itself: an Inertia visit would reload the page behind.
    expect(slideout.reload).toHaveBeenCalled();
    expect(titleInput().value).toBe('Canonical title');
    expect(editor.form.isDirty).toBe(false);

    await vi.advanceTimersByTimeAsync(5000);
    expect(postSpy).toHaveBeenCalledTimes(2);

    vi.useRealTimers();
  });

  /**
   * A screen that *loaded* as a provisional draft has nothing canonical to
   * revert to until the reload lands, so the revert has to run again then.
   */
  it('takes the canonical values from the reload a discard triggers', async () => {
    vi.useFakeTimers();

    const {editor, page} = mount(
      payload({
        canAutosave: true,
        draftId: 7,
        isProvisionalDraft: true,
        form: fieldLayout('Draft title'),
      })
    );

    await typeTitle('Edited title');

    let finish: (() => void) | undefined;
    const reload = vi
      .spyOn(router, 'reload')
      .mockImplementation((options: any) => {
        finish = () => {
          page.props = payload({
            canAutosave: true,
            form: fieldLayout('Canonical title'),
          });
          void nextTick().then(() => options?.onFinish?.());
        };
      });

    postSpy.mockResolvedValue({data: {}});
    await editor.discardDraft();
    await nextTick();

    // The first revert can only reach the payload the screen loaded with.
    expect(titleInput().value).toBe('Draft title');

    finish!();
    await vi.advanceTimersByTimeAsync(0);
    await nextTick();

    expect(titleInput().value).toBe('Canonical title');
    expect(editor.form.isDirty).toBe(false);

    await vi.advanceTimersByTimeAsync(5000);
    // The discard's own request, and nothing after it.
    expect(postSpy).toHaveBeenCalledTimes(1);

    reload.mockRestore();
    vi.useRealTimers();
  });

  /**
   * A save is an Inertia visit, so it goes out through `router.post()`. This
   * stands in for the whole round trip: the request is captured for the test to
   * assert on, and `land()` replays what Inertia does with the response — swap
   * the page props, then run the visit's callbacks.
   */
  function interceptSave() {
    const post = vi
      .spyOn(router, 'post')
      .mockImplementation((_url, _data, options?: any) => {
        // Inertia raises the form's `processing` flag for the length of the
        // visit, and the composable reads it.
        options?.onStart?.({});

        return undefined as any;
      });

    return {
      restore: () => post.mockRestore(),
      /** The request the last save sent. */
      last() {
        const [url, data, options] = post.mock.calls.at(-1) as [
          string,
          Record<string, any>,
          Record<string, any>,
        ];

        return {url, data, options};
      },
      calls: () => post.mock.calls.length,
      /**
       * The response landing, in Inertia's order: the page props are swapped —
       * which re-seeds the renderers, and they reconcile before any callback
       * runs — then the visit's own callbacks, and `processing` drops last.
       */
      async land(page: {props: Record<string, unknown>}, props: any) {
        const {options} = this.last();
        page.props = props;
        await nextTick();
        await options.onSuccess({props});
        options.onFinish?.({});
        await nextTick();
      },
    };
  }

  /**
   * Applying the provisional draft consumes it, so the screen is a plain
   * canonical element again. The notice, the Discard changes button and the
   * draft the next save would target all belong to the draft that just went
   * away — and nothing arrives to say so except the response itself.
   */
  it('drops the provisional draft state once a save applies it', async () => {
    vi.useFakeTimers();

    const {editor, page} = mount(
      payload({canAutosave: true, form: fieldLayout('Canonical title')})
    );

    await typeTitle('Edited title');

    // The autosave the edit armed creates the provisional draft, and answers
    // with both halves of the screen — which is what a save then has to clear.
    postSpy.mockResolvedValue({
      data: {
        draftId: 7,
        form: fieldLayout('Edited title'),
        screen: {
          draftId: 7,
          isProvisionalDraft: true,
          canDiscardDraft: true,
          notice: 'Showing your unsaved changes.',
        },
      },
    });
    await vi.advanceTimersByTimeAsync(2000);
    await nextTick();

    expect(editor.props.notice).toBe('Showing your unsaved changes.');

    const save = interceptSave();

    editor.save();

    // The draft holds the newer values, so the Save button applies it.
    expect(save.last().url).toBe('/actions/elements/apply-draft');
    expect(save.last().data).toMatchObject({draftId: 7, provisional: 1});

    // The server answers with the canonical element: no draft, no notice.
    await save.land(
      page,
      payload({canAutosave: true, form: fieldLayout('Edited title')})
    );

    expect(editor.props.notice).toBeUndefined();
    expect(editor.props.canDiscardDraft).toBeUndefined();
    expect(editor.autosave.screen.value).toBeNull();
    // The draft it named has been applied and deleted; a save that still
    // pointed at it would post to a draft that no longer exists.
    expect(editor.autosave.draftId.value).toBeNull();

    // Re-seeding the renderers from the response emits mutations of its own,
    // and autosaving for those would build the draft — and the notice — straight
    // back a second and a half later.
    await vi.advanceTimersByTimeAsync(5000);
    expect(postSpy).toHaveBeenCalledTimes(1);
    expect(editor.props.notice).toBeUndefined();

    // …so the next save is an ordinary save of the canonical element.
    editor.save();

    expect(save.last().url).toBe('/actions/entries/save-entry');
    expect(save.last().data).not.toHaveProperty('draftId');
    expect(save.last().data).not.toHaveProperty('provisional');

    save.restore();
    vi.useRealTimers();
  });

  /**
   * Saving before the debounce has run leaves a save armed against the draft
   * the submission is about to consume. It describes values this save is
   * writing anyway, so it's called off rather than allowed to rebuild the draft
   * once the visit releases the form.
   */
  it('calls off the autosave the last keystroke armed', async () => {
    vi.useFakeTimers();

    const {editor, page} = mount(
      payload({canAutosave: true, form: fieldLayout('Canonical title')})
    );

    await typeTitle('Edited title');

    // Straight to Save, well inside the debounce.
    const save = interceptSave();

    editor.save();

    // No draft yet, so this is an ordinary save of the canonical element.
    expect(save.last().url).toBe('/actions/entries/save-entry');

    await save.land(
      page,
      payload({canAutosave: true, form: fieldLayout('Edited title')})
    );
    await vi.advanceTimersByTimeAsync(5000);

    expect(postSpy).not.toHaveBeenCalled();
    expect(editor.props.notice).toBeUndefined();

    save.restore();
    vi.useRealTimers();
  });

  /** Same for "Save and continue editing", which stays on the screen. */
  it('drops the provisional draft state when saving without redirecting', async () => {
    postSpy.mockResolvedValue({
      data: {
        draftId: 7,
        form: fieldLayout('Edited title'),
        screen: {
          draftId: 7,
          isProvisionalDraft: true,
          canDiscardDraft: true,
          notice: 'Showing your unsaved changes.',
        },
      },
    });

    const {editor, page} = mount(
      payload({canAutosave: true, form: fieldLayout('Canonical title')})
    );

    await editor.autosave.save();
    await nextTick();
    expect(editor.props.notice).toBe('Showing your unsaved changes.');

    const save = interceptSave();

    editor.save({redirect: false});
    await save.land(
      page,
      payload({canAutosave: true, form: fieldLayout('Edited title')})
    );

    expect(editor.props.notice).toBeUndefined();
    expect(editor.autosave.draftId.value).toBeNull();

    save.restore();
  });

  /**
   * "Create a draft" deliberately makes a *named* draft and lands on it, so the
   * screen is more of a draft afterwards, not less — the pointer has to follow
   * the server rather than being cleared because a save succeeded.
   */
  it('follows the server onto the draft an alternate action created', async () => {
    postSpy.mockResolvedValue({
      data: {
        draftId: 7,
        form: fieldLayout('Edited title'),
        screen: {
          draftId: 7,
          isProvisionalDraft: true,
          canDiscardDraft: true,
          notice: 'Showing your unsaved changes.',
        },
      },
    });

    const {editor, page} = mount(
      payload({canAutosave: true, form: fieldLayout('Canonical title')})
    );

    await editor.autosave.save();
    await nextTick();

    const save = interceptSave();

    editor.submitAction({
      label: 'Create a draft',
      actionUrl: '/actions/elements/save-draft',
      params: {dropProvisional: 1},
      redirect: 'encrypted',
    });

    expect(save.last().url).toBe('/actions/elements/save-draft');
    expect(save.last().data).toMatchObject({dropProvisional: 1});

    // The action redirects to the draft it just created.
    await save.land(
      page,
      payload({
        canAutosave: true,
        draftId: 9,
        isProvisionalDraft: false,
        form: fieldLayout('Edited title'),
      })
    );

    expect(editor.autosave.draftId.value).toBe(9);

    // From here the screen edits that named draft: saves target it, and none
    // of them claim it's provisional.
    editor.save();

    expect(save.last().url).toBe('/actions/elements/apply-draft');
    expect(save.last().data).toMatchObject({draftId: 9});
    expect(save.last().data).not.toHaveProperty('provisional');

    save.restore();
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
    const {editor} = mount(payload({canAutosave: true}));
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
