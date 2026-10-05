import type {NestedOwnerContext} from './nested-owner';

type HtmlElementEditor = {
  settings?: {
    isStatic?: boolean;
    canCreateDrafts?: boolean;
    draftId?: number | null;
    canonicalId?: number | null;
    isProvisionalDraft?: boolean;
  };
  saveDraft?: () => Promise<void>;
  getDraftElementId?: (id: number) => number;
};

/** Prepares the owner through the surrounding HTML form's element editor. */
export async function prepareHtmlNestedOwner(
  form: HTMLFormElement,
  context: NestedOwnerContext
): Promise<NestedOwnerContext | null> {
  const editor = $(form).data('elementEditor') as HtmlElementEditor | undefined;
  if (!editor) {
    return context.requiresDerivative === false ? context : null;
  }

  if (editor.settings?.isStatic) {
    return null;
  }

  if (editor.settings?.canCreateDrafts) {
    await editor.saveDraft?.();
    if (!editor.settings.draftId) {
      return null;
    }
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
