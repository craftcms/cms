<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, shallowRef, watch} from 'vue';
  import ModalForm from '@/common/components/ModalForm.vue';
  import Select from '@/common/form/Select.vue';
  import type {ElementIndexExportFormat} from '@/modules/elements/index/types/exporters';

  interface Exporter {
    type: string;
    name: string;
    formattable: boolean;
  }

  const props = defineProps<{
    isActive: boolean;
    exporters: Exporter[];
    /** How many elements will be exported. */
    count: number;
    /** Whether only the selected elements are being exported. */
    selected?: boolean;
    sourceLabel?: string;
  }>();

  const emit = defineEmits<{
    close: [];
    export: [format: ElementIndexExportFormat, type: string, limit?: number];
  }>();

  const formats: ElementIndexExportFormat[] = [
    'csv',
    'xlsx',
    'json',
    'xml',
    'yaml',
  ];
  const type = shallowRef('');
  const format = shallowRef<ElementIndexExportFormat>('csv');
  const limit = shallowRef<number | undefined>();

  const exporter = computed(() =>
    props.exporters.find((candidate) => candidate.type === type.value)
  );
  const formatOptions = computed(() =>
    (exporter.value?.formattable ? formats : formats.slice(0, 1)).map(
      (value) => ({label: value.toUpperCase(), value})
    )
  );

  // Only formattable exporters offer more than CSV.
  watch(exporter, (current) => {
    if (!current?.formattable) format.value = 'csv';
  });

  function onLimitInput(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    limit.value = value ? Number(value) : undefined;
  }

  watch(
    () => props.isActive,
    (active) => {
      if (active) {
        type.value = props.exporters[0]?.type ?? '';
        format.value = 'csv';
        limit.value = undefined;
      }
    },
    {immediate: true}
  );

  function submit(): void {
    emit('export', format.value, type.value, limit.value || undefined);
    emit('close');
  }
</script>

<template>
  <ModalForm
    :is-active="isActive"
    :title="
      selected
        ? t('Export {num, number} selected', {num: count})
        : t('Export “{source}”', {source: sourceLabel ?? ''})
    "
    :submit-label="t('Export')"
    width="md"
    @close="emit('close')"
    @submit="submit"
  >
    <div class="flex flex-col gap-lg">
      <p class="text-light">
        {{
          selected
            ? t('Only the selected elements are exported.')
            : t(
                'Exports the {num, number} results that match the current filters, search and sort.',
                {num: count}
              )
        }}
      </p>
      <Select
        v-if="exporters.length > 1"
        v-model="type"
        :label="t('Export type')"
        :options="
          exporters.map((candidate) => ({
            label: candidate.name,
            value: candidate.type,
          }))
        "
      />
      <Select v-model="format" :label="t('Format')" :options="formatOptions" />
      <craft-input
        v-if="!selected"
        type="number"
        .min="1"
        .max="count"
        :label="t('Limit')"
        :placeholder="t('All {num, number}', {num: count})"
        .value="limit ?? ''"
        @input="onLimitInput"
      />
    </div>
  </ModalForm>
</template>
