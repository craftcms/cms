import {useEventListener} from '@vueuse/core';
import {type InertiaForm, usePage} from '@inertiajs/vue3';
import {computed} from 'vue';
import type {FormSaveOptions} from '@/common/types';
import {
  ElevatedSessionCancelled,
  requireElevatedSession,
} from '@/modules/elevated-session/elevated-session';

interface UseSettingsSaveOptions<T extends Record<string, any>> {
  transform?: (data: T) => Record<string, any>;
  /**
   * Which changes require an elevated (password-confirmed) session before the
   * form may submit — the Inertia-friendly port of `Craft.ElevatedSessionForm`'s
   * watched-input list. Pass an array of fields to watch just those (elevation is
   * required when any differ from their initial value at save time), or `'*'` to
   * require elevation whenever the form is dirty. When triggered, the login modal
   * is shown first (via {@link requireElevatedSession}). The server still
   * enforces elevation with `ConfirmsPasswords::requireConfirmedPassword()`.
   */
  elevatedFields?: Array<keyof T> | '*';
}

export function useSettingsSave<T extends Record<string, any>>(
  form: InertiaForm<T>,
  action: () => any,
  options: UseSettingsSaveOptions<T> = {}
) {
  const page = usePage<{
    redirectUrl?: string;
  }>();
  const redirectUrl = computed(() => page.props.redirectUrl);

  const elevatedFields = options.elevatedFields ?? [];

  // Snapshot the watched fields' initial values so we can tell, at save time,
  // whether an elevated session is required (mirrors the legacy
  // `ElevatedSessionForm.inputsChanged()`). Empty in `'all'` mode, which defers
  // to Inertia's own dirty tracking instead.
  const elevatedBaseline = new Map<keyof T, string>(
    elevatedFields === 'all'
      ? []
      : elevatedFields.map((field) => [field, normalize(form[field])])
  );

  function elevatedFieldsChanged(): boolean {
    if (elevatedFields === 'all') {
      return form.isDirty;
    }

    return elevatedFields.some(
      (field) => normalize(form[field]) !== elevatedBaseline.get(field)
    );
  }

  // Handle cmd + s events
  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save({redirect: false});
    }
  });

  async function save({
    redirect = true,
    data: extraData = {},
    // Callers can opt out of state preservation — e.g. "save as new", which
    // navigates to a different record and needs the form to re-initialize.
    preserveState = true,
  }: FormSaveOptions = {}) {
    // Establish an elevated session before submitting if a watched field changed.
    if (elevatedFieldsChanged()) {
      try {
        await requireElevatedSession();
      } catch (e) {
        if (e instanceof ElevatedSessionCancelled) {
          return;
        }
        throw e;
      }
    }

    const submitOptions = redirect
      ? {
          preserveScroll: true,
          preserveState,
        }
      : {
          replace: true,
        };

    form
      .clearErrors()
      .transform((data: T) => {
        const transformedData = options.transform?.(data) ?? data;

        return {
          ...transformedData,
          ...extraData,
          redirect:
            redirect && redirectUrl.value ? redirectUrl.value : undefined,
        };
      })
      .submit(action(), submitOptions);
  }

  return {save};
}

/**
 * Stringify a field value for change comparison. Arrays are sorted first so that
 * a set of permissions/groups compares equal regardless of order.
 */
function normalize(value: unknown): string {
  if (Array.isArray(value)) {
    return JSON.stringify([...value].sort());
  }
  return JSON.stringify(value);
}
