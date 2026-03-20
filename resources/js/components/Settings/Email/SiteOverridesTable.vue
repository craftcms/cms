<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import Pane from '@/components/Pane.vue';
  import {useEditableTable} from '@/composables/useEditableTable';
  import {usePage} from '@inertiajs/vue3';
  import type {SelectItem} from '@/types';
  import {computed} from 'vue';

  type SiteOverridesData = {
    fromEmail?: string;
    fromName?: string;
    replyToEmail?: string;
    template?: string;
    uid: string;
  };

  type ModelValue = Record<string, SiteOverridesData>;

  const emit = defineEmits<{
    (e: 'update:modelValue', value: ModelValue): void;
  }>();
  const props = defineProps<{
    sites: Array<{
      uid: string;
      name: string;
    }>;
    modelValue: ModelValue;
  }>();

  const page = usePage<{
    envSuggestions?: Array<any>;
    readOnly?: boolean;
    templateSuggestions: Array<SelectItem>;
  }>();

  const envSuggestions = computed(() => page.props.envSuggestions);
  const readOnly = computed(() => page.props.readOnly);
  const templateSuggestions = computed(() => page.props.templateSuggestions);

  function getSiteName(siteUid: string): string {
    return props.sites.find((s) => s.uid === siteUid)?.name ?? siteUid;
  }

  const {table} = useEditableTable<SiteOverridesData>({
    data: () => props.modelValue,
    key: 'uid',
    name: 'siteOverrides',
    onChange: (data) => emit('update:modelValue', data as ModelValue),
    columns: ({columnHelper}) => [
      columnHelper.display({
        id: 'name',
        header: t('Site'),
        cell: ({row}) => getSiteName(row.original.uid),
        meta: {
          cellTag: 'th',
        },
      }),
      columnHelper.autocomplete('fromEmail', {
        header: t('System Email Address'),
        class: 'font-mono text-xs !px-[var(--_cell-spacing-inline)]',
        options: envSuggestions.value,
      }),
      columnHelper.autocomplete('fromName', {
        header: t('Sender Name'),
        class: 'font-mono text-xs !px-[var(--_cell-spacing-inline)]',
        options: envSuggestions.value,
      }),
      columnHelper.autocomplete('replyToEmail', {
        header: t('Reply-To Address'),
        class: 'font-mono text-xs !px-[var(--_cell-spacing-inline)]',
        options: envSuggestions.value,
      }),
      columnHelper.autocomplete('template', {
        header: t('HTML Email Template'),
        class: 'font-mono text-xs !px-[var(--_cell-spacing-inline)]',
        options: templateSuggestions.value,
      }),
    ],
  });
</script>

<template>
  <Pane :padding="0" appearance="raised">
    <AdminTable :table="table" :reorderable="false" />
  </Pane>
</template>

<style scoped lang="scss"></style>
