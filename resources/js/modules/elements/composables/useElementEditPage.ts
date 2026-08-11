import {useEventListener} from '@vueuse/core';
import {router, useForm, usePage} from '@inertiajs/vue3';
import {actionClient, t} from '@craftcms/ui';
import {computed, onBeforeUnmount, watch} from 'vue';
import type {FormPayload} from '@/modules/forms/types';
import {useInertiaFormRenderer} from '@/modules/forms/useInertiaFormRenderer';
import {useElementAutosave} from '@/modules/elements/composables/useElementAutosave';
import {useSiteStatuses} from '@/modules/elements/composables/useSiteStatuses';
import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';

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
  autosaveUrl: string;
  discardDraftUrl: string;
  isProvisionalDraft: boolean;
  draftId: number | null;
  canAutosave: boolean;
  notice: string | null;
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
  const page = usePage<ElementEditPayload>();
  const props = page.props;

  const formPayload = computed(() => props.form);
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
    enabled: props.canAutosave,
  });

  // Applying a draft consumes it, and Inertia preserves this component across
  // the visit, so the server's view of the draft is authoritative afterwards.
  watch(
    () => props.draftId,
    (draftId) => autosave.setDraftId(draftId)
  );

  // The renderers' change callbacks are the authoritative "content changed"
  // signal, so autosave hangs off them rather than watching the form.
  function onMutation(mutation: FormPayload['values']): void {
    onLayoutMutation(mutation);
    autosave.schedule();
  }

  function onSidebarMutation(mutation: FormPayload['values']): void {
    onSidebarFormMutation(mutation);
    autosave.schedule();
  }

  const {save} = useSettingsSave(
    form,
    // Applying a draft and saving the element are different endpoints, and
    // which one applies depends on whether autosave has created a draft by the
    // time the user submits — not on how the page was first rendered.
    () => ({
      url:
        autosave.draftId.value !== null ? props.applyDraftUrl : props.saveUrl,
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
              provisional: 1,
            }
          : {}),
      }),
      onSuccess: () => {
        autosave.suspend(() => {
          advanceBaseline();
          advanceSidebarBaseline();
        });
      },
    }
  );

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
    router.reload();
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

  return {
    autosave,
    discardDraft,
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
