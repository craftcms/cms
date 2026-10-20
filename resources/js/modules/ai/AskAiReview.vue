<script setup lang="ts">
  /**
   * The review step of “Ask AI”: the proposed changes to choose from, the
   * model's summary, and its findings.
   */
  import {t} from '@craftcms/ui';
  import {computed, shallowRef, useTemplateRef} from 'vue';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import AskAiDiff from './AskAiDiff.vue';
  import AskAiFindings from './AskAiFindings.vue';
  import {renderDiff} from '@/common/utils/diff';
  import type {AskAiFinding, AskAiProposal, AskAiResult} from './ask-ai';

  const props = defineProps<{
    result: AskAiResult;
  }>();

  const emit = defineEmits<{
    apply: [proposals: AskAiProposal[]];
    done: [];
    tryAgain: [];
  }>();

  /**
   * How long Apply ignores presses after the review step appears. Apply takes
   * Ask's place, so a repeated tap, such as one made while a phone's keyboard
   * delays the first, would apply the values without a review. The button
   * stays enabled, so it isn't announced as unavailable.
   */
  const APPLY_DELAY = 700;
  const applyLocked = shallowRef(true);
  setTimeout(() => (applyLocked.value = false), APPLY_DELAY);

  const error = shallowRef<string | null>(null);
  const selected = shallowRef(
    new Set(props.result.proposals.map((proposal) => proposal.key))
  );
  const expandedFields = shallowRef(new Set<string>());
  const heading = useTemplateRef<HTMLElement>('heading');

  /** A review can come back with feedback and nothing to apply. */
  const hasProposals = computed(() => props.result.proposals.length > 0);

  /**
   * Findings for fields without a proposal, grouped by field, and for the
   * content as a whole.
   */
  const otherFindingGroups = computed(() => {
    const groups = new Map<
      string,
      {key: string; label: string; findings: AskAiFinding[]}
    >();

    for (const finding of props.result.findings) {
      if (
        props.result.proposals.some((proposal) => proposal.key === finding.key)
      ) {
        continue;
      }

      const key = finding.key ?? 'overall';
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

  /** Each proposal with its findings, and what its card header shows. */
  const cards = computed(() =>
    props.result.proposals.map((proposal) => {
      const cardFindings = props.result.findings.filter(
        (finding) => finding.key === proposal.key
      );
      const quotes = cardFindings.flatMap((finding) =>
        finding.quote ? [finding.quote] : []
      );
      const diff = proposal.diff
        ? renderDiff(proposal.diff.current, proposal.diff.proposed, quotes)
        : null;

      return {
        proposal,
        findings: cardFindings,
        quotes,
        /** Whether the diff hides unchanged text, so the full field can be shown. */
        collapsible: diff?.collapsed ?? false,
        badge: !proposal.diff
          ? null
          : proposal.diff.current
            ? t('{num, plural, =1{# edit} other{# edits}}', {num: diff!.edits})
            : t('New'),
      };
    })
  );

  /** With one proposal, there's nothing to choose. */
  const isChoosable = computed(() => props.result.proposals.length > 1);

  function toggleExpanded(key: string): void {
    const next = new Set(expandedFields.value);

    if (!next.delete(key)) {
      next.add(key);
    }

    expandedFields.value = next;
  }

  function toggle(key: string, checked: boolean): void {
    const next = new Set(selected.value);

    if (checked) {
      next.add(key);
    } else {
      next.delete(key);
    }

    selected.value = next;
  }

  function notesLabel(num: number): string {
    return t('{num, plural, =1{# note} other{# notes}}', {num});
  }

  function apply(): void {
    if (applyLocked.value) {
      return;
    }

    const accepted = props.result.proposals.filter((proposal) =>
      selected.value.has(proposal.key)
    );

    if (!accepted.length) {
      error.value = t('Choose at least one change to apply.');

      return;
    }

    emit('apply', accepted);
  }

  function checkboxValue(event: Event): boolean {
    return Boolean((event.target as {checked?: boolean} | null)?.checked);
  }

  defineExpose({focus: () => heading.value?.focus()});
</script>

<template>
  <section class="flex flex-col gap-lg">
    <h4 ref="heading" tabindex="-1" class="font-bold">
      {{ hasProposals ? t('Proposed changes') : t('Review') }}
    </h4>

    <div role="alert">
      <craft-callout v-if="error" variant="danger">{{ error }}</craft-callout>
    </div>

    <craft-callout v-if="result.summary" icon="sparkles">
      <span class="whitespace-pre-line" v-text="result.summary"></span>
    </craft-callout>

    <p v-if="!hasProposals && !result.summary && !result.findings.length">
      {{ t('Nothing was changed. Try again with a different prompt.') }}
    </p>

    <craft-card v-for="{proposal, ...card} in cards" :key="proposal.key">
      <craft-checkbox
        v-if="isChoosable"
        slot="label"
        .checked="selected.has(proposal.key)"
        @model-value-changed="toggle(proposal.key, checkboxValue($event))"
      >
        <label slot="label" class="font-medium">{{ proposal.label }}</label>
      </craft-checkbox>
      <span v-else slot="label" class="font-medium">{{ proposal.label }}</span>

      <craft-badge
        v-if="card.badge"
        slot="badge"
        :fill="proposal.diff?.current ? 'neutral' : 'success'"
        size="small"
        no-prefix
      >
        {{ card.badge }}
      </craft-badge>

      <craft-button
        v-if="card.collapsible"
        slot="actions"
        type="button"
        variant="plain"
        size="small"
        :aria-expanded="expandedFields.has(proposal.key) ? 'true' : 'false'"
        @click="toggleExpanded(proposal.key)"
      >
        {{
          expandedFields.has(proposal.key)
            ? t('Show changes only')
            : t('Show full field')
        }}
        <span class="sr-only">{{ proposal.label }}</span>
      </craft-button>

      <AskAiDiff
        v-if="proposal.diff"
        :current="proposal.diff.current"
        :proposed="proposal.diff.proposed"
        :quotes="card.quotes"
        :expanded="expandedFields.has(proposal.key)"
      />

      <div v-else-if="proposal.preview" class="grid gap-lg">
        <div class="min-w-0">
          <h5 class="mb-md text-sm font-medium">
            {{ t('Current') }}
            <span class="sr-only">{{ proposal.label }}</span>
          </h5>
          <UiRenderer :payload="proposal.preview.current" />
        </div>
        <div class="min-w-0">
          <h5 class="mb-md text-sm font-medium">
            {{ t('Proposed') }}
            <span class="sr-only">{{ proposal.label }}</span>
          </h5>
          <UiRenderer :payload="proposal.preview.proposed" />
        </div>
      </div>

      <AskAiFindings
        v-if="card.findings.length"
        slot="footer"
        :findings="card.findings"
      />
    </craft-card>

    <craft-card v-for="group in otherFindingGroups" :key="group.key">
      <span slot="label" class="font-medium">{{ group.label }}</span>
      <craft-badge slot="badge" fill="warning" size="small" no-prefix>
        {{ notesLabel(group.findings.length) }}
      </craft-badge>

      <AskAiFindings :findings="group.findings" />
    </craft-card>

    <div class="flex flex-wrap gap-md">
      <craft-button
        v-if="hasProposals"
        type="button"
        variant="primary"
        @click="apply"
      >
        {{ t('Apply') }}
      </craft-button>
      <craft-button
        v-else
        type="button"
        variant="primary"
        @click="emit('done')"
      >
        {{ t('Done') }}
      </craft-button>
      <craft-button type="button" variant="outline" @click="emit('tryAgain')">
        {{ t('Try again') }}
      </craft-button>
    </div>
  </section>
</template>
