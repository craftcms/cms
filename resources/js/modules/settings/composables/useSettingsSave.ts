import {useEventListener} from '@vueuse/core';
import {type InertiaForm, usePage} from '@inertiajs/vue3';
import {computed} from 'vue';
import type {FormSaveOptions} from '@/common/types';

interface UseSettingsSaveOptions<T extends Record<string, any>> {
  transform?: (data: T) => Record<string, any>;
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
