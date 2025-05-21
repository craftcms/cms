<template>
  <div
    :id="tableId"
    class="vue-admin-table"
    :class="{'vue-admin-table-padded': padded}"
  >
    <div v-show="showToolbar" class="toolbar">
      <div class="flex flex-nowrap">
        <div v-for="(action, index) in actions" :key="index">
          <admin-table-action-button
            :label="action.label"
            :icon="action.icon"
            :action="action.action"
            :actions="action.actions"
            :allow-multiple="action.allowMultiple"
            :ids="checks"
            :enabled="checks.length ? true : false"
            :error="action.error"
            :ajax="action.ajax"
            v-on:reload="reload"
            v-on:click="handleActionClick"
          >
          </admin-table-action-button>
        </div>

        <div v-if="search" class="flex-grow texticon search icon clearable">
          <span class="texticon-icon search icon" aria-hidden="true"></span>
          <input
            class="text fullwidth"
            type="text"
            autocomplete="off"
            :placeholder="searchPlaceholderText"
            v-model="searchTerm"
            @input="handleSearch"
            :autofocus="autofocusPreferred"
          />
          <button
            v-if="searchTerm.length"
            class="clear-btn"
            :title="searchClearTitle"
            role="button"
            :aria-label="searchClearTitle"
            @click="resetSearch"
          ></button>
        </div>

        <div class="vue-admin-table-buttons" v-if="buttons && buttons.length">
          <div class="flex flex-nowrap">
            <div v-for="(button, index) in buttons" :key="index">
              <admin-table-button
                :label="button.label"
                :icon="button.icon"
                :href="button.href"
                :btn-class="button.class"
                :enabled="
                  isLoading
                    ? false
                    : button.enabled != undefined
                      ? button.enabled
                      : true
                "
              ></admin-table-button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div :class="{'content-pane': fullPage}">
      <div v-if="this.isEmpty" class="zilch">
        <p v-if="this.searchTerm.length">{{ noSearchResults }}</p>
        <p v-else>{{ emptyMessage }}</p>
      </div>

      <div
        class="tableview"
        :class="{loading: isLoading, hidden: this.isEmpty}"
      >
        <div
          :class="{
            'vue-admin-tablepane': true,
            tablepane: fullPane,
            'mt-0': showToolbar && fullPane,
          }"
        >
          <vuetable
            ref="vuetable"
            :append-params="appendParams"
            :api-mode="isApiMode"
            :api-url="apiUrl"
            :css="tableCss"
            :data="tableData"
            :detail-row-component="detailRowComponent"
            :fields="fields"
            :per-page="perPage"
            :no-data-template="noDataTemplate"
            :query-params="queryParams"
            :row-class="rowClass"
            :http-fetch="fetch"
            pagination-path="pagination"
            @vuetable:loaded="init"
            @vuetable:loading="loading"
            @vuetable:pagination-data="onPaginationData"
            @vuetable:load-success="onLoadSuccess"
            @vuetable:cell-clicked="handleCellClicked"
            @vuetable:cell-dblclicked="handleCellDoubleClicked"
            @vuetable:row-clicked="handleRowClicked"
            @vuetable:row-dblclicked="handleRowDoubleClicked"
          >
            <template slot="checkbox" slot-scope="props">
              <admin-table-checkbox
                :id="props.rowData.id"
                :checks="checks"
                :status="checkboxStatus(props.rowData)"
                v-on:addCheck="addCheck"
                v-on:removeCheck="removeCheck"
              ></admin-table-checkbox>
            </template>
            <div slot="title" slot-scope="props" class="flex flex-nowrap gap-s">
              <span
                v-if="props.rowData.icon"
                :class="['cp-icon', 'small', props.rowData.iconColor]"
                v-html="props.rowData.icon"
              ></span>
              <span
                v-if="props.rowData.status !== undefined"
                class="status"
                :class="{
                  enabled: props.rowData.status,
                  disabled: !props.rowData.status,
                }"
              ></span>
              <a
                :class="{'cell-bold': props.rowData.status === undefined}"
                v-if="props.rowData.url"
                :href="props.rowData.url"
                >{{ props.rowData.title }}</a
              >
              <span
                :class="{'cell-bold': props.rowData.status === undefined}"
                v-else
                >{{ props.rowData.title }}</span
              >
            </div>

            <template slot="handle" slot-scope="props">
              <admin-table-copy-text-button
                :key="props.rowData.id"
                :value="props.rowData.handle"
              ></admin-table-copy-text-button>
            </template>
            <template slot="menu" slot-scope="props">
              <template v-if="props.rowData.menu.showItems">
                <a :href="props.rowData.menu.url"
                  >{{ props.rowData.menu.label
                  }}<template
                    v-if="
                      props.rowData.menu.showCount ||
                      typeof props.rowData.menu.showCount === 'undefined'
                    "
                  >
                    ({{ props.rowData.menu.items.length }})</template
                  ></a
                >
                <a class="menubtn" :title="props.rowData.menu.label"></a>
                <div class="menu">
                  <ul>
                    <li
                      v-for="(item, index) in props.rowData.menu.items"
                      :key="index"
                    >
                      <a :href="item.url">{{ item.label }}</a>
                    </li>
                  </ul>
                </div>
              </template>
              <template v-else>
                <a :href="props.rowData.menu.url">{{
                  props.rowData.menu.label
                }}</a>
              </template>
            </template>
            <template slot="detail" slot-scope="props">
              <div
                class="detail-cursor-pointer"
                @click="handleDetailRow(props.rowData.id)"
                v-if="
                  props.rowData.detail.content && props.rowData.detail.handle
                "
                v-html="props.rowData.detail.handle"
              ></div>
              <div
                class="detail-cursor-pointer"
                @click="handleDetailRow(props.rowData.id)"
                v-if="
                  props.rowData.detail.content &&
                  !props.rowData.detail.handle &&
                  (Object.keys(props.rowData.detail.content).length ||
                    props.rowData.detail.content.length)
                "
                data-icon="info"
                :title="props.rowData.detail.title"
              ></div>
            </template>
            <template slot="reorder" slot-scope="props">
              <i
                class="move icon vue-table-move-handle"
                :class="{disabled: !canReorder}"
                :data-id="props.rowData.id"
              ></i>
            </template>
            <template slot="delete" slot-scope="props">
              <admin-table-delete-button
                :id="props.rowData.id"
                :name="props.rowData.title"
                :before="beforeDelete"
                :success-message="deleteSuccessMessage"
                :confirmation-message="deleteConfirmationMessage"
                :fail-message="deleteFailMessage"
                :action-url="deleteAction"
                :disabled="!canDelete"
                v-on:loading="loading()"
                v-on:finishloading="loading(false)"
                v-on:reload="remove(props.rowIndex, props.rowData.id)"
                v-if="
                  typeof props.rowData._showDelete === 'undefined' ||
                  props.rowData._showDelete == true
                "
              ></admin-table-delete-button>
            </template>
          </vuetable>
        </div>
        <div class="flex flex-justify vue-admin-table-footer" v-if="showFooter">
          <admin-table-pagination
            ref="pagination"
            :itemLabels="itemLabels"
            @vuetable-pagination:change-page="onChangePage"
          ></admin-table-pagination>
          <div
            v-if="checkboxes && itemActions.length"
            :class="{hidden: !checks.length}"
          >
            <admin-table-action-button
              label=""
              class="vue-admin-table-footer-actions"
              :icon="'settings'"
              :actions="itemActions"
              :allow-multiple="true"
              menu-btn-class="secondary"
              :ids="checks"
              :enabled="checks.length ? true : false"
              v-on:reload="reload"
              v-on:click="handleActionClick"
            >
            </admin-table-action-button>
          </div>
        </div>
      </div>
    </div>

    <div class="hidden" v-if="moveToPageAction && lastPage !== 1">
      <admin-table-move-to-page-hud
        ref="move-to-page-hud"
        trigger=".vue-admin-table-footer-actions"
        :action="moveToPageAction"
        :current-page="currentPage"
        :per-page="perPage"
        :pages="lastPage"
        :move-to-page-action="moveToPageAction"
        :reorder-success-message="reorderSuccessMessage"
        :ids="checks"
        v-on:reload="reload"
        v-on:submit="loading()"
        v-on:error="loading(false)"
      ></admin-table-move-to-page-hud>
    </div>
  </div>
