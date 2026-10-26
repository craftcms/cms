<script setup lang="ts">
  /**
   * The tool calls the AI assistant is waiting for the user to approve, each
   * with what it would do, and buttons that approve or reject it.
   */
  import {t} from '@craftcms/ui';
  import {useId} from 'vue';

  /** A tool call waiting for approval. */
  export interface AssistantApproval {
    id: string;
    /** The tool’s name as Craft’s MCP server knows it, such as `elements.update`. */
    tool: string;
    /** Why it needs approval. */
    reason: string | null;
    input: unknown;
  }

  defineProps<{
    approvals: AssistantApproval[];
    /** The decisions made so far, by approval id. */
    decisions: Readonly<Record<string, boolean>>;
    /** Whether the decisions are being sent. */
    sending: boolean;
  }>();

  const emit = defineEmits<{
    decide: [id: string, approved: boolean];
  }>();

  const idPrefix = useId();
</script>

<template>
  <div class="assistant-approvals">
    <article
      v-for="(approval, index) in approvals"
      :key="approval.id"
      :aria-labelledby="`${idPrefix}-${index}`"
    >
      <craft-card padding="md">
        <h3
          :id="`${idPrefix}-${index}`"
          slot="label"
          class="assistant-approvals__heading"
        >
          {{ t('Allow the assistant to use {tool}?', {tool: approval.tool}) }}
        </h3>
        <div class="assistant-approvals__body">
          <p v-if="approval.reason" class="assistant-approvals__reason">
            {{ approval.reason }}
          </p>
          <craft-disclosure>
            <craft-button
              :id="`${idPrefix}-${index}-details`"
              slot="invoker"
              type="button"
              variant="plain"
              size="small"
              flush
              icon="chevron-right"
              icon-position="suffix"
              :aria-labelledby="`${idPrefix}-${index}-details ${idPrefix}-${index}`"
            >
              {{ t('Details') }}
            </craft-button>
            <pre slot="content" class="assistant-approvals__input">{{
              JSON.stringify(approval.input, null, 2)
            }}</pre>
          </craft-disclosure>
          <p
            v-if="approval.id in decisions"
            class="assistant-approvals__decision"
          >
            {{ decisions[approval.id] ? t('Approved') : t('Rejected') }}
          </p>
          <div v-else class="assistant-approvals__actions">
            <craft-button
              :id="`${idPrefix}-${index}-approve`"
              type="button"
              variant="primary"
              size="small"
              :disabled="sending || undefined"
              :aria-labelledby="`${idPrefix}-${index}-approve ${idPrefix}-${index}`"
              @click="emit('decide', approval.id, true)"
            >
              {{ t('Approve') }}
            </craft-button>
            <craft-button
              :id="`${idPrefix}-${index}-reject`"
              type="button"
              size="small"
              :disabled="sending || undefined"
              :aria-labelledby="`${idPrefix}-${index}-reject ${idPrefix}-${index}`"
              @click="emit('decide', approval.id, false)"
            >
              {{ t('Reject') }}
            </craft-button>
          </div>
        </div>
      </craft-card>
    </article>
  </div>
</template>

<style scoped>
  .assistant-approvals {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .assistant-approvals__body {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .assistant-approvals__heading {
    font-size: var(--c-text-sm);
    font-weight: 600;
    overflow-wrap: anywhere;
  }

  .assistant-approvals__reason,
  .assistant-approvals__decision {
    font-size: var(--c-text-sm);
  }

  .assistant-approvals__input {
    overflow-x: auto;
    padding: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-sunken);
    font-family: var(--c-font-mono);
    font-size: var(--c-text-xs);
  }

  .assistant-approvals__actions {
    display: flex;
    gap: var(--c-spacing-xs);
  }
</style>
