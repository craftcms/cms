<script lang="ts">
  import type {UiPayload} from './types';

  /**
   * One side of a change: a read-only preview of the field, or a recorded
   * value, such as the activity timeline’s.
   */
  export type ChangeSide = {payload: UiPayload} | {value: unknown};
</script>

<script setup lang="ts">
  /**
   * Shows a field before and after a change: a word diff when both sides are
   * text, and both sides next to each other otherwise. Used by the activity
   * timeline, the AI assistant’s proposed changes, and “Compare changes”.
   */
  import {t} from '@craftcms/ui';
  import '@craftcms/ui/components/text-diff/text-diff';
  import {computed} from 'vue';
  import ChangeValue from './ChangeValue.vue';
  import UiRenderer from './UiRenderer.vue';
  import {provideUiValueGroup} from './uiValueGroup';

  const props = defineProps<{
    /** The text before and after, shown as a word diff. */
    diff?: {current: string; proposed: string} | null;
    /** What was there before. Left out when there’s nothing to compare with. */
    from?: ChangeSide | null;
    to?: ChangeSide | null;
    /** What each side is called. Defaults to “Before” and “After”. */
    fromLabel?: string;
    toLabel?: string;
    /** The changed field’s name, for screen readers and the diff’s button. */
    label?: string;
    /** Whether the word diff collapses long unchanged stretches. */
    collapse?: boolean;
  }>();

  // Previews’ values are their own, not the page’s.
  provideUiValueGroup();

  const sides = computed(() =>
    [
      {key: 'from', side: props.from, label: props.fromLabel ?? t('Before')},
      {key: 'to', side: props.to, label: props.toLabel ?? t('After')},
    ].filter(
      (item): item is {key: string; side: ChangeSide; label: string} =>
        item.side != null
    )
  );

  /** Whether either side previews a field, which needs more room. */
  const hasPreviews = computed(() =>
    sides.value.some(({side}) => 'payload' in side)
  );
</script>

<template>
  <craft-text-diff
    v-if="diff"
    :current="diff.current"
    :proposed="diff.proposed"
    :label="label"
    :collapse="collapse"
  ></craft-text-diff>
  <div v-else class="change-comparison">
    <dl
      class="change-comparison__sides"
      :class="{'change-comparison__sides--previews': hasPreviews}"
    >
      <div v-for="{key, side, label: sideLabel} in sides" :key="key">
        <dt>
          {{ sideLabel }}
          <span v-if="label && hasPreviews" class="sr-only">{{ label }}</span>
        </dt>
        <dd>
          <UiRenderer v-if="'payload' in side" :payload="side.payload" />
          <ChangeValue v-else :value="side.value" />
        </dd>
      </div>
    </dl>
  </div>
</template>

<style scoped>
  .change-comparison {
    container: change-comparison / inline-size;
  }

  .change-comparison__sides {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--c-spacing-sm);
    margin: 0;
  }

  /* Field previews stack until there’s room for both. */
  .change-comparison__sides--previews {
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-md);
  }

  @container change-comparison (min-width: 500px) {
    .change-comparison__sides--previews {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  .change-comparison__sides div {
    min-width: 0;
  }

  .change-comparison__sides dt {
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
    font-weight: 600;
  }

  .change-comparison__sides dd {
    margin-block: var(--c-spacing-xs) 0;
    margin-inline: 0;
    overflow-wrap: anywhere;
  }
</style>
