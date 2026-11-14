<script setup lang="ts">
  /**
   * What a destination column will be filled with: the first value of its source
   * column, or its template when it's filled from one. A lightswitch shows the switch
   * the value turns into, beside the value itself.
   */
  import '@craftcms/ui/components/switch/switch';
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {defaultValueInputOf} from '../empty-values';
  import {TEMPLATE_RULE, useMappingContext, type MappingColumn} from '../types';
  import {useSourceMapping} from '../useSourceMapping';
  import TemplateField from './TemplateField.vue';

  const {col} = defineProps<{col: MappingColumn}>();

  const context = useMappingContext();
  const {mapValue} = useSourceMapping(() => col);

  const selected = computed(() =>
    context.sourceDataCols.find((option) => option.value === mapValue.value)
  );

  const isLightswitch = computed(
    () => defaultValueInputOf(col).type === 'lightswitch'
  );

  /** Whether the value turns the switch on. It's cast as PHP casts it, so "false" and "no" are on. */
  const isOn = computed(() => {
    const value = selected.value?.hint ?? '';

    return value !== '' && value !== '0';
  });
</script>

<template>
  <template v-if="!col.isContainer && selected?.value">
    <TemplateField v-if="mapValue === TEMPLATE_RULE" :col="col" />
    <span
      v-else-if="selected.hint && isLightswitch"
      class="preview-cell preview-cell__switch"
    >
      <craft-switch
        size="small"
        :label="col.label"
        label-sr-only
        inert
        .checked="isOn"
      ></craft-switch>
      <span class="sr-only">{{ isOn ? t('On') : t('Off') }}</span>
      <span class="preview-cell__value">{{ selected.hint }}</span>
    </span>
    <span v-else-if="selected.hint" class="preview-cell">
      <span class="preview-cell__value">{{ selected.hint }}</span>
    </span>
    <span v-else class="preview-cell text-quiet">{{ t('No value') }}</span>
  </template>
</template>

<style scoped>
  /* as tall as the select beside it, so the value sits level with its middle */
  .preview-cell {
    display: flex;
    align-items: center;
    min-height: var(--c-size-control-md);
    min-width: 0;
  }

  .preview-cell__switch {
    gap: var(--c-spacing-sm);
  }

  .preview-cell__value {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
</style>
