(function($) {


var columnsTableId = 'types-Table-columns',
	columnsTableName = 'types[Table][columns]',
	defaultsTableId = 'types-Table-defaults',
	defaultsTableName = 'types[Table][defaults]';


Craft.TableFieldSettings = Garnish.Base.extend({

	defaults: null,
	columnSettings: null,

	columnsTable: null,
	defaultsTable: null,

	init: function(columns, defaults, columnSettings)
	{
		this.defaults = defaults;
		this.columnSettings = columnSettings;

		this.initColumnsTable();
		this.initDefaultsTable(columns);
	},

	initColumnsTable: function()
	{
		this.columnsTable = new Craft.EditableTable(columnsTableId, columnsTableName, this.columnSettings, {
			rowIdPrefix: 'col',
			onAddRow: $.proxy(this, 'onAddColumn'),
			onDeleteRow: $.proxy(this, 'reconstructDefaultsTable')
		});

		this.initColumnSettingInputs(this.columnsTable.$tbody);
		this.columnsTable.sorter.settings.onSortChange = $.proxy(this, 'reconstructDefaultsTable');
	},

	initDefaultsTable: function(columns)
	{
		this.defaultsTable = new Craft.EditableTable(defaultsTableId, defaultsTableName, columns, {
			rowIdPrefix: 'row'
		});
	},

	onAddColumn: function($tr)
	{
		this.reconstructDefaultsTable();
		this.initColumnSettingInputs($tr);
	},

	initColumnSettingInputs: function($container)
	{
		var $textareas = $container.find('td:first-child textarea, td:nth-child(3) textarea'),
			$typeSelect = $container.find('td:nth-child(4) select')

		this.addListener($textareas, 'textchange', 'reconstructDefaultsTable');
		this.addListener($typeSelect, 'change', 'reconstructDefaultsTable');
	},

	reconstructDefaultsTable: function()
	{
		var columnsTableData = Craft.expandPostArray(Garnish.getPostData(this.columnsTable.$tbody)),
			defaultsTableData = Craft.expandPostArray(Garnish.getPostData(this.defaultsTable.$tbody)),
			columns = columnsTableData.types.Table.columns,
			defaults = defaultsTableData.types.Table.defaults;

		var tableHtml = '<table id="'+defaultsTableId+'" class="editable">' +
			'<thead>' +
				'<tr>';

		for (var colId in columns)
		{
			tableHtml += '<th scope="col" class="header">'+(columns[colId].heading ? columns[colId].heading : '&nbsp;')+'</th>';
		}

		tableHtml += '<th class="header" colspan="2"></th>' +
				'</tr>' +
			'</thead>' +
			'<tbody>';

		for (var rowId in defaults)
		{
			tableHtml += Craft.EditableTable.getRowHtml(rowId, columns, defaultsTableName, defaults[rowId]);
		}

		tableHtml += '</tbody>' +
			'</table>';

		this.defaultsTable.$table.replaceWith(tableHtml);
		this.defaultsTable.destroy();
		delete this.defaultsTable;
		this.initDefaultsTable(columns);
	}

});


})(jQuery);
