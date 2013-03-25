/**
 * Editable table class
 */
Craft.EditableTable = Garnish.Base.extend({

	id: null,
	name: null,
	colHandles: null,
	rows: null,
	sorter: null,
	biggestId: -1,

	$table: null,
	$tbody: null,
	$addRowBtn: null,

	init: function(id, name, colHandles)
	{
		this.id = id;
		this.name = name;
		this.colHandles = colHandles;

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

		this.$addRowBtn = this.$table.next('.buttons').children('.add');
		this.addListener(this.$addRowBtn, 'activate', 'addRow');
	},

	addRow: function()
	{
		var rowId = this.biggestId+1,
			$tr = $('<tr data-id="'+rowId+'"/>').appendTo(this.$tbody);

		for (var i = 0; i < this.colHandles.length; i++)
		{
			$('<td><textarea name="'+this.name+'['+rowId+']['+this.colHandles[i]+']" rows="1"></textarea></td>').appendTo($tr);
		}

		$('<td class="thin"><a class="move icon" title="'+Craft.t('Reorder')+'"></a></td>').appendTo($tr);
		$('<td class="thin"><a class="delete icon" title="'+Craft.t('Delete')+'"></a></td>').appendTo($tr);

		new Craft.EditableTable.Row(this, $tr);
		this.sorter.addItems($tr);
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
	$textareas: null,
	$deleteBtn: null,

	init: function(table, tr)
	{
		this.table = table;
		this.$tr = $(tr);
		this.id = parseInt(this.$tr.attr('data-id'));

		if (this.id > this.table.biggestId)
		{
			this.table.biggestId = this.id;
		}

		this.$textareas = this.$tr.find('textarea');
		this.niceTexts = [];

		for (var i = 0 ; i < this.$textareas.length; i++)
		{
			this.niceTexts.push(new Garnish.NiceText(this.$textareas[i], {
				onHeightChange: $.proxy(this, 'onTextareaHeightChange')
			}));
		}

		var $deleteBtn = this.$tr.children().last().find('.delete');
		this.addListener($deleteBtn, 'click', 'deleteRow');
	},

	onTextareaHeightChange: function(height)
	{
		// Keep all the textareas' heights in sync
		var tallestTextareaHeight = -1;

		for (var i = 0; i < this.niceTexts.length; i++)
		{
			if (this.niceTexts[i].stageHeight > tallestTextareaHeight)
			{
				tallestTextareaHeight = this.niceTexts[i].stageHeight;
			}
		}

		this.$textareas.css('min-height', tallestTextareaHeight);
	},

	deleteRow: function()
	{
		this.table.sorter.removeItems(this.$tr);
		this.$tr.remove();
	}
});
