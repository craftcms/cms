import {useEventListener} from '@vueuse/core';
import {type InertiaForm, router, usePage} from '@inertiajs/vue3';
import {computed, type Ref} from 'vue';
import axios from 'axios';
import type {FormSaveOptions} from '@/common/types';
import {elevatedSessionManager} from '@/modules/auth/elevated-session';
import {useSlideout} from '@/common/slideouts/useSlideout';
import {firstMessages} from '@/common/slideouts/errors';
import type {SlideoutInstance, SlideoutSaveResult} from '@/common/slideouts';

interface PasswordConfirmationOptions<T> {
  required: (data: T) => boolean;
  minimumRemainingSeconds?: number;
}

export interface UseSettingsSaveOptions<T extends object> {
  transform?: (data: T) => object;
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

interface SettingsSaveSlideout {
  instance: Pick<SlideoutInstance, 'containerId'>;
  close(options?: {force?: boolean}): void;
  saved(result?: SlideoutSaveResult): boolean;
}

export interface SettingsSaveDependencies {
  request: typeof axios.request;
  reload: typeof router.reload;
  elevatedSession: Pick<typeof elevatedSessionManager, 'require'>;
  slideout: SettingsSaveSlideout | null;
  redirectUrl: Readonly<Ref<string | undefined>>;
}

function defaultDependencies(): SettingsSaveDependencies {
  const page = usePage<{redirectUrl?: string}>();

  return {
    request: (...args) => axios.request(...args),
    reload: (...args) => router.reload(...args),
    elevatedSession: elevatedSessionManager,
    slideout: useSlideout(),
    redirectUrl: computed(() => page.props.redirectUrl),
  };
}

export function useSettingsSave<T extends object>(
  form: InertiaForm<T>,
  action: any,
  options: UseSettingsSaveOptions<T> = {},
  dependencies: SettingsSaveDependencies = defaultDependencies()
) {
  // Non-null when this screen is rendering inside a slideout, in which case
  // saving must not navigate — see `submitInSlideout()`.
  const {elevatedSession, redirectUrl, reload, request, slideout} =
    dependencies;

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

    /**
     * Save from inside a slideout, without navigating.
     *
     * `form.submit()` is an Inertia visit, which would replace the page *behind*
     * the panel. The controllers already answer JSON whenever the request
     * accepts it (`RespondsWithFlash`), so this posts directly and drives the
     * form state by hand. Note the failure status is **400**, not Laravel's
     * usual 422 — `asJsonFailure()` picks it.
     */
    async function submitInSlideout(retried = false): Promise<void> {
      const route = action();
      const routeIsString = Object(route).constructor === String;

      form.clearErrors();
      form.processing = true;

      try {
        const response = await request({
          url: routeIsString ? String(route) : route.url,
          method: routeIsString ? 'post' : (route.method ?? 'post'),
          // No `redirect`: a slideout closes rather than navigating anywhere.
          data: {
            ...(options.transform?.(form.data()) ?? form.data()),
            ...extraData,
          },
          headers: {
            'X-Craft-Container-Id': slideout!.instance.containerId,
          },
        });

        form.processing = false;

        // An opener that registered `onSaved` refreshes itself, and knows
        // better than we do what actually needs refreshing. Before the close:
        // closing drops the panel from the store, taking its handler with it.
        const handled = slideout!.saved({data: response.data});

        // `redirect: false` is "save and continue editing" (the cmd+S path),
        // which keeps the panel open. `force` because the form can still read
        // dirty right after a save — Inertia only clears that when its
        // defaults are updated, which the page behind does on reload.
        if (redirect !== false) {
          slideout!.close({force: true});
        }

        if (handled) {
          return;
        }

        // Otherwise: the controller flashes the success message to the session
        // even on its JSON branch, so refreshing the page behind both surfaces
        // that message and picks up whatever was just saved. `reload()`
        // preserves scroll and state inherently.
        reload();
      } catch (error) {
        form.processing = false;

        if (
          !axios.isAxiosError<{
            errors?: Record<string, string | string[]>;
          }>(error)
        ) {
          throw error;
        }

        const status = error.response?.status;

        if (passwordConfirmation && status === 423 && !retried) {
          void elevatedSession
            .require({
              force: true,
              minimumRemainingSeconds:
                passwordConfirmation.minimumRemainingSeconds,
            })
            .then((confirmed) => {
              if (confirmed) {
                void submitInSlideout(true);
              }
            });

          return;
        }

        const errors = error.response?.data?.errors;

        if (errors) {
          const messages = firstMessages(errors);
          Object.assign(form.errors, messages);
          form.setError(form.errors);

          return;
        }

        throw error;
      }
    }

    function submit(retried = false) {
      if (slideout) {
        void submitInSlideout(retried);

        return;
      }

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

            void elevatedSession
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
      void elevatedSession
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
function elevatedFieldsConfirmation<T extends object>(
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
function normalize<T>(value: T): string {
  return Array.isArray(value)
    ? JSON.stringify(
        [...value].sort((a, b) =>
          JSON.stringify(a).localeCompare(JSON.stringify(b))
        )
      )
    : JSON.stringify(value);
}
