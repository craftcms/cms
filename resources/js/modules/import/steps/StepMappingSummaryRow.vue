<script setup lang="ts">
  /** One mapped destination in a step's mapping summary; a container's mapping opens from it. */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/icon/icon';
  import {Color, t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {useElementNames} from '@/modules/import/mapping/useElementNames';
  import type {SummaryEntry} from './mapping-summary';

  const {entry, editable, opening} = defineProps<{
    entry: SummaryEntry;
    /** Whether a container's mapping opens to be edited, rather than only viewed. */
    editable: boolean;
    /** Whether a container's mapping is opening. */
    opening: boolean;
  }>();

  const emit = defineEmits<{editNested: [opener: HTMLElement]}>();

  const name = computed(() => entry.label ?? entry.handle);

  const elementNames = useElementNames(
    () => entry.defaultElements?.elementType,
    () => entry.defaultElements?.ids ?? []
  );

  /** The default as it reads: elements by their names. */
  const defaultLabel = computed(() =>
    entry.defaultElements ? elementNames.value.join(', ') : entry.defaultValue
  );

  const whenEmpty = computed(() => {
    switch (entry.whenEmpty) {
      case 'erase':
        return t('Erase');
      case 'default':
        return t('Default');
      case 'skip':
        return t('Skip');
      default:
        return '—';
    }
  });
</script>

<template>
  <tr class="mapping-summary-row">
    <th scope="row">
      <div class="mapping-summary-row__field">
        <!-- always the same width, so names line up whether or not they have an icon -->
        <span class="mapping-summary-row__icon" aria-hidden="true">
          <craft-icon v-if="entry.icon" :name="entry.icon"></craft-icon>
        </span>
        <div class="flex items-baseline gap-sm min-w-0">
          <span class="truncate">{{ name }}</span>
          <code v-if="entry.label" class="mapping-summary-row__handle">{{
            entry.handle
          }}</code>
        </div>
        <craft-badge
          v-if="entry.matchKey"
          class="self-center"
          size="small"
          :fill="Color.Accent"
          no-prefix
          >{{ t('Match key') }}</craft-badge
        >
      </div>
    </th>
    <td :data-label="t('Incoming Data')">
      <code v-if="entry.kind === 'column'">{{ entry.incoming }}</code>
      <span v-else-if="entry.kind === 'unmapped'" class="text-quiet">—</span>
      <template v-else-if="entry.kind === 'template'">
        <!-- the braces say it's a template; it's named only while there's none written yet -->
        <code v-if="entry.template">{{ entry.template }}</code>
        <span v-else class="text-quiet">{{ entry.incoming }}</span>
      </template>
      <craft-button
        v-else
        type="button"
        size="small"
        variant="plain"
        flush="inline-start"
        :icon="editable ? 'pencil' : 'eye'"
        .loading="opening"
        :aria-label="
          editable
            ? t('Edit mapping for {type}', {type: name})
            : t('View mapping for {type}', {type: name})
        "
        @click="emit('editNested', $event.currentTarget as HTMLElement)"
      >
        {{ editable ? t('Edit mapping') : t('View mapping') }}
      </craft-button>
    </td>
    <!-- a default can run long, so it wraps rather than being cut off -->
    <td
      :data-label="t('When empty')"
      :class="{
        wrap: true,
        'text-quiet':
          entry.whenEmpty !== 'erase' && entry.whenEmpty !== 'default',
      }"
    >
      {{ whenEmpty }}
      <span v-if="entry.defaultValue !== null" class="text-quiet"
        >· {{ defaultLabel }}</span
      >
    </td>
  </tr>
</template>

<style scoped>
  .mapping-summary-row > th {
    padding-inline-start: var(--mapping-summary-inset, var(--c-spacing-md));
  }

  .mapping-summary-row__field {
    display: inline-flex;
    align-items: baseline;
    gap: var(--c-spacing-sm);
    max-width: 100%;
  }

  /* a fixed-width slot, centering glyphs of any width */
  .mapping-summary-row__icon {
    display: inline-flex;
    flex: none;
    justify-content: center;
    align-self: center;
    inline-size: 1.25em;
    color: var(--c-text-quiet);
  }

  .mapping-summary-row__handle {
    font-weight: normal;
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }

  /* stacked where the columns would cut the names short: the destination in full, then each column's value under its name */
  @container mapping-summary (width < 36rem) {
    .mapping-summary-row {
      display: grid;
      grid-template-columns: max-content minmax(0, 1fr);
      gap: var(--c-spacing-xs) var(--c-spacing-lg);
      padding-block: var(--c-spacing-sm);
      padding-inline-start: var(--mapping-summary-inset, 0);
      border-block-end: 1px solid var(--c-table-border-color);
    }

    .mapping-summary-row > :is(th, td) {
      grid-column: 1 / -1;
      width: auto;
      height: auto;
      padding: 0;
      border: 0;
      overflow: visible;
      white-space: normal;
    }

    .mapping-summary-row > th {
      padding: 0;
      margin-block-end: var(--c-spacing-xs);
    }

    /* under the name, past the icon */
    .mapping-summary-row > td {
      display: grid;
      grid-template-columns: subgrid;
      align-items: baseline;
      padding-inline-start: calc(1.25em + var(--c-spacing-sm));
      overflow-wrap: anywhere;
    }

    .mapping-summary-row > td::before {
      content: attr(data-label);
      color: var(--c-text-quiet);
      font-size: var(--c-text-sm);
      font-weight: normal;
    }

    .mapping-summary-row__field {
      display: flex;
      flex-wrap: wrap;
    }

    .mapping-summary-row__field .truncate {
      overflow: visible;
      white-space: normal;
    }
  }
</style>
