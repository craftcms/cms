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
    import {create, destroy, run} from '@actions/Import/ImportRunController';

    interface RunRow {
        uid: string;
        name: string;
        handle: string;
        editUrl: string;
    }

    const props = defineProps<{
        readOnly: boolean;
        canSave: boolean;
        canTriggerRuns: boolean;
        canDelete: boolean;
        runs: Array<RunRow>;
    }>();

    const columnHelper = createCraftColumnHelper<RunRow>();
    const columns = ref([
        columnHelper.link('name', {
            header: t('Name'),
            props: ({row}) => ({
                href: row.original.editUrl,
                inertia: false,
            }),
        }),
        columnHelper.actions(({row}) => {
            const actions: ActionItems = [
                {
                    type: 'link',
                    label: t('Edit'),
                    href: row.original.editUrl,
                },
            ];

            if (props.canTriggerRuns) {
                actions.push({
                    type: 'button',
                    label: t('Start this run'),
                    onClick: () => {
                        if (
                            !window.confirm(
                                t(
                                    'Are you sure you want to start “{name}” run?',
                                    {
                                        name: row.original.name,
                                    }
                                )
                            )
                        ) {
                            return;
                        }
                        router.post(run().url, {uid: row.original.uid});
                    },
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
                        router.post(destroy().url, {uid: row.original.uid});
                    },
                });
            }

            return [h(ActionMenu, {actions})];
        }),
    ]);

    const table = useVueTable<RunRow>({
        get data() {
            return props.runs;
        },
        get columns() {
            return columns.value;
        },
        enableSorting: false,
        getCoreRowModel: getCoreRowModel<RunRow>(),
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
            {{ t('New import run') }}
        </CpLink>
    </LayoutSlot>

    <craft-pane padding="0" appearance="raised">
        <AdminTable :table="table" :reorderable="false">
            <template #empty-row>
                <Empty
                    :label="t('No import runs exist yet.')"
                    icon="light/upload"
                />
            </template>
        </AdminTable>
    </craft-pane>
</template>
