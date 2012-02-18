(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Blocks Select
 */
blx.ui.BlocksSelect = blx.Base.extend({

	$container: null,
	$items: null,
	$addItem: null,
	$fillerItems: null,
	$blockItems: null,

	inputName: null,

	selector: null,
	sorter: null,

	init: function(container)
	{
		this.$container = $(container);

		// Is this already a blocks select?
		if (this.$container.data('blocksselect'))
		{
			blx.log('Double-instantiating a blocks select on an element');
			this.$container.data('blocksselect').destroy();
		}

		this.$container.data('blocksselect', this);

		this.inputName = this.$container.attr('data-input-name');

		this.selector = new blx.ui.Select(this.$container, {
			multi: true
		});

		this.sorter = new blx.ui.DragSort({
			axis: 'y',
			helper: '<ul />',
			filter: '.sel',
			onSortChange: $.proxy(this, 'onSortChange')
		});

		this.$items = this.$container.find('li');
		this.$addItem = this.$items.filter('.add:first');
		this.$fillerItems = this.$items.filter('.filler');
		this.$blockItems = $();

		var $blockItems = this.$items.not(this.$addItem).not(this.$fillerItems);
		this.initBlocks($blockItems);

		this.$addBtn = this.$addItem.find('a');
		this.addListener(this.$addBtn, 'click', 'showModal');
	},

	showModal: function()
	{
		var modal = blx.getBlocksSelectModal();
		modal.attachToField(this);
	},

	addBlocks: function($blockItems)
	{
		// deselect any selected items
		this.selector.deselectAll();

		// Add the hidden inputs
		for (var i = 0; i < $blockItems.length; i++)
		{
			var $input = $(document.createElement('input'));
			$input.attr('type', 'hidden');
			$input.attr('name', this.inputName+'[]');
			$input.attr('value', $blockItems[i].getAttribute('data-block-id'));
			$input.appendTo($blockItems[i]);
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
		$blockItems.remove();
		this.$blockItems = this.$blockItems.not($blockItems);
		this.selector.removeItems($blockItems);
		this.setFillers();
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
	}

});


$.fn.blocksselect = function()
{
	return this.each(function()
	{
		if (!$.data(this, 'blocksselect'))
			new blx.ui.BlocksSelect(this);
	});
};

blx.$document.ready(function()
{
	$('#body .blocksselect').blocksselect();
});


})(jQuery);
