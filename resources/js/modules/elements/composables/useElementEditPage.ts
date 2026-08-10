import {useEventListener} from '@vueuse/core';
import {router, useForm, usePage} from '@inertiajs/vue3';
import {t} from '@craftcms/ui';
import {computed, onBeforeUnmount, ref} from 'vue';
import {expandFormData} from '@/common/utils/forms';
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
  sidebarHtml: string | null;
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
  const form = useForm<Record<string, any>>({});

  const {advanceBaseline, errors, onMutation, renderer, values} =
    useInertiaFormRenderer(form, formPayload);

  // The meta fields (entry type, slug, parent, post date, …) are still
  // server-rendered HTML, so their values are read out of the DOM at submit
  // time rather than tracked as Inertia form state.
  const sidebarEl = ref<HTMLElement | null>(null);

  function sidebarData(): Record<string, unknown> {
    const root = sidebarEl.value;

    if (!root) {
      return {};
    }

    const data = new FormData();

    for (const input of root.querySelectorAll<
      HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
    >('input[name], select[name], textarea[name]')) {
      if (
        (input instanceof HTMLInputElement &&
          (input.type === 'checkbox' || input.type === 'radio') &&
          !input.checked) ||
        input.disabled
      ) {
        continue;
      }

      data.append(input.name, input.value);
    }

    return expandFormData(data);
  }

  const {save} = useSettingsSave(
    form,
    () => ({
      url: props.saveUrl,
      method: 'post' as const,
    }),
    {
      transform: (data) => ({
        ...data,
        ...sidebarData(),
        ...saveData?.(),
      }),
      onSuccess: advanceBaseline,
    }
  );

  // Unsaved-changes guard. The field layout is tracked by Inertia's own dirty
  // state; the sidebar island is compared against a baseline captured on first
  // interaction, since it injects asynchronously.
  let sidebarBaseline: string | null = null;

  function captureSidebarBaseline(): void {
    sidebarBaseline ??= JSON.stringify(sidebarData());
  }

  function hasUnsavedChanges(): boolean {
    if (form.processing) {
      return false;
    }

    return (
      form.isDirty ||
      (sidebarBaseline !== null &&
        JSON.stringify(sidebarData()) !== sidebarBaseline)
    );
  }

  useEventListener(sidebarEl, 'focusin', captureSidebarBaseline);
  useEventListener(sidebarEl, 'pointerdown', captureSidebarBaseline);

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
    props,
    renderer,
    save,
    sidebarEl,
    values,
  };
}
