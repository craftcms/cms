/**
 * Editable table class
 */
Craft.EditableTable = Garnish.Base.extend({

	id: null,
	baseName: null,
	columns: null,
	sorter: null,
	biggestId: -1,

	$table: null,
	$tbody: null,
	$addRowBtn: null,

	init: function(id, baseName, columns, settings)
	{
		this.id = id;
		this.baseName = baseName;
		this.columns = columns;
		this.setSettings(settings, Craft.EditableTable.defaults);

		this.$table = $('#'+id);
		this.$tbody = this.$table.children('tbody');

		this.sorter = new Craft.DataTableSorter(this.$table, {
			helperClass: 'editabletablesorthelper'
		});

		var $rows = this.$tbody.children();

		for (var i = 0; i < $rows.length; i++)
		{
			new Craft.EditableTable.Row(this, $rows[i]);
		}

		this.$addRowBtn = this.$table.next('.add');
		this.addListener(this.$addRowBtn, 'activate', 'addRow');
	},

	addRow: function()
	{
		var rowId = this.settings.rowIdPrefix+(this.biggestId+1),
			rowHtml = Craft.EditableTable.getRowHtml(rowId, this.columns, this.baseName, {}),
			$tr = $(rowHtml).appendTo(this.$tbody);

		new Craft.EditableTable.Row(this, $tr);
		this.sorter.addItems($tr);

		// Focus the first input in the row
		$tr.find('input,textarea,select').first().focus();

		// onAddRow callback
		this.settings.onAddRow($tr);
	}
},
{
	textualColTypes: ['singleline', 'multiline', 'number'],
	defaults: {
		rowIdPrefix: '',
		onAddRow: $.noop,
		onDeleteRow: $.noop
	},

	getRowHtml: function(rowId, columns, baseName, values)
	{
		var rowHtml = '<tr data-id="'+rowId+'">';

		for (var colId in columns)
		{
			var col = columns[colId],
				name = baseName+'['+rowId+']['+colId+']',
				value = (typeof values[colId] != 'undefined' ? values[colId] : ''),
				textual = Craft.inArray(col.type, Craft.EditableTable.textualColTypes);

			rowHtml += '<td class="'+(textual ? 'textual' : '')+' '+(typeof col['class'] != 'undefined' ? col['class'] : '')+'"' +
			              (typeof col['width'] != 'undefined' ? ' width="'+col['width']+'"' : '') +
			              '>';

			switch (col.type)
			{
				case 'select':
				{
					rowHtml += '<div class="select small"><select name="'+name+'">';

					for (var optionValue in col.options)
					{
						rowHtml += '<option value="'+optionValue+'"'+(optionValue == value ? ' selected' : '')+'>'+col.options[optionValue]+'</option>';
					}

					rowHtml += '</select></div>';

					break;
				}

				case 'checkbox':
				{
					rowHtml += '<input type="hidden" name="'+name+'">' +
					           '<input type="checkbox" name="'+name+'" value="1"'+(value ? ' checked' : '')+'>';

					break;
				}

				default:
				{
					rowHtml += '<textarea name="'+name+'" rows="1">'+value+'</textarea>';
				}
			}

			rowHtml += '</td>';
		}

		rowHtml += '<td class="thin action"><a class="move icon" title="'+Craft.t('Reorder')+'"></a></td>' +
				'<td class="thin action"><a class="delete icon" title="'+Craft.t('Delete')+'"></a></td>' +
			'</tr>';

		return rowHtml;
	}
});

/**
 * Editable table row class
 */