</template>
<script>
  /* global Craft, Vue */
  import Vuetable from 'vuetable-2/src/components/Vuetable';
  import AdminTablePagination from './components/AdminTablePagination';
  import AdminTableDeleteButton from './components/AdminTableDeleteButton';
  import AdminTableCheckbox from './components/AdminTableCheckbox';
  import AdminTableActionButton from './components/AdminTableActionButton';
  import AdminTableDetailRow from './components/AdminTableDetailRow';
  import AdminTableButton from './components/AdminTableButton';
  import AdminTableCopyTextButton from './components/AdminTableCopyTextButton';
  import AdminTableMoveToPageHud from './components/AdminTableMoveToPageHud.vue';
  import Sortable from 'sortablejs';
  import {debounce, map} from 'lodash';

  export default {
    components: {
      AdminTableMoveToPageHud,
      AdminTableCopyTextButton,
      AdminTableActionButton,
      AdminTableCheckbox,
      AdminTableDeleteButton,
      AdminTablePagination,
      AdminTableButton,
      Vuetable,
    },

    props: {
      // NOTE: all the properties here, should also be listed in the src/web/assets/admintable/src/main.js file, under defaults
      actions: {
        type: Array,
        default: () => {
          return [];
        },
      },
      allowMultipleDeletions: {
        type: Boolean,
        default: false,
      },
      allowMultipleSelections: {
        type: Boolean,
        default: true,
      },
      beforeDelete: {
        type: Function,
        default: () => {
          return Promise.resolve(true);
        },
      },
      buttons: {
        type: Array,
        default: () => {
          return [];
        },
      },
      checkboxes: {
        type: Boolean,
        default: false,
      },
      checkboxStatus: {
        type: Function,
        default: function () {
          return true;
        },
      },
      columns: {
        type: Array,
        default: () => {
          return [];
        },
      },
      container: {
        type: String,
      },
      deleteAction: {
        type: String,
        default: null,
      },
      deleteCallback: {
        type: Function,
      },
      deleteConfirmationMessage: {
        type: String,
      },
      deleteFailMessage: {
        type: String,
      },
      deleteSuccessMessage: {
        type: String,
      },
      emptyMessage: {
        type: String,
        default: Craft.t('app', 'No data available.'),
      },
      footerActions: {
        type: Array,
        default: () => {
          return [];
        },
      },
      fullPage: {
        type: Boolean,
        default: false,
      },
      fullPane: {
        type: Boolean,
        default: true,
      },
      itemLabels: {
        type: Object,
        default: () => {
          return {
            singular: Craft.t('app', 'Item'),
            plural: Craft.t('app', 'Items'),
          };
        },
      },
      minItems: {
        type: Number,
      },
      moveToPageAction: {
        type: String,
      },
      noSearchResults: {
        type: String,
        default: Craft.t('app', 'No results.'),
      },
      padded: {
        type: Boolean,
        default: false,
      },
      paginatedReorderAction: {
        type: String,
      },
      perPage: {
        type: Number,
        default: 100,
      },
      reorderAction: {
        type: String,
      },
      reorderFailMessage: {
        type: String,
        default: Craft.t('app', 'Couldn’t reorder items.'),
      },
      reorderSuccessMessage: {
        type: String,
        default: Craft.t('app', 'Items reordered.'),
      },
      search: {
        type: Boolean,
        default: false,
      },
      searchClear: {
        type: String,
        default: Craft.t('app', 'Clear'),
      },
      searchParams: {
        type: Array,
        default: () => {
          return [];
        },
      },
      searchPlaceholder: {
        type: String,
        default: Craft.t('app', 'Search'),
      },
      tableData: {
        type: Array,
        default: () => {
          return [];
        },
      },
      tableDataEndpoint: {
        type: String,
      },

      // Events
      onCellClicked: {
        default: function () {},
      },
      onCellDoubleClicked: {
        default: function () {},
      },
      onData: {
        default: function () {},
      },
      onLoaded: {
        default: function () {},
      },
      onLoading: {
        default: function () {},
      },
      onPagination: {
        default: function () {},
      },
      onQueryParams: {
        default: function () {},
      },
      onRowClicked: {
        default: function () {},
      },
      onRowDoubleClicked: {
        default: function () {},
      },
      onSelect: {
        default: function () {},
      },
    },

    data() {
      return {
        autofocusPreferred: Craft.autofocusPreferred ?? false,
        checks: [],
        currentPage: 1,
        lastPage: 1,
        detailRow: AdminTableDetailRow,
        dragging: false,
        endpointResponse: null,
        initTableData: [],
        isEmpty: false,
        isLoading: true,
        searchClearTitle: Craft.escapeHtml(Craft.t('app', 'Clear')),
        searchTerm: '',
        selectAll: null,
        sortable: null,
        tableBodySelector: '.vuetable-body',
        tableClass: 'data fullwidth',
      };
    },

    methods: {
      init() {
        let tableBody = this.$el.querySelector(this.tableBodySelector);

        if (this.canReorder) {
          this.sortable = Sortable.create(tableBody, {
            animation: 150,
            handle: '.move.icon',
            ghostClass: 'vue-admin-table-drag',
            onSort: this.handleReorder,
            onStart: this.startReorder,
            onEnd: this.endReorder,
          });
        }
        this.isEmpty = this.$refs.vuetable.tableData.length ? false : true;

        this.$nextTick(() => {
          if (this.$refs.vuetable) {
            this.selectAll = this.$refs.vuetable.$el.querySelector(
              '.selectallcontainer'
            );
            if (this.selectAll && this.allowMultipleSelections) {
              this.selectAll.addEventListener('click', this.handleSelectAll);
            }

            if (this.tableDataEndpoint) {
              new Promise(async (resolve) => {
                if (this.endpointResponse) {
                  // Check to see if `headHtml` is in the response
                  if (this.endpointResponse['headHtml']) {
                    // Append the headHtml to the page
                    await Craft.appendHeadHtml(this.endpointResponse.headHtml);
                  }

                  // Check to see if `bodyHtml` is in the response
                  if (this.endpointResponse['bodyHtml']) {
                    // Append the bodyHtml to the page
                    await Craft.appendBodyHtml(this.endpointResponse.bodyHtml);
                  }
                }
                resolve();
              }).finally(() => {
                Craft.initUiElements(this.container);
              });
            }
          }
        });

        if (
          this.tableData &&
          this.tableData.length &&
          !this.tableDataEndpoint
        ) {
          this.$emit('data', this.tableData);

          this.$nextTick(() => {
            this.initTableData = this.$refs.vuetable.tableData;
          });
        }

        this.isLoading = false;

        if (this.onLoaded instanceof Function) {
          this.onLoaded();
        }

        // call data load success for non-endpoint implementations
        if (!this.tableDataEndpoint && this.onData instanceof Function) {
          this.onData(this.tableData);
        }
      },

      fetch(url, options) {
        return Craft.sendActionRequest('GET', url, options);
      },

      loading(isLoading = true) {
        this.isLoading = isLoading;

        if (isLoading && this.onLoading instanceof Function) {
          this.onLoading();
        }
      },

      startReorder() {
        this.dragging = true;
      },

      endReorder() {
        this.dragging = false;
      },

      rowClass(data, index) {
        if (!data) {
          return '';
        }

        if (!this.checks.length) {
          return '';
        }

        if (this.checks.indexOf(data.id) >= 0) {
          return 'sel';
        }

        return '';
      },

      handleActionClick(param, value, action, ajax) {
        if (param === 'moveToPage' && value === true) {
          this.$refs['move-to-page-hud'].show();
        } else if (ajax) {
          this.loading();
        }
      },

      handleReorder(ev) {
        // Paginated reordering must be used when supplying table data from an endpoint
        const isPaginatedReorder = this.tableDataEndpoint ? true : false;
        const reorderAction = isPaginatedReorder
          ? this.paginatedReorderAction
          : this.reorderAction;

        let elements = [...ev.to.querySelectorAll('.vue-table-move-handle')];

        if (elements.length) {
          let ids = map(elements, (element) => {
            return element.dataset.id;
          });

          let data = {
            ids: JSON.stringify(ids),
            startPosition:
              (this.currentPage > 1
                ? (this.currentPage - 1) * this.perPage
                : 0) + 1,
          };

          Craft.sendActionRequest('POST', reorderAction, {data}).then(
            (response) => {
              Craft.cp.displayNotice(
                Craft.escapeHtml(this.reorderSuccessMessage)
              );
            }
          );
        } else {
          Craft.cp.displayError(Craft.escapeHtml(this.reorderFailMessage));
        }
      },

      addCheck(id) {
        if (this.checks.indexOf(id) === -1) {
          if (this.checks.length >= 1 && !this.allowMultipleSelections) {
            this.checks = [];
          }

          this.checks.push(id);
        }

        this.handleOnSelectCallback(this.checks);
      },

      removeCheck(id) {
        let key = this.checks.indexOf(id);
        if (key >= 0) {
          this.checks.splice(key, 1);
        }

        this.handleOnSelectCallback(this.checks);
      },

      handleSearch: debounce(function () {
        // in data mode - match and show/hide via JS
        if (!this.isApiMode && this.tableData.length) {
          let tableData = this.initTableData;
          let searchTerm = this.searchTerm.toLowerCase();

          if (searchTerm !== '') {
            tableData = tableData.filter((row) => {
              let includes = false;

              this.searchParams.some((param) => {
                Object.entries(row).some(([key, value]) => {
                  // Force string values
                  value = String(value);

                  if (
                    key === param &&
                    value.toLowerCase().includes(searchTerm)
                  ) {
                    return (includes = true);
                  }
                });

                // Break if we have a match
                return includes;
              });

              return includes;
            });
          }

          this.isEmpty = tableData.length == 0;
          this.$refs.vuetable.tableData = tableData;
        } else {
          // in API mode - send to the endpoint to handle
          if (this.$refs.vuetable.currentPage !== 1) {
            this.$refs.vuetable.changePage(1);
          }
          this.reload();
        }
      }, 500),

      resetSearch: function () {
        this.searchTerm = '';
        this.handleSearch();
      },

      handleSelectAll() {
        var tableData = this.$refs.vuetable.tableData;
        let tableLength = tableData.length - this.disabledCheckboxesCount;
        if (this.checks.length != tableLength) {
          tableData.forEach((row) => {
            if (
              this.checkboxStatus instanceof Function &&
              this.checkboxStatus(row)
            ) {
              this.addCheck(row.id);
            }
          });
        } else {
          this.checks = [];
        }

        this.handleOnSelectCallback(this.checks);
      },

      handleDetailRow(id) {
        this.$refs.vuetable.toggleDetailRow(id);
      },

      deselectAll() {
        this.checks = [];

        this.handleOnSelectCallback(this.checks);
      },

      reload() {
        if (this.$refs.vuetable) {
          const reloadToPage =
            this.$refs.vuetable.currentPage > 1
              ? this.$refs.vuetable.currentPage
              : 1;
          this.$refs.vuetable.gotoPage(reloadToPage);
        }

        this.isLoading = true;
        this.deselectAll();
        this.$refs.vuetable.normalizeFields();
        this.$refs.vuetable.reload();
      },

      remove(index, id) {
        this.isLoading = true;

        if (this.apiUrl) {
          this.deselectAll();
          this.$refs.vuetable.reload();
        } else {
          Vue.delete(this.$refs.vuetable.tableData, index);
          this.removeCheck(id);
          this.$refs.vuetable.refresh();
        }

        if (
          this.deleteCallback &&
          {}.toString.call(this.deleteCallback) === '[object Function]'
        ) {
          this.deleteCallback(id);
        }

        this.isLoading = false;
      },

      onLoadSuccess(data) {
        this.endpointResponse = null;
        if (data && data.data && data.data.data) {
          this.endpointResponse = data.data;
          let emitData = data.data.data;
          this.$emit('data', emitData);
          if (this.onData instanceof Function) {
            this.onData(emitData);
          }
        }
      },

      handleCellClicked(data, field, event) {
        this.$emit('onCellClicked', data, field, event);
        if (this.onCellClicked instanceof Function) {
          this.onCellClicked(data, field, event);
        }
      },

      handleCellDoubleClicked(data, field, event) {
        this.$emit('onCellDoubleClicked', data, field, event);
        if (this.onCellDoubleClicked instanceof Function) {
          this.onCellDoubleClicked(data, field, event);
        }
      },

      handleRowClicked(data, event) {
        this.$emit('onRowClicked', data, event);
        if (this.onRowClicked instanceof Function) {
          this.onRowClicked(data, event);
        }
      },

      handleRowDoubleClicked(data, event) {
        this.$emit('onRowDoubleClicked', data, event);
        if (this.onRowDoubleClicked instanceof Function) {
          this.onRowDoubleClicked(data, event);
        }
      },

      onPaginationData(paginationData) {
        this.currentPage = paginationData.current_page;
        this.lastPage = paginationData.last_page;
        this.$refs.pagination.setPaginationData(paginationData);
        this.deselectAll();
        if (this.onPagination instanceof Function) {
          this.onPagination(paginationData);
        }
      },

      onChangePage(page) {
        this.$refs.vuetable.changePage(page);
        this.deselectAll();
      },

      handleOnSelectCallback(checks) {
        this.$emit('onSelect', checks);
        if (this.onSelect instanceof Function) {
          this.onSelect(checks);
        }
      },

      queryParams(sortOrder, currentPage, perPage) {
        let params = {
          sort: sortOrder,
          page: currentPage,
          per_page: perPage,
        };

        if (this.onQueryParams instanceof Function) {
          let callbackParams = this.onQueryParams(params);
          // if `callbackParams` is not undefined, use them instead of `params`
          params = callbackParams || params;
        }

        return params;
      },
    },

    computed: {
      tableId() {
        // Replace either `#` or `.` from the container selector
        if (this.container) {
          return this.container.replace(/[#.]/g, '');
        }

        return '';
      },

      isApiMode() {
        return this.apiUrl ? true : false;
      },

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
          search: this.searchTerm,
        };
      },

      canDelete() {
        return !(
          this.minItems && this.$refs.vuetable.tableData.length <= this.minItems
        );
      },

      itemActions() {
        let itemActions = [];

        if (this.paginatedReorderAction && this.moveToPageAction) {
          itemActions.push({
            label: Craft.t('app', 'Move to'),
            action: this.moveToPageAction,
            allowMultiple: false,
            ajax: true,
            handleClick: false,
            param: 'moveToPage',
            value: true,
            class: {'footer-actions': true},
          });
        }

        itemActions = [...itemActions, ...this.footerActions];

        if (this.deleteAction) {
          itemActions.push({
            label: Craft.t('app', 'Delete'),
            action: this.deleteAction,
            error: true,
            ajax: this.tableDataEndpoint ? true : false,
            allowMultiple: this.allowMultipleDeletions,
            separator: itemActions.length ? true : false,
          });
        }

        return itemActions;
      },

      canReorder() {
        if (
          typeof this.$refs.vuetable === 'undefined' ||
          typeof this.$refs.vuetable.tableData === 'undefined'
        ) {
          return false;
        }

        return (
          this.$refs.vuetable.tableData.length > 1 &&
          this.$el.querySelector(this.tableBodySelector) &&
          ((this.reorderAction && !this.$refs.vuetable.tablePagination) ||
            (this.paginatedReorderAction &&
              this.$refs.vuetable.tablePagination))
        );
      },

      detailRowComponent() {
        if (this.tableDataEndpoint) {
          return this.detailRow;
        }

        if (!this.tableData || this.tableData.length == 0) {
          return '';
        }

        if (
          !this.tableData.some((r) => {
            return Object.keys(r).indexOf('detail') >= 0;
          })
        ) {
          return '';
        }

        return this.detailRow;
      },

      disabledCheckboxesCount() {
        let checkboxCount = 0;

        if (this.$refs.vuetable.tableData.length) {
          let disabledRows = this.$refs.vuetable.tableData.filter(
            (row) => !this.checkboxStatus(row)
          );

          checkboxCount = disabledRows.length;
        }

        return checkboxCount;
      },

      fields() {
        let columns = [];

        // Enable/Disable checkboxes
        if (this.checkboxes) {
          var title = '';
          if (this.allowMultipleSelections) {
            title =
              '<div class="checkbox-cell selectallcontainer" role="checkbox" tabindex="0" aria-checked="false"><div class="checkbox"></div></div>';
          }

          columns.push({
            name: '__slot:checkbox',
            titleClass: 'thin',
            title: title,
            dataClass: 'checkbox-cell',
          });
        }

        let customColumns = map(this.columns, (item) => {
          // Do not allow sorting for if you can manually reorder items
          if (
            (this.reorderAction || this.paginatedReorderAction) &&
            item.hasOwnProperty('sortField')
          ) {
            delete item.sortField;
          }

          // Escape Title
          item.title = Craft.escapeHtml(item.title);

          return item;
        });

        columns = [...columns, ...customColumns];

        if (this.reorderAction || this.paginatedReorderAction) {
          columns.push({
            name: '__slot:reorder',
            title: '',
            titleClass: 'thin',
          });
        }

        if (this.deleteAction) {
          columns.push({
            name: '__slot:delete',
            titleClass: 'thin',
          });
        }

        return columns;
      },

      searchClearTitle() {
        return Craft.escapeHtml(this.searchClear);
      },

      searchPlaceholderText() {
        return Craft.escapeHtml(this.searchPlaceholder);
      },

      showToolbar() {
        return this.actions.length || this.search;
      },

      showFooter() {
        return (
          (this.checkboxes && this.itemActions.length) || this.tableDataEndpoint
        );
      },

      tableCss() {
        var tableClass = this.tableClass;
        if (this.dragging) {
          tableClass = tableClass + ' vue-admin-table-dragging';
        }

        return {
          ascendingClass: 'ordered asc',
          descendingClass: 'ordered desc',
          sortableIcon: 'orderable',
          handleIcon: 'move icon',
          loadingClass: 'loading',
          tableClass: tableClass,
        };
      },

      noDataTemplate() {
        return this.isLoading
          ? '<div class="spinner"></div>'
          : '<div class="zilch">' + this.emptyMessage + '</div>';
      },
    },

    watch: {
      checks() {
        if (this.selectAll) {
          let checkbox = this.selectAll.querySelector('.checkbox');

          if (
            this.checks.length &&
            this.checks.length == this.$refs.vuetable.tableData.length
          ) {
            checkbox.classList.add('checked');
            checkbox.classList.remove('indeterminate');
          } else if (
            this.checks.length &&
            this.checks.length != this.$refs.vuetable.tableData.length
          ) {
            checkbox.classList.remove('checked');
            checkbox.classList.add('indeterminate');
          } else {
            checkbox.classList.remove('checked');
            checkbox.classList.remove('indeterminate');
          }
        }
      },

      dragging(newVal) {
        // Update pointer events while dragging to allow sortable JS to drag to top
        let $contentHeader = document.querySelector('header#header');
        if (newVal) {
          $contentHeader.style.pointerEvents = 'none';
        } else {
          $contentHeader.style.pointerEvents = '';
        }
      },
    },
  };
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
    opacity: 0.3;
  }

  .tableview .cell-bold {
    font-weight: bold;
  }

  .vue-admin-table .toolbar {
    margin-bottom: var(--padding);
  }

  .vue-admin-table.vue-admin-table-padded .toolbar {
    margin-bottom: 14px;
  }

  .vue-admin-table-padded .tablepane {
    margin: 0;
  }

  .vue-admin-table-drag {
    opacity: 0;
  }

  table thead th.sortable:hover {
    background-color: #f9f9f9;
  }

  table.data.vue-admin-table-dragging tbody tr:not(.disabled):hover td {
    background-color: transparent;
  }

  .vue-admin-table-buttons {
    margin-left: auto;
  }

  .vue-admin-table-buttons .flex:not(.flex-nowrap) > * {
    margin-bottom: 0;
  }

  .vue-admin-table-footer {
    background-color: #fff;
    border-top: 1px solid #f3f7fc;
    bottom: 0;
    margin-bottom: calc(var(--xl) * -1);
    margin-top: 14px;
    position: sticky;
    min-height: 44px;
    z-index: 1;

    .so-body & {
      margin: 0;
      padding: 0;
      position: static;
    }
  }

  .vue-admin-tablepane + .vue-admin-table-footer {
    --pane-padding-default: calc(var(--padding) - 2px);
    margin-left: calc(var(--pane-padding, var(--pane-padding-default)) * -1);
    margin-right: calc(var(--pane-padding, var(--pane-padding-default)) * -1);
    padding-left: calc(var(--pane-padding, var(--pane-padding-default)));
    padding-right: calc(var(--pane-padding, var(--pane-padding-default)));
  }

  .detail-cursor-pointer {
    cursor: pointer;
  }
</style>
