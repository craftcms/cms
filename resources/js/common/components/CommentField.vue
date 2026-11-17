<script setup lang="ts">
  /**
   * A Markdown box for short messages, such as comments and messages to the
   * AI assistant: in the body font, without a toolbar, and growing up to a
   * point. Its label sits inside the box, with an info icon that says Markdown
   * works, plus anything in the `info` slot, such as how to mention someone.
   * Return sends, as described by `returnSends()`, as does the round send
   * button in the box's corner. Controls in the `footer` slot sit beside it.
   */
  import {t} from '@craftcms/ui';
  import {computed, useTemplateRef} from 'vue';
  import {commandShortcut} from '@/common/utils/keyShortcuts';
  import {returnSends} from '@/common/utils/messageReturn';
  import '@/modules/markdown-field/markdown-field';

  const props = withDefaults(
    defineProps<{
      /** The id of the box's textarea. */
      id: string;
      /** The box's visible label. */
      label?: string | null;
      /** A label for screen readers, for a box without a visible one. */
      ariaLabel?: string | null;
      placeholder?: string;
      rows?: number;
      disabled?: boolean;
      overageMessage?: string | null;
      /** Whether to show the send button. */
      submitButton?: boolean;
      submitLabel?: string;
      /** Whether sending is blocked, beyond the box being empty. */
      submitDisabled?: boolean;
    }>(),
    {
      label: null,
      ariaLabel: null,
      placeholder: '',
      rows: 1,
      disabled: false,
      overageMessage: null,
      submitButton: true,
      submitLabel: () => t('Send'),
      submitDisabled: false,
    }
  );

  const emit = defineEmits<{
    /** Return was pressed to send, or the send button was clicked. Return's already claimed. */
    submit: [event: KeyboardEvent | MouseEvent];
  }>();

  defineSlots<{
    info?: () => unknown;
    footer?: () => unknown;
  }>();

  const model = defineModel<string>({required: true});
  const field = useTemplateRef<HTMLElement>('field');
  const canSubmit = computed(
    () => !props.disabled && !props.submitDisabled && model.value.trim() !== ''
  );
  const submitShortcut = commandShortcut('Enter');

  function onKeydown(event: KeyboardEvent): void {
    if (!returnSends(event, event.target as HTMLTextAreaElement)) {
      return;
    }

    // Claimed even when nothing's sent, so it doesn't submit the page's form.
    event.preventDefault();
    emit('submit', event);
  }

  /** Clicks on the box around the text, like on its padding, focus the text. */
  function onClick(event: MouseEvent): void {
    if (
      (event.target as Element).closest(
        'a, button, label, craft-button, craft-info-icon, craft-markdown-field'
      )
    ) {
      return;
    }

    field.value?.querySelector('textarea')?.focus();
  }

  defineExpose({field});
</script>

<template>
  <div
    class="comment-field"
    :class="{'comment-field--disabled': disabled}"
    @click="onClick"
  >
    <!-- Beside the label rather than in it, so the info isn't part of the box's name. -->
    <div v-if="label" class="comment-field__header">
      <label :for="id">{{ label }}</label>
      <craft-info-icon>
        {{ t('Supports Markdown formatting.') }}
        <slot name="info" />
      </craft-info-icon>
    </div>
    <craft-markdown-field
      :id="id"
      ref="field"
      :max-height="200"
      :rows="rows"
      sanitize-html
      .proportional="true"
      .label="ariaLabel"
      .placeholder="placeholder"
      .value="model"
      .disabled="disabled"
      @input="model = ($event.target as HTMLTextAreaElement).value"
      @keydown="onKeydown"
    />
    <div
      v-if="submitButton || $slots.footer || overageMessage"
      class="comment-field__footer"
    >
      <span class="comment-field__overage" aria-live="polite">
        {{ overageMessage }}
      </span>
      <slot name="footer" />
      <template v-if="submitButton">
        <!-- Icon-only, named by its tooltip. -->
        <craft-button
          :id="`${id}-submit`"
          class="comment-field__submit"
          type="button"
          :variant="canSubmit ? 'primary' : 'fill'"
          size="small"
          icon
          .disabled="!canSubmit"
          focusable-when-disabled
          :aria-keyshortcuts="submitShortcut"
          @click="emit('submit', $event)"
        >
          <craft-icon name="arrow-up" :label="submitLabel"></craft-icon>
        </craft-button>
        <craft-tooltip :for="`${id}-submit`" invoker-relation="label">
          {{ submitLabel }}
        </craft-tooltip>
      </template>
    </div>
  </div>
</template>

<style scoped>
  .comment-field {
    display: flex;
    flex-direction: column;
    border: var(--c-form-control-border-width) solid
      var(--c-form-control-border-color);
    border-radius: var(--c-input-radius, var(--c-radius-sm));
    background-color: var(--c-input-fill, var(--c-form-control-fill));
    box-shadow: var(--c-input-shadow, 0 0 transparent);
    cursor: text;
  }

  /* The ring the field draws around itself when it has its own box. */
  .comment-field:has(textarea:focus) {
    box-shadow:
      0 0 0 var(--c-spacing-xs) var(--c-color-accent-border-normal),
      var(--c-input-shadow, 0 0 transparent);
  }

  .comment-field--disabled {
    opacity: 0.75;
  }

  .comment-field__header {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    padding-block-start: var(--c-spacing-md);
    padding-inline: var(--c-input-spacing-inline);
    font-weight: 600;

    & > label {
      cursor: default;
    }
  }

  /* The box is drawn around the field, label, and footer. */
  .comment-field craft-markdown-field {
    contain: inline-size;
    display: block;
    width: 100%;
    --c-input-border-width: 0;
    --c-input-fill: transparent;
    --c-input-shadow: none;
    --markdown-field-focus-ring: 0 0 transparent;
  }

  .comment-field__header
    + craft-markdown-field:defined
    :deep(:is(.overtype-input, .overtype-preview, .overtype-placeholder)) {
    padding-block-start: var(--c-spacing-xs) !important;
  }

  .comment-field:has(.comment-field__footer)
    craft-markdown-field:defined
    :deep(:is(.overtype-input, .overtype-preview, .overtype-placeholder)) {
    padding-block-end: 0 !important;
  }

  .comment-field__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm) var(--c-spacing-md);
    padding-inline: var(--c-spacing-md);
  }

  .comment-field__overage {
    margin-inline-end: auto;
    color: var(--c-color-danger-on-quiet);
    font-size: var(--c-text-sm);
  }

  .comment-field__submit {
    --c-button-radius: 9999px;
    cursor: pointer;
  }
</style>
