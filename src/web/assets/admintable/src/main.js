/* jshint esversion: 6, strict: false */
/* global Craft */
/* global Garnish */
/* global $ */

import Vue from 'vue';
import AdminTable from '@craftcms/vue/admintable/App';

Craft.VueAdminTable = Garnish.Base.extend(
  {
    instance: null,
    $table: null,

    init: function (settings) {
      this.setSettings(settings, Craft.VueAdminTable.defaults);

      const _this = this;

      this.instance = new Vue({
        components: {
          AdminTable,
        },
        data() {
          return {
            props: _this.settings,
          };
        },
        render(h) {
          return h(AdminTable, {
            ref: 'admin-table',
            props: this.props,
          });
        },
      });

      this.instance.$mount(this.settings.container);
      this.$table = this.instance.$refs['admin-table'];

      return this.instance;
    },
    reload() {
      this.$table.reload();
    },
  },
  {
    defaults: {
      actions: [],
      allowMultipleDeletions: false,
      allowMultipleSelections: true,
      beforeDelete: function () {
        return Promise.resolve(true);
      },
      buttons: [],
      checkboxes: false,
      checkboxStatus: function () {
        return true;
      },
      columns: [],
      container: null,
      deleteAction: null,
      deleteCallback: $.noop,
      deleteConfirmationMessage: null,
      deleteFailMessage: null,
      deleteSuccessMessage: null,
      emptyMessage: Craft.t('app', 'No data available.'),
      footerActions: [],
      fullPage: false,
      fullPane: true,
      itemLabels: {
        singular: Craft.t('app', 'item'),
        plural: Craft.t('app', 'items'),
      },
      minItems: null,
      moveToPageAction: null,
      noSearchResults: Craft.t('app', 'No results.'),
      padded: false,
      paginatedReorderAction: null,
      perPage: 100,
      reorderAction: null,
      reorderFailMessage: Craft.t('app', 'Couldn’t reorder items.'),
      reorderSuccessMessage: Craft.t('app', 'Items reordered.'),
      search: false,
      searchClear: Craft.t('app', 'Clear'),
      searchParams: [],
      searchPlaceholder: Craft.t('app', 'Search'),
      tableData: [],
      tableDataEndpoint: null,

      // Events
      onCellClicked: $.noop,
      onCellDoubleClicked: $.noop,
      onData: $.noop,
      onLoaded: $.noop,
      onLoading: $.noop,
      onPagination: $.noop,
      onQueryParams: $.noop,
      onRowClicked: $.noop,
      onRowDoubleClicked: $.noop,
      onSelect: $.noop,
    },
  }
);
