(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Modal
 */
blx.ui.Modal = blx.Base.extend({

	$container: null,
	$head: null,
	$body: null,
	$foot: null,
	$footBtns: null,

	visible: null,
	dragger: null,

	init: function(container, settings)
	{
		// Param mapping
		if (!settings && blx.utils.isObject(container))
		{
			// (settings)
			settings = container;
			items = null;
		}

		this.setSettings(settings, blx.ui.Modal.defaults);

		if (container)
			this.setContainer(container);

		this.visible = false;
	},

	setContainer: function(container)
	{
		this.$container = $(container);

		// Is this already a modal?
		if (this.$container.data('modal'))
		{
			blx.log('Double-instantiating a modal on an element');
			this.$container.data('modal').destroy();
		}

		this.$head = this.$container.find('.head:first');
		this.$body = this.$container.find('.body:first');
		this.$foot = this.$container.find('.foot:first');
		this.$footBtns = this.$foot.find('.btn');

		if (this.settings.draggable)
		{
			var $dragHandles = this.$head.add(this.$foot);
			if ($dragHandles.length)
			{
				this.dragger = new blx.ui.DragMove(this.$container, {
					handle: $dragHandles
				});
			}
		}
	},

	show: function()
	{
		if (this.$container)
			this.$container.fadeIn('fast');

		this.visible = true;
	},

	hide: function()
	{
		if (this.$container)
			this.$container.fadeOut('fast');

		this.visible = false;
	},

	getHeight: function()
	{
		if (!this.$container)
			throw 'Attempted to get the height of a modal whose container has not been set.';

		if (!this.visible)
			this.$container.show();

		var height = this.$container.height();

		if (!this.visible)
			this.$container.hide();

		return height;
	},

	positionRelativeTo: function(elem)
	{
		if (!this.$container)
			throw 'Attempted to position a modal whose container has not been set.';

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
	relativeElemPadding: 8,
	defaults: {
		draggable: true
	}
});


})(jQuery);
