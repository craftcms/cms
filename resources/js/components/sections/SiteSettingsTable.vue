<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {computed, h} from 'vue';
  import type {SectionSiteSettingsData} from '@/types';
  import Pane from '@/components/Pane.vue';
  import {useEditableTable} from '@/composables/useEditableTable';

  type SitesData = Record<
    string,
    {
      enabled: boolean;
      [key: string]: any;
    }
  >;

  const emit = defineEmits<{
    (e: 'update:modelValue', value: SitesData): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue: Record<string, Omit<SectionSiteSettingsData, 'handle'>>;
      selectedType: string;
      isMultisite?: boolean;
      isHeadless?: boolean;
    }>(),
    {isMultisite: false, isHeadless: false}
  );

  const columnVisibility = computed(() => {
    return {
      name: true,
      enabled: props.isMultisite,
      homepage: props.selectedType === 'single',
      singleUri: props.selectedType === 'single',
      uriFormat: props.selectedType !== 'single',
      template: !props.isHeadless,
      enabledByDefault: props.selectedType !== 'single',
    };
  });

  const {table} = useEditableTable<SectionSiteSettingsData>({
    data: () => props.modelValue,
    key: 'handle',
    columnVisibility: () => columnVisibility.value,
    onChange: (data) => emit('update:modelValue', data as SitesData),
    columns: ({columnHelper}) => [
      columnHelper.accessor('name', {
        header: t('Site'),
        cell: ({getValue}) => getValue(),
        meta: {
          cellTag: 'th',
        },
      }),
      columnHelper.input('enabled', 'lightswitch', {
        header: t('Enabled'),
        size: 80,
        meta: {
          cellClass: 'bg-[var(--c-color-neutral-bg-normal)]',
        },
        label: t('Enabled'),
        ariaLabelledBy: 'header-enabled',
      }),
      columnHelper.display({
        id: 'homepage',
        header: () => h('craft-icon', {name: 'home', label: t('Homepage')}),
        size: 44,
        meta: {
          cellClass: 'text-center',
          headerClass: 'justify-center',
        },
        cell: ({row}) =>
          h('input', {type: 'checkbox', value: row.original.singleHomepage}),
      }),
      columnHelper.input('singleUri', 'singleline', {
        header: t('URI'),
        class: 'font-mono text-xs',
        placeholder: t("Leave blank if the entry doesn't have a URL"),
        name: (row, c) => `sites[${row.original.handle}][${c}]`,
      }),
      columnHelper.input('uriFormat', 'singleline', {
        header: t('Entry URI Format'),
        class: 'font-mono text-xs',
        placeholder: t("Leave blank if the entry doesn't have a URL"),
        name: (row, c) => `sites[${row.original.handle}][${c}]`,
      }),
      columnHelper.input('template', 'singleline', {
        header: t('Template'),
        class: 'font-mono text-xs',
        name: (row, c) => `sites[${row.original.handle}][${c}]`,
      }),
      columnHelper.input('enabledByDefault', 'lightswitch', {
        header: t('Default Status'),
        size: 40,
      }),
    ],
  });
</script>

<template>
  <Pane :padding="0" appearance="raised">
    <AdminTable :table="table" spacing="relaxed" :reorderable="false" />
  </Pane>
</template>

<style scoped lang="scss"></style>
