<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {computed, h} from 'vue';
  import {type SectionSiteSettingsData} from '@/common/types';
  import {useEditableTable} from '@/modules/admin-table/composables/useEditableTable';
  import {usePage} from '@inertiajs/vue3';
  import Select from '@/common/form/Select.vue';
  import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/field-group/field-group';

  type SitesData = Record<string, Omit<SectionSiteSettingsData, 'handle'>>;

  const emit = defineEmits<{
    (e: 'update:modelValue', value: SitesData): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue: Record<string, Omit<SectionSiteSettingsData, 'handle'>>;
      selectedType?: string;
      isHeadless?: boolean;
      disabled?: boolean;
    }>(),
    {isHeadless: false}
  );

  const page = usePage<{
    homepageUri?: string;
    templateOptions: Array<import('@/common/types').SelectItem>;
    isMultiSite?: boolean;
  }>();

  const homepageUri = computed(() => page.props.homepageUri);
  const templateOptions = computed(() => page.props.templateOptions);
  const isMultiSite = computed(() => page.props.isMultiSite ?? false);

  const columnVisibility = computed(() => {
    return {
      name: true,
      enabled: !!isMultiSite.value,
      singleHomepage: props.selectedType === 'single',
      singleUri: props.selectedType === 'single',
      uriFormat: props.selectedType !== 'single',
      route: !props.isHeadless,
      enabledByDefault: props.selectedType !== 'single',
    };
  });

  const {table} = useEditableTable<SectionSiteSettingsData>({
    // SAFETY: useEditableTable injects each record key as the configured handle property.
    data: () => props.modelValue as Record<string, SectionSiteSettingsData>,
    key: 'handle',
    name: 'sites',
    columnVisibility: () => columnVisibility.value,
    onChange: (data) => {
      if (Array.isArray(data)) {
        throw new Error(
          'Section site settings must remain keyed by site handle.'
        );
      }
      // SAFETY: useEditableTable removes its injected handle before returning keyed records.
      emit('update:modelValue', data as SitesData);
    },
    columns: ({columnHelper}) => [
      columnHelper.display({
        id: 'name',
        header: t('Site'),
        cell: ({row}) => row.original.name,
        meta: {
          cellTag: 'th',
          trackSize: '0.25fr',
        },
      }),
      columnHelper.lightswitch('enabled', {
        header: t('Enabled'),
        disabled: () => props.disabled,
        meta: {
          trackSize: '80px',
        },
      }),
      columnHelper.checkbox('singleHomepage', {
        header: () => h('craft-icon', {name: 'home', label: t('Homepage')}),
        meta: {
          trackSize: '44px',
          cellClass: 'text-center',
          headerClass: 'text-center',
        },
        onChange: (value, {row}) => {
          if (value) {
            const newValue = {...props.modelValue};
            newValue[row.original.handle]!['singleUri'] =
              homepageUri.value ?? '';

            emit('update:modelValue', newValue);
          } else {
            const newValue = {...props.modelValue};
            newValue[row.original.handle]!['singleUri'] = '';

            emit('update:modelValue', newValue);
          }
        },
        disabled: (row) => props.disabled || !row.original.enabled,
      }),
      columnHelper.text('singleUri', {
        header: t('URI'),
        class: 'font-mono text-xs',
        placeholder: t("Leave blank if the entry doesn't have a URL"),
        disabled: (row) =>
          props.disabled ||
          !row.original.enabled ||
          row.original.singleHomepage,
        meta: {
          headerTip: t(
            'What the entry URI should be for the site. Leave blank if the entry doesn’t have a URL.'
          ),
        },
      }),
      columnHelper.text('uriFormat', {
        header: t('Entry URI Format'),
        class: 'font-mono text-xs',
        placeholder: t("Leave blank if the entry doesn't have a URL"),
        disabled: (row) => props.disabled || !row.original.enabled,
        meta: {
          headerTip: t(
            'What entry URIs should look like for the site. Leave blank if entries don’t have URLs.'
          ),
        },
      }),
      columnHelper.display({
        id: 'route',
        header: t('Route'),
        cell: ({row}) => {
          const site = row.original;
          const disabled = props.disabled || !site.enabled;
          const update = (key: 'routeType' | 'route', value: string) => {
            emit('update:modelValue', {
              ...props.modelValue,
              [site.handle]: {...props.modelValue[site.handle]!, [key]: value},
            });
          };

          return h(
            'craft-field',
            {
              fieldset: true,
              label: t('Route'),
              'label-sr-only': true,
              class: 'min-w-72',
            },
            [
              h(
                'craft-field-group',
                {
                  slot: 'input',
                  class: '!flex w-full flex-wrap items-end !gap-2',
                },
                [
                  h(Select, {
                    modelValue: site.routeType,
                    options: [
                      {label: t('Template'), value: 'template'},
                      {label: t('Route'), value: 'route'},
                    ],
                    label: t('Route type'),
                    'label-sr-only': true,
                    class: 'w-28 shrink-0',
                    '.disabled': disabled,
                    'onUpdate:modelValue': (value: string | number) =>
                      update('routeType', String(value)),
                  }),
                  h(CraftCombobox, {
                    modelValue: site.route ?? '',
                    options: templateOptions.value,
                    label: t('Route'),
                    'label-sr-only': true,
                    requireOptionMatch: false,
                    disabled,
                    class: 'min-w-0 flex-1 font-mono text-xs',
                    'onUpdate:modelValue': (value: unknown) =>
                      update('route', String(value ?? '')),
                  }),
                ]
              ),
            ]
          );
        },
      }),
      columnHelper.lightswitch('enabledByDefault', {
        header: t('Default Status'),
        meta: {
          trackSize: '120px',
        },
        disabled: (row) => props.disabled || !row.original.enabled,
      }),
    ],
  });
</script>

<template>
  <craft-pane padding="0" appearance="raised">
    <AdminTable :table="table" :reorderable="false" />
  </craft-pane>
</template>

<style scoped lang="scss"></style>
