(function($) {

/**
 * HUD
 */
Blocks.ui.HUD = Blocks.Base.extend({

	/**
	 * Constructor
	 */
	init: function(trigger, contents, settings) {

		this.$trigger = $(trigger);
		this.$contents = $(contents);
		this.settings = $.extend({}, Blocks.ui.HUD.defaults, settings);

		this.showing = false;

		this.$hud = $('<div class="hud" />').appendTo(document.body).hide();
		this.$tip = $('<div class="tip" />').appendTo(this.$hud);

		this.$contents.appendTo(this.$hud);

		this.addListener(this.$trigger, 'click', 'show');
	},

	/**
	 * Show
	 */
	show: function(event) {

		if (this.showing) return;

		if (Blocks.ui.HUD.active)
			Blocks.ui.HUD.active.hide();

		this.$hud.show();

		// -------------------------------------------
		//  Get all relevant dimensions, lengths, etc
		// -------------------------------------------

		this.windowWidth = $(window).width();
		this.windowHeight = $(window).height();

		this.windowScrollLeft = $(window).scrollLeft();
		this.windowScrollTop = $(window).scrollTop();

		// get the trigger element's dimensions
		this.triggerWidth = this.$trigger.width() + parseInt(this.$trigger.css('paddingLeft')) + parseInt(this.$trigger.css('borderLeftWidth')) + parseInt(this.$trigger.css('paddingRight')) + parseInt(this.$trigger.css('borderRightWidth'));
		this.triggerHeight = this.$trigger.height() + parseInt(this.$trigger.css('paddingTop')) + parseInt(this.$trigger.css('borderTopWidth')) + parseInt(this.$trigger.css('paddingBottom')) + parseInt(this.$trigger.css('borderBottomWidth'));

		// get the offsets for each side of the trigger element
		this.triggerOffset = this.$trigger.offset();
		this.triggerOffsetRight = this.triggerOffset.left + this.triggerWidth;
		this.triggerOffsetBottom = this.triggerOffset.top + this.triggerHeight;
		this.triggerOffsetLeft = this.triggerOffset.left;
		this.triggerOffsetTop = this.triggerOffset.top;

		// get the HUD dimensions
		this.width = this.$hud.width();
		this.height = this.$hud.height();

		// get the minimum horizontal/vertical clearance needed to fit the HUD
		this.minHorizontalClearance = this.width + this.settings.triggerSpacing + this.settings.windowSpacing;
		this.minVerticalClearance = this.height + this.settings.triggerSpacing + this.settings.windowSpacing;

		// find the actual available right/bottom/left/top clearances
		this.rightClearance = this.windowWidth + this.windowScrollLeft - this.triggerOffsetRight;
		this.bottomClearance = this.windowHeight + this.windowScrollTop - this.triggerOffsetBottom;
		this.leftClearance = this.triggerOffsetLeft - this.windowScrollLeft;
		this.topClearance = this.triggerOffsetTop - this.windowScrollTop;

		// -------------------------------------------
		//  Where are we putting it?
		//   - Ideally, we'll be able to find a place to put this where it's not overlapping the trigger at all.
		//     If we can't find that, either put it to the right or below the trigger, depending on which has the most room.
		// -------------------------------------------

		// below?
		if (this.bottomClearance >= this.minVerticalClearance) {
			var top = this.triggerOffsetBottom + this.settings.triggerSpacing;
			this.$hud.css('top', top);
			this._setLeftPos();
			this._setTipClass('top');
		}
		// to the right?
		else if (this.rightClearance >= this.minHorizontalClearance) {
			var left = this.triggerOffsetRight + this.settings.triggerSpacing;
			this.$hud.css('left', left);
			this._setTopPos();
			this._setTipClass('left');
		}
		// to the left?
		else if (this.leftClearance >= this.minHorizontalClearance) {
			var left = this.triggerOffsetLeft - (this.width + this.settings.triggerSpacing);
			this.$hud.css('left', left);
			this._setTopPos();
			this._setTipClass('right');
		}
		// above?
		else if (this.topClearance >= this.minVerticalClearance) {
			var top = this.triggerOffsetTop - (this.height + this.settings.triggerSpacing);
			this.$hud.css('top', top);
			this._setLeftPos();
			this._setTipClass('bottom');
		}
		// ok, which one comes the closest -- right or bottom?
		else {
			var rightClearanceDiff = this.minHorizontalClearance - this.rightClearance,
				bottomClearanceDiff = this.minVerticalClearance - this.bottomClearance;

			if (rightClearanceDiff >= bottomClearanceDiff) {
				var left = this.windowWidth - (this.width + this.settings.windowSpacing),
					minLeft = this.triggerOffsetLeft + this.settings.triggerSpacing;
				if (left < minLeft) left = minLeft;
				this.$hud.css('left', left);
				this._setTopPos();
				this._setTipClass('left');
			}
			else {
				var top = this.windowHeight - (this.height + this.settings.windowSpacing),
					minTop = this.triggerOffsetTop + this.settings.triggerSpacing;
				if (top < minTop) top = minTop;
				this.$hud.css('top', top);
				this._setLeftPos();
				this._setTipClass('top');
			}
		}

		if (event.stopPropagation)
			event.stopPropagation();

		this.addListener(Blocks.$body, 'click', 'hide');

		this.showing = true;
		Blocks.ui.HUD.active = this;

		// onShow callback
		this.settings.onShow();
	},

	/**
	 * Set Top
	 */
	_setTopPos: function() {
		var maxTop = (this.windowHeight + this.windowScrollTop) - (this.height + this.settings.windowSpacing),
			minTop = (this.windowScrollTop + this.settings.windowSpacing),

			triggerCenter = this.triggerOffsetTop + Math.round(this.triggerHeight / 2),
			top = triggerCenter - Math.round(this.height / 2);

		// adjust top position as needed
		if (top > maxTop) top = maxTop;
		if (top < minTop) top = minTop;

		this.$hud.css('top', top);

		// set the tip's top position
		var tipTop = (triggerCenter - top) - (this.settings.tipWidth / 2);
		this.$tip.css({ top: tipTop, left: '' });
	},

	/**
	 * Set Left
	 */
	_setLeftPos: function() {
		var maxLeft = (this.windowWidth + this.windowScrollLeft) - (this.width + this.settings.windowSpacing),
			minLeft = (this.windowScrollLeft + this.settings.windowSpacing),

			triggerCenter = this.triggerOffsetLeft + Math.round(this.triggerWidth / 2),
			left = triggerCenter - Math.round(this.width / 2);

		// adjust left position as needed
		if (left > maxLeft) left = maxLeft;
		if (left < minLeft) left = minLeft;

		this.$hud.css('left', left);

		// set the tip's left position
		var tipLeft = (triggerCenter - left) - (this.settings.tipWidth / 2);
		this.$tip.css({ left: tipLeft, top: '' });
	},

	/**
	 * Set Tip Class
	 */
	_setTipClass: function(c) {
		if (this.tipClass)
			this.$tip.removeClass(this.tipClass);

		this.tipClass = c;
		this.$tip.addClass(c);
	},

	/**
	 * Hide
	 */
	hide: function() {
		this.$hud.hide();
		this.showing = false;

		Blocks.ui.HUD.active = null;

		// onHide callback
		this.settings.onHide();
	}
});

Blocks.ui.HUD.defaults = {
	triggerSpacing: 7,
	windowSpacing: 20,
	tipWidth: 8,
	onShow: function(){},
	onHide: function(){}
};

})(jQuery);
