<script setup lang="ts">
  /**
   * A step's mapping at a glance: each mapped destination, what fills it, whether rows
   * are matched on it, and what happens when it's empty. A container's own mapping
   * opens from its row.
   */
  import '@craftcms/ui/components/button/button';
  import {t} from '@craftcms/ui';
  import {computed, shallowRef, useId} from 'vue';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {summarizeMapping, type DestinationLabels} from './mapping-summary';
  import StepMappingSummaryRow from './StepMappingSummaryRow.vue';

  const {
    step,
    labels = {},
    limit = 5,
    editable = false,
    openingNested = null,
  } = defineProps<{
    step: ImportStep;
    /** Keys can be layout element uids; a destination with no label shows its key alone. */
    labels?: DestinationLabels;
    /** How many mappings to list before summing up the rest. `Infinity` lists them all. */
    limit?: number;
    /** Whether a container's mapping opens to be edited, rather than only viewed. */
    editable?: boolean;
    /** The key of the container whose mapping is opening. */
    openingNested?: string | null;
  }>();

  const emit = defineEmits<{
    editNested: [key: string, opener: HTMLElement];
  }>();

  const entries = computed(() => summarizeMapping(step, labels));

  /** Whether the mappings past the limit are shown too. */
  const expanded = shallowRef(false);
  const listId = useId();

  const rows = computed(() =>
    expanded.value ? entries.value : entries.value.slice(0, limit)
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
            <col class="step-mapping-summary__incoming-col" />
            <col />
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
              v-for="entry in rows"
              :key="entry.key"
              :entry="entry"
              :editable="editable"
              :opening="openingNested === entry.key"
              @edit-nested="(opener) => emit('editNested', entry.key, opener)"
            />
          </tbody>
        </table>
      </div>
      <!-- set off from the table as an admin table's footer is -->
      <div v-if="more > 0" class="step-mapping-summary__footer">
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
    container: mapping-summary / inline-size;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-md);
  }

  .step-mapping-summary__scroll {
    margin-block-start: var(--c-spacing-sm);
    overflow-x: auto;
  }

  /* right under the table, its rule taking the place of the table's last one */
  .step-mapping-summary__footer {
    display: flex;
    align-items: center;
    padding-block-start: var(--c-spacing-sm);
    margin-block-start: calc(var(--c-spacing-md) * -1);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .step-mapping-summary__scroll:has(+ .step-mapping-summary__footer)
    :deep(tbody tr:last-child > *) {
    border-block-end: 0;
  }

  /* the columns keep their set widths, so expanding a container's longer nested names moves nothing */
  .step-mapping-summary__table {
    table-layout: fixed;
  }

  /* cells otherwise shrink to nothing, which the when-empty column, with no width of its own, would take on */
  .step-mapping-summary__table :deep(:is(th, td)) {
    width: auto;
  }

  /* incoming data is mostly a short column name, so what happens when it's empty, which can name a default, gets the rest */
  .step-mapping-summary__field-col {
    width: 40%;
  }

  .step-mapping-summary__incoming-col {
    width: 25%;
  }

  /* each row stacks (see its row); the column headings stay for assistive technology */
  @container mapping-summary (width < 36rem) {
    .step-mapping-summary__table,
    .step-mapping-summary__table :deep(tbody) {
      display: block;
    }

    .step-mapping-summary__table thead {
      position: absolute;
      inline-size: 1px;
      block-size: 1px;
      overflow: hidden;
      clip-path: inset(50%);
      white-space: nowrap;
    }

    .step-mapping-summary__table :deep(tbody) {
      border-block-start: 1px solid var(--c-table-border-color);
    }

    .step-mapping-summary__scroll:has(+ .step-mapping-summary__footer)
      :deep(tbody tr:last-child) {
      border-block-end: 0;
    }
  }
</style>
