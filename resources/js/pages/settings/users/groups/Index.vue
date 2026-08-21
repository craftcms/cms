<script setup lang="ts">
    import {t} from '@craftcms/ui';
    import {h} from 'vue';
    import LayoutSlot from '@/common/components/LayoutSlot.vue';
    import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
    import Empty from '@/common/components/Empty.vue';
    import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
    import {
        create,
        destroy,
        edit,
    } from '@actions/Settings/Users/UserGroupsController';
    import CpLink from '@/common/components/CpLink.vue';
    import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
    import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
    import {router} from '@inertiajs/vue3';
    import type {UserGroup} from '@/common/types';

    const props = defineProps<{
        groups: Array<UserGroup>;
        readOnly: boolean;
    }>();

    function deleteGroup(group: UserGroup) {
        if (
            confirm(
                t('Are you sure you want to delete "{name}"?', {
                    name: group.name,
                })
            )
        ) {
            router.delete(destroy({groupId: group.id}));
        }
    }

    const columnHelper = createCraftColumnHelper<UserGroup>();
    const table = useVueTable({
        get columns() {
            return [
                columnHelper.link('name', {
                    header: t('Name'),
                    props: ({row}) => ({
                        href: edit({userGroup: row.original.id}).url,
                        inertia: true,
                    }),
                }),
                columnHelper.handle('handle'),
                columnHelper.actions(({row}) => [
                    h(DeleteButton, {onClick: () => deleteGroup(row.original)}),
                ]),
            ];
        },
        get data() {
            return props.groups;
        },
        getCoreRowModel: getCoreRowModel<UserGroup>(),
    });
</script>

<template>
    <LayoutSlot name="actions">
        <CpLink
            :inertia="false"
            :href="create().url"
            class="btn submit add icon"
            icon="plus"
            appearance="button"
            variant="accent"
            >{{ t('New user group') }}</CpLink
        >
    </LayoutSlot>

    <craft-pane appearance="raised" padding="0" class="@container">
        <AdminTable :table="table">
            <template #empty-row>
                <Empty icon="users" :label="t('No groups exist yet.')">
                    <CpLink
                        :inertia="false"
                        :href="create().url"
                        class="btn submit add icon"
                        icon="plus"
                        appearance="button"
                        >{{ t('New user group') }}</CpLink
                    >
                </Empty>
            </template>
        </AdminTable>
    </craft-pane>
</template>
