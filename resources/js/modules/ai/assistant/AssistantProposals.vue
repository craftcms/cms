<script setup lang="ts">
  /**
   * The changes the AI assistant proposed in one reply, each with a button
   * that applies it to the field in its editor. Nothing is saved until the
   * user saves the editor.
   *
   * Each change is a card the user can collapse. It’s open until it’s
   * applied, and then collapsed, after reloads too.
   */
  import {t} from '@craftcms/ui';
  import {computed, nextTick, shallowReactive, useId} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import AssistantCard from './AssistantCard.vue';
  import ChangeComparison from '@/modules/ui/ChangeComparison.vue';
  import {
    applyAssistantProposal,
    assistantEditorFor,
    useAppliedProposals,
    type AssistantProposal,
  } from './context';

  const props = defineProps<{
    proposals: AssistantProposal[];
    /** The conversation the changes were proposed in. */
    threadId: string;
    /** The tool call that proposed them. */
    callId: string;
  }>();

  const emit = defineEmits<{
    /** Says how many changes were applied, or why some couldn’t be. */
    status: [message: string];
  }>();

  const idPrefix = useId();
  const {applied, add} = useAppliedProposals(props.threadId);

  function isApplied(proposal: AssistantProposal): boolean {
    return applied.value.has(`${props.callId}:${proposal.key}`);
  }

  /** Whether the user opened or collapsed each card themselves, by key. */
  const toggled = shallowReactive(new Map<string, boolean>());

  function isExpanded(proposal: AssistantProposal): boolean {
    return toggled.get(proposal.key) ?? !isApplied(proposal);
  }

  function onToggle(proposal: AssistantProposal, expanded: boolean): void {
    if (expanded !== isExpanded(proposal)) {
      toggled.set(proposal.key, expanded);
    }
  }

  const unapplied = computed(() =>
    props.proposals.filter(
      (proposal) => !isApplied(proposal) && assistantEditorFor(proposal)
    )
  );

  function openLabel(proposal: AssistantProposal): string {
    return t('Open “{name}” to apply this change.', {name: proposal.element});
  }

  function apply(proposals: AssistantProposal[]): void {
    const ids: string[] = [];
    let count = 0;

    for (const proposal of proposals) {
      const editor = assistantEditorFor(proposal);

      // Only the first field is shown, rather than jumping between tabs.
      if (editor && applyAssistantProposal(proposal, editor, count === 0)) {
        ids.push(`${props.callId}:${proposal.key}`);
        count++;
      }
    }

    const missing = proposals.length - count;
    add(ids);

    // Applied changes collapse. Focus goes to the first one’s toggle, as the
    // button that applied it is gone.
    const appliedProposals = proposals.filter(isApplied);
    appliedProposals.forEach((proposal) => toggled.delete(proposal.key));

    if (appliedProposals[0]) {
      const index = props.proposals.indexOf(appliedProposals[0]);
      void nextTick(() =>
        document.getElementById(`${idPrefix}-${index}-toggle`)?.focus()
      );
    }

    emit(
      'status',
      [
        count
          ? t(
              '{num, plural, =1{Applied # change.} other{Applied # changes.}}',
              {
                num: count,
              }
            )
          : '',
        missing
          ? t(
              '{num, plural, =1{# change couldn’t be applied. Its field is no longer in the form.} other{# changes couldn’t be applied. Their fields are no longer in the form.}}',
              {num: missing}
            )
          : '',
      ]
        .filter(Boolean)
        .join(' ')
    );
  }
</script>

<template>
  <div class="assistant-proposals">
    <AssistantCard
      v-for="(proposal, index) in proposals"
      :key="proposal.key"
      :heading-id="`${idPrefix}-${index}`"
      :label="`${proposal.element} › ${proposal.label}`"
      :expanded="isExpanded(proposal)"
      @update:expanded="onToggle(proposal, $event)"
    >
      <template v-if="isApplied(proposal)" #badge>
        <craft-badge fill="success" size="small">
          <craft-icon slot="prefix" name="check"></craft-icon>
          {{ t('Applied') }}
        </craft-badge>
      </template>
      <template v-else-if="assistantEditorFor(proposal)" #actions>
        <craft-button
          :id="`${idPrefix}-${index}-apply`"
          type="button"
          variant="primary"
          size="small"
          :aria-labelledby="`${idPrefix}-${index}-apply ${idPrefix}-${index}`"
          @click="apply([proposal])"
        >
          {{ t('Apply') }}
        </craft-button>
      </template>

      <ChangeComparison
        v-if="proposal.diff || proposal.preview"
        :diff="proposal.diff"
        :from="proposal.preview && {payload: proposal.preview.current}"
        :to="proposal.preview && {payload: proposal.preview.proposed}"
        :from-label="t('Current')"
        :to-label="t('Proposed')"
        :label="proposal.label"
        collapse
      />
      <p
        v-if="!isApplied(proposal) && !assistantEditorFor(proposal)"
        class="assistant-proposals__note"
      >
        <CpLink v-if="proposal.cpEditUrl" :href="proposal.cpEditUrl" bare>
          {{ openLabel(proposal) }}
        </CpLink>
        <template v-else>
          {{ openLabel(proposal) }}
        </template>
      </p>
    </AssistantCard>

    <div v-if="unapplied.length > 1">
      <craft-button
        type="button"
        variant="outline"
        size="small"
        @click="apply(unapplied)"
      >
        {{ t('Apply all') }}
      </craft-button>
    </div>
  </div>
</template>

<style scoped>
  .assistant-proposals {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .assistant-proposals__note {
    color: var(--c-color-neutral-on-quiet);
    font-size: var(--c-text-sm);
  }
</style>
