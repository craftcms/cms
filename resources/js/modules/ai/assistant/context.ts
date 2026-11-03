import {createGlobalState} from '@vueuse/core';
import {computed, shallowReactive, shallowRef, type InjectionKey} from 'vue';
import {highlightField} from '@/common/composables/useFieldHighlight';
import {firstFocusableWithin} from '@/common/utils/dom';
import {useLocalStorage} from '@/common/composables/useStorage';
import {fieldId} from '@/modules/ui/runtime';
import type {
  UiNodePayload,
  UiPayload,
  UiValue,
  UiValues,
} from '@/modules/ui/types';

export type AssistantElementParams =
  CraftCms.Cms.Ai.Assistant.Data.ElementParams;

/**
 * An open element editor, which the AI assistant can read the values of, and
 * apply proposed changes to.
 */
export interface AssistantEditor {
  /** Unique to the open editor. */
  id: string;
  /** Identifies the element, which can change while it’s edited, such as when a draft is created. */
  params: () => AssistantElementParams;
  /** The element’s label, such as its title. */
  label: () => string;
  /** The namespace of the editor’s form. */
  scope: () => string[];
  /** The form’s current values, which may not be saved yet. */
  values: () => UiValues;
  /** The dialog the editor is in, or `null` for the page. */
  container: () => Element | null;
}

/** A field added to the assistant’s context. */
export interface AssistantContextField {
  /** The path of the field’s control, relative to the element’s values, such as `fields.summary`. */
  path: string;
  label: string;
}

/**
 * An item in the assistant’s context: an element, or something a plugin
 * added. Its context type, on the server, describes it to the model.
 */
/** A status an item’s chip shows in place of its icon, as element chips do. */
export interface AssistantContextStatus {
  /** A Craft color name, such as `green`. */
  fill: string;
  label: string;
  /** Shown as the draft icon rather than a dot. */
  draft?: boolean;
}

export interface AssistantContextItem {
  /** Unique to the item, and sent with it, so proposed changes can be matched to it. */
  ref: string;
  /** The handle of its context type, such as `craft:element`. */
  type: string;
  /** Identifies what the item is, so adding it again replaces it. */
  key: string;
  /** Shown on its chip. */
  label: string;
  icon?: string;
  /** What’s sent with each message for the item, read as it’s sent. */
  data: () => Record<string, unknown>;
  /** What the message keeps of the item, for later messages, read as it’s sent. */
  reference: () => Record<string, unknown>;
  /** Its status, if it has one. Reactive. */
  status: () => AssistantContextStatus | null;
  /** Kept with the item for whoever added it, such as what to show in a picker. Not sent. */
  meta: Record<string, unknown>;
}

/** Something to add to the assistant’s context, as `Cp.$assistant.addContext()` takes it. */
export interface AssistantContextInput {
  /** The handle of its context type, registered on the server, such as `commerce:order`. */
  type: string;
  /** Identifies what it is, so adding it again replaces it rather than adding it twice. Defaults to a new one each time. */
  key?: string;
  label: string;
  icon?: string;
  /** What the context type gets, as an object, or a function that returns it when a message is sent. */
  data: Record<string, unknown> | (() => Record<string, unknown>);
  /**
   * What the message keeps of the item, which the context type gets again
   * with later messages, marked `earlier`: enough to find it again, such as
   * an ID. Stored with the message, so keep it small. Without one, later
   * messages don’t get the item.
   */
  reference?: () => Record<string, unknown>;
  /** A status its chip shows, such as an element’s, as a value or a reactive function. */
  status?: AssistantContextStatus | (() => AssistantContextStatus | null);
  /** Kept with the item, for reading back with `Cp.$assistant.getContext()`. Not sent. */
  meta?: Record<string, unknown>;
}

/**
 * A proposed change to a field, as the `propose_changes` tool returns it, with
 * its value and payloads typed as the UI renders them.
 */
export type AssistantProposal = Omit<
  CraftCms.Cms.Ai.Assistant.Data.Proposal,
  'value' | 'preview' | 'node'
> & {
  value: UiValue;
  preview: {current: UiPayload; proposed: UiPayload} | null;
  node: UiNodePayload | null;
};

/** Provided by each element editor the assistant can work with, for its fields. */
export const AssistantEditorKey: InjectionKey<AssistantEditor> =
  Symbol('AssistantEditor');

/** The open editors, in the order they were opened. */
const editors = shallowReactive(new Map<string, AssistantEditor>());

