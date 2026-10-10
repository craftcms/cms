import {isRecord, pathsMatch, visitControls} from '@/modules/ui/runtime';
import {
  nestedOwnerContext,
  type NestedOwnerEditor,
  type NestedOwnerEditorRequest,
  NESTED_OWNER_EDITOR_REQUEST,
} from '@/modules/elements/nested-owner';
import {toReactive, useEventListener} from '@vueuse/core';
import {router, useForm} from '@inertiajs/vue3';
import {actionClient, appendBodyHtml, appendHeadHtml, t} from '@craftcms/ui';
import {computed, nextTick, onBeforeUnmount, ref, shallowRef, watch} from 'vue';
import {
  useScreenPageProps,
  type ScreenPageProps,
} from '@/common/composables/screen';
import {useSlideout} from '@/common/slideouts/useSlideout';
import type {UiChangeKind, UiPayload, UiValues} from '@/modules/ui/types';
import type {
  ElementActionBehavior,
  ElementActionMenuItem,
} from '@/modules/elements/composables/useElementActionMenu';
import {useInertiaUiRenderer} from '@/modules/ui/useInertiaUiRenderer';
import {useElementAutosave} from '@/modules/elements/composables/useElementAutosave';
import {useElementActivity} from '@/modules/elements/composables/useElementActivity';
import {useSiteStatuses} from '@/modules/elements/composables/useSiteStatuses';
import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
import {provideUiValueGroup} from '@/modules/ui/uiValueGroup';
import UpdateFieldLayoutController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/UpdateFieldLayoutController';

export interface ElementEditFormData {
  typeId?: string | number | null;
  enabled?: string | number | boolean | null;
  enabledForSite?: Record<string, string | number | boolean | null>;
  provisional?: number;
  dropProvisional?: number;
  workflowSave?: number;
  redirect?: string;
}

/**
 * A save the screen can perform besides the plain Save button — an alternate
 * save action, or a header button like "Create a draft".
 *
 * `actionUrl` of `null` means "the screen's ordinary save target", which
 * depends on whether a provisional draft exists by the time it's submitted.
 */
export interface ElementFormAction {
  label: string;
  actionUrl: string | null;
  params: UiValues;
  includeFormData?: boolean;
  disabled?: boolean;
  disabledReason?: string | null;
  /** Pre-encrypted by the server; the save controllers decrypt it. */
  redirect: string | null;
  variant?: string;
  shortcut?: boolean;
  shift?: boolean;
}

export type ElementFormActionSubmitter = (action: ElementFormAction) => void;

export interface ElementPrimaryAction extends ElementFormAction {
  panelId: string | null;
}

export interface ElementEditorActions {
  primary: ElementPrimaryAction;
  menu: Array<ElementFormAction>;
  buttons: Array<ElementFormAction>;
}

/** A button the element type or a plugin adds to the footer, beside the save controls. */
export interface ElementAdditionalButton {
  label: string;
  icon?: string;
  variant?: string;
  behavior: ElementActionBehavior;
}

/** Somewhere on the front end the element can be viewed. */
export interface ElementPreviewTarget {
  label: string;
  url: string;
  icon?: string;
}

/**
 * An entry in the drafts-and-revisions switcher. Groups arrive flattened, with
 * `heading` rows standing in for the nesting the action menu has no shape for.
 */
export interface ElementContextMenuItem {
  type: 'link' | 'heading' | 'hr';
  label?: string;
  description?: string;
  href?: string;
  selected?: boolean;
}

