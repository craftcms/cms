Craft.FieldLayoutDesigner = Garnish.Base.extend(
{
	$container: null,
	$tabContainer: null,
	$unusedFieldContainer: null,
	$newTabBtn: null,
	$allFields: null,

	tabGrid: null,
	unusedFieldGrid: null,

	tabDrag: null,
	fieldDrag: null,

	init: function(container, settings)
	{
		this.$container = $(container);
		this.setSettings(settings, Craft.FieldLayoutDesigner.defaults);

		this.$tabContainer = this.$container.children('.fld-tabs');
		this.$unusedFieldContainer = this.$container.children('.unusedfields');
		this.$newTabBtn = this.$container.find('> .newtabbtn-container > .btn');
		this.$allFields = this.$unusedFieldContainer.find('.fld-field');

		// Set up the layout grids
		this.tabGrid = new Craft.Grid(this.$tabContainer, Craft.FieldLayoutDesigner.gridSettings);
		this.unusedFieldGrid = new Craft.Grid(this.$unusedFieldContainer, Craft.FieldLayoutDesigner.gridSettings);

		var $tabs = this.$tabContainer.children();
		for (var i = 0; i < $tabs.length; i++)
		{
			this.initTab($($tabs[i]));
		}

		this.fieldDrag = new Craft.FieldLayoutDesigner.FieldDrag(this);

		if (this.settings.customizableTabs)
		{
			this.tabDrag = new Craft.FieldLayoutDesigner.TabDrag(this);

			this.addListener(this.$newTabBtn, 'activate', 'addTab');
		}
	},

	initTab: function($tab)
	{
		if (this.settings.customizableTabs)
		{
			var $editBtn = $tab.find('.tabs .settings'),
				$menu = $('<div class="menu" data-align="center"/>').insertAfter($editBtn),
				$ul = $('<ul/>').appendTo($menu);

			$('<li><a data-action="rename">'+Craft.t('Rename')+'</a></li>').appendTo($ul);
			$('<li><a data-action="delete">'+Craft.t('Delete')+'</a></li>').appendTo($ul);

			new Garnish.MenuBtn($editBtn, {
				onOptionSelect: $.proxy(this, 'onTabOptionSelect')
			});
		}

		// Don't forget the fields!
		var $fields = $tab.children('.fld-tabcontent').children();

		for (var i = 0; i < $fields.length; i++)
		{
			this.initField($($fields[i]));
		}
	},

	initField: function($field)
	{
		var $editBtn = $field.find('.settings'),
			$menu = $('<div class="menu" data-align="center"/>').insertAfter($editBtn),
			$ul = $('<ul/>').appendTo($menu);

		if ($field.hasClass('fld-required'))
		{
			$('<li><a data-action="toggle-required">'+Craft.t('Make not required')+'</a></li>').appendTo($ul);
		}
		else
		{
			$('<li><a data-action="toggle-required">'+Craft.t('Make required')+'</a></li>').appendTo($ul);
		}

		$('<li><a data-action="remove">'+Craft.t('Remove')+'</a></li>').appendTo($ul);

		new Garnish.MenuBtn($editBtn, {
			onOptionSelect: $.proxy(this, 'onFieldOptionSelect')
		});
	},

	onTabOptionSelect: function(option)
	{
		if (!this.settings.customizableTabs)
		{
			return;
		}

		var $option = $(option),
			$tab = $option.data('menu').$anchor.parent().parent().parent(),
			action = $option.data('action');

		switch (action)
		{
			case 'rename':
			{
				this.renameTab($tab);
				break;
			}
			case 'delete':
			{
				this.deleteTab($tab);
				break;
			}
		}
	},

	onFieldOptionSelect: function(option)
	{
		var $option = $(option),
			$field = $option.data('menu').$anchor.parent(),
			action = $option.data('action');

		switch (action)
		{
			case 'toggle-required':
			{
				this.toggleRequiredField($field, $option);
				break;
			}
			case 'remove':
			{
				this.removeField($field);
				break;
			}
		}
	},

	renameTab: function($tab)
	{
		if (!this.settings.customizableTabs)
		{
			return;
		}

		var $labelSpan = $tab.find('.tabs .tab span'),
			oldName = $labelSpan.text(),
			newName = prompt(Craft.t('Give your tab a name.'), oldName);

		if (newName && newName != oldName)
		{
			$labelSpan.text(newName);
			$tab.find('.id-input').attr('name', this.getFieldInputName(newName));
		}
	},

	deleteTab: function($tab)
	{
		if (!this.settings.customizableTabs)
		{
			return;
		}

		// Find all the fields in this tab
		var $fields = $tab.find('.fld-field');

		for (var i = 0; i < $fields.length; i++)
		{
			var fieldId = $($fields[i]).attr('data-id');
			this.removeFieldById(fieldId);
		}

		this.tabGrid.removeItems($tab);
		this.tabDrag.removeItems($tab);

		$tab.remove();
	},

	toggleRequiredField: function($field, $option)
	{
		if ($field.hasClass('fld-required'))
		{
			$field.removeClass('fld-required');
			$field.find('.required-input').remove();

			setTimeout(function() {
				$option.text(Craft.t('Make required'));
			}, 500);
		}
		else
		{
			$field.addClass('fld-required');
			$('<input class="required-input" type="hidden" name="'+this.settings.requiredFieldInputName+'" value="'+$field.data('id')+'">').appendTo($field);

			setTimeout(function() {
				$option.text(Craft.t('Make not required'));
			}, 500);
		}
	},

	removeField: function($field)
	{
		var fieldId = $field.attr('data-id');

		$field.remove();

		this.removeFieldById(fieldId);
		this.tabGrid.refreshCols(true);
	},

	removeFieldById: function(fieldId)
	{
		var $field = this.$allFields.filter('[data-id='+fieldId+']:first'),
			$group = $field.closest('.fld-tab');

		$field.removeClass('hidden');

		if ($group.hasClass('hidden'))
		{
			$group.removeClass('hidden');
			this.unusedFieldGrid.addItems($group);

			if (this.settings.customizableTabs)
			{
				this.tabDrag.addItems($group);
			}
		}
		else
		{
			this.unusedFieldGrid.refreshCols(true);
		}
	},

	addTab: function()
	{
		if (!this.settings.customizableTabs)
		{
			return;
		}

		var $tab = $('<div class="fld-tab">' +
						'<div class="tabs">' +
							'<div class="tab sel draggable">' +
								'<span>Tab '+(this.tabGrid.$items.length+1)+'</span>' +
								'<a class="settings icon" title="'+Craft.t('Rename')+'"></a>' +
							'</div>' +
						'</div>' +
						'<div class="fld-tabcontent"></div>' +
					'</div>').appendTo(this.$tabContainer);

		this.tabGrid.addItems($tab);
		this.tabDrag.addItems($tab);

		this.initTab($tab);
	},

	getFieldInputName: function(tabName)
	{
		return this.settings.fieldInputName.replace(/__TAB_NAME__/g, Craft.encodeUriComponent(tabName));
	}
},
{
	gridSettings: {
		itemSelector: '.fld-tab:not(.hidden)',
		minColWidth: 240,
		percentageWidths: false,
		fillMode: 'grid',
		snapToGrid: 30
	},
	defaults: {
		customizableTabs: true,
		fieldInputName: 'fieldLayout[__TAB_NAME__][]',
		requiredFieldInputName: 'requiredFields[]'
	}
});


