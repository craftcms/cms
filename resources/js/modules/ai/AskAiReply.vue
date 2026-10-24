<script setup lang="ts">
  /**
   * One of the model's replies in the “Ask AI” conversation: its summary, the
   * changes it proposes, each shown as a diff and applied on its own, and its
   * findings, which can be asked to be fixed.
   */
  import {t} from '@craftcms/ui';
  import '@craftcms/ui/components/text-diff/text-diff';
  import {countEdits, diffText} from '@craftcms/ui/utilities/diff';
  import {computed, nextTick, shallowRef, watch} from 'vue';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import AskAiCard from './AskAiCard.vue';
  import AskAiFindings from './AskAiFindings.vue';
  import type {AskAiFinding, AskAiProposal, AskAiResult} from './ask-ai';

  const props = defineProps<{
    /** The reply, or `null` while it's being generated. */
    result: AskAiResult | null;
    /** The pieces of the summary written so far, while the reply is being generated. */
    streamedSummary?: string[];
    /** Whether the summary is finished, while the rest of the reply is being generated. */
    preparing?: boolean;
    /** The keys of the proposals that have been applied. */
    applied: readonly string[];
    /** The id of the prompt the reply answers, which tells this reply's controls from another's. */
    promptId: string;
  }>();

  const emit = defineEmits<{
    apply: [proposals: AskAiProposal[]];
    askToFix: [findings: AskAiFinding[]];
  }>();

  const proposals = computed(() => props.result?.proposals ?? []);
  const findings = computed(() => props.result?.findings ?? []);

  /**
   * Cards the user opened or closed themselves. The rest are open, until a
   * change card's change is applied.
   */
  const toggledCards = shallowRef(new Map<string, boolean>());

  function isCollapsed(key: string): boolean {
    return toggledCards.value.get(key) ?? props.applied.includes(key);
  }

  function toggleCard(key: string): void {
    toggledCards.value = new Map(toggledCards.value).set(
      key,
      !isCollapsed(key)
    );
  }

  // Applying a change collapses its card, even one the user opened.
  watch(
    () => props.applied,
    (applied, previous) => {
      const next = new Map(toggledCards.value);

      for (const key of applied) {
        if (!previous.includes(key)) {
          next.delete(key);
        }
      }

      toggledCards.value = next;
    }
  );

  /** Each proposal with its findings, and what its card header shows. */
  const cards = computed(() =>
    proposals.value.map((proposal) => {
      const cardFindings = findings.value.filter(
        (finding) => finding.key === proposal.key
      );
      const quotes = cardFindings.flatMap((finding) =>
        finding.quote ? [finding.quote] : []
      );

      return {
        proposal,
        findings: cardFindings,
        quotes,
        badge: !proposal.diff
          ? null
          : proposal.diff.current
            ? t(
                '{num, plural, =1{# suggested edit} other{# suggested edits}}',
                {
                  num: countEdits(
                    diffText(proposal.diff.current, proposal.diff.proposed)
                  ),
                }
              )
            : t('New'),
      };
    })
  );

  /**
   * Findings for fields without a proposal, grouped by field, and for the
   * content as a whole.
   */
  const findingGroups = computed(() => {
    const groups = new Map<
      string,
      {key: string; label: string; findings: AskAiFinding[]}
    >();

    for (const finding of findings.value) {
      if (proposals.value.some((proposal) => proposal.key === finding.key)) {
        continue;
      }

      // Kept apart from proposals' keys, as the cards share their open state.
      const key = `findings:${finding.key ?? ''}`;
      const group = groups.get(key) ?? {
        key,
        label: finding.label ?? t('Overall'),
        findings: [],
      };
      group.findings.push(finding);
      groups.set(key, group);
    }

    return [...groups.values()];
  });

  function notesLabel(num: number): string {
    return t('{num, plural, =1{# note} other{# notes}}', {num});
  }

  const unapplied = computed(() =>
    proposals.value.filter((proposal) => !props.applied.includes(proposal.key))
  );

  const cardRefs = new Map<string, InstanceType<typeof AskAiCard>>();

  function setCardRef(key: string, card: unknown): void {
    if (card) {
      cardRefs.set(key, card as InstanceType<typeof AskAiCard>);
    } else {
      cardRefs.delete(key);
    }
  }

  /**
   * Applies changes, then moves focus to the first one's card, as the button
   * that applied them is gone.
   */
  async function apply(proposals: AskAiProposal[]): Promise<void> {
    emit('apply', proposals);
    await nextTick();

    // A change whose field is gone stays open, with its Apply button focused.
    const applied = proposals.find((proposal) =>
      props.applied.includes(proposal.key)
    );

    if (applied) {
      cardRefs.get(applied.key)?.focus();
    }
  }
</script>

