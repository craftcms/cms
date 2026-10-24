import {shallowRef, type InjectionKey, type Ref, type ShallowRef} from 'vue';
import {prefixedKey, useStorage} from '@/common/composables/useStorage';
import type {
  UiNodePayload,
  UiPayload,
  UiValue,
  UiValues,
} from '@/modules/ui/types';

/** The request parameters that identify the element to “Ask AI”. */
export type AskAiParams = CraftCms.Cms.Ai.AskAi.Data.ElementParams;

/** A field that can be chosen as the prompt's context. */
export type AskAiContextOption = CraftCms.Cms.Ai.AskAi.Data.ContextOption;

/**
 * A generated value for one field, for the editor to review, with its node,
 * value and previews typed as the UI renderer takes them.
 */
export type AskAiProposal = Omit<
  CraftCms.Cms.Ai.AskAi.Data.Proposal,
  'node' | 'value' | 'preview'
> & {
  node: UiNodePayload;
  value: UiValue;
  preview: {current: UiPayload; proposed: UiPayload} | null;
};

/** A problem the model noticed, for a field or for the content as a whole. */
export type AskAiFinding = CraftCms.Cms.Ai.AskAi.Data.Finding;

/**
 * A turn of the conversation, as the server returns it, for the panel to send
 * back with follow-ups. The server doesn't keep the conversation.
 */
export type AskAiTurn = CraftCms.Cms.Ai.AskAi.Data.Turn;

/** What the model returned: the values it changed, and what it has to say about the content. */
export type AskAiResult = Omit<
  CraftCms.Cms.Ai.AskAi.Data.Result,
  'proposals'
> & {
  proposals: AskAiProposal[];
};

/** A prompt in the conversation, and the model's reply to it. */
export interface AskAiConversationTurn {
  id: number;
  prompt: string;
  /** The model's reply, or `null` while it's being generated. */
  result: AskAiResult | null;
  /** The keys of the proposals that have been applied. */
  applied: readonly string[];
}

/** A request, from a field's “Ask AI” action, to ask about some fields. */
export interface AskAiRequest {
  /** The fields to make the prompt's context, as {@link AskAiContextOption} values. */
  context: string[];
}

/**
 * The element editor the “Ask AI” details panel works with, which its fields'
 * “Ask AI” actions open the panel through.
 */
export interface AskAiEditor {
  /** The UI scope of the editor's form. */
  scope: () => string[];
  /** The editor's form values, which the model sees so it works on unsaved changes. */
  values: () => UiValues;
  /** The latest request from a field's action, which the panel takes as its context. */
  request: Readonly<ShallowRef<AskAiRequest | null>>;
  /** Opens the panel with the given fields as its context. */
  ask: (request: AskAiRequest) => void;
  /**
   * The conversation, which the panel and its header share. Emptying it
   * starts a new conversation.
   */
  conversation: Ref<AskAiConversationTurn[]>;
  /**
   * Writes accepted values into the editor's form, and returns the ones that
   * were written: a field can be gone since its value was proposed.
   */
  apply: (proposals: AskAiProposal[]) => AskAiProposal[];
}

export const AskAiEditorKey: InjectionKey<AskAiEditor> = Symbol('AskAiEditor');

export interface ApplyAskAiProposalDetail {
  proposal: AskAiProposal;
  /** The slideout the field is in, or `null` for the page, since a slideout can repeat the page’s control paths. */
  container: Element | null;
  /** Whether to show the field, selecting its tab and scrolling to it. */
  reveal: boolean;
  /** Set by the field that writes the value. */
  applied: boolean;
}

/**
 * Writes an accepted value into the form, through the field that currently renders its control path. Returns whether
 * a field wrote it.
 *
 * The field that was asked about can be gone by then: autosave replaces a new Matrix block’s fields when it creates
 * the owner’s provisional draft. Nested fields also need their own change handling to save the nested element.
 */
export function applyAskAiProposal(
  proposal: AskAiProposal,
  container: Element | null,
  reveal = false
): boolean {
  const detail: ApplyAskAiProposalDetail = {
    proposal,
    container,
    reveal,
    applied: false,
  };

  window.dispatchEvent(
    new CustomEvent<ApplyAskAiProposalDetail>('craft:apply-ask-ai-proposal', {
      detail,
    })
  );

  return detail.applied;
}

/** Returns the slideout an element is in, or `null` for the page. */
export function askAiContainer(element: Element): Element | null {
  return element.closest('[role="dialog"]');
}

/** The element editor screen a conversation belongs to. */
export interface AskAiConversationScreen {
  elementType: string;
  canonicalId: number | null;
  siteId: number | null;
  draftId: number | null;
  isProvisionalDraft: boolean;
}

/**
 * Returns an editor's conversation, kept in session storage so a refresh
 * doesn't lose it. Each browser tab keeps its own, and each screen: named
 * drafts get their own, while a provisional draft shares the element's, since
 * autosave can create one mid-conversation. An element that hasn't been saved
 * yet doesn't keep its conversation.
 *
 * Conversations belong to the user who had them, as they hold content another
 * user may not be allowed to see. Other users' are removed when a user's is
 * opened, so a different sign-in in the same tab can't read them.
 *
 * Replies that were still being generated are dropped, as they won't arrive.
 */
export function useAskAiConversation(
  screen: AskAiConversationScreen,
  userId: number | null
): Ref<AskAiConversationTurn[]> {
  if (screen.canonicalId === null || userId === null) {
    return shallowRef([]);
  }

  const key = [
    screen.elementType,
    screen.canonicalId,
    screen.siteId,
    ...(screen.draftId && !screen.isProvisionalDraft ? [screen.draftId] : []),
  ].join('.');

  const prefix = prefixedKey('askAi.conversation.');
  const own = `${prefix}${userId}.`;

  for (const stored of Object.keys(sessionStorage)) {
    if (stored.startsWith(prefix) && !stored.startsWith(own)) {
      sessionStorage.removeItem(stored);
    }
  }

  return useStorage<AskAiConversationTurn[]>(
    `askAi.conversation.${userId}.${key}`,
    [],
    sessionStorage,
    {
      shallow: true,
      serializer: {
        read: (raw) =>
          (JSON.parse(raw) as AskAiConversationTurn[]).filter(
            (turn) => turn.result
          ),
        write: (turns) => JSON.stringify(turns.filter((turn) => turn.result)),
      },
    }
  );
}
