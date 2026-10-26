<script setup lang="ts">
  /**
   * One conversation with the AI assistant: its messages, and a box to send
   * the next one. The conversation is stored on the server, so it's shown again
   * after the page reloads.
   */
  import {t} from '@craftcms/ui';
  import type {ActionMenuItem} from '@craftcms/ui/components/action-menu/action-menu.types';
  import '@craftcms/ui/components/empty/empty';
  import '@craftcms/ui/components/textarea/textarea';
  import type CraftTextarea from '@craftcms/ui/components/textarea/textarea';
  import {useChat, type UIMessage} from '@tanstack/ai-vue';
  import {useElementSize} from '@vueuse/core';
  import type {
    TextExpanderSelectDetail,
    TextExpanderTrigger,
    TextExpanderTriggers,
  } from '@craftcms/ui/components/text-expander/text-expander';
  import '@craftcms/ui/components/text-expander/text-expander';
  import {
    computed,
    onMounted,
    shallowRef,
    useId,
    useTemplateRef,
    watch,
  } from 'vue';
  import {firstFocusableWithin} from '@/common/utils/dom';
  import {usePinnedScroll} from '@/common/composables/usePinnedScroll';
  import {assistantApi} from '@/bootstrap/assistant';
  import AssistantApprovals, {
    type AssistantApproval,
  } from './AssistantApprovals.vue';
  import AssistantChipStatus from './AssistantChipStatus.vue';
  import AssistantMentionHighlights from './AssistantMentionHighlights.vue';
  import AssistantContextPicker from './AssistantContextPicker.vue';
  import AssistantProposals from './AssistantProposals.vue';
  import {
    AssistantRequestError,
    assistantConnection,
    renderMarkdown,
  } from './assistant';
  import {
    splitMessageContext,
    useAssistantContext,
    withMessageContext,
    type AssistantMessageContext,
    type AssistantProposal,
  } from './context';
  import {
    elementContextEditor,
    latestEditor,
    loadElementFields,
    openElementRequest,
  } from './element-context';

  const props = defineProps<{
    /** The conversation’s id, which the server stores it under. */
    threadId: string;
  }>();

  const emit = defineEmits<{
    /** A reply has finished. */
    finish: [];
  }>();

  const draft = shallowRef('');
  const status = shallowRef('');
  const history = useTemplateRef<HTMLElement>('history');
  const thread = useTemplateRef<HTMLElement>('thread');
  const messageBox = useTemplateRef<CraftTextarea>('messageBox');
  const composer = useTemplateRef<HTMLElement>('composer');
  // The composer floats over the end of the conversation, so whatever gets
  // focus in it is scrolled into view above the composer.
  const {height: composerHeight} = useElementSize(composer, undefined, {
    box: 'border-box',
  });
  const idPrefix = useId();
  const historyScroll = usePinnedScroll(history, thread);
  const context = useAssistantContext();

  const {
    messages,
    sendMessage,
    isLoading,
    stop,
    error,
    interrupts,
    resumeInterruptsUnsafe,
  } = useChat({
    connection: assistantConnection(() => ({
      context: [...context.request(), ...openElementRequest()],
    })),
    threadId: props.threadId,
    persistence: true,
    onFinish: (message) => {
      status.value = textOf(message);
      emit('finish');
    },
  });

  const errorMessage = computed(() => {
    if (!error.value) {
      return null;
    }

    // A failed request is the cause of the error the chat client reports.
    const cause = error.value.cause ?? error.value;

    return cause instanceof AssistantRequestError
      ? cause.message
      : t(
          'The assistant couldn’t reply. Try again, or ask an admin to check the AI connection.'
        );
  });

  /** Whether the assistant is working on a reply, but hasn’t written any of it yet. */
  const isThinking = computed(() => {
    const last = messages.value.at(-1);

    return (
      isLoading.value &&
      (last?.role !== 'assistant' ||
        !last.parts.some((part) => part.type === 'text' && part.content))
    );
  });

  type MessagePart = UIMessage['parts'][number];

  type ThreadEntry =
    | {
        key: string;
        kind: 'prompt';
        text: string;
        context: AssistantMessageContext[];
      }
    | {key: string; kind: 'text'; part: Extract<MessagePart, {type: 'text'}>}
    | {
        key: string;
        kind: 'tool';
        part: Extract<MessagePart, {type: 'tool-call'}>;
      };

  /**
   * The conversation as the thread shows it: each message the user sent, and
   * each piece of a reply, its text or a tool call, as an entry of its own.
   */
  const entries = computed<ThreadEntry[]>(() =>
    messages.value.flatMap((message): ThreadEntry[] =>
      message.role === 'user'
        ? [
            {
              key: message.id,
              kind: 'prompt',
              ...splitMessageContext(textOf(message)),
            },
          ]
        : message.parts.flatMap((part, index): ThreadEntry[] => {
            const key = `${message.id}-${index}`;

            if (part.type === 'text') {
              return part.content ? [{key, kind: 'text', part}] : [];
            }

            return part.type === 'tool-call' ? [{key, kind: 'tool', part}] : [];
          })
    )
  );

  function textOf(message: UIMessage): string {
    return message.parts
      .flatMap((part) => (part.type === 'text' ? [part.content] : []))
      .join('\n\n');
  }

  /** The message box’s own control, which the text expanders attach to. */
  const messageBoxInput = shallowRef<HTMLTextAreaElement | null>(null);
  const messageBoxInputId = computed(() => messageBoxInput.value?.id ?? null);
  const card = useTemplateRef<HTMLElement>('card');
  /** What the text expanders inserted in the message, which is highlighted. */
  const mentions = shallowRef<string[]>([]);

  onMounted(() => {
    const input = messageBox.value?.querySelector('textarea');

    if (input) {
      input.id ||= `${idPrefix}-message`;
      messageBoxInput.value = input;
    }
  });

  // The `{` expander offers the open element’s fields.
  watch(
    latestEditor,
    (editor) => {
      if (editor) {
        void loadElementFields(editor);
      }
    },
    {immediate: true}
  );

  /** The registered text expanders, as the message box’s triggers. */
  const textExpanderTriggers = computed<TextExpanderTriggers>(() =>
    assistantApi.textExpanders.flatMap((expander): TextExpanderTrigger[] => {
      const base = {
        trigger: expander.trigger,
        boundary: expander.boundary ?? ('whitespace' as const),
        label: expander.label,
        limit: expander.limit,
      };

      if (expander.source) {
        return [{...base, source: expander.source()}];
      }

      const options = expander.options?.() ?? [];

      return options.length ? [{...base, options}] : [];
    })
  );

  /** Names the triggers, leaving out `{` while there are no fields to offer. */
  const messageBoxPlaceholder = computed(() =>
    textExpanderTriggers.value.some(({trigger}) => trigger === '{')
      ? t('Ask anything. Type {element} for elements, {field} for fields', {
          element: '@',
          field: '{',
        })
      : t('Ask anything. Type {element} for elements', {element: '@'})
  );

  /** Lets the expander whose suggestion was inserted act on it. */
  function onTextExpanderSelect(
    event: CustomEvent<TextExpanderSelectDetail>
  ): void {
    const inserted = event.detail.option.value;

    if (!mentions.value.includes(inserted)) {
      mentions.value = [...mentions.value, inserted];
    }

    assistantApi.textExpanders
      .find(({trigger}) => trigger === event.detail.character)
      ?.select?.(event.detail.option);
  }

  /** The sources of context with something to add now, for the Add menu. */
  const addableSources = computed(() =>
    assistantApi.contextSources.filter((source) => source.available?.() ?? true)
  );

  function addMenuItems(): ActionMenuItem[] {
    return addableSources.value.map((source) => ({
      label: source.label(),
      icon: source.icon,
      onClick: () => source.add(),
    }));
  }

  /** The user’s answers to the assistant’s approval requests, by tool call id. */
  const decisions = shallowRef<Record<string, boolean>>({});
  const sendingDecisions = shallowRef(false);

  /**
   * The tool calls waiting for the user’s approval. They come from
   * `laravel/ai`, which TanStack AI can show but not answer itself, so they’re
   * answered with AG-UI resume entries.
   */
  const approvals = computed<AssistantApproval[]>(() =>
    interrupts.value.flatMap((interrupt) => {
      const metadata = interrupt.metadata as
        | {kind?: string; toolName?: string; input?: unknown}
        | undefined;

      return interrupt.reason === 'approval_required' &&
        metadata?.kind === 'approval'
        ? [
            {
              id: interrupt.interruptId,
              tool: mcpToolName(metadata.toolName ?? ''),
              reason: interrupt.message ?? null,
              input: metadata.input ?? {},
            },
          ]
        : [];
    })
  );

  watch(
    () => approvals.value.map((approval) => approval.id).join(),
    () => {
      if (
        approvals.value.some((approval) => !(approval.id in decisions.value))
      ) {
        status.value = t('The assistant is waiting for your approval.');
      }
    }
  );

  /**
   * Whether the user tried to send a message while the assistant was waiting
   * for their approval, which has to come first.
   */
  const sendBlocked = shallowRef(false);

  watch(
    () => interrupts.value.length,
    (count) => {
      if (count === 0) {
        sendBlocked.value = false;
      }
    }
  );

  /** Records an answer, and once every request has one, sends them. */
  async function decide(id: string, approved: boolean): Promise<void> {
    decisions.value = {...decisions.value, [id]: approved};

    if (approvals.value.some((approval) => !(approval.id in decisions.value))) {
      return;
    }

    sendingDecisions.value = true;
    historyScroll.pin();

    try {
      const sent = await resumeInterruptsUnsafe(
        approvals.value.map((approval) => ({
          interruptId: approval.id,
          status: 'resolved',
          payload: {approved: decisions.value[approval.id]},
        }))
      );

      // The requests are still open, so the user can answer them again.
      if (!sent) {
        decisions.value = Object.fromEntries(
          Object.entries(decisions.value).filter(
            ([id]) => !approvals.value.some((approval) => approval.id === id)
          )
        );
      }
    } finally {
      sendingDecisions.value = false;
    }
  }

  /** A tool’s name as Craft’s MCP server knows it, such as `elements.get`. */
  function mcpToolName(name: string): string {
    return name.replaceAll('_', '.');
  }

  /**
   * The assistant’s own tools, rather than the MCP server’s: their icons, and
   * what they say they did or are doing.
   */
  const ownTools: Record<
    string,
    {icon: string; done: () => string; running: () => string}
  > = {
    read_editor_element: {
      icon: 'eye',
      done: () => t('Read the element you’re editing'),
      running: () => t('Reading the element you’re editing…'),
    },
    propose_changes: {
      icon: 'pen-to-square',
      done: () => t('Proposed changes'),
      running: () => t('Proposing changes…'),
    },
  };

  /** A tool call’s icon: its own, or a wrench for the MCP server’s tools. */
  function toolIcon(name: string): string {
    return ownTools[name]?.icon ?? 'wrench';
  }

  /** What a tool call did, is doing, or is waiting to do. */
  function toolActivity(part: {
    id: string;
    name: string;
    state: string;
    output?: unknown;
    approval?: {approved?: boolean};
  }): string {
    const own = ownTools[part.name];
    const tool = mcpToolName(part.name);

    if ((part.approval?.approved ?? decisions.value[part.id]) === false) {
      return t('Rejected {tool}', {tool});
    }

    if (part.output !== undefined) {
      return own ? own.done() : t('Used {tool}', {tool});
    }

    if (part.state === 'error') {
      return t('Didn’t finish using {tool}', {tool});
    }

    if (
      part.state === 'approval-requested' ||
      approvals.value.some((approval) => approval.id === part.id)
    ) {
      return t('Waiting for approval to use {tool}', {tool});
    }

    return own ? own.running() : t('Using {tool}…', {tool});
  }

  /** A tool call’s result, which tools return as JSON. */
  function resultOf(output: unknown): unknown {
    if (typeof output !== 'string') {
      return output;
    }

    try {
      return JSON.parse(output);
    } catch {
      return null;
    }
  }

  /** The changes a `propose_changes` call proposed, once it’s done. */
  function proposalsOf(output: unknown): AssistantProposal[] {
    const proposals = (resultOf(output) as {proposals?: unknown} | null)
      ?.proposals;

    return Array.isArray(proposals) ? (proposals as AssistantProposal[]) : [];
  }

  /**
   * The images replies show inline: those Craft made for the conversation with
   * `assets.transform-url`. Any other image could be on a server that a reply
   * steered by injected content sends data to, so it’s a link.
   */
  const inlineImages = computed(
    () =>
      new Set(
        messages.value.flatMap((message) =>
          message.parts.flatMap((part) => {
            const url =
              part.type === 'tool-call' &&
              mcpToolName(part.name) === 'assets.transform-url'
                ? (resultOf(part.output) as {url?: unknown} | null)?.url
                : null;

            return typeof url === 'string' ? [url] : [];
          })
        )
      )
  );

  /**
   * Enter sends the message. Shift + Enter and Ctrl/Command + Enter start a new
   * line, which a textarea doesn't do for Ctrl/Command + Enter on its own. On
   * touch screens, whose keyboards have no Shift + Enter, Enter starts a new
   * line, and the Send button sends.
   */
  function onDraftKeydown(event: KeyboardEvent): void {
    if (
      event.key !== 'Enter' ||
      event.shiftKey ||
      event.altKey ||
      event.isComposing ||
      window.matchMedia?.('(pointer: coarse)').matches
    ) {
      return;
    }

    event.preventDefault();

    if (!event.ctrlKey && !event.metaKey) {
      void send();

      return;
    }

    const [textarea] = event.composedPath();

    if (textarea instanceof HTMLTextAreaElement) {
      textarea.setRangeText(
        '\n',
        textarea.selectionStart,
        textarea.selectionEnd,
        'end'
      );
      textarea.dispatchEvent(new Event('input', {bubbles: true}));
    }
  }

  function onDraftChange(event: Event): void {
    draft.value = String((event.target as CraftTextarea).modelValue ?? '');
  }

  async function send(): Promise<void> {
    const message = draft.value.trim();

    if (!message || isLoading.value) {
      return;
    }

    // The message stays in the box until the user has answered.
    if (interrupts.value.length) {
      sendBlocked.value = true;

      return;
    }

    draft.value = '';
    mentions.value = [];
    status.value = '';
    historyScroll.pin();
    await sendMessage(withMessageContext(message, context.takeForMessage()));
  }

  /** The message box’s own control, which gets focus when the chat opens. */
  function focusTarget(): HTMLElement | null {
    return (messageBox.value && firstFocusableWithin(messageBox.value)) || null;
  }

  /** Moves focus to the message box. */
  function focus(): void {
    focusTarget()?.focus();
  }

  defineExpose({focus, focusTarget});
