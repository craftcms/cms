(function($) {

/**
 * Modal
 */
Blocks.ui.Modal = Blocks.Base.extend({

	$container: null,
	$header: null,
	$body: null,
	$scrollpane: null,
	$footer: null,
	$footerBtns: null,
	$submitBtn: null,

	_headerHeight: null,
	_footerHeight: null,

	visible: false,

	dragger: null,

	init: function(container, settings)
	{
		// Param mapping
		if (!settings && Blocks.isObject(container))
		{
			// (settings)
			settings = container;
			items = null;
		}

		this.setSettings(settings, Blocks.ui.Modal.defaults);

		if (container)
		{
			this.setContainer(container);
			this.show();
		}

		Blocks.ui.Modal.instances.push(this);
	},

	setContainer: function(container)
	{
		this.$container = $(container);

		// Is this already a modal?
		if (this.$container.data('modal'))
		{
			Blocks.log('Double-instantiating a modal on an element');
			this.$container.data('modal').destroy();
		}

		this.$container.data('modal', this);

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
				this.dragger = new Blocks.ui.DragMove(this.$container, {
					handle: this.$container
				});
			}
		}

		this.addListener(this.$container, 'keydown', 'onKeyDown');
		this.addListener(this.$closeBtn, 'click', 'hide');
	},

	show: function()
	{
		if (Blocks.ui.Modal.visibleModal)
			Blocks.ui.Modal.visibleModal.hide();

		if (this.$container)
		{
			this.$container.show();
			this.centerInViewport();
			this.$container.delay(50).fadeIn();
			this.addListener(Blocks.$window, 'resize', 'centerInViewport');
		}

		this.visible = true;
		Blocks.ui.Modal.visibleModal = this;
		Blocks.ui.Modal.$shade.fadeIn(50);
		this.addListener(Blocks.ui.Modal.$shade, 'click', 'hide');
	},

	hide: function()
	{
		if (this.$container)
		{
			this.$container.fadeOut('fast');
			this.removeListener(Blocks.$window, 'resize');
		}

		this.visible = false;
		Blocks.ui.Modal.visibleModal = null;
		Blocks.ui.Modal.$shade.fadeOut('fast');
		this.removeListener(Blocks.ui.Modal.$shade, 'click');
	},

	getHeight: function()
	{
		if (!this.$container)
			throw Blocks.t('Attempted to get the height of a modal whose container has not been set.');

		if (!this.visible)
			this.$container.show();

		var height = this.$container.outerHeight();

		if (!this.visible)
			this.$container.hide();

		return height;
	},

	getWidth: function()
	{
		if (!this.$container)
			throw Blocks.t('Attempted to get the width of a modal whose container has not been set.');

		if (!this.visible)
			this.$container.show();

		var width = this.$container.outerWidth();

		if (!this.visible)
			this.$container.hide();

		return width;
	},

	centerInViewport: function()
	{
		if (!this.$container)
			throw Blocks.t('Attempted to position a modal whose container has not been set.');

		var viewportWidth = Blocks.$window.width(),
			viewportHeight = Blocks.$window.height(),
			modalWidth = this.getWidth(),
			modalHeight = this.getHeight(),
			left = Math.round((viewportWidth - modalWidth) / 2),
			top = Math.round((viewportHeight - modalHeight) / 2);

		this.$container.css({
			top: top,
			left: left
		});
	},

	positionRelativeTo: function(elem)
	{
		if (!this.$container)
			throw Blocks.t('Attempted to position a modal whose container has not been set.');

		var $elem = $(elem),
			elemOffset = $elem.offset(),
			bodyScrollTop = Blocks.$body.scrollTop(),
			topClearance = elemOffset.top - bodyScrollTop,
			modalHeight = this.getHeight();

		if (modalHeight < topClearance + Blocks.navHeight + Blocks.ui.Modal.relativeElemPadding*2)
			var top = elemOffset.top - modalHeight - Blocks.ui.Modal.relativeElemPadding;
		else
			var top = elemOffset.top + $elem.height() + Blocks.ui.Modal.relativeElemPadding;

		this.$container.css({
			top: top,
			left: elemOffset.left
		});
	},

	onKeyDown: function(event)
	{
		if (event.target.nodeName != 'TEXTAREA' && event.keyCode == Blocks.RETURN_KEY)
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
	visibleModal: null,
	$shade: $('<div class="modal-shade"/>').appendTo(Blocks.$body)
});

})(jQuery);
