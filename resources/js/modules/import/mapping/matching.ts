/** What a step can do with rows that match an existing element. */
import {t} from '@craftcms/ui';
import {emptyValueModeOf, setEmptyValueMode} from './empty-values';
import {checkedTreeValue, getAt, isChecked, setAt} from './paths';
import type {MappingColumn, MappingValues} from './types';

export const DEFAULT_MATCH_MODE = 'createAndUpdate';

export function matchModes(): Array<{
  value: string;
  label: string;
  instructions: string;
}> {
  return [
    {
      value: 'createOnly',
      label: t('Only create new ones'),
      instructions: t('Rows that match something already there are skipped.'),
    },
    {
      value: DEFAULT_MATCH_MODE,
      label: t('Create new ones and update existing ones'),
      instructions: t('The usual choice for keeping content in sync.'),
    },
    {
      value: 'updateOnly',
      label: t('Only update existing ones'),
      instructions: t('Rows that don’t match anything are skipped.'),
    },
  ];
}

/** The panel's values are edited in place, though their generated type is read-only. */
export function setMatchMode(values: MappingValues, mode: string): void {
  (values as {matchMode: string | null}).matchMode = mode;
}

/** Whether rows are matched on a column; a container's own columns decide that in its panel. */
export function isMatchKey(values: MappingValues, col: MappingColumn): boolean {
  return (
    !col.isContainer &&
    Boolean(col.canBeMatchCriteria) &&
    isChecked(getAt(values.matchCriteria, col.prefixedHandleAsArray))
  );
}

/**
 * Matches rows on a column, or stops. A column rows are matched on has no When empty rule, since an
 * empty value matches nothing, so any it had is dropped rather than left to apply unseen.
 */
export function setMatchKey(
  values: MappingValues,
  col: MappingColumn,
  matched: boolean
): void {
  setAt(
    values.matchCriteria,
    col.prefixedHandleAsArray,
    checkedTreeValue(matched)
  );

  if (matched && emptyValueModeOf(values, col) !== 'skip') {
    setEmptyValueMode(values, col, 'skip');
  }
}
