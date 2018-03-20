(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.TableFieldSettings = Garnish.Base.extend(
        {
            columnsTableName: null,
            defaultsTableName: null,
            columnsTableId: null,
            defaultsTableId: null,
            columnsTableInputPath: null,
            defaultsTableInputPath: null,

            defaults: null,
            columnSettings: null,

            columnsTable: null,
            defaultsTable: null,

            init: function(columnsTableName, defaultsTableName, columns, defaults, columnSettings) {
                this.columnsTableName = columnsTableName;
                this.defaultsTableName = defaultsTableName;

                this.columnsTableId = Craft.formatInputId(this.columnsTableName);
                this.defaultsTableId = Craft.formatInputId(this.defaultsTableName);

                this.columnsTableInputPath = Craft.filterArray(this.columnsTableName.split(/[\[\]]+/));
                this.defaultsTableInputPath = Craft.filterArray(this.defaultsTableName.split(/[\[\]]+/));

                this.defaults = defaults;
                this.columnSettings = columnSettings;

                this.initColumnsTable();
                this.initDefaultsTable(columns);
            },

            initColumnsTable: function() {
                this.columnsTable = new Craft.EditableTable(this.columnsTableId, this.columnsTableName, this.columnSettings, {
                    rowIdPrefix: 'col',
                    defaultValues: {
                        type: 'singleline'
                    },
                    onAddRow: $.proxy(this, 'onAddColumn'),
                    onDeleteRow: $.proxy(this, 'reconstructDefaultsTable')
                });

                this.initColumnSettingInputs(this.columnsTable.$tbody);
                this.columnsTable.sorter.settings.onSortChange = $.proxy(this, 'reconstructDefaultsTable');
            },

            initDefaultsTable: function(columns) {
                this.defaultsTable = new Craft.EditableTable(this.defaultsTableId, this.defaultsTableName, columns, {
                    rowIdPrefix: 'row'
                });
            },

            onAddColumn: function($tr) {
                this.reconstructDefaultsTable();
                this.initColumnSettingInputs($tr);
            },

            initColumnSettingInputs: function($container) {
                var $textareas = $container.find('td:first-child textarea, td:nth-child(3) textarea'),
                    $typeSelect = $container.find('td:nth-child(4) select');

                this.addListener($textareas, 'textchange', 'reconstructDefaultsTable');
                this.addListener($typeSelect, 'change', 'reconstructDefaultsTable');
            },

            reconstructDefaultsTable: function() {
                var columnsTableData = Craft.expandPostArray(Garnish.getPostData(this.columnsTable.$tbody)),
                    defaultsTableData = Craft.expandPostArray(Garnish.getPostData(this.defaultsTable.$tbody)),
                    columns = columnsTableData,
                    defaults = defaultsTableData;

                var i, key;

                for (i = 0; i < this.columnsTableInputPath.length; i++) {
                    key = this.columnsTableInputPath[i];
                    columns = columns[key];
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

                for (var colId in columns) {
                    if (!columns.hasOwnProperty(colId)) {
                        continue;
                    }

                    theadHtml += '<th scope="col">' + (columns[colId].heading ? columns[colId].heading : '&nbsp;') + '</th>';
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

                    Craft.EditableTable.createRow(rowId, columns, this.defaultsTableName, defaults[rowId]).appendTo($tbody);
                }

                this.defaultsTable.$table.replaceWith($table);
                this.defaultsTable.destroy();
                delete this.defaultsTable;
                this.initDefaultsTable(columns);
            }

        });
})(jQuery);
