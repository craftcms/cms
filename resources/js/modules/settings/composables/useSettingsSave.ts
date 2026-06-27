import {useEventListener} from '@vueuse/core';
import {type InertiaForm, usePage} from '@inertiajs/vue3';
import {computed} from 'vue';

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

  function save({redirect = true} = {}) {
    const submitOptions = redirect
      ? {
          preserveScroll: true,
          preserveState: true,
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
          redirect:
            redirect && redirectUrl.value ? redirectUrl.value : undefined,
        };
      })
      .submit(action(), submitOptions);
  }

  return {save};
}
