(function($) {


/**
 * Modal
 */
b.ui.Modal = b.Base.extend({

	$container: null,
	$shade: null,
	$header: null,
	$body: null,
	$scrollpane: null,
	$footer: null,
	$footerBtns: null,
	$submitBtn: null,

	_headerHeight: null,
	_footerHeight: null,

	visible: null,
	focussed: null,
	zIndex: null,

	dragger: null,

	init: function(container, settings)
	{
		// Param mapping
		if (!settings && b.utils.isObject(container))
		{
			// (settings)
			settings = container;
			items = null;
		}

		this.setSettings(settings, b.ui.Modal.defaults);

		if (container)
			this.setContainer(container);

		this.visible = false;
		this.focussed = false;

		b.ui.Modal.instances.push(this);
	},

	setContainer: function(container)
	{
		this.$container = $(container);

		// Is this already a modal?
		if (this.$container.data('modal'))
		{
			b.log('Double-instantiating a modal on an element');
			this.$container.data('modal').destroy();
		}

		this.$header = this.$container.find('.pane-head:first');
		this.$body = this.$container.find('.pane-body:first');
		this.$scrollpane = this.$body.children('.scrollpane:first');
		this.$footer = this.$container.find('.pane-foot:first');
		this.$footerBtns = this.$footer.find('.btn');
		this.$submitBtn = this.$footerBtns.filter('.submit:first');
		this.$closeBtn = this.$footerBtns.filter('.close:first');

		if (this.settings.draggable)
		{
			var $dragHandles = this.$header.add(this.$footer);
			if ($dragHandles.length)
			{
				this.dragger = new b.ui.DragMove(this.$container, {
					handle: $dragHandles
				});
			}
		}

		if (this.zIndex)
			this.setZIndex(this.zIndex);

		this.addListener(this.$container, 'keydown', 'onKeyDown');
		this.addListener(this.$closeBtn, 'click', 'hide');
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
		if (b.ui.Modal.focussedModal)
			b.ui.Modal.focussedModal.blur();

		// Add focus to this one
		this.$container.addClass('focussed');
		this.focussed = true;
		b.ui.Modal.focussedModal = this;

		if (this.$shade)
			this.$shade.hide();

		// Put this at the end of the list of visible modals
		b.utils.removeFromArray(this, b.ui.Modal.visibleModals);
		b.ui.Modal.visibleModals.push(this);

		// Set z-index's appropriately
		for (var i = 0; i < b.ui.Modal.visibleModals.length; i++)
		{
			var zIndex = i + 1;
			b.ui.Modal.visibleModals[i].setZIndex(zIndex);
		}
	},

	blur: function()
	{
		this.$container.removeClass('focussed');
		this.focussed = false;
		b.ui.Modal.focussedModal = null;

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
			b.utils.removeFromArray(this, b.ui.Modal.visibleModals);

			// Focus the next one up
			if (b.ui.Modal.visibleModals.length)
				b.ui.Modal.visibleModals[b.ui.Modal.visibleModals.length-1].focus();
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

		var viewportWidth = b.$document.width(),
			viewportHeight = b.$document.height(),
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
			bodyScrollTop = b.$body.scrollTop(),
			topClearance = elemOffset.top - bodyScrollTop,
			modalHeight = this.getHeight();

		if (modalHeight < topClearance + b.navHeight + b.ui.Modal.relativeElemPadding*2)
			var top = elemOffset.top - modalHeight - b.ui.Modal.relativeElemPadding;
		else
			var top = elemOffset.top + $elem.height() + b.ui.Modal.relativeElemPadding;

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
		if (event.target.nodeName != 'TEXTAREA' && event.keyCode == b.RETURN_KEY)
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
