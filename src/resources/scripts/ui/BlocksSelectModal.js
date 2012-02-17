(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Blocks Select Modal
 */
blx.ui.BlocksSelectModal = blx.ui.Modal.extend({

	$addBtn: null,
	$cancelBtn: null,

	$items: null,
	$addItem: null,
	$fillerItems: null,
	$blockItems: null,

	select: null,

	init: function()
	{
		this.base('#blocksselect');

		this.$addBtn = this.$footBtns.filter('.add:first');
		this.$cancelBtn = this.$footBtns.filter('.cancel:first');

		this.addListener(this.$addBtn, 'click', 'addSelectedBlocks');
		this.addListener(this.$cancelBtn, 'click', 'hide');

		this.$items = this.$container.find('li');
		this.$addItem = this.$items.filter('.add:first');
		this.$fillerItems = this.$items.filter('.filler');
		this.$blockItems = this.$items.not(this.$addItem).not(this.$fillerItems);
		console.log(this.$blockItems);

		this.select = new blx.ui.Select(this.$body, this.$blockItems, {
			multi: true,
			multiDblClick: true
		});
	},

	addSelectedBlocks: function()
	{
		
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
