import {toReactive, useEventListener} from '@vueuse/core';
import {router, useForm} from '@inertiajs/vue3';
import {actionClient, t} from '@craftcms/ui';
import {computed, nextTick, onBeforeUnmount, ref, shallowRef, watch} from 'vue';
import {useScreenPageProps} from '@/common/composables/screen';
import {useSlideout} from '@/common/slideouts/useSlideout';
import type {FormPayload} from '@/modules/forms/types';
import type {ElementActionMenuItem} from '@/modules/elements/composables/useElementActionMenu';
import {useInertiaFormRenderer} from '@/modules/forms/useInertiaFormRenderer';
import {useElementAutosave} from '@/modules/elements/composables/useElementAutosave';
import {useElementActivity} from '@/modules/elements/composables/useElementActivity';
import {useSiteStatuses} from '@/modules/elements/composables/useSiteStatuses';
import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';

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
  params: Record<string, unknown>;
  /** Pre-encrypted by the server; the save controllers decrypt it. */
  redirect: string | null;
  variant?: string;
  shortcut?: boolean;
  shift?: boolean;
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
  elementId: number | null;
  canonicalId: number | null;
  elementType: string;
  siteId: number | null;
  fieldLayoutId: number | null;
  title: string;
  docTitle: string;
  crumbs: Array<Record<string, any>>;
  readOnly: boolean;
  form: FormPayload | null;
  sidebarForm: FormPayload | null;
  metadataHtml: string | null;
  /** The element's status badge. `null` for element types without statuses. */
  statusLabelHtml: string | null;
  saveUrl: string;
  applyDraftUrl: string;
  formActions: Array<ElementFormAction>;
  headerActions: Array<ElementFormAction>;
  autosaveUrl: string;
  discardDraftUrl: string;
  isProvisionalDraft: boolean;
  draftId: number | null;
  canAutosave: boolean;
  notice: string | null;
  mergeNotice: string | null;
  canDiscardDraft: boolean;
  submitButtonLabel: string;
  actionMenu: Array<ElementActionMenuItem>;
  previewTargets: Array<{label: string; url: string}>;
  elementDisplayName: string;
  activityUrl: string | null;
  updatedTimestamps: {element: number | null; canonical: number | null};
  contextMenu: {
    label: string;
    items: Array<ElementContextMenuItem>;
  } | null;
  // Element-type view models add their own keys on top of the shared payload.
  [key: string]: unknown;
}

interface Options {
  /**
   * Identity and element-type attributes merged into every submission —
   * whatever the type's save action needs to resolve the element it's saving.
   */
  saveData?: () => Record<string, unknown>;
}

/**
 * Drives an element edit screen: bridges the compiled field layout into an
 * Inertia form, collects the still-server-rendered sidebar meta fields, and
 * owns the unsaved-changes guard and saving. Tabs belong to `FormRenderer`.
 *
 * Element-type pages supply only what their save action needs via
 * {@link Options.saveData}; everything else comes from the shared payload.
 */
