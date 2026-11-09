/**
 * Reading a control's value from outside it, and writing one back, for the
 * controls that say they can: a readable control's value, with the props it
 * names, is everything it draws; a writable one draws whatever value it's
 * given. Copying a value takes a readable control on one side and a writable
 * one on the other; undoing that takes both on the same side.
 */

import {cloneRaw} from './renderer/uiTree';
import {valueAt} from './runtime';
import type {UiControlPayload, UiValue, UiValues} from './types';

/** A control's value as it was read, with the props it draws it with. */
export interface UiValueSnapshot {
  value: UiValue;
  /** The props named by the control's `valueProps`, when it names any. */
  props?: Record<string, unknown>;
}

/**
 * The control's value among `values`, with the props it draws it with, or
 * `null` when the control doesn't say its value can be read.
 */
export function readControlValue(
  control: UiControlPayload<object>,
  values: UiValues
): UiValueSnapshot | null {
  if (!control.readable) {
    return null;
  }

  const value = cloneRaw(valueAt(values, control.path));
  const names = control.valueProps ?? [];

  if (names.length === 0) {
    return {value};
  }

  const props = control.props as Record<string, unknown>;

  return {
    value,
    // SAFETY: the props are the server's JSON, so they clone as values do.
    props: cloneRaw(
      Object.fromEntries(names.map((name) => [name, props[name]])) as UiValues
    ),
  };
}

/**
 * The control redefined with the props a snapshot brings, for writing it, or
 * `undefined` when it brings none and the control stays as it is.
 */
export function controlForSnapshot(
  control: UiControlPayload<object>,
  snapshot: UiValueSnapshot
): UiControlPayload<object> | undefined {
  return snapshot.props
    ? {...control, props: {...control.props, ...snapshot.props}}
    : undefined;
}
