import {computed, toValue, type MaybeRefOrGetter} from 'vue';
import {setEmptyValueMode} from './empty-values';
import {getAt, isChecked, setAt} from './paths';
import {useMappingContext, type MappingColumn} from './types';

/** Which source column a destination column is mapped to, and a way to change it. */
export function useSourceMapping(col: MaybeRefOrGetter<MappingColumn>) {
  const context = useMappingContext();
  const path = computed(() => toValue(col).prefixedHandleAsArray);

  /** The chosen source column, or `''`; a container's branch of nested mappings isn't one. */
  const mapValue = computed(() => {
    const value = getAt(context.values.map, path.value);

    return typeof value === 'string' || typeof value === 'number'
      ? String(value)
      : '';
  });

  const isSuggested = computed(() =>
    Boolean(getAt(context.suggestedMap, path.value))
  );

  function choose(value: string): void {
    setAt(context.values.map, path.value, value);
    setAt(context.suggestedMap, path.value, false);

    if (value !== '') {
      return;
    }

    // only a mapped column can be matched on or have a When empty rule, so unmapping drops both
    if (isChecked(getAt(context.values.matchCriteria, path.value))) {
      setAt(context.values.matchCriteria, path.value, '');
    }

    setEmptyValueMode(context.values, toValue(col), 'skip');
  }

  return {mapValue, isSuggested, choose};
}