/** The editor each context item came from, kept after the item is removed. */
const refEditors = new Map<string, string>();

/** Remembers the editor a context item came from, for applying the changes proposed for it. */
export function rememberAssistantEditor(ref: string, editorId: string): void {
  refEditors.set(ref, editorId);
}

/** Registers an open editor, and returns a function that unregisters it. */
export function registerAssistantEditor(editor: AssistantEditor): () => void {
  editors.set(editor.id, editor);

  return () => editors.delete(editor.id);
}

/** Returns the open editors, the most recently opened last. */
export function assistantEditors(): AssistantEditor[] {
  return [...editors.values()];
}

export function assistantEditor(id: string): AssistantEditor | null {
  return editors.get(id) ?? null;
}

/**
 * Returns the open editor a proposed change is applied to: the one its context
 * item came from, or after a reload, the latest one open for its element, or
 * else for a draft of the element, or the element of a draft, as autosaving
 * creates a draft and saving applies it. Returns `null` when none is open.
 */
export function assistantEditorFor(
  proposal: AssistantProposal
): AssistantEditor | null {
  const id = proposal.ref ? refEditors.get(proposal.ref) : undefined;
  const editors = assistantEditors()
    .reverse()
    .filter((editor) => editor.params().siteId === proposal.siteId);

  return (
    (id ? assistantEditor(id) : null) ??
    editors.find(
      (editor) => editor.params().elementId === proposal.elementId
    ) ??
    editors.find(
      (editor) =>
        (editor.params().canonicalId ?? editor.params().elementId) ===
        proposal.canonicalId
    ) ??
    null
  );
}

/**
 * The elements and fields the user added to the assistant’s context, which go
 * with each message they send.
 */
export const useAssistantContext = createGlobalState(() => {
  /** What the user is adding for their next message. */
  const items = shallowRef<AssistantContextItem[]>([]);

  return {
    items,

    /**
     * Adds an item, or replaces the one with its key, keeping its place and
     * ref, and returns its ref.
     */
    add(input: AssistantContextInput): string {
      const key = input.key ?? crypto.randomUUID();
      const {data} = input;
      const existing = items.value.find((item) => item.key === key);
      const next: AssistantContextItem = {
        ref: existing?.ref ?? crypto.randomUUID(),
        type: input.type,
        key,
        label: input.label,
        icon: input.icon,
        data: typeof data === 'function' ? data : () => data,
        reference: input.reference ?? (() => ({})),
        status:
          typeof input.status === 'function'
            ? input.status
            : () =>
                (input.status as AssistantContextStatus | undefined) ?? null,
        meta: input.meta ?? {},
      };

      items.value = existing
        ? items.value.map((item) => (item === existing ? next : item))
        : [...items.value, next];

      return next.ref;
    },

    /** The item with a key, if it’s in the context. */
    get(key: string): AssistantContextItem | undefined {
      return items.value.find((item) => item.key === key);
    },

    remove(ref: string): void {
      items.value = items.value.filter((item) => item.ref !== ref);
    },

    /**
     * Takes the items for a message being sent, clearing them from the
     * composer. Later messages get them from the conversation.
     */
    takeForMessage(): AssistantContextItem[] {
      const taken = items.value;
      items.value = [];

      return taken;
    },
  };
});

/**
 * The context for a request: the data of each item a message was sent with,
 * with its type and ref, so answers to the message’s approval requests have it
 * too.
 */
export function contextRequest(
  items: readonly AssistantContextItem[]
): Array<Record<string, unknown>> {
  return items.map((item) => ({
    ...item.data(),
    type: item.type,
    ref: item.ref,
  }));
}

/** How many conversations the applied changes are remembered for. */
const APPLIED_THREADS = 20;

/**
 * The proposed changes the user applied in a conversation, remembered across
 * page loads, for the latest conversations. Each is identified by the tool
 * call that proposed it and its field’s key.
 */
export function useAppliedProposals(threadId: string) {
  // Written straight away, so every list of changes reads what the others
  // have just written.
  const stored = useLocalStorage<Record<string, string[]>>(
    'aiAssistant.applied',
    {},
    {flush: 'sync'}
  );

  return {
    applied: computed(() => new Set(stored.value[threadId] ?? [])),

    add(ids: string[]): void {
      const {[threadId]: current = [], ...others} = stored.value;

      // The conversation moves to the end, so the oldest are forgotten first.
      stored.value = {
        ...Object.fromEntries(
          Object.entries(others).slice(-(APPLIED_THREADS - 1))
        ),
        [threadId]: [...new Set([...current, ...ids])],
      };
    },
  };
}

