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

      const props = this.settings;

      this.instance = new Vue({
        components: {
          AdminTable,
        },
        data() {
          return {};
        },
        render: (h) => {
          return h(AdminTable, {
            ref: 'admin-table',
            props: props,
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
      checkboxes: false,
      checkboxStatus: function () {
        return true;
      },
      columns: [],
      container: null,
      deleteAction: null,
      reorderAction: null,
      reorderSuccessMessage: Craft.t('app', 'Items reordered.'),
      reorderFailMessage: Craft.t('app', 'Couldn’t reorder items.'),
      search: false,
      searchPlaceholder: Craft.t('app', 'Search'),
      buttons: [],
      tableData: [],
      tableDataEndpoint: null,
      onLoaded: $.noop,
      onLoading: $.noop,
      onData: $.noop,
      onPagination: $.noop,
      onSelect: $.noop,
      onQueryParams: $.noop,
    },
  }
);
