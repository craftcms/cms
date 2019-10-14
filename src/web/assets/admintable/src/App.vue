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
                    >
                    </admin-table-action-button>
                </div>

            </div>
        </div>
        <div class="tableview">
            <vuetable
                    ref="vuetable"
                    :per-page="resultsPerPage"
                    :css="tableCss"
                    :fields="fields"
                    :api-url="apiUrl"
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
                <template slot="delete" slot-scope="props">
                    <admin-table-delete-button
                        :id="props.rowData.id"
                        :name="props.rowData.name"
                        :action-url="deleteActionUrl"
                        v-on:reload="reload"
                    ></admin-table-delete-button>
                </template>
            </vuetable>
        </div>
    </div>
</template>
<script>
    /* global Craft */
    import {mapState} from 'vuex'
    import Vuetable from 'vuetable-2/src/components/Vuetable'
    import AdminTableDeleteButton from './js/components/AdminTableDeleteButton';
    import AdminTableCheckbox from './js/components/AdminTableCheckbox';
    import AdminTableActionButton from './js/components/AdminTableActionButton';

    export default {
        components: {
            Vuetable,
            AdminTableCheckbox,
            AdminTableDeleteButton,
            AdminTableActionButton
        },

        props: [
            'actionButtons',
            'checkboxes',
            'columns',
            'deleteActionUrl',
            'endpoint',
            'perPage',
            'reorder'
        ],

        data() {
            return {
                checks: [],
                tableClass: 'data fullwidth',
                showToolbar: false,
                isSelectAll: false
            }
        },

        methods: {
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
                this.$refs.vuetable.reload();
            }
        },

        computed: {
            actions() {
                return this.actionButtons !== undefined ? JSON.parse(this.actionButtons) : [];
            },

            apiUrl() {
                return Craft.actionUrl + this.endpoint;
            },

            tableColumns() {
               return this.columns !== undefined ? JSON.parse(this.columns) : [];
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
                    // columns.push({
                    //     name: '__checkbox',
                    //     titleClass: 'checkbox-cell thin',
                    //     dataClass: 'checkbox-cell',
                    // });
                }

                columns = [...columns,...this.tableColumns];

                if (this.showReorder) {
                    columns.push({
                        name: '__handle',
                        titleClass: 'thin'
                    })
                }

                if (this.deleteActionUrl) {
                    columns.push({
                        name: '__slot:delete',
                        titleClass: 'thin'
                    });
                }

                return columns;
            },

            showActions() {
                return this.actions && this.actions.length
            },

            showCheckboxes() {
                return this.checkboxes !== undefined ? JSON.parse(this.checkboxes) : false;
            },

            showReorder() {
                return this.reorder !== undefined ? JSON.parse(this.reorder) : false;
            },

            resultsPerPage() {
                return this.perPage !== undefined ? JSON.parse(this.perPage) : 20;
            },

            onPaginationData (paginationData) {
                this.$store.commit('updatePagination', paginationData);
                return true;
            },

            tableCss() {
                return {
                    tableClass: this.tableClass,
                    handleIcon: 'move icon',
                    loadingClass: 'loading'
                }
            },

            ...mapState({
                currentPage: state => {
                    return state.currentPage
                },
            })
        },

        watch: {
            currentPage() {
                this.deselectAll();
            }
        },

        mounted() {
            this.$store.commit('updateTable', this.$refs.vuetable)
        }
    }
</script>

<style style="scss">
    .tableview td.checkbox-cell {
        padding-right: 7px;
        width: 12px !important;
        position: relative;
    }

    .tableview td.checkbox-cell .checkbox {
        position: absolute;
        top: calc(50% - 6px);
    }

    .tableview .loading {
        opacity: .3;
    }
</style>