Craft.FieldLayoutDesigner.BaseDrag = Garnish.Drag.extend(
{
	designer: null,
	$insertion: null,
	showingInsertion: false,
	$caboose: null,
	draggingUnusedItem: false,
	addToTabGrid: false,

	/**
	 * Constructor
	 */
	init: function(designer, settings)
	{
		this.designer = designer;

		// Find all the items from both containers
		var $items = this.designer.$tabContainer.find(this.itemSelector)
			.add(this.designer.$unusedFieldContainer.find(this.itemSelector));

		this.base($items, settings);
	},

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		this.base();

		// Are we dragging an unused item?
		this.draggingUnusedItem = this.$draggee.hasClass('unused');

		// Create the insertion
		this.$insertion = this.getInsertion();

		// Add the caboose
		this.addCaboose();
		this.$items = $().add(this.$items.add(this.$caboose));

		if (this.addToTabGrid)
		{
			this.designer.tabGrid.addItems(this.$caboose);
		}

		// Swap the draggee with the insertion if dragging a selected item
		if (this.draggingUnusedItem)
		{
			this.showingInsertion = false;
		}
		else
		{
			// Actually replace the draggee with the insertion
			this.$insertion.insertBefore(this.$draggee);
			this.$draggee.detach();
			this.$items = $().add(this.$items.not(this.$draggee).add(this.$insertion));
			this.showingInsertion = true;

			if (this.addToTabGrid)
			{
				this.designer.tabGrid.removeItems(this.$draggee);
				this.designer.tabGrid.addItems(this.$insertion);
			}
		}

		this.setMidpoints();
	},

	/**
	 * Append the caboose
	 */
	addCaboose: $.noop,

	/**
	 * Returns the item's container
	 */
	getItemContainer: $.noop,

	/**
	 * Tests if an item is within the tab container.
	 */
	isItemInTabContainer: function($item)
	{
		return (this.getItemContainer($item)[0] == this.designer.$tabContainer[0]);
	},

	/**
	 * Sets the item midpoints up front so we don't have to keep checking on every mouse move
	 */
	setMidpoints: function()
	{
		for (var i = 0; i < this.$items.length; i++)
		{
			var $item = $(this.$items[i]);

			// Skip the unused tabs
			if (!this.isItemInTabContainer($item))
			{
				continue;
			}

			var offset = $item.offset();

			$item.data('midpoint', {
				left: offset.left + $item.outerWidth() / 2,
				top:  offset.top + $item.outerHeight() / 2
			});
		}
	},

	/**
	 * On Drag
	 */
	onDrag: function()
	{
		// Are we hovering over the tab container?
		if (this.draggingUnusedItem && !Garnish.hitTest(this.mouseX, this.mouseY, this.designer.$tabContainer))
		{
			if (this.showingInsertion)
			{
				this.$insertion.remove();
				this.$items = $().add(this.$items.not(this.$insertion));
				this.showingInsertion = false;

				if (this.addToTabGrid)
				{
					this.designer.tabGrid.removeItems(this.$insertion);
				}
				else
				{
					this.designer.tabGrid.refreshCols(true);
				}

				this.setMidpoints();
			}
		}
		else
		{
			// Is there a new closest item?
			this.onDrag._closestItem = this.getClosestItem();

			if (this.onDrag._closestItem != this.$insertion[0])
			{
				if (this.showingInsertion &&
					($.inArray(this.$insertion[0], this.$items) < $.inArray(this.onDrag._closestItem, this.$items)) &&
					($.inArray(this.onDrag._closestItem, this.$caboose) == -1)
				)
				{
					this.$insertion.insertAfter(this.onDrag._closestItem);
				}
				else
				{
					this.$insertion.insertBefore(this.onDrag._closestItem);
				}

				this.$items = $().add(this.$items.add(this.$insertion));
				this.showingInsertion = true;

				if (this.addToTabGrid)
				{
					this.designer.tabGrid.addItems(this.$insertion);
				}
				else
				{
					this.designer.tabGrid.refreshCols(true);
				}

				this.setMidpoints();
			}
		}

		this.base();
	},

	/**
	 * Returns the closest item to the cursor.
	 */
	getClosestItem: function()
	{
		this.getClosestItem._closestItem = null;
		this.getClosestItem._closestItemMouseDiff = null;

		for (this.getClosestItem._i = 0; this.getClosestItem._i < this.$items.length; this.getClosestItem._i++)
		{
			this.getClosestItem._$item = $(this.$items[this.getClosestItem._i]);

			// Skip the unused tabs
			if (!this.isItemInTabContainer(this.getClosestItem._$item))
			{
				continue;
			}

			this.getClosestItem._midpoint = this.getClosestItem._$item.data('midpoint');
			this.getClosestItem._mouseDiff = Garnish.getDist(this.getClosestItem._midpoint.left, this.getClosestItem._midpoint.top, this.mouseX, this.mouseY);

			if (this.getClosestItem._closestItem === null || this.getClosestItem._mouseDiff < this.getClosestItem._closestItemMouseDiff)
			{
				this.getClosestItem._closestItem = this.getClosestItem._$item[0];
				this.getClosestItem._closestItemMouseDiff = this.getClosestItem._mouseDiff;
			}
		}

		return this.getClosestItem._closestItem;
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		if (this.showingInsertion)
		{
			this.$insertion.replaceWith(this.$draggee);
			this.$items = $().add(this.$items.not(this.$insertion).add(this.$draggee));

			if (this.addToTabGrid)
			{
				this.designer.tabGrid.removeItems(this.$insertion);
				this.designer.tabGrid.addItems(this.$draggee);
			}
		}

		// Drop the caboose
		this.$items = this.$items.not(this.$caboose);
		this.$caboose.remove();

		if (this.addToTabGrid)
		{
			this.designer.tabGrid.removeItems(this.$caboose);
		}

		// "show" the drag items, but make them invisible
		this.$draggee.css({
			display:    this.draggeeDisplay,
			visibility: 'hidden'
		});

		this.designer.tabGrid.refreshCols(true);
		this.designer.unusedFieldGrid.refreshCols(true);

		// return the helpers to the draggees
		this.returnHelpersToDraggees();

		this.base();
	}
});


