(function($) {


/**
 * Blocks Select Modal
 */
blx.ui.BlocksSelectModal = blx.ui.Modal.extend({

	$cancelBtn: null,

	$items: null,
	$addItem: null,
	$fillerItems: null,
	$blockItems: null,
	$visibleBlockItems: null,

	selector: null,
	$selectedItems: null,

	field: null,

	init: function()
	{
		this.base();

		$.get(baseUrl+'_includes/blocksselect/modal', $.proxy(this, 'onLoad'));
	},

	onLoad: function(data)
	{
		var $container = $(data);
		$container.appendTo(blx.$body);
		this.setContainer($container);

		this.selector = new blx.ui.Select(this.$body, {
			multi: true,
			waitForDblClick: true,
			handle: 'div.block',
			onSelectionChange: $.proxy(this, 'onSelectionChange')
		});

		this.$cancelBtn = this.$footerBtns.filter('.cancel:first');

		this.$items = this.$container.find('li');
		this.$addItem = this.$items.filter('.add:first');
		this.$fillerItems = this.$items.filter('.filler');
		this.$blockItems = $();
		this.$visibleBlockItems = $();
		this.$selectedItems = $();

		this.addListener(this.$addItem, 'click', 'showCreateBlockModal');
		this.addListener(this.$submitBtn, 'click', 'addSelectedBlocks');
		this.addListener(this.$cancelBtn, 'click', 'hide');
		this.addListener(this.$body, 'keydown', 'onKeyDown');

		var $blockItems = this.$items.not(this.$addItem).not(this.$fillerItems);
		this.addBlocks($blockItems, false);

		if (this.field)
			this.attachToField(this.field);
	},

	attachToField: function(field)
	{
		this.field = field;

		if (!this.$container)
			return;

		if (!this.visible)
			this.$container.show();

		if (this.$visibleBlockItems.length)
			this.$visibleBlockItems.first().removeClass('first');
		else
			this.$addItem.removeClass('first');

		// Show only the blocks that aren't already selected
		this.$blockItems.hide();
		this.$visibleBlockItems = $();
		var selectedBlockIds = [];
		this.selector.reset();
		this.onSelectionChange();

		for (var i = 0; i < this.field.$blockItems.length; i++)
		{
			var blockId = this.field.$blockItems[i].getAttribute('data-block-id');
			selectedBlockIds.push(blockId);
		}
		for (var i = 0; i < this.$blockItems.length; i++)
		{
			var blockId = this.$blockItems[i].getAttribute('data-block-id');
			if ($.inArray(blockId, selectedBlockIds) == -1)
			{
				$(this.$blockItems[i]).show();
				this.$visibleBlockItems = this.$visibleBlockItems.add(this.$blockItems[i]);
			}
		}

		if (this.$visibleBlockItems.length)
			this.$visibleBlockItems.first().addClass('first');
		else
			this.$addItem.addClass('first');

		this.selector.addItems(this.$visibleBlockItems);

		this.setFillers();

		// Hard-set the scrollpane's height
		this.$scrollpane.height(this.$body.height());

		if (!this.visible)
			this.$container.hide();

		this.positionRelativeTo(field.$container);
		this.show();
		this.$body.focus();
	},

	addBlocks: function(blockItems, addToSelector)
	{
		var $blockItems = $(blockItems);
		this.$blockItems = this.$blockItems.add($blockItems);
		this.addListener($blockItems, 'dblclick', 'onDblClick');

		if (addToSelector)
			this.selector.addItems($blockItems);
	},

	insertNewBlock: function(id, name, type)
	{
		var $blockItem = $('<li class="block-item sel" data-block-id="'+id+'">'
		                 +   '<div class="block">'
		                 +     '<span class="icon icon137"></span>'
		                 +     '<span class="block-name">'+name+'</span> <span class="block-type">'+type+'</span>'
		                 +   '</div>'
		                 + '</li>');

		$blockItem.insertBefore(this.$addItem);
		this.addBlocks($blockItem, true);
		this.onSelectionChange();
	},

	onDblClick: function()
	{
		clearTimeout(this.selector.clearMouseUpTimeout());
		this.onSelectionChange();
		this.addSelectedBlocks();
	},

	onSelectionChange: function()
	{
		this.$selectedItems = this.selector.getSelectedItems();

		if (this.$selectedItems.length)
			this.$submitBtn.removeClass('disabled');
		else
			this.$submitBtn.addClass('disabled');
	},

	showCreateBlockModal: function()
	{
		var modal = blx.getCreateBlockModal();
		modal.show();
	},

	addSelectedBlocks: function()
	{
		this.field.addBlocks(this.$selectedItems.clone().removeClass('first'));
		this.$selectedItems.removeClass('sel').hide();
		this.hide();
		this.field.$container.focus();
	},

	setFillers: function()
	{
		var totalFillers = this.$fillerItems.length,
			totalBlocks = this.$visibleBlockItems.length,
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

	onKeyDown: function(event)
	{
		// Ignore if meta key is down
		if (event.metaKey) return;

		// Ignore if the modal body doesn't have focus
		if (event.target != this.$body[0]) return;

		switch (event.keyCode)
		{
			case blx.SPACE_KEY:
			case blx.RETURN_KEY:
				event.preventDefault();
				if (this.$selectedItems.length)
					this.addSelectedBlocks();
				break;
			case blx.ESC_KEY:
				event.preventDefault();
				this.hide();
		}

	}

});


var _modal;

blx.getBlocksSelectModal = function()
{
	if (typeof _modal == 'undefined')
		_modal = new blx.ui.BlocksSelectModal();

	return _modal;
}


})(jQuery);
