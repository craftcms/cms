import {toReactive, useEventListener} from '@vueuse/core';
import {router, useForm} from '@inertiajs/vue3';
import {actionClient, t} from '@craftcms/ui';
import {computed, nextTick, onBeforeUnmount, ref, shallowRef, watch} from 'vue';
import {useScreenPageProps} from '@/common/composables/screen';
import {useSlideout} from '@/common/slideouts/useSlideout';
import type {FormChangeKind, FormPayload} from '@/modules/forms/types';
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

  // Read through a computed rather than capturing the props object: it's
  // replaced wholesale on each visit, and saving in place — the normal path for
  // an element with no drafts — does that without remounting this component, so
  // the title, notices, and timestamps below have to track the live payload.
  const props = toReactive(
    computed(() => pageProps() as unknown as ElementEditPayload)
  );

  // Autosave answers with the field layout as the server now sees it, and
  // applying it is the only way a nested element the save just created — a new
  // Matrix entry or address — receives its own Form payload. Until it does, the
  // block has nothing to render but a spinner.
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
    values,
  } = useInertiaFormRenderer(form, formPayload);

  const {
    advanceBaseline: advanceSidebarBaseline,
    errors: sidebarErrors,
    onMutation: onSidebarFormMutation,
    renderer: sidebarRenderer,
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
  });

  const activity = useElementActivity({
    url: props.activityUrl,
    elementType: props.elementType,
    elementId: props.canonicalId,
    draftId: props.draftId,
    siteId: props.siteId,
    isProvisionalDraft: props.isProvisionalDraft,
    updatedTimestamps: props.updatedTimestamps,
  });

  /**
   * Whether the draft the screen is working against is provisional — either it
   * loaded that way, or autosave created one on a canonical element (autosave
   * only ever creates provisional drafts).
   */
  const draftIsProvisional = computed(
    () =>
      props.isProvisionalDraft ||
      (props.draftId === null && autosave.draftId.value !== null)
  );

  // Applying a draft consumes it, and Inertia preserves this component across
  // the visit, so the server's view of the draft is authoritative afterwards.
  watch(
    () => props.draftId,
    (draftId) => autosave.setDraftId(draftId)
  );

  /**
   * Whether the layout autosave just returned is being handed to the renderer.
   * Reconciling it emits a mutation of its own, but that's the server echoing
   * what it already saved rather than a fresh edit, so it must not re-arm
   * autosave — which would save again, and never settle.
   */
  let applyingSavedForm = false;

  watch(
    () => autosave.form.value,
    (payload) => {
      if (!payload) {
        return;
      }

      applyingSavedForm = true;
      savedForm.value = payload;
      // Released after the flush, so the renderer's pre-flush reconcile — and
      // the mutation it emits — is covered.
      void nextTick(() => (applyingSavedForm = false));
    },
    {flush: 'sync'}
  );

  // A payload arriving by Inertia visit is the newer of the two; drop what
  // autosave stashed so it stops shadowing it.
  watch(
    () => props.form,
    () => {
      savedForm.value = null;
      autosave.clearForm();
    }
  );

  // The renderers' change callbacks are the authoritative "content changed"
  // signal, so autosave hangs off them rather than watching the form — and off
  // whether the values actually differ from the server's, not off having been
  // told a control changed.
  function onMutation(
    mutation: FormPayload['values'],
    kind: FormChangeKind = 'discrete'
  ): void {
    const changed = onLayoutMutation(mutation);

    if (!applyingSavedForm && changed) {
      autosave.schedule(kind);
    }
  }

  function onSidebarMutation(
    mutation: FormPayload['values'],
    kind: FormChangeKind = 'discrete'
  ): void {
    if (onSidebarFormMutation(mutation)) {
      autosave.schedule(kind);
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
      // A submission supersedes any draft write: drop the in-flight request and
      // anything queued behind it, so a late draft save can't land on top of
      // the submission or resurrect values it just consumed.
      onBeforeSave: () => autosave.cancel(),
      onSuccess: () => {
        autosave.suspend(() => {
          advanceBaseline();
          advanceSidebarBaseline();
        });

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

  /** Throws away the provisional draft, reverting to the canonical element. */
  async function discardDraft(): Promise<void> {
    if (autosave.draftId.value === null) {
      return;
    }

    await actionClient.post(props.discardDraftUrl, {
      elementType: props.elementType,
      elementId: props.canonicalId,
      siteId: props.siteId,
      draftId: autosave.draftId.value,
      provisional: 1,
    });

    // Re-render from the canonical element rather than patching state here.
    // In a panel that's the panel's own request: an Inertia visit would reload
    // the page behind it and leave the discarded draft on screen.
    if (slideout) {
      await slideout.reload();
    } else {
      router.reload();
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
