import {usePage} from '@inertiajs/vue3';
import {ref, watch, type Ref} from 'vue';

const SOURCE_BADGES_PROP = 'navSourceBadges';

const lastKnown = ref<Record<string, number>>({});

/**
 * Element index source counts, by nav item id.
 *
 * They're deferred, so every visit arrives without them and they follow a
 * moment later. The last counts stay up in the meantime rather than the
 * badges blinking out on each visit. A response that isn't deferring them
 * clears them, since no source shows a count anymore.
 */
export function useSourceBadges(): Readonly<Ref<Record<string, number>>> {
  const page = usePage<{[SOURCE_BADGES_PROP]?: Record<string, number>}>();

  watch(
    () => [page.props[SOURCE_BADGES_PROP], page.deferredProps] as const,
    ([badges]) => {
      if (badges) {
        lastKnown.value = badges;
      } else if (
        !Object.values(page.deferredProps ?? {})
          .flat()
          .includes(SOURCE_BADGES_PROP)
      ) {
        lastKnown.value = {};
      }
    },
    {immediate: true}
  );

  return lastKnown;
}
