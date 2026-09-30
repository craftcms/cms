<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, shallowRef, useId} from 'vue';
  import type {ElementIndexExportFormat} from '@/modules/elements/index/types/exporters';

  interface Exporter {
    type: string;
    name: string;
    formattable: boolean;
  }

  const props = withDefaults(
    defineProps<{
      exporters: Exporter[];
      maxLimit?: number;
      disabled?: boolean;
    }>(),
    {maxLimit: undefined, disabled: false}
  );

  const emit = defineEmits<{
    export: [format: ElementIndexExportFormat, type: string, limit?: number];
  }>();

  const formats: ElementIndexExportFormat[] = [
    'csv',
    'xlsx',
    'json',
    'xml',
    'yaml',
  ];
  const limit = shallowRef<number | undefined>();
  const limitId = useId();
  const choices = computed(() =>
    props.exporters.flatMap((exporter) =>
      (exporter.formattable ? formats : formats.slice(0, 1)).map((format) => ({
        exporter,
        format,
      }))
    )
  );
</script>

<template>
  <craft-popover v-if="exporters.length">
    <craft-button
      slot="invoker"
      type="button"
      :variant="ButtonVariant.Fill"
      .disabled="disabled"
      >{{ t('Export') }}</craft-button
    >
    <fieldset slot="content-body" class="flex flex-col gap-sm">
      <legend class="sr-only">{{ t('Export format') }}</legend>
      <label :for="limitId" class="flex items-center gap-md">
        {{ t('Limit') }}
        <input
          :id="limitId"
          v-model.number="limit"
          type="number"
          min="1"
          :max="maxLimit"
          :disabled="disabled"
          class="text small"
          @keydown.enter.stop.prevent
        />
      </label>
      <craft-button
        v-for="choice in choices"
        :key="`${choice.exporter.type}:${choice.format}`"
        type="button"
        :variant="ButtonVariant.Plain"
        .disabled="disabled"
        @click="emit('export', choice.format, choice.exporter.type, limit)"
        >{{ choice.exporter.name }}
        <template v-if="choice.exporter.formattable">
          ({{ choice.format.toUpperCase() }})
        </template></craft-button
      >
    </fieldset>
  </craft-popover>
</template>