Craft.FieldLayoutDesigner.TabDrag = Craft.FieldLayoutDesigner.BaseDrag.extend(
{
	itemSelector: '> div.fld-tab',
	addToTabGrid: true,

	/**
	 * Constructor
	 */
	init: function(designer)
	{
		var settings = {
			handle: '.tab'
		};

		this.base(designer, settings);
	},

	/**
	 * Append the caboose
	 */
	addCaboose: function()
	{
		this.$caboose = $('<div class="fld-tab fld-tab-caboose"/>').appendTo(this.designer.$tabContainer);
	},

	/**
	 * Returns the insertion
	 */
	getInsertion: function()
	{
		var $tab = this.$draggee.find('.tab');

		return $('<div class="fld-tab fld-insertion" style="height: '+this.$draggee.height()+'px;">' +
					'<div class="tabs"><div class="tab sel draggable" style="width: '+$tab.width()+'px; height: '+$tab.height()+'px;"></div></div>' +
					'<div class="fld-tabcontent" style="height: '+this.$draggee.find('.fld-tabcontent').height()+'px;"></div>' +
				'</div>');
	},

	/**
	 * Returns the item's container
	 */
	getItemContainer: function($item)
	{
		return $item.parent();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		if (this.draggingUnusedItem && this.showingInsertion)
		{
			// Create a new tab based on that field group
			var $tab = this.$draggee.clone().removeClass('unused'),
				tabName = $tab.find('.tab span').text();

			$tab.find('.fld-field').removeClass('unused');

			// Add the edit button
			$tab.find('.tabs .tab').append('<a class="settings icon" title="'+Craft.t('Edit')+'"></a>');

			// Remove any hidden fields
			var $fields = $tab.find('.fld-field'),
				$hiddenFields = $fields.filter('.hidden').remove();

			$fields = $fields.not($hiddenFields);
			$fields.prepend('<a class="settings icon" title="'+Craft.t('Edit')+'"></a>');

			for (var i = 0; i < $fields.length; i++)
			{
				var $field = $($fields[i]),
					inputName = this.designer.getFieldInputName(tabName);

				$field.append('<input class="id-input" type="hidden" name="'+inputName+'" value="'+$field.data('id')+'">');
			}

			this.designer.fieldDrag.addItems($fields);

			this.designer.initTab($tab);

			// Set the unused field group and its fields to hidden
			this.$draggee.css({ visibility: 'inherit', display: 'field' }).addClass('hidden');
			this.$draggee.find('.fld-field').addClass('hidden');

			// Set this.$draggee to the clone, as if we were dragging that all along
			this.$draggee = $tab;

			// Remember it for later
			this.addItems($tab);

			// Update the grids
			this.designer.tabGrid.addItems($tab);
			this.designer.unusedFieldGrid.removeItems(this.$draggee);
		}

		this.base();
	}
});


