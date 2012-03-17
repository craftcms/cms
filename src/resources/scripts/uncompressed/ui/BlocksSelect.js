(function($) {


/**
 * Blocks Select
 */
b.ui.BlocksSelect = b.Base.extend({

	$container: null,
	$items: null,
	$addItem: null,
	$fillerItems: null,
	$blockItems: null,
	$selectedItems: null,

	inputName: null,

	selector: null,
	sorter: null,

	init: function(container)
	{
		this.$container = $(container);

		// Is this already a blocks select?
		if (this.$container.data('blocksselect'))
		{
			b.log('Double-instantiating a blocks select on an element');
			this.$container.data('blocksselect').destroy();
		}

		this.$container.data('blocksselect', this);

		this.inputName = this.$container.attr('data-input-name');

		this.selector = new b.ui.Select(this.$container, {
			multi: true,
			handle: 'div.block',
			onSelectionChange: $.proxy(this, 'onSelectionChange')
		});

		this.sorter = new b.ui.DragSort({
			axis: 'y',
			handle: 'div.block',
			helper: '<ul />',
			filter: '.sel',
			onSortChange: $.proxy(this, 'onSortChange')
		});

		this.$items = this.$container.find('li');
		this.$addItem = this.$items.filter('.add:first');
		this.$fillerItems = this.$items.filter('.filler');
		this.$blockItems = $();

		var $blockItems = this.$items.not(this.$addItem).not(this.$fillerItems);
		if ($blockItems)
			this.initBlocks($blockItems);

		this.$addBtn = this.$addItem.find('a');
		this.addListener(this.$addBtn, 'click', 'showModal');

		this.addListener(this.$container, 'keydown', 'onKeyDown');
	},

	showModal: function()
	{
		var modal = b.getBlocksSelectModal();
		modal.attachToField(this);
	},

	addBlocks: function($blockItems)
	{
		// deselect any selected items
		this.selector.deselectAll();

		// Add the hidden inputs
		for (var i = 0; i < $blockItems.length; i++)
		{
			// Get the block ID
			var blockId = $blockItems[i].getAttribute('data-block-id');

			// Add the [selections] input
			var $input = $(document.createElement('input'));
			$input.attr('type', 'hidden');
			$input.attr('name', this.inputName+'[selections][]');
			$input.attr('value', blockId);
			$input.appendTo($blockItems[i]);

			// Add the [required] label + input
			var $label = $(document.createElement('label'));
			$label.addClass('required');
			$label.attr('title', 'Required?');
			$label.prependTo($blockItems[i]);

			var $input = $(document.createElement('input'));
			$input.attr('type', 'checkbox');
			$input.attr('name', this.inputName+'[required]['+blockId+']');
			$input.attr('value', 'y');
			$input.appendTo($label);
		}

		$blockItems.insertBefore(this.$addItem);
		this.initBlocks($blockItems);
		this.setFillers();
	},

	initBlocks: function($blockItems)
	{
		this.$blockItems = this.$blockItems.add($blockItems);
		this.selector.addItems($blockItems);
		this.sorter.addItems($blockItems);
	},

	removeBlocks: function($blockItems)
	{
		this.selector.removeItems($blockItems);
		this.$blockItems = this.$blockItems.not($blockItems);
		$blockItems.remove();
		this.setFillers();
	},

	removeSelectedBlocks: function()
	{
		this.removeBlocks(this.$selectedItems);
	},

	onSortChange: function()
	{
		this.selector.reset();
		this.selector.addItems(this.sorter.$items);
	},

	setFillers: function()
	{
		var totalFillers = this.$fillerItems.length,
			totalBlocks = this.$blockItems.length,
			neededFillers = 2 - totalBlocks;

		if (neededFillers > totalFillers)
		{
			var missingFillers = neededFillers - totalFillers;
			for (var i = 0; i < missingFillers; i++)
			{
				var $filler = $(document.createElement('li'));
				$filler.addClass('filler');
				$filler.insertAfter(this.$addItem);
				this.$fillerItems = this.$fillerItems.add($filler);
			}
		}
		else if (neededFillers < totalFillers)
		{
			var extraFillers = totalFillers - neededFillers;
			for (var i = 0; i < extraFillers; i++)
			{
				var $filler = this.$fillerItems.last();
				this.$fillerItems = this.$fillerItems.not($filler);
				$filler.remove();
			}
		}
	},

	onSelectionChange: function()
	{
		this.$selectedItems = this.selector.getSelectedItems();
	},

	onKeyDown: function(event)
	{
		// Ignore if meta key is down
		if (event.metaKey) return;

		// Ignore if the container doesn't have focus
		if (event.target != this.$container[0]) return;

		// Ignore if there are no selected items
		if (! this.$selectedItems.length) return;

		if (event.keyCode == b.DELETE_KEY)
		{
			event.preventDefault();
			this.removeSelectedBlocks();
		}
	}

});


$.fn.blocksselect = function()
{
	return this.each(function()
	{
		if (!$.data(this, 'blocksselect'))
			new b.ui.BlocksSelect(this);
	});
};

b.$document.ready(function()
{
	$('#body .blocksselect').blocksselect();
});


})(jQuery);
