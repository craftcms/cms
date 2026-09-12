<script setup lang="ts">
    import {t} from '@craftcms/ui';
    import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
    import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
    import {h, ref} from 'vue';
    import Empty from '@/common/components/Empty.vue';
    import CpLink from '@/common/components/CpLink.vue';
    import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
    import {router} from '@inertiajs/vue3';
    import LayoutSlot from '@/common/components/LayoutSlot.vue';
    import ActionMenu from '@/common/components/ActionMenu.vue';
    import type {ActionItems} from '@/common/types';
    import {
        create,
        destroy,
        duplicate,
        run,
    } from '@actions/Import/ImportConfigController';

    interface EditableConfigRow {
        uid: string;
        name: string;
        handle: string;
        file: string | null;
        site: string | null;
        isElementImport: boolean;
        className: string | null;
        editUrl: string;
        mapUrl: string | null;
    }

    interface NonEditableConfigRow {
        handle: string;
        name: string;
        site: string | null;
        isElementImport: boolean;
        className: string | null;
        transformer: string | null;
        hasMap: boolean;
    }

    const props = defineProps<{
        readOnly: boolean;
        canSave: boolean;
        canDelete: boolean;
        canTriggerRuns: boolean;
        editableConfigs: Array<EditableConfigRow>;
        nonEditableConfigs: Array<NonEditableConfigRow>;
    }>();

    const editableColumnHelper = createCraftColumnHelper<EditableConfigRow>();
    const editableColumns = ref([
        editableColumnHelper.link('name', {
            header: t('Name'),
            props: ({row}) => ({
                href: row.original.editUrl,
                inertia: false,
            }),
        }),
        editableColumnHelper.handle('handle'),
        editableColumnHelper.accessor('file', {
            header: t('File'),
        }),
        editableColumnHelper.accessor('site', {
            header: t('Site'),
            cell: ({getValue}) => getValue() ?? t('n/a'),
        }),
        editableColumnHelper.accessor('isElementImport', {
            header: t('Element import?'),
            cell: ({getValue}) =>
                getValue() ? h('craft-icon', {name: 'check'}) : '',
        }),
        editableColumnHelper.accessor('className', {
            header: t('Class name'),
        }),
        editableColumnHelper.actions(({row}) => {
            const actions: ActionItems = [
                {
                    type: 'link',
                    label: t('Edit'),
                    href: row.original.editUrl,
                },
            ];

            if (row.original.mapUrl) {
                actions.push({
                    type: 'link',
                    label: t('Edit Mapping'),
                    href: row.original.mapUrl,
                });
            }

            if (props.canSave) {
                actions.push({
                    type: 'button',
                    label: t('Duplicate'),
                    onClick: () =>
                        router.post(duplicate().url, {uid: row.original.uid}),
                });
            }

            if (props.canDelete) {
                actions.push({
                    type: 'button',
                    label: t('Delete'),
                    variant: 'danger',
                    onClick: () => {
                        if (
                            !window.confirm(
                                t('Are you sure you want to delete “{name}”?', {
                                    name: row.original.name,
                                })
                            )
                        ) {
                            return;
                        }
                        router.delete(destroy().url, {
                            data: {uid: row.original.uid},
                        });
                    },
                });
            }

            return [h(ActionMenu, {actions})];
        }),
    ]);

    const editableTable = useVueTable<EditableConfigRow>({
        get data() {
            return props.editableConfigs;
        },
        get columns() {
            return editableColumns.value;
        },
        enableSorting: false,
        getCoreRowModel: getCoreRowModel<EditableConfigRow>(),
    });

    const nonEditableColumnHelper =
        createCraftColumnHelper<NonEditableConfigRow>();
    const nonEditableColumns = ref([
        nonEditableColumnHelper.accessor('name', {
            header: t('Name'),
            cell: ({row, getValue}) =>
                h('div', [
                    h('div', {class: 'font-bold'}, getValue()),
                    h('code', row.original.handle),
                ]),
        }),
        nonEditableColumnHelper.accessor('site', {
            header: t('Site'),
            cell: ({getValue}) => getValue() ?? t('n/a'),
        }),
        nonEditableColumnHelper.accessor('isElementImport', {
            header: t('Element import?'),
            cell: ({getValue}) =>
                getValue() ? h('craft-icon', {name: 'check'}) : '',
        }),
        nonEditableColumnHelper.accessor('className', {
            header: t('Class name'),
        }),
        nonEditableColumnHelper.accessor('transformer', {
            header: t('Transformer'),
            cell: ({getValue}) => getValue() ?? t('none'),
        }),
        nonEditableColumnHelper.accessor('hasMap', {
            header: t('Map'),
            cell: ({getValue}) => (getValue() ? t('Yes') : t('n/a')),
        }),
        nonEditableColumnHelper.actions(({row}) => {
            if (!props.canTriggerRuns) {
                return [];
            }

            const actions: ActionItems = [
                {
                    type: 'button',
                    label: t('Run'),
                    onClick: () =>
                        router.post(run().url, {handle: row.original.handle}),
                },
            ];

            return [h(ActionMenu, {actions})];
        }),
    ]);

    const nonEditableTable = useVueTable<NonEditableConfigRow>({
        get data() {
            return props.nonEditableConfigs;
        },
        get columns() {
            return nonEditableColumns.value;
        },
        enableSorting: false,
        getCoreRowModel: getCoreRowModel<NonEditableConfigRow>(),
    });
</script>

<template>
    <LayoutSlot v-if="canSave" name="actions">
        <CpLink
            variant="accent"
            appearance="button"
            :href="create().url"
            :inertia="false"
        >
            {{ t('New import config') }}
        </CpLink>
    </LayoutSlot>

    <div class="grid gap-6">
        <craft-pane padding="0" appearance="raised">
            <AdminTable :table="editableTable" :reorderable="false">
                <template #empty-row>
                    <Empty
                        :label="t('No editable import configs yet.')"
                        icon="light/gear"
                    />
                </template>
            </AdminTable>
        </craft-pane>

        <craft-pane padding="0" appearance="raised">
            <AdminTable :table="nonEditableTable" :reorderable="false">
                <template #empty-row>
                    <Empty
                        :label="t('No non-editable import configs yet.')"
                        icon="light/gear"
                    />
                </template>
            </AdminTable>
        </craft-pane>
    </div>
</template>
