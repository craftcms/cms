(function($) {


var columnsTableId = 'types-Table-columns',
	columnsTableName = 'types[Table][columns]',
	defaultsTableId = 'types-Table-defaults',
	defaultsTableName = 'types[Table][defaults]';


Craft.TableFieldSettings = Garnish.Base.extend({

	columns: null,
	defaults: null,
	columnSettings: null,

	columnsTable: null,
	defaultsTable: null,
	reconstructTimeout: null,
	reconstructInterval: null,

	init: function(columns, defaults, columnSettings)
	{
		this.columns = columns;
		this.defaults = defaults;
		this.columnSettings = columnSettings;

		this.initColumnsTable();
		this.initDefaultsTable();
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

	initDefaultsTable: function()
	{
		this.defaultsTable = new Craft.EditableTable(defaultsTableId, defaultsTableName, this.columns, {
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
		var $allInputs = $container.find('td:first-child textarea, td:nth-child(3) textarea, td:nth-child(4) select'),
			$textareas = $allInputs.filter('textarea');

		$allInputs.change($.proxy(this, 'reconstructDefaultsTable'));

		$textareas.focus($.proxy(function() {
			clearTimeout(this.reconstructTimeout);
			this.reconstructTimeout = setTimeout($.proxy(function() {
				clearInterval(this.reconstructInterval);
				this.reconstructInterval = setInterval($.proxy(this, 'reconstructDefaultsTable'), 500);
			}, this), 1);
		}, this));

		$textareas.blur($.proxy(function() {
			clearInterval(this.reconstructInterval);
			clearTimeout(this.reconstructTimeout);
		}, this));
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
			tableHtml += '<th scope="col" class="header">'+columns[colId].heading+'</th>';
		}

		tableHtml += '<th class="header" colspan="2"></th>' +
				'</tr>' +
			'</thead>' +
			'<tbody>';

		for (var rowId in defaults)
		{
			var row = defaults[rowId];

			tableHtml += '<tr data-id="'+rowId+'">';

			for (var colId in columns)
			{
				var col = columns[colId],
					name = defaultsTableName+'['+rowId+']['+colId+']',
					value = (typeof row[colId] != 'undefined' ? row[colId] : ''),
					textual = Craft.inArray(col.type, Craft.EditableTable.textualColTypes);

				tableHtml += '<td'+(textual ? ' class="textual"' : '')+(col.width ? ' width="'+col.width+'"' : '')+'>';

				if (col.type == 'checkbox')
				{
					tableHtml += '<input type="hidden" name="'+name+'">' +
						'<input type="checkbox" name="'+name+'" value="1"'+(value ? ' checked' : '')+'>';
				}
				else
				{
					tableHtml += '<textarea name="'+name+'" rows="1">'+value+'</textarea>';
				}

				tableHtml += '</td>';
			}

			tableHtml += '<td class="thin action"><a class="move icon" title="'+Craft.t('Reorder')+'"></a></td>' +
					'<td class="thin action"><a class="delete icon" title="'+Craft.t('Delete')+'"></a></td>' +
				'</tr>';
		}

		tableHtml += '</tbody>' +
			'</table>';

		this.defaultsTable.$table.replaceWith(tableHtml);
		this.defaultsTable.destroy();
		delete this.defaultsTable;
		this.initDefaultsTable();
	}

});


})(jQuery);
