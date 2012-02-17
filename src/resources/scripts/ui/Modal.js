(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Modal
 */
blx.ui.Modal = blx.Base.extend({

	$container: null,
	$head: null,
	$foot: null,
	$footBtns: null,

	visible: null,
	dragger: null,

	init: function(container)
	{
		this.$container = $(container);

		// Is this already a modal?
		if (this.$container.data('modal'))
		{
			blx.log('Double-instantiating a modal on an element');
			this.$container.data('modal').destroy();
		}

		this.$head = this.$container.find('.head:first');
		this.$foot = this.$container.find('.foot:first');
		this.$footBtns = this.$foot.find('.btn');

		var $dragHandles = this.$head.add(this.$foot);
		if ($dragHandles.length)
		{
			this.dragger = new blx.ui.DragMove(this.$container, {
				handle: $dragHandles
			});
		}

		this.visible = false;
	},

	show: function()
	{
		this.$container.fadeIn('fast');
		this.visible = true;
	},

	hide: function()
	{
		this.$container.fadeOut('fast');
		this.visible = false;
	},

	getHeight: function()
	{
		if (!this.visible)
			this.$container.show();

		var height = this.$container.height();

		if (!this.visible)
			this.$container.hide();

		return height;
	},

	positionRelativeTo: function(elem)
	{
		var $elem = $(elem),
			elemOffset = $elem.offset(),
			bodyScrollTop = blx.$body.scrollTop(),
			topClearance = elemOffset.top - bodyScrollTop,
			modalHeight = this.getHeight();

		if (modalHeight < topClearance + blx.navHeight + blx.ui.Modal.relativeElemPadding*2)
			var top = elemOffset.top - modalHeight - blx.ui.Modal.relativeElemPadding;
		else
			var top = elemOffset.top + $elem.height() + blx.ui.Modal.relativeElemPadding;

		this.$container.css({
			top: top,
			left: elemOffset.left
		});
	},

	destroy: function()
	{
		this.base();

		if (this.dragger)
			this.dragger.destroy();
	}

}, {
	relativeElemPadding: 8
});


})(jQuery);
