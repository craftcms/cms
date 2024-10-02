(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.TableFieldSettings = Garnish.Base.extend({
    columnsTableName: null,
    defaultsTableName: null,
    columnsData: null,
    columnsTableId: null,
    defaultsTableId: null,
    columnsTableInputPath: null,
    defaultsTableInputPath: null,

    defaults: null,
    columnSettings: null,

    dropdownSettingsHtml: null,
    dropdownSettingsCols: null,

    columnsTable: null,
    defaultsTable: null,

    init: function (
      columnsTableName,
      defaultsTableName,
      columnsData,
      defaults,
      columnSettings,
      dropdownSettingsHtml,
      dropdownSettingsCols
    ) {
      this.columnsTableName = columnsTableName;
      this.defaultsTableName = defaultsTableName;
      this.columnsData = columnsData;

      this.columnsTableId = Craft.formatInputId(this.columnsTableName);
      this.defaultsTableId = Craft.formatInputId(this.defaultsTableName);

      this.columnsTableInputPath = Craft.filterArray(
        this.columnsTableName.split(/[\[\]]+/)
      );
      this.defaultsTableInputPath = Craft.filterArray(
        this.defaultsTableName.split(/[\[\]]+/)
      );

      this.defaults = defaults;
      this.columnSettings = columnSettings;

      this.dropdownSettingsHtml = dropdownSettingsHtml;
      this.dropdownSettingsCols = dropdownSettingsCols;

      this.initColumnsTable();
      this.initDefaultsTable();
    },

    initColumnsTable: function () {
      this.columnsTable = new ColumnTable(
        this,
        this.columnsTableId,
        this.columnsTableName,
        this.columnSettings,
        {
          rowIdPrefix: 'col',
          defaultValues: {
            type: 'singleline',
          },
          allowAdd: true,
          allowReorder: true,
          allowDelete: true,
          lazyInitRows: false,
          onAddRow: this.onAddColumn.bind(this),
          onDeleteRow: this.reconstructDefaultsTable.bind(this),
        }
      );
    },

    initDefaultsTable: function () {
      this.defaultsTable = new Craft.EditableTable(
        this.defaultsTableId,
        this.defaultsTableName,
        this.columnsData,
        {
          rowIdPrefix: 'row',
          allowAdd: true,
          allowReorder: true,
          allowDelete: true,
        }
      );
    },

    onAddColumn: function ($tr) {
      this.reconstructDefaultsTable();
      this.initColumnSettingInputs($tr);
    },

    initColumnSettingInputs: function ($container) {
      const $textareas = $container.find(
        'td:first-child textarea, td:nth-child(3) textarea'
      );
      this.addListener($textareas, 'input', 'reconstructDefaultsTable');
    },

    reconstructDefaultsTable: function () {
      this.columnsData = Craft.expandPostArray(
        Garnish.getPostData(this.columnsTable.$tbody)
      );
      let defaults = Craft.expandPostArray(
        Garnish.getPostData(this.defaultsTable.$tbody)
      );

      // If there are no columns, drop the defaults table rows and disable add row button
      if (!Object.keys(this.columnsData).length) {
        const $rows = this.defaultsTable.$tbody.children();
        for (let r = 0; r < $rows.length; r++) {
          this.defaultsTable.deleteRow(
            this.defaultsTable.createRowObj($rows[r])
          );
        }
        this.defaultsTable.$addRowBtn.css('opacity', '0.2');
        this.defaultsTable.$addRowBtn.css('pointer-events', 'none');
        return;
      }

      for (let i = 0; i < this.columnsTableInputPath.length; i++) {
        const key = this.columnsTableInputPath[i];
        if (typeof this.columnsData[key] !== 'undefined') {
          this.columnsData = this.columnsData[key];
        }
      }

      // Add in the dropdown options
      for (let colId in this.columnsData) {
        if (this.columnsData.hasOwnProperty(colId)) {
          switch (this.columnsData[colId].type) {
            case 'select':
              const rowObj = this.columnsTable.getRowObj(
                this.columnsTable.$tbody.find(`tr[data-id="${colId}"]`)
              );
              this.columnsData[colId].options = rowObj.options || [];
              break;
            case 'heading':
              // Replace with singleline
              this.columnsData[colId].type = 'singleline';
              this.columnsData[colId].class = 'heading';
              break;
          }
        }
      }

      for (let i = 0; i < this.defaultsTableInputPath.length; i++) {
        const key = this.defaultsTableInputPath[i];

        if (typeof defaults[key] === 'undefined') {
          defaults = {};
          break;
        } else {
          defaults = defaults[key];
        }
      }

      const $table = $('<table/>', {
        id: this.defaultsTableId,
        class: 'editable fullwidth',
      });

      if (Object.values(this.columnsData).some((c) => c.heading !== '')) {
        let theadHtml = '';

        for (let colId in this.columnsData) {
          if (!this.columnsData.hasOwnProperty(colId)) {
            continue;
          }

          theadHtml +=
            '<th scope="col">' +
            (this.columnsData[colId].heading
              ? Craft.escapeHtml(this.columnsData[colId].heading)
              : '&nbsp;') +
            '</th>';
        }

        if (theadHtml !== '') {
          theadHtml += '<th colspan="2"></th>';
          $table.append(`<thead><tr>${theadHtml}</tr></thead>`);
        }
      }

      const $tbody = $('<tbody/>').appendTo($table);

      for (let rowId in defaults) {
        if (!defaults.hasOwnProperty(rowId)) {
          continue;
        }

        Craft.EditableTable.createRow(
          rowId,
          this.columnsData,
          this.defaultsTableName,
          defaults[rowId],
          true,
          true
        ).appendTo($tbody);
      }

      this.defaultsTable.$table.replaceWith($table);
      this.defaultsTable.destroy();
      delete this.defaultsTable;
      this.initDefaultsTable();
    },
  });

  const ColumnTable = Craft.EditableTable.extend({
    fieldSettings: null,

    init: function (fieldSettings, id, baseName, columns, settings) {
      this.fieldSettings = fieldSettings;
      this.base(id, baseName, columns, settings);
    },

    initialize: function () {
      if (!this.base()) {
        return false;
      }

      this.fieldSettings.initColumnSettingInputs(this.$tbody);
      this.sorter.settings.onSortChange =
        this.fieldSettings.reconstructDefaultsTable.bind(this.fieldSettings);
      return true;
    },

    createRowObj: function ($tr) {
      return new ColumnTable.Row(this, $tr);
    },
  });

  ColumnTable.Row = Craft.EditableTable.Row.extend({
    $typeSelect: null,
    $settingsBtn: null,

    options: null,
    settingsModal: null,
    optionsTable: null,

    init: function (table, tr) {
      this.base(table, tr);

      if (this.table.fieldSettings.columnsData[this.id]) {
        this.options =
          this.table.fieldSettings.columnsData[this.id].options || null;
      }

      const $typeCell = this.$tr.find('td:nth-child(4)');
      const $typeSelectContainer = $typeCell.find('.select');
      this.$settingsBtn = $typeCell.find('.settings');

      if (!this.$settingsBtn.length) {
        this.$settingsBtn = $('<button/>', {
          class: 'settings light invisible',
          type: 'button',
          'data-icon': 'settings',
        });
        $('<div/>', {class: 'flex flex-nowrap'})
          .appendTo($typeCell)
          .append($typeSelectContainer)
          .append(this.$settingsBtn);
      }

      this.$typeSelect = $typeSelectContainer.find('select');
      this.addListener(this.$typeSelect, 'change', 'handleTypeChange');
      this.addListener(this.$settingsBtn, 'click', 'showSettingsModal');

      this.addListener(this.$tr.closest('form'), 'submit', 'handleFormSubmit');
    },

    handleTypeChange: function () {
      if (this.$typeSelect.val() === 'select') {
        this.$settingsBtn.removeClass('invisible');
      } else {
        this.$settingsBtn.addClass('invisible');
      }

      this.table.fieldSettings.reconstructDefaultsTable();
    },

    showSettingsModal: function (ev) {
      if (!this.settingsModal) {
        const id =
          'dropdownsettingsmodal' + Math.floor(Math.random() * 1000000);
        const $modal = $('<div/>', {
          class: 'modal dropdownsettingsmodal',
        }).appendTo(Garnish.$bod);
        const $body = $('<div/>', {class: 'body'})
          .appendTo($modal)
          .html(
            this.table.fieldSettings.dropdownSettingsHtml.replace(/__ID__/g, id)
          );

        this.optionsTable = new Craft.EditableTable(
          id,
          '__NAME__',
          this.table.fieldSettings.dropdownSettingsCols,
          {
            allowAdd: true,
            allowDelete: true,
            allowReorder: true,
            onAddRow: this.handleOptionsRowChange.bind(this),
            onDeleteRow: this.handleOptionsRowChange.bind(this),
          }
        );

        if (this.options && this.options.length) {
          for (let i = 0; i < this.options.length; i++) {
            const row = this.optionsTable.addRow(false);
            row.$tr.find('.option-label textarea').val(this.options[i].label);
            row.$tr.find('.option-value textarea').val(this.options[i].value);
            row.$tr
              .find('.option-default input[type="checkbox"]')
              .prop('checked', !!this.options[i].default);
          }
        } else {
          this.optionsTable.addRow(false);
        }

        const $closeButton = $('<button/>', {
          type: 'button',
          class: 'btn submit',
          text: Craft.t('app', 'Done'),
        }).appendTo($body);

        this.settingsModal = new Garnish.Modal($modal, {
          onHide: this.handleSettingsModalHide.bind(this),
        });

        this.addListener($closeButton, 'click', function () {
          this.settingsModal.hide();
        });
      } else {
        this.settingsModal.show();
      }

      setTimeout(() => {
        this.optionsTable.$tbody.find('textarea').first().focus();
      }, 100);
    },

    handleOptionsRowChange: function () {
      if (this.settingsModal) {
        this.settingsModal.updateSizeAndPosition();
      }
    },

    handleSettingsModalHide: function () {
      this.options = [];
      const $rows = this.optionsTable.$table.find('tbody tr');
      for (let i = 0; i < $rows.length; i++) {
        let $row = $rows.eq(i);
        this.options.push({
          label: $row.find('.option-label textarea').val(),
          value: $row.find('.option-value textarea').val(),
          default: $row
            .find('.option-default input[type=checkbox]')
            .prop('checked'),
        });
      }

      this.table.fieldSettings.reconstructDefaultsTable();
    },

    handleFormSubmit: function (ev) {
      if (this.$typeSelect.val() === 'select') {
        $('<input/>', {
          type: 'hidden',
          name:
            this.table.fieldSettings.columnsTableName +
            '[' +
            this.id +
            '][options]',
          value: JSON.stringify(this.options),
        }).appendTo(ev.currentTarget);
      }
    },
  });
})(jQuery);
