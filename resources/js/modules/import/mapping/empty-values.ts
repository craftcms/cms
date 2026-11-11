/**
 * What a destination column does when a row's value for it is missing or empty: skip
 * it and keep the existing value, erase the existing value, or use a default.
 *
 * Erasing is a `clearableItems` leaf, and a default is a `defaultValues` leaf; skipping
 * is neither. A default that's yet to be filled in is kept as `''`, which the server
 * treats as skipping.
 */
import {t} from '@craftcms/ui';
import {getAt, isChecked, setAt} from './paths';
import type {MappingColumn, MappingValues} from './types';

export type EmptyValueMode = 'skip' | 'erase' | 'default';

/** The input a column's default value is entered with. */
export type DefaultValueInput = NonNullable<MappingColumn['defaultValueInput']>;

/** Only asked of a column that can have a default; see {@link canHaveDefault}. */
export function defaultValueInputOf(col: MappingColumn): DefaultValueInput {
  return col.defaultValueInput ?? {type: 'text'};
}

/**
 * Whether a column can be given a default: one the server offers an input for, which it
 * doesn't for a container, or for a field no single value entered by hand suits.
 */
export function canHaveDefault(col: MappingColumn): boolean {
  return (
    !col.isContainer && col.canBeSet !== false && Boolean(col.defaultValueInput)
  );
}

export function emptyValueModeOf(
  values: MappingValues,
  col: MappingColumn
): EmptyValueMode {
  const path = col.prefixedHandleAsArray;

  if (canHaveDefault(col) && getAt(values.defaultValues, path) !== undefined) {
    return 'default';
  }

  return isChecked(getAt(values.clearableItems, path)) ? 'erase' : 'skip';
}

export function setEmptyValueMode(
  values: MappingValues,
  col: MappingColumn,
  mode: EmptyValueMode
): void {
  const path = col.prefixedHandleAsArray;

  setAt(values.clearableItems, path, mode === 'erase' ? '1' : '');

  if (mode !== 'default') {
    setAt(values.defaultValues, path, undefined);
  } else if (getAt(values.defaultValues, path) === undefined) {
    // a lightswitch is never empty, so it starts off rather than unset
    setAt(
      values.defaultValues,
      path,
      defaultValueInputOf(col).type === 'lightswitch' ? '0' : ''
    );
  }
}

export function defaultValueOf(
  values: MappingValues,
  col: MappingColumn
): string {
  const value = getAt(values.defaultValues, col.prefixedHandleAsArray);

  return typeof value === 'string' || typeof value === 'number'
    ? String(value)
    : '';
}

/**
 * A multi-choice default's values. It's entered as a JSON list, but a saved one comes
 * back from the server decoded, which the trees then hold as a numeric-keyed object.
 */
export function defaultValuesOf(
  values: MappingValues,
  col: MappingColumn
): string[] {
  return defaultListOf(getAt(values.defaultValues, col.prefixedHandleAsArray));
}

/** A multi-choice default's values, from any of the shapes it can be held in. */
export function defaultListOf(value: unknown): string[] {
  if (typeof value === 'string') {
    try {
      value = JSON.parse(value || '[]');
    } catch {
      return [];
    }
  }

  return value !== null && typeof value === 'object'
    ? Object.values(value).map(String)
    : [];
}

export function setDefaultValue(
  values: MappingValues,
  col: MappingColumn,
  value: string
): void {
  setAt(values.defaultValues, col.prefixedHandleAsArray, value);
}

/** The choices a column offers, leaving out erasing for one that can't be cleared. */
export function emptyValueModes(
  col: MappingColumn
): Array<{value: EmptyValueMode; label: string}> {
  return [
    {value: 'skip' as const, label: t('Skip')},
    ...(col.canBeCleared
      ? [{value: 'erase' as const, label: t('Erase existing value')}]
      : []),
    ...(canHaveDefault(col)
      ? [{value: 'default' as const, label: t('Use a default value')}]
      : []),
  ];
}
