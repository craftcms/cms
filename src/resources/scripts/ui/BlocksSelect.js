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

		this.$items = this.$container.find('li');
		this.$addItem = this.$items.filter('.add:first');
		this.$fillerItems = this.$items.filter('.filler');
		this.$blockItems = this.$items.not(this.$addItem).not(this.$fillerItems);

		this.$addBtn = this.$addItem.find('a');
		this.addListener(this.$addBtn, 'click', 'showModal');
	},

	showModal: function()
	{
		blx.blocksSelectModal.positionRelativeTo(this.$container);
		blx.blocksSelectModal.show();
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