/** The detail of the event that applies a proposed change to its field. */
export interface ApplyAssistantProposalDetail {
  proposal: AssistantProposal;
  /** The dialog the field’s editor is in, or `null` for the page. */
  container: Element | null;
  /** Whether to show the field, such as by selecting its tab. */
  reveal: boolean;
  /** Set by the field that applied it. */
  applied: boolean;
}

/** Returns the dialog an element is in, or `null` for the page. */
export function assistantContainer(element: Element): Element | null {
  return element.closest('[role="dialog"]');
}

/**
 * Applies a proposed change to its field in an open editor, and returns
 * whether a field took it.
 */
export function applyAssistantProposal(
  proposal: AssistantProposal,
  editor: AssistantEditor,
  reveal: boolean
): boolean {
  const detail: ApplyAssistantProposalDetail = {
    proposal,
    container: editor.container(),
    reveal,
    applied: false,
  };

  window.dispatchEvent(
    new CustomEvent('craft:apply-ai-assistant-proposal', {detail})
  );

  return detail.applied;
}

/** A field a `show_field` call showed, in its element’s editor. */
export interface AssistantFieldTarget {
  element: {
    canonicalId: number;
    siteId: number;
    label: string;
    cpEditUrl: string | null;
  };
  field: {
    label: string;
    /** The path of the field’s control, relative to the element’s values, such as `['fields', 'summary']`. */
    path: string[];
  };
}

/**
 * Highlights a field in its element’s open editor, the latest one if there
 * are several, and moves focus to it. Returns whether it found it.
 */
export function revealAssistantField(target: AssistantFieldTarget): boolean {
  const editor = assistantEditors()
    .reverse()
    .find(
      (editor) =>
        editor.params().canonicalId === target.element.canonicalId &&
        editor.params().siteId === target.element.siteId
    );
  const id = editor ? fieldId([...editor.scope(), ...target.field.path]) : null;
  const field = id
    ? (editor?.container() ?? document).querySelector<HTMLElement>(
        `craft-field#${CSS.escape(id)}`
      )
    : null;

  if (!field) {
    return false;
  }

  // Once it’s shown, as it may be on a tab that isn’t. The control rather than
  // the first thing in the field, which can be its actions menu.
  void highlightField(field).then(() =>
    firstFocusableWithin(
      field.querySelector<HTMLElement>(':scope > [slot="input"]') ?? field
    )?.focus({preventScroll: true})
  );

  return true;
}

/**
 * The URL that opens a field’s element at the field, or `null` if the user
 * can’t edit it. The page’s editor highlights the field its fragment names.
 */
export function assistantFieldUrl(target: AssistantFieldTarget): string | null {
  return target.element.cpEditUrl
    ? `${target.element.cpEditUrl}#${fieldId(target.field.path)}`
    : null;
}

/** What a message keeps of each item it was sent with. */
export interface AssistantMessageContext {
  type: string;
  ref: string;
  label: string;
  icon?: string;
  status?: AssistantContextStatus;
  /** The item’s reference, if it has one. */
  data?: Record<string, unknown>;
}

const CONTEXT_BLOCK = /^<context>(.*?)<\/context>\n\n/s;

/**
 * Puts what a message was sent with at its start, so it’s stored with the
 * message, where the model reads it as part of the message, later messages
 * find it again, and the chat shows it again.
 */
export function withMessageContext(
  text: string,
  items: readonly AssistantContextItem[]
): string {
  if (!items.length) {
    return text;
  }

  const context = items.map((item): AssistantMessageContext => {
    const reference = item.reference();
    const status = item.status();

    return {
      type: item.type,
      ref: item.ref,
      label: item.label,
      icon: item.icon,
      ...(status ? {status} : {}),
      ...(Object.keys(reference).length ? {data: reference} : {}),
    };
  });

  return `<context>${JSON.stringify(context)}</context>\n\n${text}`;
}

/** Splits a message into its text and what it was sent with. */
export function splitMessageContext(content: string): {
  text: string;
  context: AssistantMessageContext[];
} {
  const match = CONTEXT_BLOCK.exec(content);

  if (!match) {
    return {text: content, context: []};
  }

  try {
    const context = JSON.parse(match[1] ?? '') as unknown;

    return {
      text: content.slice(match[0].length),
      context: Array.isArray(context)
        ? (context as AssistantMessageContext[])
        : [],
    };
  } catch {
    return {text: content, context: []};
  }
}
