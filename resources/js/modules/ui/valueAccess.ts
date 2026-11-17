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
import {builtInValueAcceptors} from './valueAcceptors';

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
 * Decides whether a control takes a value read from another control of its
 * kind, from the value itself: whether it fits the control's length limit or
 * options, say. Registered for the control's component.
 */
export type UiValueAcceptor = (
  control: UiControlPayload<object>,
  snapshot: UiValueSnapshot
) => boolean;

/** The control a value was read from. */
export type UiValueOrigin = Pick<
  UiControlPayload<object>,
  'component' | 'valueScope'
>;

const acceptors = new Map<string, UiValueAcceptor>(
  Object.entries(builtInValueAcceptors)
);

/**
 * Lets the controls drawn by `component` decide which values they take,
 * replacing any acceptor registered for it before. Without one, a control
 * takes any value read from a control of its kind.
 */
export function registerValueAcceptor(
  component: string,
  acceptor: UiValueAcceptor
): void {
  acceptors.set(component, acceptor);
}

/**
 * Whether `control` can be given a value read from `origin`: it's the same
 * kind of control, in the same value scope (the field a relation field's
 * value comes from, say), it can be edited and given a value from outside,
 * and it takes the value itself.
 */
export function acceptsValue(
  control: UiControlPayload<object>,
  origin: UiValueOrigin,
  snapshot: UiValueSnapshot
): boolean {
  return (
    control.component === origin.component &&
    (control.valueScope ?? null) === (origin.valueScope ?? null) &&
    control.mode === 'editable' &&
    Boolean(control.writable) &&
    (acceptors.get(control.component)?.(control, snapshot) ?? true)
  );
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
