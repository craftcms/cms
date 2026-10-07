import type {InjectionKey} from 'vue';
import {pathsMatch, visitControls} from '@/modules/ui/runtime';
import type {UiPayload} from '@/modules/ui/types';

/** The editor that owns a nested field, including when it lives in a slideout. */
export interface NestedOwnerEditor {
  prepare(
    path: string[],
    context?: NestedOwnerContext
  ): Promise<NestedOwnerContext | null>;
  resolveElementId?(id: number): number;
  refresh?(): Promise<void>;
}

export interface NestedOwnerContext {
  ownerId: number;
  ownerIsDerivative: boolean;
  ownerIsInDerivativeTree: boolean;
  ownerIsUnpublishedDraft: boolean;
  requiresDerivative?: boolean;
  canonicalId?: number | null;
  draftId?: number | null;
  isProvisionalDraft?: boolean;
}

export function nestedOwnerContext(
  form: UiPayload | null,
  path: string[]
): NestedOwnerContext | null {
  if (!form) {
    return null;
  }

  let context: NestedOwnerContext | null = null;
  visitControls(form.nodes, (control) => {
    if (
      !['craft:nested-elements', 'craft:nested-element-blocks'].includes(
        control.component
      ) ||
      control.mode !== 'editable' ||
      !pathsMatch(control.path, path)
    ) {
      return;
    }

    const manager =
      control.component === 'craft:nested-element-blocks'
        ? control.props.create
        : control.props.manager;
    if (
      manager &&
      typeof manager === 'object' &&
      'ownerId' in manager &&
      typeof manager.ownerId === 'number'
    ) {
      context = {
        ownerId: manager.ownerId,
        ownerIsDerivative: manager.ownerIsDerivative === true,
        ownerIsInDerivativeTree: manager.ownerIsInDerivativeTree === true,
        ownerIsUnpublishedDraft: manager.ownerIsUnpublishedDraft === true,
        requiresDerivative: manager.ownerHasDrafts !== false,
      };
    }
  });
  return context;
}

/**
 * An owner that's always saved and never drafted, e.g. a user on their own
 * Addresses screen: nested element changes apply to it directly, and the
 * screen re-renders the manager through `refresh()`.
 */
export function savedNestedOwner(
  ownerId: number,
  refresh: () => Promise<void>
): NestedOwnerEditor {
  return {
    prepare: async () => ({
      ownerId,
      ownerIsDerivative: false,
      ownerIsInDerivativeTree: false,
      ownerIsUnpublishedDraft: false,
    }),
    refresh,
  };
}

export const NestedOwnerEditorKey: InjectionKey<NestedOwnerEditor> = Symbol(
  'nested-owner-editor'
);

export const NESTED_OWNER_EDITOR_REQUEST = 'craft:nested-owner-editor-request';
export type NestedOwnerEditorRequest = CustomEvent<{
  editor: NestedOwnerEditor | null;
}>;

/** Lets native controls in separate HTML form islands find their containing editor. */
export function requestNestedOwnerEditor(
  element: HTMLElement
): NestedOwnerEditor | null {
  const request: NestedOwnerEditorRequest = new CustomEvent(
    NESTED_OWNER_EDITOR_REQUEST,
    {
      bubbles: true,
      detail: {editor: null},
    }
  );
  element.dispatchEvent(request);
  return request.detail.editor;
}
