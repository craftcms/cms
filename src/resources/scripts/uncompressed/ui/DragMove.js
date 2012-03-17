(function($) {


/**
 * Drag Move
 */
b.ui.DragMove = b.ui.DragCore.extend({

	onDrag: function(items, settings)
	{
		this.$targetItem.css({
			left: this.mouseX - this.targetItemMouseDiffX,
			top:  this.mouseY - this.targetItemMouseDiffY
		});
	}

});


})(jQuery);