/** The shared payload every {@link ElementEditViewModel} emits. */
export interface ElementEditPayload {
  editorContainerId?: string;
  elementId: number | null;
  canonicalId: number | null;
  elementType: string;
  siteId: number | null;
  fieldLayoutId: number | null;
  title: string;
  docTitle: string;
  crumbs: Array<{label: string; url?: string}>;
  readOnly: boolean;
  ui: UiPayload | null;
  sidebarUi: UiPayload | null;
  metadataHtml: string | null;
  /** The element's status badge. `null` for element types without statuses. */
  statusLabelHtml: string | null;
  saveUrl: string;
  /** The element's own control panel edit page, if it has one. */
  cpEditUrl?: string | null;
  applyDraftUrl: string;
  editorActions: ElementEditorActions;
  additionalButtons: Array<ElementAdditionalButton>;
  autosaveUrl: string;
  discardDraftUrl: string;
  saveForDerivativeUrl?: string;
  /** The owner a nested element is being edited through. See `contextParams()`. */
  nestedContext?: {fieldId: number | null; ownerId: number} | null;
  /** A brand-new element being filled in for the first time. */
  fresh?: boolean;
  isProvisionalDraft: boolean;
  draftId: number | null;
  canAutosave: boolean;
  notice: string | null;
  mergeNotice: string | null;
  canDiscardDraft: boolean;
  actionMenu: Array<ElementActionMenuItem>;
  previewTargets: Array<ElementPreviewTarget>;
  elementDisplayName: string;
  activityUrl: string | null;
  activityTimelineUrl: string | null;
  activityPageUrl: string | null;
  updatedTimestamps: {element: number | null; canonical: number | null};
  contextMenu: {
    label: string;
    items: Array<ElementContextMenuItem>;
  } | null;
  workflow: {
    convertedToDraft?: boolean;
    current: CraftCms.Cms.Workflow.Data.WorkflowReviewData | null;
    draftReviews: CraftCms.Cms.Workflow.Data.WorkflowDraftReviewData[];
  };
}

type IncomingElementEditPayload = Omit<ElementEditPayload, 'workflow'> & {
  workflow?: ElementEditPayload['workflow'];
};

export type ElementEditPayloadUpdater = (
  patch:
    | Partial<ElementEditPayload>
    | ((payload: ElementEditPayload) => Partial<ElementEditPayload>)
) => void;

const emptyWorkflow = {
  convertedToDraft: false,
  current: null,
  draftReviews: [],
};

/*
 * `origin/6.x` added an `isElementEditPayload()` guard here that threw when the
 * merged payload was missing any key. It is dropped rather than merged: this
 * branch lets `pageProps()` read a slideout panel's own props, which are a
 * partial payload by design, so the guard rejected every slideout screen and
 * failed 14 of this composable's tests. Worth reinstating once the slideout
 * path supplies a complete payload.
 */
interface Options {
  /**
   * Identity and element-type attributes merged into every submission —
   * whatever the type's save action needs to resolve the element it's saving.
   */
  saveData?: () => UiValues;
  /** Adapts form state before autosave and explicit submissions. */
  transform?: (data: object) => UiValues;
  /**
   * Where the screen's content is rendered. Failed saves announce invalid
   * nested elements from here, so the nested element fields inside hear it.
   */
  root?: () => HTMLElement | null | undefined;
}

/**
 * Drives an element edit screen: bridges the compiled field layout into an
 * Inertia form, collects the still-server-rendered sidebar meta fields, and
 * owns the unsaved-changes guard and saving. Tabs belong to `UiRenderer`.
 *
 * Element-type pages supply only what their save action needs via
 * {@link Options.saveData}; everything else comes from the shared payload.
 */
