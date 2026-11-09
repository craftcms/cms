<script setup lang="ts">
  /**
   * What happens when an item's value for a destination column is empty: Skip, Erase,
   * or a default, which is entered in the popover too.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {
    defaultValueInputOf,
    defaultValueOf,
    defaultValuesOf,
    emptyValueModeOf,
    emptyValueModes,
    setEmptyValueMode,
    type EmptyValueMode,
  } from '../empty-values';
  import {useMappingContext, type MappingColumn} from '../types';
  import DefaultValueInput from './DefaultValueInput.vue';
  import SettingPopover from './SettingPopover.vue';

  const {col} = defineProps<{col: MappingColumn}>();

  const context = useMappingContext();

  const mode = computed(() => emptyValueModeOf(context.values, col));
  const modes = computed(() => emptyValueModes(col));

  /** The default as it reads rather than as it's stored: an option's label, or On or Off. */
  const defaultLabel = computed(() => {
    const value = defaultValueOf(context.values, col);
    const input = defaultValueInputOf(col);

    const labelOf = (option: string) =>
      input.options?.find((candidate) => candidate.value === option)?.label ??
      option;

    switch (input.type) {
      case 'lightswitch':
        return value === '1'
          ? (input.onLabel ?? t('On'))
          : (input.offLabel ?? t('Off'));
      case 'select':
        return value ? labelOf(value) : '';
      case 'multiselect':
        return defaultValuesOf(context.values, col).map(labelOf).join(', ');
      default:
        return value;
    }
  });

  const summary = computed(() => {
    switch (mode.value) {
      case 'erase':
        return t('Erase');
      case 'default':
        return defaultLabel.value
          ? t('Default · {value}', {value: defaultLabel.value})
          : t('Default');
      default:
        return t('Skip');
    }
  });
</script>

<template>
  <!-- a fixed width, so a default being typed doesn't resize the column; a long one is cut short -->
  <div class="when-empty-cell">
    <SettingPopover
      :label="t('When {field} is empty', {field: col.label})"
      :summary="summary"
      :editable="context.editable"
      :options="modes"
      :value="mode"
      @change="setEmptyValueMode(context.values, col, $event as EmptyValueMode)"
    >
      <DefaultValueInput v-if="mode === 'default'" :col="col" />
    </SettingPopover>
  </div>
</template>

<style scoped>
  .when-empty-cell {
    inline-size: 11rem;
  }
</style>
