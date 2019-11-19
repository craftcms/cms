<template>
    <div>
        <div v-show="actions.length || showToolbar" class="toolbar">
            <div class="flex">

                <div class="selectallcontainer">
                    <div v-on:click="handleSelectAll" class="btn" role="checkbox" tabindex="0" aria-checked="false">
                        <div class="checkbox" :class="{ checked: isSelectAll === true }"></div>
                    </div>
                </div>

                <div v-for="(action,index) in actions" :key="index" v-if="checks.length">
                    <admin-table-action-button
                        :label="action.label"
                        :icon="action.icon"
                        :action="action.action"
                        :actions="action.actions"
                        :ids="checks"
                        v-on:reload="reload"
                    >
                    </admin-table-action-button>
                </div>

            </div>
        </div>
        <div class="tableview" :class="{ loading: isLoading }">
            <vuetable
                    ref="vuetable"
                    :per-page="perPage"
                    :css="tableCss"
                    :fields="fields"
                    :api-url="apiUrl"
                    :api-mode="apiUrl ? true : false"
                    :data="tableData"
                    pagination-path="links.pagination"
                    @vuetable:loaded="init"
                    @vuetable:pagination-data="onPaginationData"
            >
                <template slot="checkbox" slot-scope="props">
                    <admin-table-checkbox
                        :id="props.rowData.id"
                        :checks="checks"
                        :select-all="isSelectAll"
                        v-on:addCheck="addCheck"
                        v-on:removeCheck="removeCheck"
                    ></admin-table-checkbox>
                </template>
                <template slot="statusName" slot-scope="props">
                    <span v-if="props.rowData.status !== undefined" class="status" :class="{enabled: props.rowData.status}"></span>
                    <a class="cell-bold" v-if="props.rowData.url" href="props.rowData.url">{{ props.rowData.name }}</a>
                    <span class="cell-bold" v-if="!props.rowData.url">{{ props.rowData.name }}</span>
                </template>
                <template slot="reorder" slot-scope="props">
                    <i class="move icon" :data-id="props.rowData.id"></i>
                </template>
                <template slot="delete" slot-scope="props">
                    <admin-table-delete-button
                        :id="props.rowData.id"
                        :name="props.rowData.name"
                        :action-url="deleteAction"
                        v-on:reload="reload"
                    ></admin-table-delete-button>
                </template>
            </vuetable>
            <admin-table-pagination
                    ref="pagination"
                    @vuetable-pagination:change-page="onChangePage"
            ></admin-table-pagination>
        </div>
    </div>
</template>
<script>
    /* global Craft */
    import Vuetable from 'vuetable-2/src/components/Vuetable'
    import AdminTablePagination from './js/components/AdminTablePagination'
    import AdminTableDeleteButton from './js/components/AdminTableDeleteButton';
    import AdminTableCheckbox from './js/components/AdminTableCheckbox';
    import AdminTableActionButton from './js/components/AdminTableActionButton';
    import Sortable from 'sortablejs'

    export default {
        components: {
            Vuetable,
            AdminTablePagination,
            AdminTableCheckbox,
            AdminTableDeleteButton,
            AdminTableActionButton
        },

        props: [
            'actions',
            'checkboxes',
            'columns',
            'container',
            'deleteAction',
            'tableDataEndpoint',
            'perPage',
            'reorderAction',
            'reorderSuccessMessage',
            'reorderFailMessage',
            'tableData',
        ],

        data() {
            return {
                checks: [],
                currentPage: 1,
                tableClass: 'data fullwidth',
                showToolbar: false,
                isLoading: true,
                isSelectAll: false,
                sortable: null,
            }
        },

        methods: {
            init() {
                let tableBody = this.$el.querySelector('.vuetable-body');
                if (this.reorderAction && tableBody) {
                    this.sortable = Sortable.create(tableBody, {
                        handle: '.move.icon',
                        onSort: this.updateSortOrder
                    })
                }

                this.isLoading = false;
            },

            updateSortOrder(ev) {
                let newIndex = ev.newIndex + (this.currentPage > 1 ? (this.currentPage-1) * this.perPage : 0);
                // Make the order non-zero based
                newIndex = newIndex + 1;
                let moveHandle = ev.item.querySelector('.move.icon');

                if (moveHandle) {
                    let data = {
                        id: moveHandle.dataset.id,
                        position: newIndex
                    };

                    Craft.postActionRequest(this.reorderAction, data, response => {
                        if (response && response.success) {
                            Craft.cp.displayNotice(this.reorderSuccessMessage);
                        }
                    });
                } else {
                    Craft.cp.displayError(this.reorderFailMessage);
                }
            },

            addCheck(id) {
                if (this.checks.indexOf(id) === -1) {
                    this.checks.push(id);
                }
            },

            removeCheck(id) {
                let key = this.checks.indexOf(id);
                if (key >= 0) {
                    this.checks.splice(key, 1);
                }
            },

            handleSelectAll() {
                this.isSelectAll = !this.isSelectAll;
            },

            deselectAll() {
                this.checks = [];
                this.isSelectAll = false;
            },

            reload() {
                this.isLoading = true;
                this.deselectAll();
                this.$refs.vuetable.reload();
            },

            onPaginationData (paginationData) {
                this.currentPage = paginationData.current_page;
                this.$refs.pagination.setPaginationData(paginationData)
            },

            onChangePage (page) {
                this.$refs.vuetable.changePage(page)
            },

        },

        computed: {
            apiUrl() {
                if (!this.tableDataEndpoint) {
                    return '';
                }

                return Craft.getActionUrl(this.tableDataEndpoint);
            },

            fields() {
                let columns = [];

                // Enable/Disable checkboxes
                if (this.checkboxes && this.actions.length) {
                    columns.push({
                        name: '__slot:checkbox',
                        titleClass: 'thin',
                        dataClass: 'checkbox-cell'
                    });
                }

                columns = [...columns,...this.columns];

                if (this.reorderAction) {
                    columns.push({
                        name: '__slot:reorder',
                        title: '',
                        titleClass: 'thin'
                    });
                }

                if (this.deleteAction) {
                    columns.push({
                        name: '__slot:delete',
                        titleClass: 'thin'
                    });
                }

                return columns;
            },

            tableCss() {
                return {
                    tableClass: this.tableClass,
                    handleIcon: 'move icon',
                    loadingClass: 'loading'
                }
            },

        },
    }
</script>

<style lang="scss">
    .tableview td.checkbox-cell {
        padding-right: 7px;
        width: 12px !important;
        position: relative;
    }

    .tableview td.checkbox-cell .checkbox {
        position: absolute;
        top: calc(50% - 6px);
    }

    .tableview.loading {
        opacity: .3;
    }

    .tableview .cell-bold {
        font-weight: bold;
    }
</style>