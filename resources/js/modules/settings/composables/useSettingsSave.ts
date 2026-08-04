import {useEventListener} from '@vueuse/core';
import {type InertiaForm, usePage} from '@inertiajs/vue3';
import {computed} from 'vue';
import type {FormSaveOptions} from '@/common/types';
import {elevatedSessionManager} from '@/modules/auth/elevated-session';

interface PasswordConfirmationOptions<T> {
  required: (data: T) => boolean;
  minimumRemainingSeconds?: number;
}

interface UseSettingsSaveOptions<T extends Record<string, any>> {
  transform?: (data: T) => Record<string, any>;
  onSuccess?: () => void;
  passwordConfirmation?: PasswordConfirmationOptions<T>;
  /**
   * Sugar over {@link passwordConfirmation}: require an elevated session when the
   * named fields differ from their initial values, or `'*'` for any dirty change
   * (via Inertia's `form.isDirty`). Ignored when `passwordConfirmation` is set
   * explicitly — reach for that when you need a custom predicate or
   * `minimumRemainingSeconds`.
   */
  elevatedFields?: Array<keyof T> | '*';
}

export function useSettingsSave<T extends Record<string, any>>(
  form: InertiaForm<T>,
  action: any,
  options: UseSettingsSaveOptions<T> = {}
) {
  const page = usePage<{
    redirectUrl?: string;
  }>();
  const redirectUrl = computed(() => page.props.redirectUrl);

  // `elevatedFields` is sugar that generates a `passwordConfirmation` config, so
  // the proactive check and the 423 retry below both flow through one path. An
  // explicit `passwordConfirmation` always wins.
  const passwordConfirmation =
    options.passwordConfirmation ??
    elevatedFieldsConfirmation(form, options.elevatedFields);

  // Handle cmd + s events
  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save({redirect: false});
    }
  });

  function save({
    redirect = true,
    data: extraData = {},
    // Callers can opt out of state preservation — e.g. "save as new", which
    // navigates to a different record and needs the form to re-initialize.
    preserveState = true,
  }: FormSaveOptions = {}) {
    const submitOptions = redirect
      ? {
          preserveScroll: true,
          preserveState,
        }
      : {
          replace: true,
        };

    function submit(retried = false) {
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
        .submit(action(), {
          ...submitOptions,
          onHttpException: (response) => {
            if (!passwordConfirmation || response.status !== 423 || retried) {
              return;
            }

            elevatedSessionManager
              .require({
                force: true,
                minimumRemainingSeconds:
                  passwordConfirmation.minimumRemainingSeconds,
              })
              .then((confirmed) => {
                if (confirmed) {
                  submit(true);
                }
              });

            return false;
          },
          onSuccess: options.onSuccess,
        });
    }

    if (passwordConfirmation?.required(form.data())) {
      elevatedSessionManager
        .require({
          minimumRemainingSeconds: passwordConfirmation.minimumRemainingSeconds,
        })
        .then((confirmed) => {
          if (confirmed) {
            submit();
          }
        });

      return;
    }

    submit();
  }

  return {save};
}

/**
 * Build a {@link PasswordConfirmationOptions} from the `elevatedFields` sugar.
 * An array snapshots each field's initial value and requires elevation when any
 * changes; `'*'` defers to Inertia's own dirty tracking.
 */
function elevatedFieldsConfirmation<T extends Record<string, any>>(
  form: InertiaForm<T>,
  fields: Array<keyof T> | '*' | undefined
): PasswordConfirmationOptions<T> | undefined {
  if (!fields) {
    return undefined;
  }

  if (fields === '*') {
    return {required: () => form.isDirty};
  }

  const baseline = new Map<keyof T, string>(
    fields.map((field) => [field, normalize(form[field])])
  );

  return {
    required: (data) =>
      fields.some((field) => normalize(data[field]) !== baseline.get(field)),
  };
}

/**
 * Stringify a field value for change comparison. Arrays are sorted first so a set
 * of permissions/groups compares equal regardless of order.
 */
function normalize(value: unknown): string {
  return Array.isArray(value)
    ? JSON.stringify([...value].sort())
    : JSON.stringify(value);
}
