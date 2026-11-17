import {ref, watch, toValue, type MaybeRefOrGetter} from 'vue';
import {iconSvg} from '@/common/craft/icon-svg';

export type IconState = 'idle' | 'success' | 'error' | 'fetching';

/**
 * Reactively resolves an icon's SVG markup, fetched from `app/icon-svg` and
 * cached per name.
 *
 * The source is watched, so changing the bound icon name refetches the markup.
 * When the name is empty/null/undefined no fetch happens and the state resets to
 * `idle`. A race-guard ensures a slow fetch can't clobber a newer selection.
 *
 * @example
 * const {html, state} = useIcon(() => model.value);
 * // In template: <div v-html="html" /> with a spinner while state === 'fetching'
 */
export function useAsyncIcon(
  icon: MaybeRefOrGetter<string | null | undefined>
) {
  const html = ref<string | null>(null);
  const state = ref<IconState>('idle');

  watch(
    () => toValue(icon),
    async (name) => {
      if (!name) {
        html.value = null;
        state.value = 'idle';
        return;
      }

      state.value = 'fetching';

      try {
        const el = await iconSvg(name);

        // Race-guard: a newer request superseded this one while awaiting.
        if (toValue(icon) !== name) {
          return;
        }

        html.value = el?.outerHTML ?? null;
        state.value = 'success';
      } catch {
        // Race-guard: ignore failures from superseded requests.
        if (toValue(icon) !== name) {
          return;
        }

        html.value = null;
        state.value = 'error';
      }
    },
    {immediate: true}
  );

  return {html, state};
}
