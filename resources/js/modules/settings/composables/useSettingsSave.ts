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
  passwordConfirmation?: PasswordConfirmationOptions<T>;
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
            if (
              !options.passwordConfirmation ||
              response.status !== 423 ||
              retried
            ) {
              return;
            }

            elevatedSessionManager
              .require({
                force: true,
                minimumRemainingSeconds:
                  options.passwordConfirmation?.minimumRemainingSeconds,
              })
              .then((confirmed) => {
                if (confirmed) {
                  submit(true);
                }
              });

            return false;
          },
        });
    }

    if (options.passwordConfirmation?.required(form.data())) {
      elevatedSessionManager
        .require({
          minimumRemainingSeconds:
            options.passwordConfirmation.minimumRemainingSeconds,
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
