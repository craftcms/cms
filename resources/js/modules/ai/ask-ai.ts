import type {InjectionKey, ShallowRef} from 'vue';
import type {
  UiNodePayload,
  UiPayload,
  UiValue,
  UiValues,
} from '@/modules/ui/types';

/** Identifies the element to generate content for. */
export type AskAiParams = {
  elementType: string;
  elementId: number;
  draftId: number | null;
  siteId: number;
  /** For a nested element, the owner it's being edited in, such as a draft. */
  ownerId?: number;
  fieldId?: number | null;
};

/** A field that can be chosen as the prompt's context. */
export interface AskAiContextOption {
  /** The field's control path, relative to the editor's scope, joined by dots. */
  value: string;
  label: string;
}

/** A generated value for one field, for the editor to review. */
export interface AskAiProposal {
  /** The field's key in the prompt, such as `summary` or `body.2.heading`. */
  key: string;
  label: string;
  /** The field's editable node, compiled with the generated value. */
  node: UiNodePayload;
  /** The generated value, as the field's control holds it. */
  value: UiValue;
  /** The current and proposed text, for fields whose values are text. */
  diff: {current: string; proposed: string} | null;
  /** Read-only payloads of the field, for fields whose values aren't text. */
  preview: {current: UiPayload; proposed: UiPayload} | null;
}

/** A problem the model noticed, for a field or for the content as a whole. */
export interface AskAiFinding {
  /** The key of the field it concerns, or `null` for the content as a whole. */
  key: string | null;
  label: string | null;
  /** The passage of the field's current text the problem is in, if any. */
  quote: string | null;
  issue: string;
}

/** What the model returned: the values it changed, and what it has to say about the content. */
export interface AskAiResult {
  proposals: AskAiProposal[];
  /** What the model changed, or its review when it changed nothing. */
  summary: string | null;
  findings: AskAiFinding[];
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
  /** Writes accepted values into the editor's form. */
  apply: (proposals: AskAiProposal[]) => void;
}

export const AskAiEditorKey: InjectionKey<AskAiEditor> = Symbol('AskAiEditor');

export interface ApplyAskAiProposalDetail {
  proposal: AskAiProposal;
  /** The slideout the field is in, or `null` for the page, since a slideout can repeat the page’s control paths. */
  container: Element | null;
}

/**
 * Writes an accepted value into the form, through the field that currently renders its control path.
 *
 * The field that was asked about can be gone by then: autosave replaces a new Matrix block’s fields when it creates
 * the owner’s provisional draft. Nested fields also need their own change handling to save the nested element.
 */
export function applyAskAiProposal(
  proposal: AskAiProposal,
  container: Element | null
): void {
  window.dispatchEvent(
    new CustomEvent<ApplyAskAiProposalDetail>('craft:apply-ask-ai-proposal', {
      detail: {proposal, container},
    })
  );
}

/** Returns the slideout an element is in, or `null` for the page. */
export function askAiContainer(element: Element): Element | null {
  return element.closest('[role="dialog"]');
}
