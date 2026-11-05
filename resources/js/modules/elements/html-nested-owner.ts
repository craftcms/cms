import type {NestedOwnerContext} from './nested-owner';
import {jq} from '@/common/utils/jquery';

type HtmlElementEditor = {
  settings?: {
    isStatic?: boolean;
    canCreateDrafts?: boolean;
    draftId?: number | null;
    canonicalId?: number | null;
    isProvisionalDraft?: boolean;
  };
  setFormValue: (name: string, value: string) => Promise<unknown>;
  getDraftElementId?: (id: number) => number;
};

/** The legacy element editor a form belongs to; it only exists with the Yii2 adapter. */
export function legacyElementEditor<T = HtmlElementEditor>(
  form: HTMLFormElement
): T | undefined {
  return jq()?.(form).data('elementEditor') as T | undefined;
}

/** Prepares the owner through the surrounding HTML form's element editor. */
export async function prepareHtmlNestedOwner(
  form: HTMLFormElement,
  context: NestedOwnerContext,
  fieldName: string
): Promise<NestedOwnerContext | null> {
  const editor = legacyElementEditor(form);
  if (!editor) {
    return context.requiresDerivative === false ? context : null;
  }

  if (editor.settings?.isStatic) {
    return null;
  }

  await editor.setFormValue(fieldName, '*');

  if (editor.settings?.canCreateDrafts && !editor.settings.draftId) {
    return null;
  }

  const ownerId =
    editor.getDraftElementId?.(context.ownerId) ?? context.ownerId;
  return {
    ...context,
    ownerId,
    canonicalId: editor.settings?.canonicalId,
    draftId: editor.settings?.draftId,
    isProvisionalDraft: editor.settings?.isProvisionalDraft,
    ownerIsDerivative: ownerId !== context.ownerId || context.ownerIsDerivative,
    ownerIsInDerivativeTree:
      ownerId !== context.ownerId || context.ownerIsInDerivativeTree,
    requiresDerivative: Boolean(editor.settings?.canCreateDrafts),
  };
}
