(function($) {

/**
 * Admin Pane
 */
Blocks.ui.AdminPane = Blocks.Base.extend({

	$container: null,
	$listContainer: null,
	$list: null,
	$addBtn: null,
	$settingsContainer: null,

	inputName: null,

	items: null,
	itemSort: null,
	selectedItem: null,
	totalNewItems: 0,

	init: function(container)
	{
		this.$container = $(container);

		// Is this already a item adminPane?
		if (this.$container.data('adminpane'))
		{
			Blocks.log('Double-instantiating an admin pane on an element');
			this.$container.data('adminpane').destroy();
		}

		this.$container.data('adminpane', this);

		this.inputName = this.$container.attr('data-input-name');

		// Find the DOM nodes
		this.$listContainer = this.$container.children('.list:first');
		this.$list = this.$listContainer.children('ul:first');
		this.$addBtn = this.$listContainer.children('.btn:first');
		this.$settingsContainer = this.$container.children('.settings:first');

		// Get the fresh item settings HTML
		var $freshSettings = this.$container.children('.freshsettings:first').remove().removeClass('freshsettings');
		this.freshSettingsHtml = $freshSettings.html();

		// Initialize the sorter
		this.itemSort = new Blocks.ui.DragSort({
			axis: 'y',
			helper: function($li) {
				var $ul = $('<ul/>');
				$li.appendTo($ul);
				$li.removeClass('sel');
				return $ul;
			}
		});

		// Initialize the items
		this.items = {};
		var $items = this.$list.children('li'),
			$settings = this.$settingsContainer.children();

		for (var i = 0; i < $items.length; i++)
		{
			var $item = $($items[i]),
				itemId = $item.attr('data-item-id'),
				$itemSettings = $settings.filter('[data-item-id='+itemId+']:first');

			var item = new Blocks.ui.AdminPane.Item(this, itemId, $item, $itemSettings);
			this.items[itemId] = item;

			// Is this a new item? (Could be if there were validation errors)
			if (item.isNew)
				this.totalNewItems++;

			// Add its parent LI to the sorter
			this.itemSort.addItems($item);
		}

		// Add new items
		this.addListener(this.$addBtn, 'click', 'addItem');
	},

	selectItem: function(itemId)
	{
		this.items[itemId].select();
	},

	setHeight: function()
	{
		var height = Math.max(this.$listContainer.height(), this.$settingsContainer.height());
		this.$container.height(height + 28);
	},

	addItem: function()
	{
		this.totalNewItems++;

		var itemId = 'new'+(this.totalNewItems),
			settingsId = this.inputName+'-'+itemId,
			$item = $('<li class="item" data-settings-id="'+settingsId+'">' +
					'<div class="name"></div>' +
					'<div class="type"></div>' +
					'<input type="hidden" name="'+this.inputName+'[order][]" value="'+itemId+'"/>' +
					'</a>'
				).appendTo(this.$list),
			settingsHtml = this.freshSettingsHtml.replace(/ITEM_ID/g, itemId),
			$settings = $('<div data-item-id="'+itemId+'"/>').html(settingsHtml).appendTo(this.$settingsContainer);

		var item = new Blocks.ui.AdminPane.Item(this, itemId, $item, $settings);
		this.items[itemId] = item;
		item.select();

		this.itemSort.addItems($item);

		return item;
	}

});

Blocks.ui.AdminPane.Item = Blocks.Base.extend({

	adminPane: null,
	id: null,
	isNew: null,

	$item: null,
	$settings: null,

	$name: null,
	$type: null,

	$doneBtn: null,
	$deleteBtn: null,

	init: function(adminPane, id, $item, $settings)
	{
		this.adminPane = adminPane;
		this.id = id;
		this.isNew = (this.id.substr(0, 3) == 'new');

		this.$item = $item;
		this.$settings = $settings;

		this.$name = $item.children('.name:first');
		this.$type = $item.children('.type:first');

		var $buttons = this.$settings.children('.buttons:first');
		this.$doneBtn = $buttons.children('.done:first');
		this.$deleteBtn = $buttons.children('.delete:first');

		this.addListener(this.$item, 'mousedown', 'select');

		this.addListener(this.$doneBtn, 'click', function() {
			this.deselect();
			this.adminPane.setHeight();
		});

		this.addListener(this.$deleteBtn, 'click', 'deleteItem');
	},

	select: function()
	{
		if (this.adminPane.selectedItem)
			this.adminPane.selectedItem.deselect();

		this.adminPane.selectedItem = this;

		this.$item.addClass('sel');
		this.$settings.show();

		this.adminPane.setHeight();
	},

	deselect: function()
	{
		this.$item.removeClass('sel');
		this.$settings.hide();
		this.adminPane.selectedItem = null;
	},

	setName: function(name)
	{
		this.$name.html(name);
	},

	setType: function(type)
	{
		this.$type.html(type);
	},

	deleteItem: function()
	{
		if (confirm(Blocks.t('Are you sure you want to delete “{name}”?', {'name': this.$name.html()})))
		{
			this.$item.remove();
			this.$settings.remove();

			this.adminPane.setHeight();

			if (!this.isNew)
				$('<input type="hidden" name="'+this.adminPane.inputName+'[delete][]" value="'+this.id+'"/>').appendTo(this.adminPane.$container);

			this.destroy();
		}
	}

});

})(jQuery);
