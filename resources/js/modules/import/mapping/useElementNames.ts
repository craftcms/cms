/**
 * Names the elements a default holds by their IDs, from the page's shared cache of
 * element chips, fetching any nothing has drawn yet.
 */
import {computed, toValue, watch, type MaybeRefOrGetter} from 'vue';
import {useElementPresentations} from '@/modules/elements/element-presentations';

export function useElementNames(
  elementType: MaybeRefOrGetter<string | null | undefined>,
  ids: MaybeRefOrGetter<number[]>
) {
  const elements = () =>
    toValue(elementType) ? toValue(ids).map((id) => ({id, siteId: null})) : [];

  const cache = useElementPresentations(
    () => ({elementType: toValue(elementType) ?? '', viewMode: 'list'}),
    elements
  );

  watch(
    elements,
    (next) => {
      const missing = next.filter(({id}) => !cache.get(id, null));

      if (missing.length) {
        void cache.load(missing).catch(() => {});
      }
    },
    {immediate: true}
  );

  /** Each element's name, or its ID until it's been fetched. */
  return computed(() =>
    toValue(ids).map((id) => cache.get(id, null)?.label ?? `#${id}`)
  );
}
