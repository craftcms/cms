(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Modal
 */
blx.ui.Modal = blx.Base.extend({

	$container: null,
	$shade: null,
	$head: null,
	$body: null,
	$foot: null,
	$footBtns: null,
	$submitBtn: null,

	visible: null,
	focussed: null,
	zIndex: null,

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
		this.focussed = false;

		blx.ui.Modal.instances.push(this);
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
		this.$submitBtn = this.$footBtns.filter('.submit:first');

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

		if (this.zIndex)
			this.setZIndex(this.zIndex);

		this.addListener(this.$container, 'keydown', 'onKeyDown');
	},

	setZIndex: function(zIndex)
	{
		this.zIndex = zIndex;

		if (this.$container)
			this.$container.css('zIndex', this.zIndex);
	},

	focus: function()
	{
		// Blur the currently focussed modal
		if (blx.ui.Modal.focussedModal)
			blx.ui.Modal.focussedModal.blur();

		// Add focus to this one
		this.$container.addClass('focussed');
		this.focussed = true;
		blx.ui.Modal.focussedModal = this;

		this.$shade.hide();

		// Put this at the end of the list of visible modals
		blx.utils.removeFromArray(this, blx.ui.Modal.visibleModals);
		blx.ui.Modal.visibleModals.push(this);

		// Set z-index's appropriately
		for (var i = 0; i < blx.ui.Modal.visibleModals.length; i++)
		{
			var zIndex = i + 1;
			blx.ui.Modal.visibleModals[i].setZIndex(zIndex);
		}
	},

	blur: function()
	{
		this.$container.removeClass('focussed');
		this.focussed = false;
		blx.ui.Modal.focussedModal = null;

		if (!this.$shade)
		{
			this.$shade = $(document.createElement('div'));
			this.$shade.addClass('shade');
			this.$shade.appendTo(this.$container);

			this.addListener(this.$container, 'mousedown', 'onMouseDown');
		}

		this.$shade.show();
	},

	show: function()
	{
		if (this.$container)
		{
			this.$container.fadeIn('fast');
			this.focus();
		}

		this.visible = true;
	},

	hide: function()
	{
		if (this.$container)
		{
			this.$container.fadeOut('fast');
			this.blur();
			blx.utils.removeFromArray(this, blx.ui.Modal.visibleModals);

			// Focus the next one up
			if (blx.ui.Modal.visibleModals.length)
				blx.ui.Modal.visibleModals[blx.ui.Modal.visibleModals.length-1].focus();
		}

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

	getWidth: function()
	{
		if (!this.$container)
			throw 'Attempted to get the width of a modal whose container has not been set.';

		if (!this.visible)
			this.$container.show();

		var width = this.$container.width();

		if (!this.visible)
			this.$container.hide();

		return width;
	},

	centerInViewport: function()
	{
		if (!this.$container)
			throw 'Attempted to position a modal whose container has not been set.';

		var viewportWidth = blx.$document.width(),
			viewportHeight = blx.$document.height(),
			modalWidth = this.getWidth(),
			modalHeight = this.getHeight(),
			left = (viewportWidth - modalWidth) / 2,
			top = (viewportHeight - modalHeight) / 2;

		this.$container.css({
			top: top,
			left: left
		});
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

	onMouseDown: function(event)
	{
		if (!this.focussed)
			this.focus();
	},

	onKeyDown: function(event)
	{
		if (event.target.nodeName != 'TEXTAREA' && event.keyCode == blx.RETURN_KEY)
		{
			this.$submitBtn.click();
		}
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
	},
	instances: [],
	visibleModals: [],
	focussedModal: null
});


})(jQuery);
