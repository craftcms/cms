<script setup lang="ts">
  /**
   * “Ask AI”, as an element details panel: a conversation with the model about
   * the element. Each prompt goes out with the fields chosen as its context,
   * and each reply lists the changes it proposes, which are applied to the
   * editor one by one. Follow-ups refine earlier replies.
   *
   * A field's own “Ask AI” action sets the context to that field. The
   * conversation belongs to the editor, so the panel's header can start a new
   * one.
   */
  import {actionClient, t} from '@craftcms/ui';
  import '@craftcms/ui/components/checkbox-select/checkbox-select';
  import '@craftcms/ui/components/empty/empty';
  import '@craftcms/ui/components/popover/popover';
  import type CraftCheckboxSelect from '@craftcms/ui/components/checkbox-select/checkbox-select';
  import {
    computed,
    inject,
    nextTick,
    onBeforeUnmount,
    ref,
    shallowRef,
    useId,
    useTemplateRef,
    watch,
  } from 'vue';
  import {useTimeoutFn} from '@vueuse/core';
  import {firstFocusableWithin} from '@/common/utils/dom';
  import {usePinnedScroll} from '@/common/composables/usePinnedScroll';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import {provideUiValueGroup} from '@/modules/ui/uiValueGroup';
  import type {UiPayload} from '@/modules/ui/types';
  import AskAiController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AskAiController';
  import AskAiReply from './AskAiReply.vue';
  import {streamAction} from './stream-action';
  import {
    AskAiEditorKey,
    type AskAiContextOption,
    type AskAiConversationTurn as Turn,
    type AskAiFinding,
    type AskAiParams,
    type AskAiProposal,
    type AskAiResult,
  } from './ask-ai';

  interface FormResponse {
    ui: UiPayload;
    contextOptions: AskAiContextOption[];
    submitLabel: string;
  }

  /** The context value that sends every field. */
  const ALL_CONTEXT = '*';

  /** How long, in milliseconds, the summary must pause before it’s taken as finished. */
  const SUMMARY_PAUSE = 1000;

  const props = defineProps<{
    params: AskAiParams;
    active: boolean;
  }>();

  const editor = inject(AskAiEditorKey, null);

  // The prompt's values are its own, not the element's.
  provideUiValueGroup();

  const promptForm = shallowRef<FormResponse | null>(null);
  const contextOptions = shallowRef<AskAiContextOption[]>([]);
  const context = shallowRef<string[]>([ALL_CONTEXT]);
  const turns = editor?.conversation ?? shallowRef<Turn[]>([]);
  const processing = shallowRef(false);
  const error = shallowRef<string | null>(null);
  const errors = shallowRef<UiPayload['errors']>([]);
  const status = shallowRef('');
  const renderer = useTemplateRef<InstanceType<typeof UiRenderer>>('renderer');
  const composer = useTemplateRef<HTMLElement>('composer');
  const thread = useTemplateRef<HTMLElement>('thread');
  const history = useTemplateRef<HTMLElement>('history');

  // The prompt box growing as the user types isn't new content, so only the
  // conversation is observed.
  const historyScroll = usePinnedScroll(history, thread);

  let loading: Promise<void> | null = null;
  let request: AbortController | null = null;
  let nextTurnId =
    turns.value.reduce((max, turn) => Math.max(max, turn.id), 0) + 1;

  /**
   * The empty state's message, with a non-breaking space before its last word,
   * so that word doesn't end up on a line of its own. Added here, rather than in
   * the message, so translations get it too.
   */
  const emptyMessage = t(
    'Ask for new content, changes, or a review. You choose which changes to apply, and nothing is saved until you save.'
  ).replace(/ (\S+)$/, '\u00A0$1');

  /** What the context picker's button shows: “All fields”, a field, or how many. */
  const contextLabel = computed(() => {
    if (context.value.includes(ALL_CONTEXT)) {
      return t('All fields');
    }

    if (context.value.length === 1) {
      return (
        contextOptions.value.find((option) => option.value === context.value[0])
          ?.label ?? t('1 field')
      );
    }

    return t('{num, plural, =0{No fields} =1{# field} other{# fields}}', {
      num: context.value.length,
    });
  });

  /**
   * Loads the prompt form once, and the fields that can be chosen as context
   * each time, since the editor's nested elements come and go.
   */
  function load(): Promise<void> {
    loading ??= (async () => {
      try {
        const {data} = await actionClient.post<FormResponse>(
          AskAiController.form.url(),
          {...editor?.values(), ...props.params}
        );
        promptForm.value ??= data;
        contextOptions.value = data.contextOptions;
      } catch (exception: any) {
        error.value =
          exception?.response?.data?.message || t('A server error occurred.');
      } finally {
        loading = null;
      }
    })();

    return loading;
  }

  watch(
    () => props.active,
    (active) => {
      if (active) {
        void load();
      }
    },
    {immediate: true}
  );

  // A field's action asks about that field alone, in the same conversation.
  watch(
    () => editor?.request.value,
    async (fieldRequest) => {
      if (!fieldRequest) {
        return;
      }

      await load();
      context.value = fieldRequest.context;
      await nextTick();
      focusPrompt();
    }
  );

  /**
   * The pieces of the summary written so far, while a reply is being
   * generated. Kept out of the conversation, which is saved on every change.
   */
  const streamedSummary = ref<string[]>([]);

  /**
   * Whether the summary has finished while the rest of the reply is still
   * being generated. Providers don’t say when the summary ends, so a pause in
   * it is taken as its end.
   */
  const preparing = shallowRef(false);
  const summaryPause = useTimeoutFn(
    () => (preparing.value = true),
    SUMMARY_PAUSE,
    {immediate: false}
  );

  const idPrefix = useId();

  /** Whether the panel itself is changing the conversation, rather than the header. */
  let isUpdating = false;

  /** Replaces the conversation's turns, without starting a new conversation. */
  function setTurns(next: Turn[]): void {
    isUpdating = true;
    turns.value = next;
    isUpdating = false;
  }

  // The header's “New conversation” empties the conversation. Synchronous, so
  // the panel's own changes can be told apart.
  watch(
    turns,
    async (current, previous) => {
      if (isUpdating || current.length || !previous.length) {
        return;
      }

      request?.abort();
      error.value = null;
      status.value = t('Started a new conversation.');

      // The header's button is gone, so focus moves to the prompt.
      await nextTick();
      focusPrompt();
    },
    {flush: 'sync'}
  );

  // The conversation outlives the panel, so a reply it won't get is dropped.
  onBeforeUnmount(() => {
    request?.abort();
    setTurns(turns.value.filter((turn) => turn.result));
  });

  function onContextChange(event: Event): void {
    // Its checkboxes fire their own `model-value-changed`, which bubbles up
    // to this listener too.
    if (event.target !== event.currentTarget) {
      return;
    }

    // SAFETY: The listener is bound to the `craft-checkbox-select`.
    context.value = (event.currentTarget as CraftCheckboxSelect).modelValue;
  }

  /** Ctrl/Command + Enter asks, rather than saving the element. */
  function onPromptKeydown(event: KeyboardEvent): void {
    if (
      event.key === 'Enter' &&
      (event.ctrlKey || event.metaKey) &&
      !event.altKey &&
      !event.isComposing
    ) {
      event.preventDefault();
      void ask();
    }
  }

  function updateTurn(id: number, changes: Partial<Turn>): void {
    setTurns(
      turns.value.map((turn) => (turn.id === id ? {...turn, ...changes} : turn))
    );
  }

  /**
   * Sends a prompt, with the conversation so far. Without a `prompt`, sends
   * the one typed in the prompt field, and clears it.
   */
  async function ask(
    prompt: string | null = null,
    askContext: string[] = context.value
  ): Promise<void> {
    if (!renderer.value) {
      return;
    }

    // Ask to fix and Fix all stay available while a reply streams, so say why
    // they don't do anything yet. Cleared first, so asking again announces it again.
    if (processing.value) {
      status.value = '';
      await nextTick();
      status.value = t('Wait for the current reply to finish.');

      return;
    }

    status.value = '';
    error.value = null;
    errors.value = [];

    if (!askContext.length) {
      error.value = t('Choose at least one field for the context.');

      return;
    }

    const values = renderer.value.currentValues();
    const typed = typeof values.prompt === 'string' ? values.prompt : '';
    const text = prompt ?? typed;
    const history = turns.value.flatMap((turn) =>
      turn.result ? [turn.result.turn] : []
    );
    const turn: Turn = {
      id: nextTurnId++,
      prompt: text,
      result: null,
      applied: [],
    };

    processing.value = true;
    request = new AbortController();

    if (text.trim()) {
      // Sending a prompt shows it, and the reply to it, wherever the user had scrolled to.
      historyScroll.pin();
      setTurns([...turns.value, turn]);

      if (prompt === null) {
        renderer.value.setValue(['prompt'], '');
      }
    }

    try {
      let data: AskAiResult | null = null;
      streamedSummary.value = [];

      await streamAction(
        AskAiController.ask.url(),
        {
          ...editor?.values(),
          ...values,
          ...props.params,
          prompt: text,
          context: askContext,
          history,
          namespace: editor?.scope().join('.') || null,
        },
        {
          signal: request.signal,
          onEvent: (event) => {
            if (event.type === 'summary') {
              streamedSummary.value.push(String(event.delta ?? ''));
              preparing.value = false;
              summaryPause.start();
            } else if (event.type === 'result') {
              data = event.result as AskAiResult;
            }
          },
        }
      );

      if (!data) {
        throw {response: {data: {}}};
      }

      updateTurn(turn.id, {result: data});
      status.value = replyStatus(data);
    } catch (exception: any) {
      if (request?.signal.aborted) {
        return;
      }

      setTurns(turns.value.filter(({id}) => id !== turn.id));

      // Unless the user has typed a new prompt while waiting.
      if (prompt === null && !renderer.value?.currentValues().prompt) {
        renderer.value?.setValue(['prompt'], typed);
      }

      const responseErrors: Record<string, string[]> =
        exception?.response?.data?.errors ?? {};
      errors.value = Object.entries(responseErrors)
        .filter(([path]) => !path.startsWith('history'))
        .map(([path, messages]) => ({path: path.split('.'), messages}));
      error.value =
        exception?.response?.data?.message || t('A server error occurred.');

      await nextTick();
      focusPrompt(true);
    } finally {
      processing.value = false;
      streamedSummary.value = [];
      summaryPause.stop();
      preparing.value = false;
      request = null;
    }
  }

  /** What a screen reader announces when a reply arrives. */
  function replyStatus(result: AskAiResult): string {
    const proposals = result.proposals.length
      ? t('{num, plural, =1{Proposed # change.} other{Proposed # changes.}}', {
          num: result.proposals.length,
        })
      : '';

    return [result.summary, proposals].filter(Boolean).join(' ');
  }

  function apply(turn: Turn, proposals: AskAiProposal[]): void {
    const applied = editor?.apply(proposals) ?? [];
    const missing = proposals.length - applied.length;

    updateTurn(turn.id, {
      applied: [...turn.applied, ...applied.map((proposal) => proposal.key)],
    });
    status.value = applied.length
      ? t('{num, plural, =1{Applied # change.} other{Applied # changes.}}', {
          num: applied.length,
        })
      : '';
    error.value = missing
      ? t(
          '{num, plural, =1{# change couldn’t be applied. Its field is no longer in the form.} other{# changes couldn’t be applied. Their fields are no longer in the form.}}',
          {num: missing}
        )
      : null;
  }

  /**
   * Asks the model to fix findings, with just the fields they concern as the
   * context, or the chosen context when one concerns the content as a whole.
   */
  function askToFix(findings: AskAiFinding[]): void {
    const paths = findings.map((finding) => finding.path);
    const prompt =
      findings.length === 1
        ? t('Fix this: {issue}', {issue: findings[0]!.issue})
        : [
            t('Fix these problems:'),
            ...findings.map((finding) => `- ${finding.issue}`),
          ].join('\n');

    void ask(
      prompt,
      paths.every((path) => path !== null) ? [...new Set(paths)] : context.value
    );
  }

  watch([turns, () => streamedSummary.value.length], historyScroll.stick, {
    flush: 'post',
  });

  // A hidden panel can't scroll, so a restored conversation is scrolled once it shows.
  watch(
    () => props.active,
    (active) => {
      if (active) {
        void nextTick(historyScroll.stick);
      }
    }
  );

  /** Focuses the prompt, or with `invalid`, the first control with an error, as `UiModal` does. */
  function focusPrompt(invalid = false): void {
    const root = invalid
      ? composer.value?.querySelector<HTMLElement>('[aria-invalid="true"]')
      : composer.value;

    if (root) {
      firstFocusableWithin(root)?.focus();
    }
  }
