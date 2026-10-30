<script setup lang="ts">
  /**
   * The tool calls the AI assistant asked the user to approve, each a card with
   * buttons that approve or deny it in its header, and a badge once it’s
   * answered. The card opens to show why the call needs approval, and what it
   * would do.
   */
  import {t} from '@craftcms/ui';
  import {shallowReactive, useId} from 'vue';
  import AssistantCard from './AssistantCard.vue';

  /** A tool call waiting for approval. */
  export interface AssistantApproval {
    id: string;
    /** The tool’s name as Craft’s MCP server knows it, such as `elements.update`. */
    tool: string;
    /** Why it needs approval. */
    reason: string | null;
    input: unknown;
  }

  const props = defineProps<{
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

  function isAnswered(approval: AssistantApproval): boolean {
    return approval.id in props.decisions;
  }

  /** The cards the user opened, by id. They start collapsed. */
  const expanded = shallowReactive(new Set<string>());

  function isExpanded(approval: AssistantApproval): boolean {
    return expanded.has(approval.id);
  }

  function onToggle(approval: AssistantApproval, open: boolean): void {
    if (open) {
      expanded.add(approval.id);
    } else {
      expanded.delete(approval.id);
    }
  }
</script>

<template>
  <div class="assistant-approvals">
    <AssistantCard
      v-for="(approval, index) in approvals"
      :key="approval.id"
      :heading-id="`${idPrefix}-${index}`"
      :label="t('Allow the assistant to use {tool}?', {tool: approval.tool})"
      :expanded="isExpanded(approval)"
      @update:expanded="onToggle(approval, $event)"
    >
      <template v-if="isAnswered(approval)" #badge>
        <craft-badge
          v-if="decisions[approval.id]"
          fill="success"
          size="small"
          class="assistant-approvals__decision"
        >
          <craft-icon slot="prefix" name="check"></craft-icon>
          {{ t('Approved') }}
        </craft-badge>
        <craft-badge
          v-else
          fill="danger"
          size="small"
          class="assistant-approvals__decision"
        >
          <craft-icon slot="prefix" name="xmark"></craft-icon>
          {{ t('Denied') }}
        </craft-badge>
      </template>
      <template v-else #actions>
        <div class="assistant-approvals__actions">
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
            :id="`${idPrefix}-${index}-deny`"
            type="button"
            size="small"
            :disabled="sending || undefined"
            :aria-labelledby="`${idPrefix}-${index}-deny ${idPrefix}-${index}`"
            @click="emit('decide', approval.id, false)"
          >
            {{ t('Deny') }}
          </craft-button>
        </div>
      </template>
      <p v-if="approval.reason" class="assistant-approvals__reason">
        {{ approval.reason }}
      </p>
      <pre class="assistant-approvals__input">{{
        JSON.stringify(approval.input, null, 2)
      }}</pre>
    </AssistantCard>
  </div>
</template>

<style scoped>
  .assistant-approvals {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .assistant-approvals__reason {
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
