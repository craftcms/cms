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
  import '@craftcms/ui/components/text-diff/text-diff';
  import {computed, nextTick, shallowReactive, useId} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import {provideUiValueGroup} from '@/modules/ui/uiValueGroup';
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

  // The previews’ values are their own, not the page’s.
  provideUiValueGroup();

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
    <article
      v-for="(proposal, index) in proposals"
      :key="proposal.key"
      :aria-labelledby="`${idPrefix}-${index}`"
    >
      <craft-card padding="md" :collapsed="!isExpanded(proposal)">
        <h3
          :id="`${idPrefix}-${index}`"
          slot="label"
          class="assistant-proposals__label"
        >
          <craft-disclosure
            appearance="plain"
            :state="isExpanded(proposal) ? 'expanded' : 'collapsed'"
            @craft-show="onToggle(proposal, true)"
            @craft-hide="onToggle(proposal, false)"
          >
            <button
              :id="`${idPrefix}-${index}-toggle`"
              type="button"
              class="assistant-proposals__toggle"
              :aria-controls="`${idPrefix}-${index}-body`"
            >
              <span
                class="assistant-proposals__chevron"
                :class="{
                  'assistant-proposals__chevron--collapsed':
                    !isExpanded(proposal),
                }"
              >
                <craft-icon
                  :name="
                    isExpanded(proposal) ? 'chevron-down' : 'chevron-right'
                  "
                ></craft-icon>
              </span>
              {{ proposal.element }} › {{ proposal.label }}
            </button>
          </craft-disclosure>
        </h3>
        <craft-badge
          v-if="isApplied(proposal)"
          slot="badge"
          fill="success"
          size="small"
        >
          <craft-icon slot="prefix" name="check"></craft-icon>
          {{ t('Applied') }}
        </craft-badge>
        <craft-button
          v-else-if="assistantEditorFor(proposal)"
          :id="`${idPrefix}-${index}-apply`"
          slot="actions"
          type="button"
          variant="primary"
          size="small"
          :aria-labelledby="`${idPrefix}-${index}-apply ${idPrefix}-${index}`"
          @click="apply([proposal])"
        >
          {{ t('Apply') }}
        </craft-button>
        <div
          :id="`${idPrefix}-${index}-body`"
          class="assistant-proposals__body"
        >
          <craft-text-diff
            v-if="proposal.diff"
            :current="proposal.diff.current"
            :proposed="proposal.diff.proposed"
            :label="proposal.label"
            :collapse="true"
          ></craft-text-diff>
          <div
            v-else-if="proposal.preview"
            class="assistant-proposals__preview"
          >
            <div class="min-w-0">
              <h4 class="assistant-proposals__preview-heading">
                {{ t('Current') }}
                <span class="sr-only">{{ proposal.label }}</span>
              </h4>
              <UiRenderer :payload="proposal.preview.current" />
            </div>
            <div class="min-w-0">
              <h4 class="assistant-proposals__preview-heading">
                {{ t('Proposed') }}
                <span class="sr-only">{{ proposal.label }}</span>
              </h4>
              <UiRenderer :payload="proposal.preview.proposed" />
            </div>
          </div>
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
        </div>
      </craft-card>
    </article>

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
    container: assistant-proposals / inline-size;
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .assistant-proposals__label {
    font-size: var(--c-text-sm);
    font-weight: 600;
    overflow-wrap: anywhere;
  }

  /* At least 24px tall, with its first line in the middle of the 24px. */
  .assistant-proposals__toggle {
    display: flex;
    align-items: start;
    gap: var(--c-spacing-xs);
    padding-block: max(0px, (24px - 1lh) / 2);
    padding-inline: 0;
    border: 0;
    background: none;
    color: inherit;
    font: inherit;
    text-align: start;
    cursor: pointer;
  }

  .assistant-proposals__toggle:focus-visible {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
  }

  /* As tall as the label's first line, with the icon in its middle. The
    icon's own font size is smaller, so it can't be lined up by itself. */
  .assistant-proposals__chevron {
    display: flex;
    flex: none;
    align-items: center;
    block-size: 1lh;
  }

  /* Collapsed, it points toward the end of the line. */
  .assistant-proposals__chevron--collapsed:dir(rtl) {
    transform: scaleX(-1);
  }

  .assistant-proposals__body {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .assistant-proposals__preview {
    display: grid;
    gap: var(--c-spacing-md);
  }

  @container assistant-proposals (min-width: 500px) {
    .assistant-proposals__preview {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  .assistant-proposals__preview-heading {
    margin-block-end: var(--c-spacing-xs);
    font-size: var(--c-text-sm);
    font-weight: 500;
  }

  .assistant-proposals__note {
    color: var(--c-color-neutral-on-quiet);
    font-size: var(--c-text-sm);
  }
</style>