</script>

<template>
  <div class="ask-ai-panel">
    <div role="status" class="sr-only">{{ status }}</div>

    <!-- The history scrolls on its own, apart from the prompt box: a field in a
      scrolling area makes the browser scroll it to the caret as the user types. -->
    <div ref="history" class="ask-ai-panel__history">
      <craft-spinner
        v-if="!promptForm && !error"
        class="ask-ai-spinner"
        :label="t('Loading')"
      ></craft-spinner>

      <ol
        v-if="turns.length"
        ref="thread"
        class="ask-ai-thread"
        :aria-label="t('Conversation')"
      >
        <li
          v-for="turn in turns"
          :key="turn.id"
          :aria-busy="turn.result ? undefined : 'true'"
          class="flex flex-col gap-xl"
        >
          <p class="ask-ai-thread__prompt">
            <span class="sr-only">{{ t('You:') }}</span>
            <span :id="`${idPrefix}-prompt-${turn.id}`">{{ turn.prompt }}</span>
          </p>

          <AskAiReply
            :result="turn.result"
            :streamed-summary="turn.result ? undefined : streamedSummary"
            :preparing="!turn.result && preparing"
            :applied="turn.applied"
            :prompt-id="`${idPrefix}-prompt-${turn.id}`"
            @apply="apply(turn, $event)"
            @ask-to-fix="askToFix"
          />
        </li>
      </ol>
      <craft-empty
        v-else-if="promptForm"
        class="ask-ai-empty"
        icon="sparkles"
        :label="t('Ask AI about this content')"
      >
        <p class="ask-ai-empty__message">{{ emptyMessage }}</p>
      </craft-empty>
    </div>

    <div class="ask-ai-composer">
      <div role="alert">
        <craft-callout v-if="error" variant="danger">{{ error }}</craft-callout>
      </div>

      <div
        v-if="promptForm"
        ref="composer"
        class="ask-ai-composer__box"
        @keydown="onPromptKeydown"
        @beforeinput.capture="historyScroll.onBeforeInput"
        @input.capture="historyScroll.onInput"
      >
        <craft-field-group>
          <UiRenderer
            ref="renderer"
            :payload="promptForm.ui"
            :errors="errors"
          />
        </craft-field-group>

        <div class="ask-ai-composer__toolbar">
          <craft-popover :label="t('Context')" placement="top-start">
            <craft-button
              slot="invoker"
              type="button"
              variant="plain"
              size="small"
              icon="chevron-down"
              icon-position="suffix"
            >
              <span class="sr-only">{{ t('Context:') }}</span>
              {{ contextLabel }}
            </craft-button>
            <div slot="content" class="ask-ai-composer__context">
              <craft-checkbox-select
                :all-label="t('All fields')"
                :all-value="ALL_CONTEXT"
                .options="contextOptions"
                .modelValue="context"
                @model-value-changed="onContextChange"
              ></craft-checkbox-select>
            </div>
          </craft-popover>

          <craft-button
            type="button"
            variant="primary"
            size="small"
            icon="sparkles"
            :loading="processing || undefined"
            @click="void ask()"
          >
            {{ turns.length ? t('Send') : promptForm.submitLabel }}
          </craft-button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
  .ask-ai-panel {
    container: ask-ai-panel / inline-size;
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .ask-ai-panel__history {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--c-spacing-lg);
    min-height: 0;
    padding-block: var(--c-spacing-lg);
    padding-inline: var(--cp-container-padding);
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  .ask-ai-spinner {
    --size: 16px;
  }

  .ask-ai-empty {
    flex: 1;
    align-content: center;
  }

  .ask-ai-empty__message {
    max-width: 30ch;
    margin-inline: auto;
    text-align: center;
  }

  .ask-ai-thread {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--c-spacing-xl);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .ask-ai-thread__prompt {
    align-self: flex-end;
    max-inline-size: 85%;
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-md);
    border: 1px solid transparent;
    border-radius: var(--c-radius-lg);
    background-color: var(--c-color-accent-fill-quiet);
    color: var(--c-color-accent-on-quiet);
    overflow-wrap: anywhere;
    white-space: pre-line;
  }

  .ask-ai-composer {
    display: flex;
    flex: none;
    flex-direction: column;
    gap: var(--c-spacing-md);
    padding-inline: var(--cp-container-padding);
    padding-block-end: var(--cp-container-padding);
  }

  .ask-ai-composer__box {
    --markdown-field-font-size: var(--c-text-sm);

    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    padding: var(--c-spacing-sm);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-lg);
    background-color: var(--c-surface-raised);
    box-shadow: var(--c-shadow-sm);
  }

  .ask-ai-composer__toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--c-spacing-sm);
  }

  .ask-ai-composer__context {
    max-height: 20rem;
    padding: var(--c-spacing-md);
    overflow-y: auto;
  }
</style>
