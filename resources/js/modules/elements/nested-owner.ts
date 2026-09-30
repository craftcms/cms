import type {InjectionKey} from 'vue';
import {pathsMatch, visitControls} from '@/modules/forms/runtime';
import type {FormPayload} from '@/modules/forms/types';

/** The editor that owns a nested field, including when it lives in a slideout. */
export interface NestedOwnerEditor {
  prepare(path: string[]): Promise<NestedOwnerContext | null>;
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
  form: FormPayload | null,
  path: string[]
): NestedOwnerContext | null {
  if (!form) {
    return null;
  }

  let context: NestedOwnerContext | null = null;
  visitControls(form.nodes, (control) => {
    if (
      control.component !== 'craft:nested-entries' ||
      !pathsMatch(control.path, path)
    ) {
      return;
    }

    const manager = control.props.manager;
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
      };
    }
  });
  return context;
}

export const NestedOwnerEditorKey: InjectionKey<NestedOwnerEditor> = Symbol(
  'nested-owner-editor'
);