Craft.EditableTable.Row = Garnish.Base.extend({

	table: null,
	id: null,
	niceTexts: null,

	$tr: null,
	$tds: null,
	$textareas: null,
	$deleteBtn: null,

	init: function(table, tr)
	{
		this.table = table;
		this.$tr = $(tr);
		this.$tds = this.$tr.children();

		// Get the row ID, sans prefix
		var id = parseInt(this.$tr.attr('data-id').substr(this.table.settings.rowIdPrefix.length));

		if (id > this.table.biggestId)
		{
			this.table.biggestId = id;
		}

		this.$textareas = $();
		this.niceTexts = [];
		var textareasByColId = {};

		var i = 0;

		for (var colId in this.table.columns)
		{
			var col = this.table.columns[colId];

			if (Craft.inArray(col.type, Craft.EditableTable.textualColTypes))
			{
				$textarea = $('textarea', this.$tds[i]);
				this.$textareas = this.$textareas.add($textarea);

				this.addListener($textarea, 'focus', 'onTextareaFocus');
				this.addListener($textarea, 'mousedown', 'ignoreNextTextareaFocus');

				this.niceTexts.push(new Garnish.NiceText($textarea, {
					onHeightChange: $.proxy(this, 'onTextareaHeightChange')
				}));

				if (col.type == 'singleline' || col.type == 'number')
				{
					this.addListener($textarea, 'keypress', { type: col.type }, 'validateKeypress');
				}

				textareasByColId[colId] = $textarea;
			}

			i++;
		}

		// Now that all of the text cells have been nice-ified, let's normalize the heights
		this.onTextareaHeightChange();

		// Now look for any autopopulate columns
		for (var colId in this.table.columns)
		{
			var col = this.table.columns[colId];

			if (col.autopopulate && typeof textareasByColId[col.autopopulate] != 'undefined' && !textareasByColId[colId].val())
			{
				if (col.autopopulate == 'handle')
				{
					new Craft.HandleGenerator(textareasByColId[colId], textareasByColId[col.autopopulate]);
				}
				else
				{
					new Craft.BaseInputGenerator(textareasByColId[colId], textareasByColId[col.autopopulate]);
				}
			}
		}

		var $deleteBtn = this.$tr.children().last().find('.delete');
		this.addListener($deleteBtn, 'click', 'deleteRow');
	},

	onTextareaFocus: function(ev)
	{
		var $textarea = $(ev.currentTarget);

		if ($textarea.data('ignoreNextFocus'))
		{
			$textarea.data('ignoreNextFocus', false);
			return;
		}

		setTimeout(function()
		{
			var val = $textarea.val();

			// Does the browser support setSelectionRange()?
			if (typeof $textarea[0].setSelectionRange != 'undefined')
			{
				// Select the whole value
				var length = val.length * 2;
				$textarea[0].setSelectionRange(0, length);
			}
			else
			{
				// Refresh the value to get the cursor positioned at the end
				$textarea.val(val);
			}
		}, 0);
	},

	ignoreNextTextareaFocus: function(ev)
	{
		$.data(ev.currentTarget, 'ignoreNextFocus', true);
	},

	validateKeypress: function(ev)
	{
		var keyCode = ev.keyCode ? ev.keyCode : ev.charCode;

		if (!ev.metaKey && !ev.ctrlKey && (
			(keyCode == Garnish.RETURN_KEY) ||
			(ev.data.type == 'number' && !Craft.inArray(keyCode, Craft.EditableTable.Row.numericKeyCodes))
		))
		{
			ev.preventDefault();
		}
	},

	onTextareaHeightChange: function()
	{
		// Keep all the textareas' heights in sync
		var tallestTextareaHeight = -1;

		for (var i = 0; i < this.niceTexts.length; i++)
		{
			if (this.niceTexts[i].height > tallestTextareaHeight)
			{
				tallestTextareaHeight = this.niceTexts[i].height;
			}
		}

		this.$textareas.css('min-height', tallestTextareaHeight);
	},

	deleteRow: function()
	{
		this.table.sorter.removeItems(this.$tr);
		this.$tr.remove();

		// onDeleteRow callback
		this.table.settings.onDeleteRow(this.$tr);
	}
},
{
	numericKeyCodes: [9 /* (tab) */ , 8 /* (delete) */ , 37,38,39,40 /* (arrows) */ , 45,91 /* (minus) */ , 46,190 /* period */ , 48,49,50,51,52,53,54,55,56,57 /* (0-9) */ ]
});
