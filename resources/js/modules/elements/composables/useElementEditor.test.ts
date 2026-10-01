import {
  createApp,
  defineComponent,
  h,
  nextTick,
  provide,
  shallowReactive,
} from 'vue';
import {router} from '@inertiajs/vue3';
import axios from 'axios';
import {
  afterEach,
  beforeEach,
  describe,
  expect,
  it,
  onTestFinished,
  vi,
} from 'vite-plus/test';
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

function deferred<T>() {
  let resolve!: (value: T) => void;
  const promise = new Promise<T>((resolvePromise) => {
    resolve = resolvePromise;
  });

  return {promise, resolve};
}

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
    fieldLayoutId: undefined,
    title: undefined,
    docTitle: undefined,
    crumbs: undefined,
    readOnly: undefined,
    draftId: null,
    isProvisionalDraft: false,
    canAutosave: false,
    form: null,
    sidebarForm: null,
    metadataHtml: undefined,
    statusLabelHtml: undefined,
    saveUrl: '/actions/entries/save-entry',
    applyDraftUrl: '/actions/elements/apply-draft',
    autosaveUrl: '/actions/elements/save-draft',
    discardDraftUrl: '/actions/elements/delete-draft',
    notice: undefined,
    mergeNotice: undefined,
    canDiscardDraft: undefined,
    activityUrl: null,
    activityTimelineUrl: undefined,
    activityPageUrl: undefined,
    updatedTimestamps: {element: 1, canonical: 1},
    editorActions: {
      primary: {
        label: 'Save',
        actionUrl: null,
        params: {},
        redirect: null,
        tabId: null,
      },
      menu: [],
      buttons: [],
    },
    actionMenu: [],
    previewTargets: [],
    elementDisplayName: undefined,
    contextMenu: undefined,
    workflow: {
      current: null,
      draftReviews: [],
    },
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
    slideout: ReturnType<typeof slideoutController> | null = null,
    options: Parameters<typeof useElementEditor>[0] = {}
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
        editor = useElementEditor(options);

        return () =>
          h('div', [
            editor.formPayload.value
              ? h(FormRenderer, {
                  ref: editor.renderer as any,
                  payload: editor.formPayload.value,
                  errors: editor.errors.value,
                  'onUpdate:mutation': editor.onMutation,
                })
              : null,
            editor.sidebarPayload.value
              ? h(FormRenderer, {
                  ref: editor.sidebarRenderer as any,
                  payload: editor.sidebarPayload.value,
                  errors: editor.sidebarErrors.value,
                  'onUpdate:mutation': editor.onSidebarMutation,
                })
              : null,
          ]);
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

  it('updates the editor payload from a details tab', () => {
    const {editor} = mount(payload({title: 'Original title'}));

    editor.updatePayload((current) => ({
      title: `${current.title} updated`,
    }));

    expect(editor.props.title).toBe('Original title updated');
    expect(editor.activityTimelineVersion.value).toBe(1);
  });

  it('supplies an empty workflow payload when a visit omits workflow data', () => {
    const {editor} = mount(payload({workflow: undefined as never}));

    expect(editor.props.workflow).toEqual({
      convertedToDraft: false,
      current: null,
      draftReviews: [],
    });
  });

  it.each(['pending', 'approved'] as const)(
    'locks a %s workflow review until editing is explicitly started',
    async (status) => {
      const {editor} = mount(
        payload({
          workflow: {
            current: {
              status,
            } as CraftCms.Cms.Workflow.Data.WorkflowReviewData,
            draftReviews: [],
          },
        })
      );

      expect(editor.workflowReviewLocked.value).toBe(true);

      editor.startEditingReviewedDraft();
      await nextTick();

      expect(editor.workflowReviewLocked.value).toBe(false);
    }
  );

  it.each(['failed', 'invalidated'] as const)(
    'keeps a %s workflow draft editable',
    (status) => {
      const {editor} = mount(
        payload({
          workflow: {
            current: {
              status,
            } as CraftCms.Cms.Workflow.Data.WorkflowReviewData,
            draftReviews: [],
          },
        })
      );

      expect(editor.workflowReviewLocked.value).toBe(false);
    }
  );

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

  function sidebarForm(slug: string, autoGenerate = true): FormPayload {
    return {
      scope: [],
      refreshable: false,
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
          component: 'craft:field',
          props: {label: 'Slug', instructions: null, required: false},
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\Slug',
            component: 'craft:slug',
            props: {
              source: ['title'],
              ...(autoGenerate ? {} : {autoGenerate: false}),
            },
            path: ['slug'],
            mode: 'editable',
            deltaGroup: ['slug'],
            forms: [],
          },
        },
      ],
      values: {slug},
      errors: [],
      globalErrors: [],
    };
  }

  function cardsLayout(): FormPayload {
    return {
      scope: [],
      refreshable: false,
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
          component: 'craft:field',
          props: {label: 'Cards', instructions: null, required: false},
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\NestedEntries',
            component: 'craft:nested-entries',
            props: {
              viewMode: 'cards',
              manager: null,
              cards: [],
              unavailableMessage: null,
            },
            path: ['fields', 'matrixField'],
            mode: 'editable',
            deltaGroup: ['fields', 'matrixField'],
            forms: [],
            omitNullValue: true,
          },
        },
      ],
      values: {fields: {matrixField: null}},
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

  it('generates an empty entry slug from its title until the slug is edited', async () => {
    vi.stubGlobal('Craft', {
      ...(globalThis as any).Craft,
      allowUppercaseInSlug: false,
      limitAutoSlugsToAscii: true,
      slugWordSeparator: '-',
    });
    mount(
      payload({
        form: fieldLayout(''),
        sidebarForm: sidebarForm(''),
      })
    );

    await typeTitle('First Title');

    const slugInput =
      container!.querySelector<HTMLInputElement>('input[name="slug"]')!;
    expect(slugInput.value).toBe('first-title');

    slugInput.value = 'custom-slug';
    slugInput.dispatchEvent(new Event('input', {bubbles: true}));
    slugInput.dispatchEvent(new Event('change', {bubbles: true}));
    await typeTitle('Second Title');

    expect(slugInput.value).toBe('custom-slug');
  });

  it('continues generating the slug after autosave returns an established slug', async () => {
    vi.useFakeTimers();
    vi.stubGlobal('Craft', {
      ...(globalThis as any).Craft,
      allowUppercaseInSlug: false,
      limitAutoSlugsToAscii: true,
      slugWordSeparator: '-',
    });
    postSpy.mockResolvedValue({
      data: {
        draftId: 7,
        form: fieldLayout('First'),
        screen: {sidebarForm: sidebarForm('first')},
      },
    });
    mount(
      payload({
        canAutosave: true,
        form: fieldLayout(''),
        sidebarForm: sidebarForm(''),
      })
    );

    await typeTitle('First');
    await vi.advanceTimersByTimeAsync(1000);
    await nextTick();

    expect(postSpy).toHaveBeenCalledTimes(1);

    await typeTitle('First Article');

    const slugInput =
      container!.querySelector<HTMLInputElement>('input[name="slug"]')!;
    expect(slugInput.value).toBe('first-article');

    vi.useRealTimers();
  });

  it('refreshes the field layout without autosaving and preserves unsaved values', async () => {
    const {editor} = mount(
      payload({
        canAutosave: false,
        form: fieldLayout('Original title'),
      })
    );

    await typeTitle('Edited title');
    postSpy.mockResolvedValue({
      data: {form: fieldLayout('Server title')},
    });

    await editor.refreshForm();

    expect(postSpy).toHaveBeenCalledOnce();
    expect(postSpy.mock.calls[0]?.[0]).toContain(
      '/elements/update-field-layout'
    );
    expect(postSpy.mock.calls[0]?.[1]).toMatchObject({
      elementType: 'craft\\elements\\Entry',
      elementId: 12,
      draftId: null,
      siteId: 1,
      title: 'Edited title',
    });
    expect(postSpy.mock.calls[0]?.[1]).not.toHaveProperty('canonicalId');
    expect(titleInput().value).toBe('Edited title');
  });

  it('doesn’t report its own nested changes as someone else’s edit', async () => {
    let serverStamp = 1;
    postSpy.mockImplementation(async (url: string) =>
      url.includes('update-field-layout')
        ? {
            data: {
              form: fieldLayout('Original title'),
              updatedTimestamp: serverStamp,
              canonicalUpdatedTimestamp: 1,
            },
          }
        : {
            data: {
              activity: [],
              updatedTimestamp: serverStamp,
              canonicalUpdatedTimestamp: 1,
            },
          }
    );
    const {editor} = mount(
      payload({
        activityUrl: '/actions/elements/recent-activity',
        updatedTimestamps: {element: 1, canonical: 1},
        form: fieldLayout('Original title'),
      })
    );
    await editor.activity.poll();

    // Saving a nested element into the element bumps its `dateUpdated`.
    serverStamp = 2;
    await editor.refreshAfterNestedChange();
    await editor.activity.poll();

    expect(editor.activity.isStale.value).toBe(false);

    // Something that changes it afterwards is still someone else's edit.
    serverStamp = 3;
    await editor.activity.poll();

    expect(editor.activity.isStale.value).toBe(true);
  });

  it('omits presentation-only null controls when refreshing an untouched form', async () => {
    const form = cardsLayout();
    const {editor} = mount(payload({canAutosave: false, form}));
    await nextTick();
    postSpy.mockResolvedValue({data: {form}});

    await editor.refreshForm();

    expect(postSpy).toHaveBeenCalledOnce();
    expect(postSpy.mock.calls[0]?.[1]).not.toHaveProperty('fields');
  });

  it('keeps the latest field-layout refresh when responses arrive out of order', async () => {
    const first = deferred<{data: {form: FormPayload}}>();
    const second = deferred<{data: {form: FormPayload}}>();
    postSpy
      .mockImplementationOnce(() => first.promise)
      .mockImplementationOnce(() => second.promise);
    const {editor} = mount(
      payload({canAutosave: false, form: fieldLayout('Original title')})
    );

    const firstRefresh = editor.refreshForm();
    const secondRefresh = editor.refreshForm();
    second.resolve({data: {form: fieldLayout('Latest title')}});
    await secondRefresh;
    await nextTick();

    first.resolve({data: {form: fieldLayout('Stale title')}});
    await firstRefresh;
    await nextTick();

    expect(editor.formPayload.value?.values).toEqual({title: 'Latest title'});
  });

  it('refreshes the layout for a reactive control and returns its payload', async () => {
    const {editor} = mount(
      payload({canAutosave: false, form: fieldLayout('Original title')})
    );
    postSpy.mockResolvedValue({data: {form: fieldLayout('Server title')}});

    const refreshed = await editor.refreshLayout({}, []);

    expect(postSpy.mock.calls[0]?.[0]).toContain(
      '/elements/update-field-layout'
    );
    expect(refreshed.values).toEqual({title: 'Server title'});
    expect(editor.formPayload.value?.values).toEqual({title: 'Server title'});
  });

  it('leaves the layout to the renderer when refreshing a nested scope', async () => {
    const {editor} = mount(
      payload({canAutosave: false, form: fieldLayout('Original title')})
    );
    const nested = {...fieldLayout('Nested title'), scope: ['fields']};
    postSpy.mockResolvedValue({data: {form: nested}});

    const refreshed = await editor.refreshLayout({}, ['fields']);

    expect(postSpy.mock.calls[0]?.[2]?.headers).toMatchObject({
      'X-Craft-Form-Scope': JSON.stringify(['fields']),
    });
    expect(refreshed).toEqual(nested);
    expect(editor.formPayload.value?.values).toEqual({
      title: 'Original title',
    });
  });

  it('rejects a layout refresh that a newer one superseded', async () => {
    const first = deferred<{data: {form: FormPayload}}>();
    postSpy
      .mockImplementationOnce(() => first.promise)
      .mockResolvedValueOnce({data: {form: fieldLayout('Latest title')}});
    const {editor} = mount(
      payload({canAutosave: false, form: fieldLayout('Original title')})
    );

    const stale = editor.refreshLayout({}, []);
    await editor.refreshLayout({}, []);
    first.resolve({data: {form: fieldLayout('Stale title')}});

    await expect(stale).rejects.toThrow();
    expect(editor.formPayload.value?.values).toEqual({title: 'Latest title'});
  });

  it('ignores a refresh that predates an authoritative page payload', async () => {
    const refresh = deferred<{data: {form: FormPayload}}>();
    postSpy.mockImplementationOnce(() => refresh.promise);
    const {editor, page} = mount(
      payload({canAutosave: false, form: fieldLayout('Original title')})
    );

    const pendingRefresh = editor.refreshForm();
    page.props = payload({
      canAutosave: false,
      form: fieldLayout('Saved title'),
    });
    await nextTick();

    refresh.resolve({data: {form: fieldLayout('Stale title')}});
    await pendingRefresh;
    await nextTick();

    expect(editor.formPayload.value?.values).toEqual({title: 'Saved title'});
  });

  it('reloads the owner when another tab reorders the same draft, but not an unrelated draft', async () => {
    const broadcaster = new EventTarget();
    vi.stubGlobal('Craft', {broadcaster});
    const slideout = slideoutController();
    mount(payload({draftId: 7}), slideout);
    await nextTick();

    for (const data of [
      {canonicalId: 12, draftId: 8},
      {canonicalId: 13, draftId: 7},
    ]) {
      broadcaster.dispatchEvent(
        new MessageEvent('message', {
          data: {event: 'reorderNestedElements', ...data},
        })
      );
    }
    expect(slideout.reload).not.toHaveBeenCalled();

    broadcaster.dispatchEvent(
      new MessageEvent('message', {
        data: {event: 'reorderNestedElements', canonicalId: 12, draftId: 7},
      })
    );
    expect(slideout.reload).toHaveBeenCalledOnce();
    vi.unstubAllGlobals();
  });

  it('defers a matching reorder reload while a slideout has unsaved changes', async () => {
    const broadcaster = new EventTarget();
    vi.stubGlobal('Craft', {broadcaster});
    const slideout = slideoutController();
    const {editor} = mount(
      payload({
        canAutosave: false,
        draftId: 7,
        form: fieldLayout('Original title'),
      }),
      slideout
    );
    await typeTitle('Unsaved title');

    broadcaster.dispatchEvent(
      new MessageEvent('message', {
        data: {event: 'reorderNestedElements', canonicalId: 12, draftId: 7},
      })
    );
    await nextTick();

    expect(slideout.reload).not.toHaveBeenCalled();
    expect(editor.form.isDirty).toBe(true);
    expect(titleInput().value).toBe('Unsaved title');
    vi.unstubAllGlobals();
  });

  it('defers a matching reorder reload until the latest autosave is acknowledged', async () => {
    const broadcaster = new EventTarget();
    vi.stubGlobal('Craft', {broadcaster});
    const slideout = slideoutController();
    const {editor} = mount(
      payload({
        canAutosave: true,
        draftId: 7,
        form: fieldLayout('Original title'),
      }),
      slideout
    );

    await typeTitle('First saved title');
    await editor.autosave.save();
    await typeTitle('Pending title');

    broadcaster.dispatchEvent(
      new MessageEvent('message', {
        data: {event: 'reorderNestedElements', canonicalId: 12, draftId: 7},
      })
    );
    await nextTick();

    expect(editor.autosave.status.value).toBe('saved');
    expect(editor.autosave.hasPendingChanges.value).toBe(true);
    expect(slideout.reload).not.toHaveBeenCalled();
    expect(titleInput().value).toBe('Pending title');

    await editor.autosave.save();
    await nextTick();

    expect(editor.autosave.hasPendingChanges.value).toBe(false);
    expect(slideout.reload).toHaveBeenCalledOnce();
    vi.unstubAllGlobals();
  });

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
          editorActions: {
            primary: {
              label: 'Save',
              actionUrl: null,
              params: {},
              redirect: null,
              tabId: null,
            },
            menu: [],
            buttons: [],
          },
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

  it('starts a workflow draft when Save beats the autosave debounce', async () => {
    vi.useFakeTimers();

    const {editor} = mount(
      payload({
        canAutosave: true,
        form: fieldLayout('Canonical title'),
        workflow: {
          current: null,
          draftReviews: [],
        },
        editorActions: {
          primary: {
            label: 'Save',
            actionUrl: '/actions/elements/save-draft',
            params: {
              elementType: 'craft\\elements\\Entry',
              elementId: 12,
              siteId: 1,
              dropProvisional: 1,
              workflowSave: 1,
            },
            redirect: null,
            tabId: null,
          },
          menu: [],
          buttons: [],
        },
      })
    );

    await typeTitle('Edited title');

    const save = interceptSave();
    editor.save();

    expect(save.last().url).toBe('/actions/elements/save-draft');
    expect(save.last().data).toMatchObject({
      elementType: 'craft\\elements\\Entry',
      elementId: 12,
      siteId: 1,
      dropProvisional: 1,
      workflowSave: 1,
    });
    expect(save.last().data).not.toHaveProperty('draftId');

    await vi.advanceTimersByTimeAsync(5000);
    expect(postSpy).not.toHaveBeenCalled();

    save.restore();
    vi.useRealTimers();
  });

  it('saves a workflow draft with Cmd+S when the primary button opens its review tab', () => {
    mount(
      payload({
        draftId: 9,
        editorActions: {
          primary: {
            label: 'Request review',
            actionUrl: '/actions/elements/save-draft',
            params: {
              elementType: 'craft\\elements\\Entry',
              elementId: 12,
              siteId: 1,
              dropProvisional: 1,
              workflowSave: 1,
            },
            redirect: null,
            tabId: 'workflow',
          },
          menu: [],
          buttons: [],
        },
      })
    );
    const save = interceptSave();

    window.dispatchEvent(
      new KeyboardEvent('keydown', {key: 's', metaKey: true})
    );

    expect(save.last().url).toBe('/actions/elements/save-draft');
    expect(save.last().data).toMatchObject({
      draftId: 9,
      dropProvisional: 1,
      workflowSave: 1,
    });

    save.restore();
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
  it('follows the server onto the workflow draft an alternate action created', async () => {
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
        workflow: {
          current: {} as CraftCms.Cms.Workflow.Data.WorkflowReviewData,
          draftReviews: [],
        },
        editorActions: {
          primary: {
            label: 'Save draft',
            actionUrl: '/actions/elements/save-draft',
            params: {
              elementType: 'craft\\elements\\Entry',
              elementId: 12,
              siteId: 1,
              dropProvisional: 1,
              workflowSave: 1,
            },
            redirect: null,
            tabId: null,
          },
          menu: [],
          buttons: [],
        },
      })
    );

    expect(editor.autosave.draftId.value).toBe(9);

    // From here ordinary saves preserve the named draft for review rather than
    // applying it to the canonical element.
    editor.save();

    expect(save.last().url).toBe('/actions/elements/save-draft');
    expect(save.last().data).toMatchObject({draftId: 9, dropProvisional: 1});
    expect(save.last().data).not.toHaveProperty('provisional');

    save.restore();
  });

  it('submits an approved draft action without resaving form values', async () => {
    const {editor} = mount(
      payload({
        draftId: 9,
        form: fieldLayout('Reviewed title'),
      })
    );
    const save = interceptSave();

    await typeTitle('Unapproved browser value');
    editor.submitAction({
      label: 'Apply approved draft',
      actionUrl: '/actions/elements/apply-draft',
      params: {
        workflowRunId: 4,
        workflowCurrentStage: 1,
      },
      redirect: null,
      includeFormData: false,
    });

    expect(save.last().url).toBe('/actions/elements/apply-draft');
    expect(save.last().data).toEqual({
      elementType: 'craft\\elements\\Entry',
      elementId: 12,
      draftId: 9,
      siteId: 1,
      workflowRunId: 4,
      workflowCurrentStage: 1,
    });

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

  describe('nested elements in a slideout', () => {
    const nestedContext = {fieldId: 3, ownerId: 40};

    afterEach(() => {
      vi.restoreAllMocks();
      vi.unstubAllGlobals();
    });

    function stubSaveRequest(response: () => Promise<unknown>) {
      return vi.spyOn(axios, 'request').mockImplementation(response as never);
    }

    /** A panel whose opener registered `onSaved`, as every opener here does. */
    function handledSlideout() {
      const slideout = slideoutController();
      slideout.saved.mockReturnValue(true);

      return slideout;
    }

    it('sends the owner it was opened through with every draft save', async () => {
      const {editor} = mount(
        payload({canAutosave: true, nestedContext, fresh: true}),
        slideoutController()
      );

      await editor.autosave.save();

      expect(postSpy.mock.calls[0]![1]).toMatchObject({
        fieldId: 3,
        ownerId: 40,
        fresh: 1,
      });
    });

    it('reports autosaved drafts to the opener', async () => {
      const slideout = slideoutController();
      const {editor} = mount(payload({canAutosave: true}), slideout);

      await editor.autosave.save();

      expect(slideout.saved).toHaveBeenCalledWith({
        draft: true,
        data: {draftId: 7},
      });
    });

    it('saves into the owner draft the opener prepares, then closes', async () => {
      const slideout = handledSlideout();
      const prepareNestedOwner = vi.fn().mockResolvedValue(73);
      Object.assign(slideout.instance, {prepareNestedOwner});
      const request = stubSaveRequest(() =>
        Promise.resolve({data: {element: {id: 12}}})
      );
      const {editor} = mount(
        payload({
          canAutosave: true,
          nestedContext,
          saveForDerivativeUrl:
            '/actions/elements/save-nested-element-for-derivative',
        }),
        slideout
      );

      editor.save({redirect: false});

      await vi.waitFor(() => expect(request).toHaveBeenCalledOnce());
      expect(prepareNestedOwner).toHaveBeenCalledOnce();
      // The nested element has no draft yet, so one is made to move.
      expect(postSpy).toHaveBeenCalledOnce();
      expect(request.mock.calls[0]![0]).toMatchObject({
        url: '/actions/elements/save-nested-element-for-derivative',
        data: expect.objectContaining({
          newOwnerId: 73,
          draftId: 7,
          fieldId: 3,
          ownerId: 40,
        }),
      });
      await vi.waitFor(() =>
        expect(slideout.close).toHaveBeenCalledWith({force: true})
      );
    });

    it('saves normally when the opener has no owner draft to save into', async () => {
      const slideout = handledSlideout();
      Object.assign(slideout.instance, {
        prepareNestedOwner: vi.fn().mockResolvedValue(undefined),
      });
      const request = stubSaveRequest(() => Promise.resolve({data: {}}));
      const {editor} = mount(payload({nestedContext}), slideout);

      editor.save();

      await vi.waitFor(() => expect(request).toHaveBeenCalledOnce());
      expect(request.mock.calls[0]![0]).toMatchObject({
        url: '/actions/entries/save-entry',
        data: expect.not.objectContaining({newOwnerId: expect.anything()}),
      });
    });

    it('announces invalid nested elements when a save fails', async () => {
      const displayError = vi.fn();
      vi.stubGlobal('Craft', {cp: {displayError}});
      const root = document.createElement('div');
      document.body.append(root);
      const listener = vi.fn();
      root.addEventListener('craft:nested-validation', listener);
      // A plain stand-in rather than a spy: a spy tracks the rejected promise
      // it returns, and reports that as unhandled.
      const request = axios.request;
      axios.request = (() =>
        Promise.reject(
          new axios.AxiosError('Bad Request', '400', undefined, undefined, {
            status: 400,
            data: {
              message: 'Couldn’t save entry.',
              errors: {title: ['Title cannot be blank.']},
              invalidNestedElementIds: [5],
            },
          } as never)
        )) as never;
      onTestFinished(() => {
        axios.request = request;
      });
      const {editor} = mount(payload(), handledSlideout(), {
        root: () => root,
      });

      editor.save();

      await vi.waitFor(() => expect(listener).toHaveBeenCalledOnce());
      expect((listener.mock.calls[0]![0] as CustomEvent).detail).toEqual({
        ids: [5],
      });
      expect(displayError).toHaveBeenCalledWith('Couldn’t save entry.');
      root.remove();
    });

    it('announces a finished save to the rest of the CP', async () => {
      const displaySuccess = vi.fn();
      const postMessage = vi.fn();
      const refresh = vi.fn();
      vi.stubGlobal('Craft', {
        cp: {displaySuccess},
        broadcaster: {
          postMessage,
          addEventListener: vi.fn(),
          removeEventListener: vi.fn(),
        },
        Preview: {refresh},
      });
      stubSaveRequest(() =>
        Promise.resolve({
          data: {message: 'Entry saved.', element: {id: 12}},
        })
      );
      const {editor} = mount(payload(), handledSlideout());

      editor.save();

      await vi.waitFor(() => expect(displaySuccess).toHaveBeenCalled());
      expect(displaySuccess.mock.calls[0]![0]).toBe('Entry saved.');
      expect(postMessage).toHaveBeenCalledWith({
        event: 'saveElement',
        id: 12,
      });
      expect(refresh).toHaveBeenCalled();
    });
  });
});
