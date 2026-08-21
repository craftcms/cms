<script setup lang="ts">
    import {t} from '@craftcms/ui';
    import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
    import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
    import {computed, h, ref} from 'vue';
    import Empty from '@/common/components/Empty.vue';
    import CpLink from '@/common/components/CpLink.vue';
    import {
        create,
        destroy,
        edit,
    } from '@actions/Settings/FilesystemsController';
    import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
    import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
    import {router} from '@inertiajs/vue3';
    import LayoutSlot from '@/common/components/LayoutSlot.vue';

    interface FileSystemData {
        name: string;
        handle: string;
        oldHandle: any;
        hasUrls: boolean;
        url: string;
        uid?: string;
        missing: boolean;
        rootUrl: string;
        type: string;
        id: number;
        dateCreated: string;
        dateUpdated: string;
        settingsHtml: string;
        rootPath: string;
        path: string;
    }

    const props = defineProps<{
        filesystems: {data: Array<FileSystemData>};
        readOnly: boolean;
    }>();

    function deleteFs(fs: FileSystemData) {
        if (
            confirm(
                t('Are you sure you want to delete “{name}”', {
                    name: fs.name,
                })
            )
        ) {
            router.delete(destroy({handle: fs.handle}));
        }
    }

    const columnHelper = createCraftColumnHelper<FileSystemData>();

    const columnVisibility = computed(() => {
        return {
            name: true,
            handle: true,
            type: true,
            actions: !props.readOnly,
        };
    });
    const columns = ref([
        columnHelper.link('name', {
            header: t('Name'),
            props: ({row}) => ({
                href: edit['/{cpTrigger?}/settings/filesystems/{handle}/edit']({
                    handle: row.original.handle,
                }).url,
                inertia: false,
            }),
        }),
        columnHelper.handle('handle'),
        columnHelper.accessor('type', {
            header: t('Type'),
            cell: ({row, getValue}) => {
                if (row.original.missing) {
                    return h('span', {class: 'c-color-error'}, getValue());
                }
                return getValue();
            },
        }),
        columnHelper.actions(({row}) => [
            h(DeleteButton, {
                onClick: () => deleteFs(row.original),
            }),
        ]),
    ]);
    const table = useVueTable<FileSystemData>({
        get data() {
            return props.filesystems.data;
        },
        get columns() {
            return columns.value;
        },
        state: {
            get columnVisibility() {
                return columnVisibility.value;
            },
        },
        enableSorting: false,
        getCoreRowModel: getCoreRowModel<FileSystemData>(),
    });
</script>

<template>
    <LayoutSlot name="actions">
        <CpLink
            variant="accent"
            appearance="button"
            :href="create().url"
            :inertia="false"
            >{{ t('New filesystem') }}</CpLink
        >
    </LayoutSlot>

    <craft-pane padding="0" appearance="raised">
        <AdminTable :table="table" :reorderable="false">
            <template #empty-row>
                <Empty
                    :label="t('No filesystems exist yet.')"
                    icon="light/folder-open"
                >
                    <CpLink
                        appearance="button"
                        :href="create().url"
                        :inertia="false"
                        >{{ t('New filesystem') }}</CpLink
                    >
                </Empty>
            </template>
        </AdminTable>
    </craft-pane>
</template>
