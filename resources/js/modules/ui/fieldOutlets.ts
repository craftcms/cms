import type {InjectionKey} from 'vue';

/**
 * Where a form's fields render, by layout UID, rather than in their place in
 * the form. A field whose outlet doesn't exist (yet) renders in place.
 */
export type UiFieldOutlets = (
  layoutUid: string
) => HTMLElement | null | undefined;

export const UiFieldOutletsKey: InjectionKey<UiFieldOutlets | null> =
  Symbol('UiFieldOutlets');
