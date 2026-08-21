<script setup lang="ts">
    import {t} from '@craftcms/ui/utilities/translate';
    import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
    import {computed, h} from 'vue';
    import {type SectionSiteSettingsData} from '@/common/types';
    import {useEditableTable} from '@/modules/admin-table/composables/useEditableTable';
    import {usePage} from '@inertiajs/vue3';

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
            selectedType?: string;
            isMultiSite?: boolean;
            isHeadless?: boolean;
            disabled?: boolean;
        }>(),
        {isMultiSite: false, isHeadless: false}
    );

    const page = usePage<{
        homepageUri?: string;
        templateOptions: Array<any>;
    }>();

    const homepageUri = computed(() => page.props.homepageUri);
    const templateOptions = computed(() => page.props.templateOptions);

    const columnVisibility = computed(() => {
        return {
            name: true,
            enabled: props.isMultiSite,
            singleHomepage: props.selectedType === 'single',
            singleUri: props.selectedType === 'single',
            uriFormat: props.selectedType !== 'single',
            template: !props.isHeadless,
            enabledByDefault: props.selectedType !== 'single',
        };
    });

    const {table} = useEditableTable<SectionSiteSettingsData>({
        data: () => props.modelValue as Record<string, SectionSiteSettingsData>,
        key: 'handle',
        name: 'sites',
        columnVisibility: () => columnVisibility.value,
        onChange: (data) => emit('update:modelValue', data as SitesData),
        columns: ({columnHelper}) => [
            columnHelper.accessor('name', {
                header: t('Site'),
                cell: ({getValue}) => getValue(),
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
                    cellClass: 'bg-[var(--c-color-neutral-fill-quiet)]',
                },
                label: t('Enabled'),
            }),
            columnHelper.checkbox('singleHomepage', {
                header: () =>
                    h('craft-icon', {name: 'home', label: t('Homepage')}),
                meta: {
                    trackSize: '44px',
                    cellClass: 'text-center',
                    headerClass: 'justify-center',
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
            columnHelper.autocomplete('template', {
                header: t('Template'),
                class: 'w-full flex-1 font-mono text-xs !px-[var(--_cell-spacing)]',
                options: templateOptions.value,
                disabled: (row) => props.disabled || !row.original.enabled,
                meta: {
                    headerTip: t(
                        'Which template should be loaded when an entry’s URL is requested.'
                    ),
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
