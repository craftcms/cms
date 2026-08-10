import {useEventListener} from '@vueuse/core';
import {router, useForm, usePage} from '@inertiajs/vue3';
import {t} from '@craftcms/ui';
import {computed, onBeforeUnmount} from 'vue';
import type {FormPayload} from '@/modules/forms/types';
import {useInertiaFormRenderer} from '@/modules/forms/useInertiaFormRenderer';
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
  const {advanceBaseline, errors, onMutation, renderer, values} =
    useInertiaFormRenderer(form, formPayload);

  const {
    advanceBaseline: advanceSidebarBaseline,
    errors: sidebarErrors,
    onMutation: onSidebarMutation,
    renderer: sidebarRenderer,
  } = useInertiaFormRenderer(form, sidebarPayload);

  const {save} = useSettingsSave(
    form,
    () => ({
      url: props.saveUrl,
      method: 'post' as const,
    }),
    {
      transform: (data) => ({
        ...data,
        ...saveData?.(),
      }),
      onSuccess: () => {
        advanceBaseline();
        advanceSidebarBaseline();
      },
    }
  );

  // Both the field layout and the sidebar feed the same Inertia form, so its
  // own dirty state covers the whole screen.
  function hasUnsavedChanges(): boolean {
    return !form.processing && form.isDirty;
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
