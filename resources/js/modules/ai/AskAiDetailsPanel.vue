<script setup lang="ts">
  /**
   * “Ask AI”, as an element details panel: a prompt with the fields to send
   * as its context, then a review step whose accepted values are written into
   * the editor.
   *
   * A field's own “Ask AI” action opens the panel with that field as the
   * context.
   */
  import {actionClient, t} from '@craftcms/ui';
  import '@craftcms/ui/components/checkbox-select/checkbox-select';
  import type CraftCheckboxSelect from '@craftcms/ui/components/checkbox-select/checkbox-select';
  import {
    inject,
    nextTick,
    onBeforeUnmount,
    shallowRef,
    useTemplateRef,
    watch,
  } from 'vue';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import {provideUiValueGroup} from '@/modules/ui/uiValueGroup';
  import type {UiPayload} from '@/modules/ui/types';
  import AskAiController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AskAiController';
  import AskAiReview from './AskAiReview.vue';
  import {
    AskAiEditorKey,
    type AskAiContextOption,
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
  const step = shallowRef<'prompt' | 'review'>('prompt');
  const result = shallowRef<AskAiResult | null>(null);
  const processing = shallowRef(false);
  const error = shallowRef<string | null>(null);
  const errors = shallowRef<UiPayload['errors']>([]);
  const status = shallowRef('');
  const renderer = useTemplateRef<InstanceType<typeof UiRenderer>>('renderer');
  const review = useTemplateRef<InstanceType<typeof AskAiReview>>('review');
  const promptBody = useTemplateRef<HTMLElement>('promptBody');

  let loading: Promise<void> | null = null;
  let request: AbortController | null = null;

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

  // A field's action asks about that field alone.
  watch(
    () => editor?.request.value,
    async (fieldRequest) => {
      if (!fieldRequest) {
        return;
      }

      request?.abort();
      step.value = 'prompt';
      result.value = null;
      error.value = null;
      await load();
      context.value = fieldRequest.context;
      await nextTick();
      focusPrompt();
    }
  );

  onBeforeUnmount(() => request?.abort());

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

  async function ask(): Promise<void> {
    if (!renderer.value || processing.value) {
      return;
    }

    status.value = '';
    error.value = null;
    errors.value = [];

    if (!context.value.length) {
      error.value = t('Choose at least one field for the context.');

      return;
    }

    processing.value = true;
    request = new AbortController();

    try {
      const {data} = await actionClient.post<AskAiResult>(
        AskAiController.ask.url(),
        {
          ...editor?.values(),
          ...renderer.value.currentValues(),
          ...props.params,
          context: context.value,
          namespace: editor?.scope().join('.') || null,
        },
        {signal: request.signal}
      );

      result.value = data;
      step.value = 'review';
      await nextTick();
      review.value?.focus();
    } catch (exception: any) {
      if (request?.signal.aborted) {
        return;
      }

      const responseErrors: Record<string, string[]> =
        exception?.response?.data?.errors ?? {};
      errors.value = Object.entries(responseErrors).map(([path, messages]) => ({
        path: path.split('.'),
        messages,
      }));
      error.value =
        exception?.response?.data?.message || t('A server error occurred.');

      await nextTick();
      focusPrompt('[aria-invalid="true"]');
    } finally {
      processing.value = false;
      request = null;
    }
  }

  async function backToPrompt(message = ''): Promise<void> {
    step.value = 'prompt';
    result.value = null;
    error.value = null;
    status.value = message;

    // The review's buttons are gone, so focus moves to the prompt.
    await nextTick();
    focusPrompt();
  }

  function apply(proposals: AskAiProposal[]): void {
    editor?.apply(proposals);
    void backToPrompt(
      t('{num, plural, =1{Applied # change.} other{Applied # changes.}}', {
        num: proposals.length,
      })
    );
  }

  /** Focuses the prompt step's first form control, or the first one matching `filter`. */
  function focusPrompt(filter = ''): void {
    promptBody.value
      ?.querySelector<HTMLElement>(
        ['textarea', 'input', 'select']
          .map((control) => `${control}${filter}`)
          .join(', ')
      )
      ?.focus();
  }
</script>

<template>
  <div class="flex flex-col gap-lg py-lg px-(--cp-container-padding)">
    <div role="alert">
      <craft-callout v-if="error" variant="danger">{{ error }}</craft-callout>
    </div>
    <div role="status" class="sr-only">{{ status }}</div>

    <craft-spinner v-if="!promptForm" :label="t('Loading')"></craft-spinner>

    <div
      v-else
      v-show="step === 'prompt'"
      ref="promptBody"
      class="flex flex-col gap-lg"
      @keydown="onPromptKeydown"
    >
      <craft-field-group>
        <UiRenderer ref="renderer" :payload="promptForm.ui" :errors="errors" />
        <craft-field :label="t('Context')" fieldset>
          <craft-checkbox-select
            slot="input"
            presentation="add-menu"
            :all-label="t('All fields')"
            :all-value="ALL_CONTEXT"
            .options="contextOptions"
            .modelValue="context"
            @model-value-changed="onContextChange"
          ></craft-checkbox-select>
        </craft-field>
      </craft-field-group>

      <div>
        <craft-button
          type="button"
          variant="primary"
          icon="sparkles"
          :loading="processing || undefined"
          @click="void ask()"
        >
          {{ promptForm.submitLabel }}
        </craft-button>
      </div>
    </div>

    <AskAiReview
      v-if="step === 'review' && result"
      ref="review"
      :result="result"
      @apply="apply"
      @done="void backToPrompt()"
      @try-again="void backToPrompt()"
    />
  </div>
</template>