export function useElementEditPage({saveData}: Options = {}) {
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
    computed(() => ({
      ...(pageProps() as unknown as ElementEditPayload),
      ...(savedScreen.value ?? {}),
    }))
  );

  // The field layout comes back on the response separately from the screen
  // payload — scoped to whatever the request asked for — and applying it is the
  // only way a nested element the save just created (a new Matrix entry or
  // address) receives its own Form payload. Until it does, the block has
  // nothing to render but a spinner.
  const savedForm = shallowRef<FormPayload | null>(null);
  const formPayload = computed(() => savedForm.value ?? props.form);
  const sidebarPayload = computed(() => props.sidebarForm);
  const form = useForm<Record<string, any>>({});

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
  } = useInertiaFormRenderer(form, formPayload);

  const {
    advanceBaseline: advanceSidebarBaseline,
    errors: sidebarErrors,
    onMutation: onSidebarFormMutation,
    renderer: sidebarRenderer,
    resetValues: resetSidebarValues,
  } = useInertiaFormRenderer(form, sidebarPayload);

  useSiteStatuses(form);

  const autosave = useElementAutosave(form, {
    url: props.autosaveUrl,
    elementType: props.elementType,
    elementId: props.canonicalId,
    siteId: props.siteId,
    draftId: props.draftId,
    isProvisional: props.isProvisionalDraft,
    enabled: props.canAutosave,
    // Autosave moves the draft's `dateUpdated` without a visit, so the poller
    // has to re-baseline against what the save just wrote — otherwise it reads
    // our own keystrokes back as an edit from elsewhere. `activity` is
    // initialized just below; this only ever runs after a save settles.
    onSaved: (timestamps) => activity.rebase(timestamps),
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
    [() => autosave.form.value, () => autosave.screen.value],
    ([form, screen]) => {
      if (!form && !screen) {
        return;
      }

      applyingSavedPayload = true;

      if (form) {
        savedForm.value = form;
      }

      if (screen) {
        savedScreen.value = screen as Partial<ElementEditPayload>;
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
      savedForm.value = null;
      savedScreen.value = null;
      autosave.clearSaved();
    }
  );

  // The renderers' change callbacks are the authoritative "content changed"
  // signal, so autosave hangs off them rather than watching the form — and off
  // whether the values actually differ from the server's, not off having been
  // told a control changed.
  function onMutation(mutation: FormPayload['values']): void {
    const changed = onLayoutMutation(mutation);

    if (!applyingSavedPayload && !reverting && changed) {
      autosave.schedule();
    }
  }

  function onSidebarMutation(mutation: FormPayload['values']): void {
    const changed = onSidebarFormMutation(mutation);

    // The screen payload carries the sidebar form too, so reconciling it emits
    // mutations here for the same reason the field layout does.
    if (!applyingSavedPayload && !reverting && changed) {
      autosave.schedule();
    }
  }

  // Set for the duration of one submission when an alternate action owns it,
  // so the shared save pipeline (elevated sessions, error handling, the
  // processing flag) is reused rather than reimplemented per action.
  const pendingAction = ref<ElementFormAction | null>(null);

  const {save} = useSettingsSave(
    form,
    // Applying a draft and saving the element are different endpoints, and
    // which one applies depends on whether autosave has created a draft by the
    // time the user submits — not on how the page was first rendered.
    () => ({
      url:
        pendingAction.value?.actionUrl ??
        (autosave.draftId.value !== null ? props.applyDraftUrl : props.saveUrl),
      method: 'post' as const,
    }),
    {
      transform: (data) => ({
        ...data,
        ...saveData?.(),
        // Once autosave has created a provisional draft, the submission has to
        // target it — otherwise applying would save the canonical element and
        // strand the draft holding the newer values.
        //
        // This posts to a shared `elements/*` action rather than the element
        // type's own, so it needs the generic identity params: the type-specific
        // ones (an entry's `entryId`) mean nothing there.
        ...(autosave.draftId.value !== null
          ? {
              elementType: props.elementType,
              elementId: props.canonicalId,
              draftId: autosave.draftId.value,
              ...(draftIsProvisional.value ? {provisional: 1} : {}),
            }
          : {}),
        // An alternate action's own params win — "Create a draft" has to be
        // able to override the provisional targeting above.
        ...pendingAction.value?.params,
        ...(pendingAction.value?.redirect
          ? {redirect: pendingAction.value.redirect}
          : {}),
      }),
      onSuccess: () => {
        autosave.suspend(() => {
          advanceBaseline();
          advanceSidebarBaseline();
        });

        // Anything armed before the submission went out — the keystroke that
        // prompted it — describes values this save has just written, and
        // applying a provisional draft deletes the draft it would write them to.
        autosave.cancel();

        // The save itself moved the element's `dateUpdated`; without this the
        // next poll would report our own write as someone else's change.
        activity.rebase(props.updatedTimestamps);
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
    }
  }

  /** Throws away the provisional draft, reverting to the canonical element. */
  async function discardDraft(): Promise<void> {
    if (autosave.draftId.value === null) {
      return;
    }

    // Held for the whole discard, so a mutation emitted while the screen is
    // being torn back down can't schedule a save against the deleted draft.
    reverting++;
    autosave.cancel();

    try {
      await actionClient.post(props.discardDraftUrl, {
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
      savedForm.value = null;
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

  return {
    activity,
    autosave,
    discardDraft,
    submitAction,
    errors,
    form,
    formPayload,
    onMutation,
    onSidebarMutation,
    props,
    renderer,
    save,
    sidebarErrors,
    sidebarPayload,
    sidebarRenderer,
    values,
  };
}
