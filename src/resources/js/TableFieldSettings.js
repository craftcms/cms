(function($) {


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

	init: function(columnsTableName, defaultsTableName, columns, defaults, columnSettings)
	{
		this.columnsTableName = columnsTableName;
		this.defaultsTableName = defaultsTableName;

		this.columnsTableId = Craft.formatInputId(this.columnsTableName);
		this.defaultsTableId = Craft.formatInputId(this.defaultsTableName);

		this.columnsTableInputPath = this.columnsTableId.split('-');
		this.defaultsTableInputPath = this.defaultsTableId.split('-');

		this.defaults = defaults;
		this.columnSettings = columnSettings;

		this.initColumnsTable();
		this.initDefaultsTable(columns);
	},

	initColumnsTable: function()
	{
		this.columnsTable = new Craft.EditableTable(this.columnsTableId, this.columnsTableName, this.columnSettings, {
			rowIdPrefix: 'col',
			onAddRow: $.proxy(this, 'onAddColumn'),
			onDeleteRow: $.proxy(this, 'reconstructDefaultsTable')
		});

		this.initColumnSettingInputs(this.columnsTable.$tbody);
		this.columnsTable.sorter.settings.onSortChange = $.proxy(this, 'reconstructDefaultsTable');
	},

	initDefaultsTable: function(columns)
	{
		this.defaultsTable = new Craft.EditableTable(this.defaultsTableId, this.defaultsTableName, columns, {
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
			$typeSelect = $container.find('td:nth-child(4) select');

		this.addListener($textareas, 'textchange', 'reconstructDefaultsTable');
		this.addListener($typeSelect, 'change', 'reconstructDefaultsTable');
	},

	reconstructDefaultsTable: function()
	{
		var columnsTableData = Craft.expandPostArray(Garnish.getPostData(this.columnsTable.$tbody)),
			defaultsTableData = Craft.expandPostArray(Garnish.getPostData(this.defaultsTable.$tbody)),
			columns = columnsTableData,
			defaults = defaultsTableData;

		for (var i = 0; i < this.columnsTableInputPath.length; i++)
		{
			var key = this.columnsTableInputPath[i];
			columns = columns[key];
		}

		for (var i = 0; i < this.defaultsTableInputPath.length; i++)
		{
			var key = this.defaultsTableInputPath[i];
			defaults = defaults[key];
		}

		var tableHtml = '<table id="'+this.defaultsTableId+'" class="editable shadow-box">' +
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
			tableHtml += Craft.EditableTable.getRowHtml(rowId, columns, this.defaultsTableName, defaults[rowId]);
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
