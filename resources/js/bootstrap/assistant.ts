import type {
  TextExpanderOption,
  TextExpanderTriggerBoundary,
} from '@craftcms/ui/components/text-expander/text-expander';
import {shallowReactive} from 'vue';
import {useAssistantPanel} from '@/modules/ai/assistant/assistant';
import {
  useAssistantContext,
  type AssistantContextInput,
  type AssistantContextItem,
} from '@/modules/ai/assistant/context';

/** Something the user can add to the context, from the message box’s Add menu. */
export interface AssistantContextSource {
  /** A plugin-scoped identifier, such as `commerce:order`. */
  id: string;
  /** The menu item’s label, read each time the menu opens. */
  label: () => string;
  icon?: string;
  /** Lower comes first. Craft’s own sources use 0. */
  order?: number;
  /**
   * Whether it has anything to add now, such as while its page is open, read
   * each time the menu opens. Reactive state it reads also shows and hides the
   * menu. Defaults to always.
   */
  available?: () => boolean;
  /** Adds its items to the context, with {@link AssistantApi.addContext}. */
  add: () => void;
}

/**
 * Suggestions the message box offers as the user types a trigger character,
 * such as `@`, which can add what’s chosen to the context.
 */
export interface AssistantTextExpander {
  /** A plugin-scoped identifier, such as `commerce:orders`. */
  id: string;
  /** The character that opens the suggestions. Each one belongs to one expander. */
  trigger: string;
  /** Where the trigger counts. Defaults to `whitespace`, after a space or at the start. */
  boundary?: TextExpanderTriggerBoundary;
  /** Names the suggestions, for assistive technology. */
  label: string;
  /**
   * The suggestions, read as the user types, or `source`, the URL of an
   * action that returns them for `?query=`. Return none to offer nothing.
   */
  options?: () => TextExpanderOption[];
  source?: () => string;
  /** The most suggestions to show. Each can carry a short `data.hint`, such as what kind of thing it is. */
  limit?: number;
  /**
   * Runs once a suggestion is inserted, such as to add it to the context with
   * {@link AssistantApi.addContext}.
   */
  select?: (option: Readonly<TextExpanderOption>) => void;
}

/**
 * The AI assistant, for plugins: what they can add to its context, the
 * sources of context they can offer in its Add menu, and the suggestions
 * its message box offers as the user types.
 */
export interface AssistantApi {
  readonly contextSources: readonly AssistantContextSource[];
  readonly textExpanders: readonly AssistantTextExpander[];
  /**
   * Adds an item to the context, which goes with the user’s next messages
   * until they remove it, and returns its ref. Its `type` is the handle of a
   * context type registered on the server, which describes it to the model.
   */
  addContext(item: AssistantContextInput): string;
  /** The item added with a key, while it’s in the context. Reactive. */
  getContext(key: string): Readonly<AssistantContextItem> | undefined;
  removeContext(ref: string): void;
  registerContextSource(source: AssistantContextSource): void;
  registerTextExpander(expander: AssistantTextExpander): void;
  /** Opens the assistant, and moves focus to its message box. */
  open(): void;
}

export function createAssistantApi(): AssistantApi {
  const contextSources = shallowReactive<AssistantContextSource[]>([]);
  const textExpanders = shallowReactive<AssistantTextExpander[]>([]);

  return {
    get contextSources() {
      return contextSources;
    },

    get textExpanders() {
      return textExpanders;
    },

    addContext(item) {
      return useAssistantContext().add(item);
    },

    getContext(key) {
      return useAssistantContext().get(key);
    },

    removeContext(ref) {
      useAssistantContext().remove(ref);
    },

    registerContextSource(source) {
      const existing = contextSources.find(({id}) => id === source.id);

      if (existing !== undefined && existing !== source) {
        throw new Error(
          `AI assistant context source already registered: ${source.id}`
        );
      }

      if (existing !== undefined) {
        return;
      }

      contextSources.push(source);
      contextSources.sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
    },

    registerTextExpander(expander) {
      const existing = textExpanders.find(
        ({id, trigger}) => id === expander.id || trigger === expander.trigger
      );

      if (existing === expander) {
        return;
      }

      if (existing !== undefined) {
        throw new Error(
          existing.id === expander.id
            ? `AI assistant text expander already registered: ${expander.id}`
            : `AI assistant text expander trigger already taken by ${existing.id}: ${expander.trigger}`
        );
      }

      textExpanders.push(expander);
    },

    open() {
      useAssistantPanel().show();
    },
  };
}

export const assistantApi = createAssistantApi();
