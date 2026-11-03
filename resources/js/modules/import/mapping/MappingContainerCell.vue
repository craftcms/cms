<script setup lang="ts">
  /**
   * One cell of a container column's row — a Matrix field, say. A container has no
   * mapping of its own: its row says what it is, and expands to show its own columns
   * beneath it.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/checkbox/checkbox';
  import {computed, inject} from 'vue';
  import {t} from '@craftcms/ui';
  import {ignoreModelValueInitialization} from '@/modules/ui/runtime';
  import {
    checkedValue,
    countMapped,
    getAt,
    isChecked,
    keepFlagPath,
    setAt,
  } from './paths';
  import {MappingContextKey, type MappingColumn} from './types';

  const props = defineProps<{
    col: MappingColumn;
    part: 'destination' | 'incoming';
  }>();

  const context = inject(MappingContextKey);

  if (!context) {
    throw new Error(
      'MappingContainerCell must be rendered inside a mapping editor.'
    );
  }

  const nested = computed(() => context.nested[props.col.prefixedHandle]);
  const expanded = computed(() =>
    context.expanded.has(props.col.prefixedHandle)
  );

  /** How many of the container's own columns are mapped, at any depth. */
  const mappedCount = computed(() =>
    countMapped(getAt(context.values.map, props.col.prefixedHandleAsArray))
  );

  // kept out of the template, whose interpolation would end at the message's `}}`
  const mappedLabel = computed(() =>
    t('{count, plural, =1{# field mapped} other{# fields mapped}}', {
      count: mappedCount.value,
    })
  );

  const buttonLabel = computed(() =>
    context.editable ? t('Edit mapping') : t('View mapping')
  );

  const keepPath = computed(() => keepFlagPath(props.col));

  const keepMissingChecked = computed(() =>
    isChecked(getAt(context.values.keepMissingNestedElements, keepPath.value))
  );

  const onKeepMissingChanged = ignoreModelValueInitialization((event) => {
    setAt(
      context.values.keepMissingNestedElements,
      keepPath.value,
      checkedValue(event)
    );
  });
</script>

<template>
  <template v-if="part === 'destination'">
    {{ col.label }}
    <code class="block font-normal text-xs text-quiet">{{ col.handle }}</code>
  </template>

  <div v-else class="mapping-container-cell">
    <div class="mapping-container-cell__row">
      <craft-button
        type="button"
        variant="dashed"
        :icon="expanded ? 'chevron-up' : 'chevron-down'"
        :aria-label="`${buttonLabel}: ${col.label}`"
        :aria-expanded="expanded ? 'true' : 'false'"
        .loading="nested?.loading ?? false"
        @click="context.toggleNested(col)"
      >
        {{ buttonLabel }}
      </craft-button>
      <span v-if="mappedCount" class="mapping-container-cell__count">{{
        mappedLabel
      }}</span>
    </div>
    <craft-checkbox
      v-if="col.canKeepMissingNestedElements"
      :label="
        t('Keep existing nested elements missing from the imported data.')
      "
      .checked="keepMissingChecked"
      .disabled="!context.editable"
      @model-value-changed="onKeepMissingChanged"
    ></craft-checkbox>
    <p v-if="nested?.error" class="error m-0" role="alert">
      {{ nested.error }}
    </p>
  </div>
</template>

<style scoped>
  .mapping-container-cell {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .mapping-container-cell__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm) var(--c-spacing-md);
  }

  .mapping-container-cell__count {
    color: var(--c-text-quiet);
    font-size: 0.9em;
  }
</style>