<template>
  <div class="ask-ai-reply">
    <span class="ask-ai-reply__icon">
      <craft-icon name="sparkles"></craft-icon>
    </span>

    <div class="flex min-w-0 flex-1 flex-col gap-md">
      <span class="sr-only">{{ t('Ask AI:') }}</span>
      <template v-if="result">
        <p v-if="result.summary" class="whitespace-pre-line">
          {{ result.summary }}
        </p>
        <p v-else-if="!proposals.length && !findings.length">
          {{ t('Nothing was changed. Try asking in a different way.') }}
        </p>
      </template>
      <!-- Each piece fades in as it arrives, so the text doesn’t pop in. -->
      <p v-else-if="streamedSummary?.length" class="whitespace-pre-line">
        <span
          v-for="(piece, index) in streamedSummary"
          :key="index"
          class="ask-ai-reply__piece"
          >{{ piece }}</span
        >
      </p>
      <p v-else class="ask-ai-reply__thinking">{{ t('Thinking…') }}</p>

      <AskAiCard
        v-for="{proposal, ...card} in cards"
        :key="proposal.key"
        :ref="(card) => setCardRef(proposal.key, card)"
        :label="proposal.label"
        :collapsed="isCollapsed(proposal.key)"
        :badge="card.badge"
        :badge-fill="proposal.diff?.current ? 'neutral' : 'success'"
        @toggle="toggleCard(proposal.key)"
      >
        <template #actions>
          <span
            v-if="applied.includes(proposal.key)"
            class="ask-ai-reply__applied"
          >
            <craft-icon name="check"></craft-icon>
            {{ t('Applied') }}
          </span>
          <craft-button
            v-else
            type="button"
            variant="primary"
            size="small"
            @click="apply([proposal])"
          >
            {{ t('Apply') }}
            <span class="sr-only">{{ proposal.label }}</span>
          </craft-button>
        </template>

        <div class="flex flex-col gap-md">
          <craft-text-diff
            v-if="proposal.diff"
            :current="proposal.diff.current"
            :proposed="proposal.diff.proposed"
            :highlights.prop="card.quotes"
            :label="proposal.label"
            :collapse="true"
          ></craft-text-diff>

          <div v-else-if="proposal.preview" class="ask-ai-reply__preview">
            <div class="min-w-0">
              <h4 class="mb-md text-sm font-medium">
                {{ t('Current') }}
                <span class="sr-only">{{ proposal.label }}</span>
              </h4>
              <UiRenderer :payload="proposal.preview.current" />
            </div>
            <div class="min-w-0">
              <h4 class="mb-md text-sm font-medium">
                {{ t('Proposed') }}
                <span class="sr-only">{{ proposal.label }}</span>
              </h4>
              <UiRenderer :payload="proposal.preview.proposed" />
            </div>
          </div>
        </div>

        <template v-if="card.findings.length" #footer>
          <AskAiFindings :findings="card.findings" :fixable="false" />
        </template>
      </AskAiCard>

      <AskAiCard
        v-for="group in findingGroups"
        :key="group.key"
        :label="group.label"
        :collapsed="isCollapsed(group.key)"
        :badge="notesLabel(group.findings.length)"
        badge-fill="warning"
        @toggle="toggleCard(group.key)"
      >
        <template v-if="group.findings.length > 1" #actions>
          <craft-button
            type="button"
            variant="outline"
            size="small"
            @click="emit('askToFix', group.findings)"
          >
            {{ t('Fix all') }}
            <span class="sr-only">{{ group.label }}</span>
          </craft-button>
        </template>

        <AskAiFindings
          :findings="group.findings"
          @ask-to-fix="emit('askToFix', $event)"
        />
      </AskAiCard>

      <div v-if="unapplied.length > 1">
        <craft-button
          :id="`${promptId}-apply-all`"
          type="button"
          variant="outline"
          size="small"
          :aria-labelledby="`${promptId}-apply-all ${promptId}`"
          @click="apply(unapplied)"
        >
          {{ t('Apply all') }}
        </craft-button>
      </div>
    </div>

    <!-- The changes and notes take a while longer, so the summary doesn’t look
      like the whole reply. -->
    <template v-if="!result && preparing">
      <span class="ask-ai-reply__icon">
        <craft-icon name="sparkles"></craft-icon>
      </span>
      <p class="ask-ai-reply__thinking">{{ t('Preparing suggestions…') }}</p>
    </template>
  </div>
</template>

<style scoped>
  /* The icons share a column, so they line up. */
  .ask-ai-reply {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: var(--c-spacing-md) var(--c-spacing-sm);
  }

  .ask-ai-reply__icon {
    display: flex;
    flex: none;
    align-items: center;
    block-size: 1lh;
    color: var(--c-color-accent-on-quiet);
  }

  .ask-ai-reply__preview {
    display: grid;
    gap: var(--c-spacing-lg);
  }

  @container ask-ai-panel (min-width: 500px) {
    .ask-ai-reply__preview {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  .ask-ai-reply__piece {
    animation: ask-ai-reply-fade-in 200ms cubic-bezier(0.23, 1, 0.32, 1);
  }

  @keyframes ask-ai-reply-fade-in {
    from {
      opacity: 0;
    }
  }

  .ask-ai-reply__thinking {
    color: var(--c-color-neutral-on-quiet);
  }

  .ask-ai-reply__applied {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    color: var(--c-color-success-on-quiet);
    font-size: var(--c-text-sm);
  }
</style>
