(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Drag Move
 */
blx.ui.DragMove = blx.ui.DragCore.extend({

	onDrag: function()
	{
		this.base();

		this.$targetItem.css({
			left: this.mouseX - this.targetItemMouseDiffX,
			top:  this.mouseY - this.targetItemMouseDiffY
		});
	}

});


})(jQuery);
