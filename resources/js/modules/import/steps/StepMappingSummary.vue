<script setup lang="ts">
  /**
   * A step's mapping at a glance: each mapped destination, what fills it, whether rows
   * are matched on it, and what happens when it's empty. A container's nested
   * destinations expand beneath it.
   */
  import '@craftcms/ui/components/button/button';
  import {t} from '@craftcms/ui';
  import {computed, ref, shallowRef, useId} from 'vue';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {
    summarizeMapping,
    type DestinationLabels,
    type SummaryEntry,
  } from './mapping-summary';
  import StepMappingSummaryRow from './StepMappingSummaryRow.vue';

  const {
    step,
    labels = {},
    limit = 5,
  } = defineProps<{
    step: ImportStep;
    /** Keys can be layout element uids; a destination with no label shows its key alone. */
    labels?: DestinationLabels;
    /** How many mappings to list before summing up the rest. `Infinity` lists them all. */
    limit?: number;
  }>();

  const entries = computed(() => summarizeMapping(step, labels));

  /** Whether the mappings past the limit are shown too. */
  const expanded = shallowRef(false);
  const listId = useId();

  /** The containers whose nested destinations are shown, by key. */
  const openContainers = ref(new Set<string>());

  function toggleContainer(entry: SummaryEntry): void {
    if (!openContainers.value.delete(entry.key)) {
      openContainers.value.add(entry.key);
    }
  }

  function withOpenChildren(
    entry: SummaryEntry,
    level: number
  ): Array<{entry: SummaryEntry; level: number}> {
    return [
      {entry, level},
      ...(openContainers.value.has(entry.key)
        ? entry.children.flatMap((child) => withOpenChildren(child, level + 1))
        : []),
    ];
  }

  const rows = computed(() =>
    (expanded.value ? entries.value : entries.value.slice(0, limit)).flatMap(
      (entry) => withOpenChildren(entry, 0)
    )
  );

  const more = computed(() => Math.max(entries.value.length - limit, 0));
</script>

<template>
  <div class="step-mapping-summary">
    <slot name="header" :count="entries.length" />
    <slot v-if="!entries.length" name="empty">
      <p class="m-0 text-quiet">
        {{ t('Nothing mapped yet.') }}
      </p>
    </slot>
    <template v-else>
      <!-- only the table scrolls on a narrow screen; the header and its button stay put -->
      <div class="step-mapping-summary__scroll">
        <table
          :id="listId"
          class="step-mapping-summary__table cp-table cp-table--auto cp-table--aligned"
        >
          <colgroup>
            <col class="step-mapping-summary__field-col" />
            <col />
            <col class="step-mapping-summary__empty-col" />
          </colgroup>
          <thead>
            <tr>
              <th scope="col">{{ t('Destination') }}</th>
              <th scope="col">{{ t('Incoming Data') }}</th>
              <th scope="col">{{ t('When empty') }}</th>
            </tr>
          </thead>
          <tbody>
            <StepMappingSummaryRow
              v-for="row in rows"
              :key="row.entry.key"
              :entry="row.entry"
              :level="row.level"
              :open="openContainers.has(row.entry.key)"
              @toggle="toggleContainer(row.entry)"
            />
          </tbody>
        </table>
      </div>
      <div v-if="more > 0">
        <craft-button
          size="small"
          variant="plain"
          :aria-controls="listId"
          :aria-expanded="expanded ? 'true' : 'false'"
          @click="expanded = !expanded"
        >
          {{ expanded ? t('Show less') : t('+{count} more', {count: more}) }}
        </craft-button>
      </div>
    </template>
  </div>
</template>

<style scoped>
  .step-mapping-summary {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-md);
  }

  .step-mapping-summary__scroll {
    overflow-x: auto;
  }

  /* the columns keep their set widths, so expanding a container's longer nested names moves nothing */
  .step-mapping-summary__table {
    table-layout: fixed;
  }

  /* cells otherwise shrink to nothing, which the incoming data column, with no width of its own, would take on */
  .step-mapping-summary__table :deep(:is(th, td)) {
    width: auto;
  }

  .step-mapping-summary__field-col {
    width: 42%;
  }

  .step-mapping-summary__empty-col {
    width: 9rem;
  }
</style>