Craft.FieldLayoutDesigner.FieldDrag = Craft.FieldLayoutDesigner.BaseDrag.extend(
{
	itemSelector: '> div.fld-tab .fld-field',

	/**
	 * Append the caboose
	 */
	addCaboose: function()
	{
		this.$caboose = $();

		var $fieldContainers = this.designer.$tabContainer.children().children('.fld-tabcontent');

		for (var i = 0; i < $fieldContainers.length; i++)
		{
			var $caboose = $('<div class="fld-tab fld-tab-caboose"/>').appendTo($fieldContainers[i]);
			this.$caboose = this.$caboose.add($caboose);
		}
	},

	/**
	 * Returns the insertion
	 */
	getInsertion: function()
	{
		return $('<div class="fld-field fld-insertion" style="height: '+this.$draggee.height()+'px;"/>');
	},

	/**
	 * Returns the item's container
	 */
	getItemContainer: function($item)
	{
		return $item.parent().parent().parent();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		if (this.draggingUnusedItem && this.showingInsertion)
		{
			// Create a new field based on that one
			var $field = this.$draggee.clone().removeClass('unused');
			$field.prepend('<a class="settings icon" title="'+Craft.t('Edit')+'"></a>');
			this.designer.initField($field);

			// Hide the unused field
			this.$draggee.css({ visibility: 'inherit', display: 'field' }).addClass('hidden');

			// Hide the group too?
			if (this.$draggee.siblings(':not(.hidden)').length == 0)
			{
				var $group = this.$draggee.parent().parent();
				$group.addClass('hidden');
				this.designer.unusedFieldGrid.removeItems($group);
			}

			// Set this.$draggee to the clone, as if we were dragging that all along
			this.$draggee = $field;

			// Remember it for later
			this.addItems($field);
		}

		if (this.showingInsertion)
		{
			// Find the field's new tab name
			var tabName = this.$insertion.parent().parent().find('.tab span').text(),
				inputName = this.designer.getFieldInputName(tabName);

			if (this.draggingUnusedItem)
			{
				this.$draggee.append('<input class="id-input" type="hidden" name="'+inputName+'" value="'+this.$draggee.data('id')+'">');
			}
			else
			{
				this.$draggee.find('.id-input').attr('name', inputName);
			}
		}

		this.base();
	}
});
