(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Blocks Select Modal
 */
blx.ui.BlocksSelectModal = blx.ui.Modal.extend({

	$addBtn: null,
	$cancelBtn: null,

	init: function()
	{
		this.base('#blocksselect');

		this.$addBtn = this.$footBtns.filter('.add:first');
		this.$cancelBtn = this.$footBtns.filter('.cancel:first');

		this.addListener(this.$addBtn, 'click', 'addSelectedBlocks');
		this.addListener(this.$cancelBtn, 'click', 'hide');
	}

});


blx.blocksSelectModal = new blx.ui.BlocksSelectModal();


})(jQuery);
