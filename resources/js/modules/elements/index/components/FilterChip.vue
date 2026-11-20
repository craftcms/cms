<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {RuleSummaryPart} from '@/modules/conditions/summarizeRule';
  import FilterPill from './FilterPill.vue';

  /** One applied filter: a button that edits it, and one that removes it. */
  defineProps<{
    label: string;
    parts: RuleSummaryPart[];
    expanded?: boolean;
    disabled?: boolean;
  }>();

  const emit = defineEmits<{
    edit: [anchor: HTMLElement];
    remove: [];
  }>();

  function edit(event: Event): void {
    const pill = (event.currentTarget as HTMLElement).closest<HTMLElement>(
      '.filter-pill'
    );
    if (pill) emit('edit', pill);
  }
</script>

<template>
  <FilterPill
    :label="label"
    :remove-label="t('Remove {name}', {name: label})"
    :disabled="disabled"
    @remove="emit('remove')"
  >
    <!-- One button for the whole rule: the popover it opens edits every part. -->
    <button
      type="button"
      class="filter-pill__segment"
      :disabled="disabled"
      :aria-expanded="expanded"
      :aria-label="t('Edit {name}', {name: label})"
      @click="edit"
    >
      <slot>
        <span>{{ label }}</span>
        <span
          v-for="(part, index) in parts"
          :key="index"
          :class="`filter-chip__${part.kind}`"
          >{{ part.text
          }}<craft-icon
            v-if="part.icon"
            class="filter-chip__icon"
            :name="part.icon"
          ></craft-icon
        ></span>
      </slot>
    </button>
    <!-- The popover that edits it, right after the button that opens it, so
      Tab moves on into it. -->
    <slot name="editor" />
  </FilterPill>
</template>

<style scoped lang="scss">
  .filter-chip__icon {
    margin-inline-start: var(--c-spacing-xs);
  }

  .filter-chip__operator {
    color: var(--c-text-quiet);
  }

  .filter-chip__value {
    max-width: 18rem;
    overflow: hidden;
    font-weight: 500;
    text-overflow: ellipsis;
  }
</style>
