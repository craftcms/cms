import {useEventListener} from '@vueuse/core';
import {type InertiaForm, usePage} from '@inertiajs/vue3';
import {computed} from 'vue';

export function useSettingsSave<T extends Record<string, any>>(
  form: InertiaForm<T>,
  action: any,
  opts?: {
    transform?: (data: T) => Record<string, any>;
  }
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
    let options = {};
    if (redirect) {
      options = {
        preserveScroll: true,
        preserveState: true,
      };
    }

    form
      .clearErrors()
      .transform((data: T) => {
        const transformed = opts?.transform ? opts.transform(data) : data;
        return {
          ...transformed,
          redirect:
            redirect && redirectUrl.value ? redirectUrl.value : undefined,
        };
      })
      .submit(action(), options);
  }

  return {save};
}