export function useElementEditor({saveData, root, transform}: Options = {}) {
  // Not `usePage()`: inside a slideout that's the page *behind* the panel, so
  // the editor would read the index's props and find no payload at all. This
  // resolves to the panel's own props there, and to `usePage()` on a full page.
  const pageProps = useScreenPageProps();
  const slideout = useSlideout();

  // Autosave answers with the edit screen as the server now sees it, and page
  // props are only replaced by a visit — so what it returns is held here and
  // read over the top of them. The first autosave of a canonical element
  // creates a provisional draft, and the notice, the Discard changes button and
  // the drafts menu all belong to the draft rather than to the page that
  // loaded. Dropped again as soon as a visit brings a newer payload.
  const savedScreen = shallowRef<Partial<ElementEditPayload> | null>(null);

  // Read through a computed rather than capturing the props object: it's
  // replaced wholesale on each visit, and saving in place — the normal path for
  // an element with no drafts — does that without remounting this component, so
  // the title, notices, and timestamps below have to track the live payload.
  const props = toReactive(
    computed(() => {
      const page = pageProps() as unknown as IncomingElementEditPayload;

      return {
        ...page,
        ...savedScreen.value,
        workflow: savedScreen.value?.workflow ?? page.workflow ?? emptyWorkflow,
      };
    })
  );

  const editingReviewedDraft = shallowRef(false);
  const workflowReviewLocked = computed(
    () =>
      !editingReviewedDraft.value &&
      ['pending', 'approved'].includes(props.workflow?.current?.status ?? '')
  );

  watch(
    () => props.workflow?.current?.status,
    (status, previousStatus) => {
      if (
        status !== previousStatus &&
        (status === 'pending' || status === 'approved')
      ) {
        editingReviewedDraft.value = false;
      }
    }
  );

  function startEditingReviewedDraft(): void {
    editingReviewedDraft.value = true;
  }

  // The field layout comes back on the response separately from the screen
  // payload — scoped to whatever the request asked for — and applying it is the
  // only way a nested element the save just created (a new Matrix entry or
  // address) receives its own UI payload. Until it does, the block has
  // nothing to render but a spinner.
  const savedUi = shallowRef<UiPayload | null>(null);
  const uiPayload = computed(() => savedUi.value ?? props.ui);
  const sidebarPayload = computed(() => props.sidebarUi);
  const form = useForm<ElementEditFormData>({});
  let refreshGeneration = 0;
  let nestedElementsReloadPending = false;

  function invalidateUiRefreshes(): void {
    refreshGeneration++;
  }

  provideUiValueGroup();

  // Two bridges share one Inertia form. Each only ever deletes the root keys
  // it wrote itself, and both are constructed here — before either receives a
  // mutation — so neither can claim the other's keys.
  const {
    advanceBaseline,
    errors,
    onMutation: onLayoutMutation,
    renderer,
    resetValues,
    values,
  } = useInertiaUiRenderer(form, uiPayload);

  const {
    advanceBaseline: advanceSidebarBaseline,
    errors: sidebarErrors,
    onMutation: onSidebarFormMutation,
    renderer: sidebarRenderer,
    resetValues: resetSidebarValues,
  } = useInertiaUiRenderer(form, sidebarPayload);

  useSiteStatuses(form);

  /**
   * Params every request about the element carries besides its identity.
   *
   * A nested element resolves through its primary owner unless it's told
   * otherwise, so each request names the owner it's being edited through —
   * which may be a draft of it. A fresh element says so on each save, so the
   * server propagates it to all of its sites.
   */
  function contextParams(): UiValues {
    return {
      ...props.nestedContext,
      ...(props.fresh ? {fresh: 1} : {}),
      ...(props.editorContainerId
        ? {editorContainerId: props.editorContainerId}
        : {}),
    };
  }

  const preparingOwnerGroups = new Map<symbol, string[]>();
  const draftElementIds = new Map<number, number>();

  const autosave = useElementAutosave(form, {
    url: props.autosaveUrl,
    params: contextParams,
    elementType: props.elementType,
    elementId: props.canonicalId,
    siteId: props.siteId,
    draftId: props.draftId,
    isProvisional: props.isProvisionalDraft,
    enabled: props.canAutosave,
    transform: (data) =>
      requestValues(
        preparingOwnerGroups.size
          ? {
              ...data,
              ...renderer.value?.mutation([...preparingOwnerGroups.values()]),
            }
          : data
      ),
    // Autosave moves the draft's `dateUpdated` without a visit, so the poller
    // has to re-baseline against what the save just wrote — otherwise it reads
    // our own keystrokes back as an edit from elsewhere. `activity` is
    // initialized just below; this only ever runs after a save settles.
    onSaved: (timestamps, response) => {
      if (isRecord(response.draftElementIds)) {
        for (const [canonicalId, draftId] of Object.entries(
          response.draftElementIds
        )) {
          if (typeof draftId === 'number') {
            draftElementIds.set(Number(canonicalId), draftId);
          }
        }
      }

      activity.rebase(timestamps);
      // The draft is what an opener shows until the element is saved, so it
      // hears about each one — debounced on its side.
      slideout?.saved({draft: true, data: response as ScreenPageProps});
    },
  });

  /**
   * Whether the draft the screen is working against is provisional — either it
   * loaded that way, or autosave created one on a canonical element (autosave
   * only ever creates provisional drafts).
   *
   * Declared above the poller because its first poll runs during setup.
   */
  const draftIsProvisional = computed(
    () =>
      props.isProvisionalDraft ||
      (props.draftId === null && autosave.draftId.value !== null)
  );

  const activity = useElementActivity({
    url: props.activityUrl,
    elementType: props.elementType,
    elementId: props.canonicalId,
    // Autosave can create a provisional draft mid-session, and from then on
    // that's the element the screen is editing — so the poll has to ask about
    // it, not the canonical, or its timestamps won't be the ones the autosave
    // response re-baselined against.
    draftId: () => autosave.draftId.value ?? props.draftId,
    siteId: props.siteId,
    isProvisionalDraft: () => draftIsProvisional.value,
    updatedTimestamps: props.updatedTimestamps,
  });

  useEventListener(
    () => window.Craft?.broadcaster,
    'message',
    (event: MessageEvent) => {
      const data = event.data;
      const draftId = autosave.draftId.value ?? props.draftId;
      if (
        data.event === 'reorderNestedElements' &&
        data.canonicalId === props.canonicalId &&
        (data.draftId === draftId || (data.isProvisionalDraft && !draftId))
      ) {
        if (slideout) {
          if (
            form.processing ||
            autosave.hasPendingChanges.value ||
            hasUnsavedChanges()
          ) {
            nestedElementsReloadPending = true;
            return;
          }

          void slideout.reload();
        } else {
          router.reload();
        }
      }
    }
  );

  // Applying a draft consumes it, and Inertia preserves this component across
  // the visit, so the server's view of the draft is authoritative afterwards.
  watch(
    () => props.draftId,
    (draftId) => autosave.setDraftId(draftId)
  );

  /**
   * Whether what autosave just returned is being handed to the renderers.
   * Reconciling a layout emits a mutation of its own, but that's the server
   * echoing what it already saved rather than a fresh edit, so it must not
   * re-arm autosave — which would save again, and never settle.
   */
  let applyingSavedPayload = false;

  /**
   * How many reverts are in progress. Discarding a draft replaces the values
   * the renderers are holding, which emits mutations like any other write —
   * but they describe values the user has just thrown away, so they must not
   * arm autosave and recreate the draft the discard deleted.
   *
   * A count rather than a flag: a discard reverts once against the payload the
   * page already has and again when the reload lands, and the two overlap.
   */
  let reverting = 0;

  watch(
    [() => autosave.ui.value, () => autosave.screen.value],
    ([form, screen]) => {
      if (!form && !screen) {
        return;
      }

      invalidateUiRefreshes();
      applyingSavedPayload = true;

      if (form) {
        savedUi.value = form;
      }

      if (screen) {
        const payload: Partial<ElementEditPayload> = {};
        Object.assign(payload, screen);
        savedScreen.value = payload;
      }

      // Released after the flush, so the renderers' pre-flush reconcile — and
      // the mutations it emits — are covered.
      void nextTick(() => (applyingSavedPayload = false));
    },
    {flush: 'sync'}
  );

  // A payload arriving by Inertia visit is the newer of the two; drop what
  // autosave stashed so it stops shadowing it. Keyed on the props object
  // itself, which Inertia replaces on every visit and the slideout store
  // reassigns on every panel load — `props` here is the merged view, so
  // watching that would see the overlay's own writes.
  watch(
    () => pageProps(),
    () => {
      invalidateUiRefreshes();
      draftElementIds.clear();
      savedUi.value = null;
      savedScreen.value = null;
      autosave.clearSaved();
    }
  );

  // The renderers' change callbacks are the authoritative "content changed"
  // signal, so autosave hangs off them rather than watching the form — and off
  // whether the values actually differ from the server's, not off having been
  // told a control changed.
  function onMutation(
    mutation: UiPayload['values'],
    kind: UiChangeKind = 'discrete'
  ): void {
    const changed = onLayoutMutation(mutation);

    if (!applyingSavedPayload && !reverting && changed) {
      autosave.schedule(kind);
    }
  }

  function onSidebarMutation(
    mutation: UiPayload['values'],
    kind: UiChangeKind = 'discrete'
  ): void {
    const changed = onSidebarFormMutation(mutation);

    // The screen payload carries the sidebar form too, so reconciling it emits
    // mutations here for the same reason the field layout does.
    if (!applyingSavedPayload && !reverting && changed) {
      autosave.schedule(kind);
    }
  }

  function resolveNestedElementId(id: number): number {
    return draftElementIds.get(id) ?? id;
  }

  const nestedOwnerEditor: NestedOwnerEditor = {
    async prepare(path, initialContext) {
      if (
        props.readOnly ||
        workflowReviewLocked.value ||
        !(nestedOwnerContext(uiPayload.value, path) ?? initialContext)
      ) {
        return null;
      }

      const preparation = Symbol();

      try {
        if (props.canAutosave) {
          visitControls(uiPayload.value?.nodes ?? [], (control) => {
            if (
              control.mode === 'editable' &&
              control.path.every((segment, index) => path[index] === segment) &&
              !pathsMatch(control.deltaGroup, path)
            ) {
              preparingOwnerGroups.set(preparation, control.deltaGroup);
            }
          });

          await autosave.save();
          if (autosave.status.value !== 'saved') {
            return null;
          }
          await nextTick();
        }

        const context =
          nestedOwnerContext(uiPayload.value, path) ?? initialContext;
        if (!context) {
          return null;
        }

        const ownerId = resolveNestedElementId(context.ownerId);
        return {
          ...context,
          ownerId,
          ownerIsDerivative:
            ownerId !== context.ownerId || context.ownerIsDerivative,
          ownerIsInDerivativeTree:
            ownerId !== context.ownerId || context.ownerIsInDerivativeTree,
          requiresDerivative: Boolean(props.canAutosave),
          canonicalId: props.canonicalId,
          draftId: props.draftId,
          isProvisionalDraft: props.isProvisionalDraft,
        };
      } finally {
        preparingOwnerGroups.delete(preparation);
      }
    },
    resolveElementId: resolveNestedElementId,
    refresh: refreshAfterNestedChange,
  };

  useEventListener(
    () => root?.(),
    NESTED_OWNER_EDITOR_REQUEST,
    (event: NestedOwnerEditorRequest) => {
      event.detail.editor = nestedOwnerEditor;
      event.stopPropagation();
    }
  );

  function requestValues(data: object): UiValues {
    return transform?.(data) ?? ({...data} as UiValues);
  }

  /**
   * Re-renders the field layout from the server, keeping unsaved values.
   *
   * Resolves with the server's response once it's been applied, or nothing
   * when a newer refresh superseded it.
   */
  async function refreshUi(
    scope: string[] = uiPayload.value?.scope ?? []
  ): Promise<UiValues | undefined> {
    const generation = ++refreshGeneration;
    const rootScope = uiPayload.value?.scope ?? [];
    const currentValues = renderer.value?.currentValues() ?? values.value;
    const {data: response} = await actionClient.post(
      UpdateFieldLayoutController.url(),
      {
        ...saveData?.(),
        ...currentValues,
        ...contextParams(),
        elementType: props.elementType,
        elementId: props.elementId,
        draftId: autosave.draftId.value ?? props.draftId,
        siteId: props.siteId,
        provisional: draftIsProvisional.value ? 1 : null,
      },
      {
        headers: {
          'X-Craft-Ui-Root-Scope': JSON.stringify(rootScope),
          'X-Craft-Ui-Scope': JSON.stringify(scope),
        },
      }
    );

    if (generation !== refreshGeneration) {
      return;
    }

    if (!response.ui) {
      throw new Error('The Element Editor did not return a UI payload.');
    }

    await appendHeadHtml(response.headHtml);

    if (generation !== refreshGeneration) {
      return;
    }

    // A nested scope's payload is only that form; the renderer that asked for
    // it reconciles it into the layout.
    if (JSON.stringify(scope) === JSON.stringify(rootScope)) {
      applyingSavedPayload = true;
      savedUi.value = response.ui;
      await nextTick();
      applyingSavedPayload = false;
    }

    if (generation !== refreshGeneration) {
      return;
    }

    await appendBodyHtml(response.bodyHtml);

    return response;
  }

  /**
   * Refreshes the field layout for the renderer's reactive controls.
   */
  async function refreshLayout(
    _values: UiValues,
    scope?: string[]
  ): Promise<UiPayload> {
    const response = await refreshUi(scope);

    if (!response) {
      throw new Error('A newer refresh superseded this one.');
    }

    return response.ui as UiPayload;
  }

  // Set for the duration of one submission when an alternate action owns it,
  // so the shared save pipeline (elevated sessions, error handling, the
  // processing flag) is reused rather than reimplemented per action.
  const pendingAction = ref<ElementFormAction | null>(null);
  const activityTimelineVersion = ref(0);

  /**
   * The owner draft a nested element's changes are being saved into, when the
   * opener asked for that. Set just before each save; see `prepareNestedOwner`.
   */
  const nestedOwnerId = ref<number | null>(null);

  async function prepareNestedOwner(): Promise<boolean> {
    nestedOwnerId.value = null;

    try {
      const ownerId = await slideout!.instance.prepareNestedOwner!();

      // Element types without drafts (e.g. variants) save in place.
      if (!ownerId || !props.canAutosave) {
        return true;
      }

      // Only a draft of the nested element can be moved into the owner's draft.
      if (autosave.draftId.value === null) {
        await autosave.save();
      }

      if (autosave.draftId.value === null) {
        throw new Error(t('Could not save the nested entry draft.'));
      }

      nestedOwnerId.value = ownerId;

      return true;
    } catch (error) {
      window.Craft?.cp?.displayError(
        error instanceof Error ? error.message : t('Couldn’t save.')
      );

      return false;
    }
  }

  const {save} = useSettingsSave(
    form,
    () => ({
      url:
        (nestedOwnerId.value !== null ? props.saveForDerivativeUrl : null) ??
        pendingAction.value?.actionUrl ??
        props.editorActions.primary.actionUrl ??
        (autosave.draftId.value !== null ? props.applyDraftUrl : props.saveUrl),
      method: 'post' as const,
    }),
    {
      keyboardShortcutEnabled: () => !props.readOnly,
      transform: (data) => {
        // Identity first, so the form wins where they overlap: the entry type
        // can be changed in the sidebar, and `saveData()` only knows the one
        // the page was rendered with.
        const transformed =
          pendingAction.value?.includeFormData === false
            ? {}
            : {...saveData?.(), ...requestValues(data)};
        // Shared `elements/*` actions need generic identity params: the
        // type-specific ones (an entry's `entryId`) mean nothing there.
        if (autosave.draftId.value !== null) {
          Object.assign(transformed, {
            elementType: props.elementType,
            elementId: props.canonicalId,
            siteId: props.siteId,
          });
          if (autosave.draftId.value !== null) {
            Object.assign(transformed, {draftId: autosave.draftId.value});
            if (draftIsProvisional.value) transformed.provisional = 1;
          }
        }

        Object.assign(transformed, contextParams());
        if (nestedOwnerId.value !== null) {
          Object.assign(transformed, {newOwnerId: nestedOwnerId.value});
        }

        const action = pendingAction.value ?? props.editorActions.primary;
        Object.assign(transformed, action.params);
        if (action.redirect) {
          transformed.redirect = action.redirect;
        }

        return transformed;
      },
      prepare: slideout?.instance.prepareNestedOwner
        ? prepareNestedOwner
        : undefined,
      // Saved into the owner's draft, the nested draft this panel was editing
      // is gone, so there's nothing left to keep editing.
      forceClose: () => nestedOwnerId.value !== null,
      onError: (data) => {
        if (data?.message) {
          window.Craft?.cp?.displayError(data.message);
        }

        if (Array.isArray(data?.invalidNestedElementIds)) {
          root?.()?.dispatchEvent(
            new CustomEvent('craft:nested-validation', {
              bubbles: true,
              detail: {ids: data.invalidNestedElementIds},
            })
          );
        }
      },
      // A submission supersedes any in-flight draft write.
      onBeforeSave: () => {
        invalidateUiRefreshes();
        autosave.cancel();
      },
      onSuccess: (data) => {
        draftElementIds.clear();
        if (slideout) {
          announceSaved(data);
        }

        autosave.suspend(() => {
          advanceBaseline();
          advanceSidebarBaseline();
        });

        // Anything armed before the submission went out — the keystroke that
        // prompted it — describes values this save has just written, and
        // applying a provisional draft deletes the draft it would write them to.
        autosave.cancel();
        autosave.acknowledgeChanges();

        if (!slideout) {
          // The save itself moved the element's `dateUpdated`; without this the
          // next poll would report our own write as someone else's change.
          activity.rebase(props.updatedTimestamps);
        }

        activityTimelineVersion.value++;
      },
    }
  );

  /**
   * Submits the edit form under an alternate action — a Save-menu entry or a
   * header button. The action owns the request only until it settles, so the
   * plain Save button reverts to the normal target afterwards.
   */
  function submitAction(action: ElementFormAction): void {
    pendingAction.value = action;

    // `redirect: false` keeps `useSettingsSave` from layering the screen's own
    // redirect on top of the one this action carries.
    save({redirect: false});

    void nextTick(() => (pendingAction.value = null));
  }

  const updatePayload: ElementEditPayloadUpdater = (patch): void => {
    const updatedPayload = typeof patch === 'function' ? patch(props) : patch;

    savedScreen.value = {
      ...savedScreen.value,
      ...updatedPayload,
    };
    activityTimelineVersion.value++;
  };

  /**
   * Re-seeds both renderers from the payload the screen currently holds,
   * dropping the unsaved values on top of it and re-baselining the form.
   *
   * A refresh deliberately merges a server payload *under* the client's
   * unsaved values — the client owns those — so nothing a reload brings can
   * clear them. Only the host knows the user has abandoned them, so it says so
   * explicitly.
   */
  async function revertToLoadedPayload(): Promise<void> {
    draftElementIds.clear();
    reverting++;

    try {
      // A payload that has just arrived has a reconcile queued behind it; let
      // that land first so the reset is the last word on the values.
      await nextTick();
      resetValues();
      resetSidebarValues();
      // …and cover the mutations the reset emits in turn.
      await nextTick();
    } finally {
      reverting--;
      // Anything already on the autosave debounce — the keystroke that
      // triggered all this — describes values that no longer exist.
      autosave.cancel();
      autosave.acknowledgeChanges();
    }
  }

  /**
   * Catches the screen up with a change it made to one of its nested elements
   * — one created, saved, moved or deleted from inside it.
   *
   * Saving a nested element into this one bumps this one's `dateUpdated`.
   * That's this screen's own doing, not someone else's edit, so the activity
   * poll is re-baselined on what the server now reports rather than left to
   * flag it.
   */
  async function refreshAfterNestedChange(): Promise<void> {
    const response = await refreshUi();

    if (response && 'updatedTimestamp' in response) {
      activity.rebase({
        element: (response.updatedTimestamp as number | null) ?? null,
        canonical:
          (response.canonicalUpdatedTimestamp as number | null) ?? null,
      });
    }
  }

  /**
   * What a save from inside a panel tells the rest of the CP, since the page
   * behind it isn't reloaded: the confirmation, other tabs and element indexes
   * (via the broadcaster), and Live Preview.
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  function announceSaved(data: any): void {
    const craft = window.Craft;

    if (data?.message) {
      craft?.cp?.displaySuccess(data.message, data.notificationSettings);
    }

    if (data?.element?.id) {
      craft?.broadcaster?.postMessage({
        event: 'saveElement',
        id: data.element.id,
      });
    }

    craft?.Preview?.refresh?.();
  }

  /** Throws away the provisional draft, reverting to the canonical element. */
  async function discardDraft(): Promise<void> {
    if (autosave.draftId.value === null) {
      return;
    }

    invalidateUiRefreshes();

    // Held for the whole discard, so a mutation emitted while the screen is
    // being torn back down can't schedule a save against the deleted draft.
    reverting++;
    autosave.cancel();

    try {
      await actionClient.post(props.discardDraftUrl, {
        ...contextParams(),
        elementType: props.elementType,
        elementId: props.canonicalId,
        siteId: props.siteId,
        draftId: autosave.draftId.value,
        provisional: 1,
      });

      // The draft is gone, so anything autosave stashed about it now describes
      // something that no longer exists — drop it before the reload rather than
      // waiting for the response, or the "unsaved changes" notice it carries
      // goes on shadowing the page props for the length of the round trip.
      savedUi.value = null;
      savedScreen.value = null;
      autosave.clearSaved();
      // The draft the autosave pointer names has just been deleted; leaving it
      // set would send the next save at it, and `draftIsProvisional` reads it to
      // decide whether the screen is showing provisional changes.
      autosave.setDraftId(null);

      // Revert against the payload the page already holds, so the fields go
      // back the moment the draft is deleted rather than a round trip later —
      // and so the form is clean before the reload, which is what keeps the
      // unsaved-changes guard from prompting on the way out.
      await revertToLoadedPayload();

      // Then re-render from the canonical element rather than patching state
      // here — a screen that *loaded* as a provisional draft only gets the
      // canonical values from the server. In a panel that's the panel's own
      // request: an Inertia visit would reload the page behind it and leave the
      // discarded draft on screen.
      if (slideout) {
        await slideout.reload();
        await revertToLoadedPayload();
      } else {
        // Not awaited: `router.reload()` doesn't resolve, and the visit's own
        // callbacks are the only way to know the payload has landed.
        router.reload({onFinish: () => void revertToLoadedPayload()});
      }
    } finally {
      reverting--;
    }
  }

  // Both the field layout and the sidebar feed the same Inertia form, so its
  // own dirty state covers the whole screen.
  //
  // When autosave is running, edits are already safe in a provisional draft, so
  // only warn if it hasn't caught up — mid-save, or after a failed write.
  function hasUnsavedChanges(): boolean {
    if (form.processing || !form.isDirty) {
      return false;
    }

    return !props.canAutosave || autosave.status.value !== 'saved';
  }

  if (slideout) {
    watch(
      [
        () => form.processing,
        () => form.isDirty,
        autosave.status,
        autosave.hasPendingChanges,
      ],
      () => {
        if (
          nestedElementsReloadPending &&
          !form.processing &&
          !autosave.hasPendingChanges.value &&
          !hasUnsavedChanges()
        ) {
          nestedElementsReloadPending = false;
          void slideout.reload();
        }
      }
    );
  }

  // Only on a full page. A panel's unsaved changes are the slideout store's to
  // guard — it prompts on close and on being replaced — and these would fire on
  // the base page's own navigation, including the reload that follows a
  // successful save from inside the panel.
  if (!slideout) {
    useEventListener(window, 'beforeunload', (event) => {
      if (hasUnsavedChanges()) {
        event.preventDefault();
      }
    });

    const removeNavigationGuard = router.on('before', (event) => {
      const visit = event.detail.visit;

      if (
        visit.method === 'get' &&
        !visit.prefetch &&
        hasUnsavedChanges() &&
        !window.confirm(t('Any changes will be lost if you leave this page.'))
      ) {
        event.preventDefault();
      }
    });

    onBeforeUnmount(removeNavigationGuard);
  }

  onBeforeUnmount(invalidateUiRefreshes);
  onBeforeUnmount(autosave.cancel);

  return {
    activity,
    activityTimelineVersion,
    autosave,
    discardDraft,
    submitAction,
    errors,
    form,
    uiPayload,
    onMutation,
    onSidebarMutation,
    props,
    renderer,
    nestedOwnerEditor,
    refreshUi,
    refreshLayout,
    save,
    sidebarErrors,
    sidebarPayload,
    sidebarRenderer,
    startEditingReviewedDraft,
    updatePayload,
    values,
    workflowReviewLocked,
  };
}
