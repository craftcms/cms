<script setup lang="ts">
  /** One mapped destination in a step's mapping summary, stepped in beneath any container it's nested in. */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/icon/icon';
  import {Color, t} from '@craftcms/ui';
  import {computed} from 'vue';
  import type {SummaryEntry} from './mapping-summary';

  const {entry, level, open} = defineProps<{
    entry: SummaryEntry;
    level: number;
    /** Whether a container's nested destinations are shown beneath it. */
    open: boolean;
  }>();

  const emit = defineEmits<{toggle: []}>();

  const name = computed(() => entry.label ?? entry.handle);

  const toggleLabel = computed(() =>
    open
      ? t('Hide the nested fields of {field}', {field: name.value})
      : t('Show the nested fields of {field}', {field: name.value})
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
  <tr :class="{'mapping-summary-row--nested': level > 0}">
    <th scope="row" :style="{'--mapping-summary-level': level}">
      <div class="mapping-summary-row__field">
        <!-- always the same width, so names line up whether or not they have an icon -->
        <span class="mapping-summary-row__icon" aria-hidden="true">
          <craft-icon
            v-if="entry.icon"
            :name="entry.icon"
            :class="{'mapping-summary-row__icon--match': entry.matchKey}"
          ></craft-icon>
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
          >{{ t('Match Key') }}</craft-badge
        >
      </div>
    </th>
    <td>
      <code v-if="entry.kind === 'column'">{{ entry.incoming }}</code>
      <template v-else-if="entry.kind === 'template'">
        <span class="text-quiet">{{ `${entry.incoming} ` }}</span>
        <code v-if="entry.template">{{ entry.template }}</code>
      </template>
      <button
        v-else
        type="button"
        class="mapping-summary-row__toggle"
        :aria-expanded="open ? 'true' : 'false'"
        :aria-label="toggleLabel"
        @click="emit('toggle')"
      >
        <craft-icon
          :name="open ? 'chevron-down' : 'chevron-right'"
        ></craft-icon>
        <span>{{ entry.incoming }}</span>
      </button>
    </td>
    <td
      :class="{
        'text-quiet':
          entry.whenEmpty !== 'erase' && entry.whenEmpty !== 'default',
      }"
    >
      {{ whenEmpty }}
      <span v-if="entry.defaultValue !== null" class="text-quiet"
        >· {{ entry.defaultValue }}</span
      >
    </td>
  </tr>
</template>

<style scoped>
  /* nested destinations step in under the container they belong to */
  .mapping-summary-row--nested > th {
    padding-inline-start: calc(
      var(--mapping-summary-level) * var(--c-spacing-lg)
    );
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

  .mapping-summary-row__icon--match {
    color: var(--c-color-accent-on-normal);
  }

  .mapping-summary-row__handle {
    font-weight: normal;
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }

  .mapping-summary-row__toggle {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    padding: 0;
    border: 0;
    background: none;
    color: var(--c-text-quiet);
    font: inherit;
    cursor: pointer;
  }

  .mapping-summary-row__toggle:hover {
    color: var(--c-text-default, inherit);
  }
</style>
