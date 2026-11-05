import {useEventListener} from '@vueuse/core';
import {computed, ref, watch, type Ref} from 'vue';
import useCraftData from '@/common/composables/useCraftData';

const refreshed = ref<Record<string, number> | null>(null);

/**
 * The nav's badge counts, by nav item id.
 *
 * They come with every response, but can be refreshed in between with a
 * `craft:nav-badges` event, e.g. once an update check finishes. Refreshed
 * counts stand until the next response brings its own.
 */
export function useNavBadges(): Readonly<Ref<Record<string, number>>> {
  const {navBadges} = useCraftData();

  useEventListener(window, 'craft:nav-badges', (event: Event) => {
    refreshed.value = (event as CustomEvent<Record<string, number>>).detail;
  });

  watch(navBadges, () => {
    refreshed.value = null;
  });

  return computed(() => refreshed.value ?? navBadges.value);
}