</script>

<template>
  <div class="assistant-chat">
    <div role="status" class="sr-only">{{ status }}</div>

    <div
      ref="history"
      class="assistant-chat__history"
      :style="{
        scrollPaddingBlockEnd: `calc(${composerHeight}px + var(--c-spacing-md))`,
      }"
    >
      <ol
        v-if="messages.length || approvals.length"
        ref="thread"
        class="assistant-chat__thread"
        :aria-label="t('Conversation')"
      >
        <template v-for="entry in entries" :key="entry.key">
          <li
            v-if="entry.kind === 'prompt'"
            class="assistant-chat__message assistant-chat__message--user"
          >
            <div class="assistant-chat__prompt">
              <span class="sr-only">{{ t('You:') }}</span>
              <ul
                v-if="entry.context.length"
                class="assistant-chat__chips"
                :aria-label="t('Context')"
              >
                <li v-for="(item, index) in entry.context" :key="index">
                  <craft-chip
                    :icon="item.status ? undefined : item.icon"
                    :show-status="!!item.status || undefined"
                    size="auto"
                  >
                    <AssistantChipStatus
                      v-if="item.status"
                      :status="item.status"
                    />
                    {{ item.label }}
                  </craft-chip>
                </li>
              </ul>
              <div
                class="prose assistant-chat__prompt-text"
                v-html="renderMarkdown(entry.text)"
              ></div>
            </div>
          </li>
          <li v-else-if="entry.kind === 'text'" class="assistant-chat__message">
            <div class="assistant-chat__reply">
              <span class="sr-only">{{ t('AI assistant:') }}</span>
              <div
                class="prose assistant-chat__markdown"
                v-html="renderMarkdown(entry.part.content, inlineImages)"
              ></div>
            </div>
          </li>
          <li
            v-else
            class="assistant-chat__message assistant-chat__message--assistant assistant-chat__message--activity"
          >
            <span class="assistant-chat__icon">
              <craft-icon :name="toolIcon(entry.part.name)"></craft-icon>
            </span>
            <p class="assistant-chat__activity">
              {{ toolActivity(entry.part) }}
            </p>
            <AssistantProposals
              v-if="
                entry.part.name === 'propose_changes' &&
                proposalsOf(entry.part.output).length
              "
              class="assistant-chat__cards"
              :proposals="proposalsOf(entry.part.output)"
              :thread-id="threadId"
              :call-id="entry.part.id"
              @status="status = $event"
            />
          </li>
        </template>
        <li v-if="approvals.length" class="assistant-chat__message">
          <AssistantApprovals
            :approvals="approvals"
            :decisions="decisions"
            :sending="sendingDecisions"
            @decide="decide"
          />
        </li>
        <li
          v-if="isThinking"
          class="assistant-chat__message assistant-chat__message--assistant assistant-chat__message--activity"
        >
          <span class="assistant-chat__icon">
            <craft-icon name="sparkles"></craft-icon>
          </span>
          <p class="assistant-chat__activity">{{ t('Thinking…') }}</p>
        </li>
      </ol>
      <craft-empty
        v-else
        class="assistant-chat__empty"
        icon="sparkles"
        :label="t('Ask about this site')"
      >
        <p>
          {{
            t(
              'Ask about the site’s content and settings. The assistant can only see what you can.'
            )
          }}
        </p>
      </craft-empty>

      <form
        ref="composer"
        class="assistant-chat__composer"
        @submit.prevent="send"
      >
        <div role="alert">
          <craft-callout v-if="errorMessage" variant="danger">{{
            errorMessage
          }}</craft-callout>
          <craft-callout v-else-if="sendBlocked" variant="warning">{{
            t(
              'Approve or reject the assistant’s request before you send another message.'
            )
          }}</craft-callout>
        </div>

        <div ref="card" class="assistant-chat__card">
          <AssistantMentionHighlights
            v-if="messageBoxInput && card"
            :target="messageBoxInput"
            :container="card"
            :text="draft"
            :mentions="mentions"
          />
          <ul
            v-if="context.items.value.length"
            class="assistant-chat__chips"
            :aria-label="t('Context')"
          >
            <li v-for="item in context.items.value" :key="item.ref">
              <craft-chip
                :icon="item.status() ? undefined : item.icon"
                :show-status="!!item.status() || undefined"
              >
                <AssistantChipStatus
                  v-if="item.status()"
                  :status="item.status()!"
                />
                <AssistantContextPicker
                  v-if="elementContextEditor(item)"
                  :editor="elementContextEditor(item)!"
                  :label="item.label"
                />
                <template v-else>{{ item.label }}</template>
                <craft-button
                  slot="suffix"
                  type="button"
                  icon
                  size="small"
                  variant="plain"
                  @click="assistantApi.removeContext(item.ref)"
                >
                  <craft-icon
                    name="xmark"
                    :label="t('Remove {name}', {name: item.label})"
                  ></craft-icon>
                </craft-button>
              </craft-chip>
            </li>
          </ul>

          <craft-textarea
            ref="messageBox"
            class="assistant-chat__message-box"
            :class="{'assistant-chat__message-box--empty': !draft}"
            :label="t('Ask anything')"
            :placeholder="messageBoxPlaceholder"
            rows="2"
            .modelValue="draft"
            @model-value-changed="onDraftChange"
            @keydown="onDraftKeydown"
          ></craft-textarea>
          <craft-text-expander
            v-if="messageBoxInputId"
            :for="messageBoxInputId"
            .triggers="textExpanderTriggers"
            @craft-select="onTextExpanderSelect"
          ></craft-text-expander>

          <div class="assistant-chat__toolbar">
            <craft-action-menu
              v-if="addableSources.length"
              :label="t('Add to context')"
              .actions="addMenuItems"
            >
              <craft-button
                slot="invoker"
                type="button"
                variant="plain"
                size="small"
                icon="plus"
              >
                {{ t('Add') }}
              </craft-button>
            </craft-action-menu>
            <!-- Icon-only buttons, named by their tooltips, so the names show on
              hover and focus too. The icons name them until the tooltips do. -->
            <template v-if="isLoading">
              <craft-button
                :id="`${idPrefix}-stop`"
                key="stop"
                class="assistant-chat__send"
                type="button"
                variant="danger"
                size="small"
                icon
                @click="stop"
              >
                <craft-icon name="stop" :label="t('Stop')"></craft-icon>
              </craft-button>
              <craft-tooltip :for="`${idPrefix}-stop`" invoker-relation="label">
                {{ t('Stop') }}
              </craft-tooltip>
            </template>
            <template v-else>
              <craft-button
                :id="`${idPrefix}-send`"
                key="send"
                class="assistant-chat__send"
                type="submit"
                :variant="draft.trim() ? 'primary' : 'fill'"
                size="small"
                icon
              >
                <craft-icon name="arrow-up" :label="t('Send')"></craft-icon>
              </craft-button>
              <craft-tooltip :for="`${idPrefix}-send`" invoker-relation="label">
                {{ t('Send') }}
              </craft-tooltip>
            </template>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
  .assistant-chat {
    display: flex;
    flex-direction: column;
    min-height: 0;
    flex: 1;
  }

  .assistant-chat__history {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding-block-start: var(--c-spacing-lg);
    padding-inline: var(--c-spacing-lg);
  }

  .assistant-chat__thread {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-lg);
  }

  .assistant-chat__message--user {
    display: flex;
    justify-content: flex-end;
  }

  .assistant-chat__prompt {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--c-spacing-md);
    max-inline-size: 85%;
    padding: var(--c-spacing-lg);
    border-radius: var(--c-radius-lg);
    background-color: var(--c-color-neutral-fill-quiet);
    color: var(--c-color-neutral-on-quiet);
  }

  .assistant-chat__prompt-text {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    align-self: stretch;
    text-box: trim-both cap alphabetic;
    overflow-wrap: anywhere;
  }

  .assistant-chat__prompt-text :deep(p) {
    white-space: pre-line;
  }

  .assistant-chat__message--assistant {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: var(--c-spacing-md) var(--c-spacing-sm);
  }

  /* Tool calls, and the thinking after them, read as steps of one reply, so
     they sit closer together. */
  .assistant-chat__message--activity + .assistant-chat__message--activity {
    margin-block-start: calc(var(--c-spacing-md) - var(--c-spacing-lg));
  }

  /* The icon box is a line of the activity's smaller text, so the icon is
     centered on it, at the size of the other icons. */
  .assistant-chat__message--activity .assistant-chat__icon {
    font-size: var(--c-text-sm);
  }

  .assistant-chat__icon {
    display: flex;
    align-items: center;
    block-size: 1lh;
    color: var(--c-text-quiet);
  }

  .assistant-chat__icon craft-icon {
    font-size: var(--c-text-base);
  }

  .assistant-chat__reply {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    overflow-wrap: anywhere;
  }

  .assistant-chat__cards {
    grid-column: 1 / -1;
  }

  .assistant-chat__activity {
    color: var(--c-color-neutral-on-quiet);
    font-size: var(--c-text-sm);
  }

  .assistant-chat__markdown {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  /* Headings in a reply structure a short answer in a narrow panel, so they
     stay close to the text's size. */
  .assistant-chat__markdown :deep(:is(h1, h2)) {
    font-size: var(--c-text-lg);
    font-weight: 600;
  }

  .assistant-chat__markdown :deep(:is(h3, h4, h5, h6)) {
    font-size: var(--c-text-base);
    font-weight: 600;
  }

  .assistant-chat__markdown :deep(pre) {
    overflow-x: auto;
    padding: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-sunken);
  }

  .assistant-chat__markdown :deep(code) {
    font-family: var(--c-font-mono);
    font-size: 0.9em;
  }

  .assistant-chat__markdown :deep(img) {
    max-inline-size: 100%;
    block-size: auto;
  }

  .assistant-chat__markdown :deep(table) {
    display: block;
    overflow-x: auto;
    border-collapse: collapse;
  }

  .assistant-chat__markdown :deep(th),
  .assistant-chat__markdown :deep(td) {
    padding-block: var(--c-spacing-xs);
    padding-inline: var(--c-spacing-sm);
    border: 1px solid var(--c-color-neutral-border-quiet);
    text-align: start;
  }

  .assistant-chat__empty {
    flex: 1;
  }

  .assistant-chat__composer {
    position: sticky;
    inset-block-end: 0;
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    margin-block-start: auto;
    padding-block: var(--c-spacing-lg);
  }

  .assistant-chat__card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xs);
    padding-block: var(--c-spacing-sm) var(--c-spacing-md);
    padding-inline: var(--c-spacing-md);
    border: var(--c-form-control-border-width) solid
      var(--c-form-control-border-color);
    border-radius: var(--c-radius-lg);
    background-color: var(--c-surface-white);
    box-shadow: var(--c-shadow-md);
  }

  .assistant-chat__card:has(textarea:focus-visible) {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
  }

  .assistant-chat__message-box {
    position: relative;
    z-index: 1;
    --c-input-border-width: 0;
    --c-input-fill: transparent;
    --c-input-shadow: none;
    --c-focus-outline-width: 0;
  }

  .assistant-chat__message-box :deep(label) {
    display: block;
    max-block-size: 1lh;
    overflow: clip;
    transition-property: max-block-size, margin-block-end, opacity;
    transition-duration: 150ms;
    transition-timing-function: var(--ease-out);
  }

  .assistant-chat__message-box--empty :deep(label) {
    max-block-size: 0;
    margin-block-end: calc(var(--c-spacing-sm) * -1);
    opacity: 0;
  }

  @media (prefers-reduced-motion: reduce) {
    .assistant-chat__message-box :deep(label) {
      transition-property: opacity;
    }
  }

  .assistant-chat__message-box :deep(textarea) {
    padding-block: var(--c-spacing-xs);
    padding-inline: 0;
    resize: none;
  }

  .assistant-chat__chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--c-spacing-xs);
  }

  .assistant-chat__card > .assistant-chat__chips {
    justify-content: flex-start;
    margin-block-end: var(--c-spacing-sm);
  }

  .assistant-chat__toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-xs);
  }

  .assistant-chat__send {
    margin-inline-start: auto;
  }

  .assistant-chat__send {
    --c-button-radius: 9999px;
  }
</style>
