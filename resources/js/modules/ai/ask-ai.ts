import type {InjectionKey} from 'vue';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import type {
  UiNodePayload,
  UiPayload,
  UiValue,
  UiValues,
} from '@/modules/ui/types';

/** Identifies the element, and for field-level requests the field, to generate content for. */
export type AskAiParams = {
  elementType: string;
  elementId: number;
  draftId: number | null;
  siteId: number;
  /** For a nested element, the owner it's being edited in, such as a draft. */
  ownerId?: number;
  fieldId?: number | null;
  layoutElementUid?: string;
};

/** A generated value for one field, for the editor to review. */
export interface AskAiProposal {
  layoutElementUid: string;
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
  /** The field it concerns, or `null` for the content as a whole. */
  layoutElementUid: string | null;
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

export interface AskAiOptions {
  params: AskAiParams;
  /** The UI scope of the form the values are applied to. */
  namespace?: string | null;
  /** The element's current form values, so the model sees changes that haven't been saved yet. */
  values: () => UiValues;
  onApply: (proposals: AskAiProposal[]) => void;
}

/**
 * Opens “Ask AI” for the element editor that provides it, so element-level actions apply to the right
 * form when slideouts are open.
 */
export const AskAiKey: InjectionKey<(params: AskAiParams) => void> =
  Symbol('AskAi');

/**
 * The element editor's form, so a field's “Ask AI” sends the same values a
 * save would, sidebar included. Fields of nested elements send their own.
 */
export const AskAiFormKey: InjectionKey<{
  scope: () => string[];
  values: () => UiValues;
}> = Symbol('AskAiForm');

export interface ApplyAskAiProposalDetail {
  proposal: AskAiProposal;
  /** The slideout the field is in, or `null` for the page, since a slideout can repeat the page’s control paths. */
  container: Element | null;
}

/**
 * Writes an accepted value into the form, through the field that currently renders its control path.
 *
 * The field that opened the dialog can be gone by then: autosave replaces a new Matrix block’s fields when it creates
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

const contexts = new Map<string, AskAiOptions>();
let nextContextId = 0;

/** Hands the panel the options it was opened with. Each context is taken once. */
export function takeAskAiContext(id: string): AskAiOptions {
  const context = contexts.get(id);
  contexts.delete(id);

  if (!context) {
    throw new Error('Ask AI context was not found.');
  }

  return context;
}

/**
 * Opens the prompt and review steps in a slideout panel.
 *
 * Opened with `openSlideoutWith()` rather than `openSlideout()`: the generated
 * values stay client-side until they're applied, so there's no screen to fetch.
 */
export async function openAskAi(options: AskAiOptions): Promise<void> {
  const [{openSlideoutWith}, {default: AskAiSlideout}] = await Promise.all([
    import('@/common/slideouts'),
    import('./AskAiSlideout.vue'),
  ]);

  const contextId = `ask-ai-${++nextContextId}`;
  contexts.set(contextId, options);

  // SAFETY: The slideout host renders this imported Vue SFC exactly like its
  // Inertia page components; it does not require an Inertia page module.
  const panel = openSlideoutWith(AskAiSlideout as InertiaPageComponent, {
    contextId,
  });

  if (!panel) {
    contexts.delete(contextId);
  }
}
