import {usePage} from '@inertiajs/vue3';
import {computed} from 'vue';

export function useFlash() {
  const page = usePage<{
    flash: {
      success: string | null;
      error: string | null;
    };
  }>();

  const flash = computed(() => page.props.flash);
  const successFlash = computed(() => flash.value.success);
  const errorFlash = computed(() => flash.value.error);

  return {flash, successFlash, errorFlash};
}
