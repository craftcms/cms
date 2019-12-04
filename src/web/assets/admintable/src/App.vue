<template>
    <div>
        <div v-show="showToolbar" class="toolbar">
            <div class="flex">

                <div v-if="showCheckboxes" class="selectallcontainer">
                    <div v-on:click="handleSelectAll" class="btn" role="checkbox" tabindex="0" aria-checked="false">
                        <div class="checkbox" :class="{ checked: checks.length && checks.length == $refs.vuetable.tableData.length, indeterminate: this.checks.length && this.checks.length != $refs.vuetable.tableData.length }"></div>
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

                <div v-if="search && !tableData.length" class="flex-grow texticon search icon clearable">
                    <input
                        class="text fullwidth"
                        type="text"
                        autocomplete="off"
                        :placeholder="searchPlaceholder"
                        v-model="searchTerm"
                        @input="handleSearch"
                    >
                    <div class="clear hidden" title="Clear"></div>
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
                    :append-params="appendParams"
                    pagination-path="pagination"
                    @vuetable:loaded="init"
                    @vuetable:loading="loading"
                    @vuetable:pagination-data="onPaginationData"
            >
                <template slot="checkbox" slot-scope="props">
                    <admin-table-checkbox
                        :id="props.rowData.id"
                        :checks="checks"
                        v-on:addCheck="addCheck"
                        v-on:removeCheck="removeCheck"
                    ></admin-table-checkbox>
                </template>
                <template slot="title" slot-scope="props">
                    <span v-if="props.rowData.status !== undefined" class="status" :class="{enabled: props.rowData.status}"></span>
                    <a class="cell-bold" v-if="props.rowData.url" :href="props.rowData.url">{{ props.rowData.title }}</a>
                    <span class="cell-bold" v-if="!props.rowData.url">{{ props.rowData.title }}</span>
                </template>
                <template slot="handle" slot-scope="props">
                    <code>{{ props.rowData.handle }}</code>
                </template>
                <template slot="menu" slot-scope="props">
                    <template v-if="props.rowData.menu.showItems">
                        <a :href="props.rowData.url">{{props.rowData.menu.label}} ({{props.rowData.menu.items.length}})</a>
                        <a class="menubtn" :title="props.rowData.menu.label"></a>
                        <div class="menu">
                            <ul>
                                <li v-for="(item, index) in props.rowData.menu.items" :key="index">
                                    <a :href="item.url">{{item.label}}</a>
                                </li>
                            </ul>
                        </div>
                    </template>
                    <template v-else>
                        <a :href="props.rowData.menu.url">{{props.rowData.menu.label}}</a>
                    </template>
                </template>
                <template slot="reorder" slot-scope="props">
                    <i class="move icon" :data-id="props.rowData.id"></i>
                </template>
                <template slot="delete" slot-scope="props">
                    <admin-table-delete-button
                        :id="props.rowData.id"
                        :name="props.rowData.title"
                        :success-message="deleteSuccessMessage"
                        :confirmation-message="deleteConfirmationMessage"
                        :action-url="deleteAction"
                        :disabled="canDelete"
                        v-on:reload="remove(props.rowIndex)"
                        v-if="props.rowData._showDelete == undefined || props.rowData._showDelete == true"
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
    /* global Craft, Vue */
    import Vuetable from 'vuetable-2/src/components/Vuetable'
    import AdminTablePagination from './js/components/AdminTablePagination'
    import AdminTableDeleteButton from './js/components/AdminTableDeleteButton';
    import AdminTableCheckbox from './js/components/AdminTableCheckbox';
    import AdminTableActionButton from './js/components/AdminTableActionButton';
    import Sortable from 'sortablejs'
    import {debounce, map} from 'lodash'

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
            'deleteCallback',
            'deleteConfirmationMessage',
            'deleteSuccessMessage',
            'minItems',
            'perPage',
            'reorderAction',
            'reorderSuccessMessage',
            'reorderFailMessage',
            'search',
            'searchPlaceholder',
            'tableData',
            'tableDataEndpoint',
        ],

        data() {
            return {
                checks: [],
                currentPage: 1,
                tableClass: 'data fullwidth',
                isLoading: true,
                searchTerm: null,
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

            loading() {
              this.isLoading = true;
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

            handleSearch: debounce(function() {
                this.reload();
            }, 350),

            handleSelectAll() {
                var tableData = this.$refs.vuetable.tableData;
                if (this.checks.length != tableData.length) {
                    tableData.forEach(row => {
                        this.addCheck(row.id);
                    });
                } else {
                    this.checks = [];
                }
            },

            deselectAll() {
                this.checks = [];
            },

            reload() {
                this.isLoading = true;
                this.deselectAll();
                this.$refs.vuetable.reload();
            },

            remove(index) {
              this.isLoading = true;

              if (this.apiUrl) {
                  this.deselectAll();
                  this.$refs.vuetable.reload();
              } else {
                  Vue.delete(this.$refs.vuetable.tableData, index);
                  this.$refs.vuetable.refresh();
              }

              if (this.deleteCallback && {}.toString.call(this.deleteCallback) === '[object Function]') {
                  this.deleteCallback();
              }

              this.isLoading = false;
            },

            onPaginationData (paginationData) {
                this.currentPage = paginationData.current_page;
                this.$refs.pagination.setPaginationData(paginationData)
                this.deselectAll();
            },

            onChangePage (page) {
                this.$refs.vuetable.changePage(page)
                this.deselectAll();
            },

        },

        computed: {
            apiUrl() {
                if (!this.tableDataEndpoint) {
                    return '';
                }

                return Craft.getActionUrl(this.tableDataEndpoint);
            },

            appendParams() {
                if (!this.searchTerm) {
                    return {};
                }

                return {
                    search: this.searchTerm
                };
            },

            canDelete() {
                return this.minItems && this.$refs.vuetable.tableData.length <= this.minItems
            },

            fields() {
                let columns = [];

                // Enable/Disable checkboxes
                if (this.showCheckboxes) {
                    columns.push({
                        name: '__slot:checkbox',
                        titleClass: 'thin',
                        dataClass: 'checkbox-cell'
                    });
                }

                let customColumns = map(this.columns, item => {
                    // Do not allow sorting for if you can manually reorder items
                    if (this.reorderAction && item.hasOwnProperty('sortField')) {
                        delete item.sortField;
                        return item;
                    }

                    return item;
                });

                columns = [...columns,...customColumns];

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

            showCheckboxes() {
              return (this.actions.length && this.checkboxes);
            },

            showToolbar() {
                return (this.actions.length || (this.search && !this.tableData.length));
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

    table thead th.sortable:hover {
        background-color: #f9f9f9;
    }
</style>