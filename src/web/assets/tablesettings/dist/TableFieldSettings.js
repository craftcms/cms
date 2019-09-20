(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.TableFieldSettings = Garnish.Base.extend(
        {
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

            init: function(columnsTableName, defaultsTableName, columnsData, defaults, columnSettings, dropdownSettingsHtml, dropdownSettingsCols) {
                this.columnsTableName = columnsTableName;
                this.defaultsTableName = defaultsTableName;
                this.columnsData = columnsData;

                this.columnsTableId = Craft.formatInputId(this.columnsTableName);
                this.defaultsTableId = Craft.formatInputId(this.defaultsTableName);

                this.columnsTableInputPath = Craft.filterArray(this.columnsTableName.split(/[\[\]]+/));
                this.defaultsTableInputPath = Craft.filterArray(this.defaultsTableName.split(/[\[\]]+/));

                this.defaults = defaults;
                this.columnSettings = columnSettings;

                this.dropdownSettingsHtml = dropdownSettingsHtml;
                this.dropdownSettingsCols = dropdownSettingsCols;

                this.initColumnsTable();
                this.initDefaultsTable();
            },

            initColumnsTable: function() {
                this.columnsTable = new ColumnTable(this, this.columnsTableId, this.columnsTableName, this.columnSettings, {
                    rowIdPrefix: 'col',
                    defaultValues: {
                        type: 'singleline'
                    },
                    onAddRow: $.proxy(this, 'onAddColumn'),
                    onDeleteRow: $.proxy(this, 'reconstructDefaultsTable')
                });
            },

            initDefaultsTable: function() {
                this.defaultsTable = new Craft.EditableTable(this.defaultsTableId, this.defaultsTableName, this.columnsData, {
                    rowIdPrefix: 'row'
                });
            },

            onAddColumn: function($tr) {
                this.reconstructDefaultsTable();
                this.initColumnSettingInputs($tr);
            },

            initColumnSettingInputs: function($container) {
                var $textareas = $container.find('td:first-child textarea, td:nth-child(3) textarea');
                this.addListener($textareas, 'textchange', 'reconstructDefaultsTable');
            },

            reconstructDefaultsTable: function() {
                this.columnsData = Craft.expandPostArray(Garnish.getPostData(this.columnsTable.$tbody));
                var defaults = Craft.expandPostArray(Garnish.getPostData(this.defaultsTable.$tbody));

                var i, key;

                for (i = 0; i < this.columnsTableInputPath.length; i++) {
                    key = this.columnsTableInputPath[i];
                    this.columnsData = this.columnsData[key];
                }

                // Add in the dropdown options
                for (var colId in this.columnsData) {
                    if (this.columnsData.hasOwnProperty(colId) && this.columnsData[colId].type === 'select') {
                        var rowObj = this.columnsTable.$tbody.find('tr[data-id="' + colId + '"]').data('editable-table-row');
                        this.columnsData[colId].options = rowObj.options || [];
                    }
                }

                for (i = 0; i < this.defaultsTableInputPath.length; i++) {
                    key = this.defaultsTableInputPath[i];

                    if (typeof defaults[key] === 'undefined') {
                        defaults = {};
                        break;
                    }
                    else {
                        defaults = defaults[key];
                    }
                }

                var theadHtml = '<thead>' +
                    '<tr>';

                for (var colId in this.columnsData) {
                    if (!this.columnsData.hasOwnProperty(colId)) {
                        continue;
                    }

                    theadHtml += '<th scope="col">' + (this.columnsData[colId].heading ? this.columnsData[colId].heading : '&nbsp;') + '</th>';
                }

                theadHtml += '<th colspan="2"></th>' +
                    '</tr>' +
                    '</thead>';

                var $table = $('<table/>', {
                    id: this.defaultsTableId,
                    'class': 'editable shadow-box'
                }).append(theadHtml);

                var $tbody = $('<tbody/>').appendTo($table);

                for (var rowId in defaults) {
                    if (!defaults.hasOwnProperty(rowId)) {
                        continue;
                    }

                    Craft.EditableTable.createRow(rowId, this.columnsData, this.defaultsTableName, defaults[rowId]).appendTo($tbody);
                }

                this.defaultsTable.$table.replaceWith($table);
                this.defaultsTable.destroy();
                delete this.defaultsTable;
                this.initDefaultsTable();
            }
        });

    var ColumnTable = Craft.EditableTable.extend({
        fieldSettings: null,

        init: function(fieldSettings, id, baseName, columns, settings) {
            this.fieldSettings = fieldSettings;
            this.base(id, baseName, columns, settings);
        },

        initialize: function() {
            if (!this.base()) {
                return false;
            }

            this.fieldSettings.initColumnSettingInputs(this.$tbody);
            this.sorter.settings.onSortChange = $.proxy(this.fieldSettings.reconstructDefaultsTable, this.fieldSettings);
            return true;
        },

        createRowObj: function($tr) {
            return new ColumnTable.Row(this, $tr);
        }
    });

    ColumnTable.Row = Craft.EditableTable.Row.extend({
        $typeSelect: null,
        $settingsBtn: null,

        options: null,
        settingsModal: null,
        optionsTable: null,

        init: function(table, tr) {
            this.base(table, tr);

            if (this.table.fieldSettings.columnsData[this.id]) {
                this.options = this.table.fieldSettings.columnsData[this.id].options || null;
            }

            var $typeCell = this.$tr.find('td:nth-child(4)');
            var $typeSelectContainer = $typeCell.find('.select');
            this.$settingsBtn = $typeCell.find('.settings');

            if (!this.$settingsBtn.length) {
                this.$settingsBtn = $('<a/>', {
                    'class': 'settings light invisible',
                    role: 'button',
                    'data-icon': 'settings'
                });
                $('<div/>', {'class': 'flex flex-nowrap'})
                    .appendTo($typeCell)
                    .append($typeSelectContainer)
                    .append(this.$settingsBtn);
            }

            this.$typeSelect = $typeSelectContainer.find('select');
            this.addListener(this.$typeSelect, 'change', 'handleTypeChange');
            this.addListener(this.$settingsBtn, 'click', 'showSettingsModal');

            this.addListener(this.$tr.closest('form'), 'submit', 'handleFormSubmit');
        },

        handleTypeChange: function() {
            if (this.$typeSelect.val() === 'select') {
                this.$settingsBtn.removeClass('invisible');
            } else {
                this.$settingsBtn.addClass('invisible');
            }

            this.table.fieldSettings.reconstructDefaultsTable();
        },

        showSettingsModal: function(ev) {
            if (!this.settingsModal) {
                var id = 'dropdownsettingsmodal' + Math.floor(Math.random() * 1000000);
                var $modal = $('<div/>', {'class': 'modal dropdownsettingsmodal'}).appendTo(Garnish.$bod);
                var $body = $('<div/>', {'class': 'body'})
                    .appendTo($modal)
                    .html(this.table.fieldSettings.dropdownSettingsHtml.replace(/__ID__/g, id));

                this.optionsTable = new Craft.EditableTable(id, '__NAME__', this.table.fieldSettings.dropdownSettingsCols, {
                    onAddRow: $.proxy(this, 'handleOptionsRowChange'),
                    onDeleteRow: $.proxy(this, 'handleOptionsRowChange')
                });

                if (this.options && this.options.length) {
                    var row;
                    for (var i = 0; i < this.options.length; i++) {
                        row = this.optionsTable.addRow(false);
                        row.$tr.find('.option-label textarea').val(this.options[i].label);
                        row.$tr.find('.option-value textarea').val(this.options[i].value);
                        row.$tr.find('.option-default input[type="checkbox"]').prop('checked', !!this.options[i].default);
                    }
                } else {
                    this.optionsTable.addRow(false);
                }

                var $closeButton = $('<div/>', {
                    'class': 'btn submit',
                    role: 'button',
                    text: Craft.t('app', 'Done')
                }).appendTo($body);

                this.settingsModal = new Garnish.Modal($modal, {
                    onHide: $.proxy(this, 'handleSettingsModalHide')
                });

                this.addListener($closeButton, 'click', function() {
                    this.settingsModal.hide();
                });
            } else {
                this.settingsModal.show();
            }

            setTimeout($.proxy(function() {
                this.optionsTable.$tbody.find('textarea').first().trigger('focus')
            }, this), 100);
        },

        handleOptionsRowChange: function() {
            if (this.settingsModal) {
                this.settingsModal.updateSizeAndPosition();
            }
        },

        handleSettingsModalHide: function() {
            this.options = [];
            var $row;
            var $rows = this.optionsTable.$table.find('tbody tr');
            for (var i = 0; i < $rows.length; i++) {
                var $row  = $rows.eq(i);
                this.options.push({
                    label: $row.find('.option-label textarea').val(),
                    value: $row.find('.option-value textarea').val(),
                    default: $row.find('.option-default input[type=checkbox]').prop('checked')
                })
            }

            this.table.fieldSettings.reconstructDefaultsTable();
        },

        handleFormSubmit: function(ev) {
            if (this.$typeSelect.val() === 'select') {
                $('<input/>', {
                    type: 'hidden',
                    name: this.table.fieldSettings.columnsTableName + '[' + this.id + '][options]',
                    value: JSON.stringify(this.options)
                }).appendTo(ev.currentTarget);
            }
        }
    });
})(jQuery);
