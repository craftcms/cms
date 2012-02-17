(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Select
 */
blx.ui.Select = blx.Base.extend({

	$container: null,
	$scrollpane: null,
	$items: null,

	totalSelected: null,

	mousedownX: null,
	mousedownY: null,
	mouseUpTimeout: null,
	mouseUpTimeoutDuration: null,
	callbackTimeout: null,

	$first: null,
	first: null,
	$last: null,
	last: null,

	/**
	 * Init
	 */
	init: function(container, items, settings) {

		this.$container = $(container);

		// Is this already a switch?
		if (this.$container.data('select'))
		{
			blx.log('Double-instantiating a select on an element');
			this.$container.data('select').destroy();
		}

		this.$container.data('select', this);

		this.setSettings(settings, blx.ui.Select.defaults);
		this.mouseUpTimeoutDuration = (this.settings.multiDblClick ? 300 : 0);

		this.$scrollpane = $('.scrollpane:first', this.$container);
		this.$items = $();
		this.addItems(items);

		// --------------------------------------------------------------------

		this.addListener(this.$container, 'click', function(event) {
			if (this.ignoreClick) {
				this.ignoreClick = false;
			} else {
				// deselect all items on container click
				this.deselectAll(true);
			}
		});

		// --------------------------------------------------------------------

		this.$container.attr('tabindex', '0');
		blx.utils.preventOutlineOnMouseFocus(this.$container);

		this.addListener(this.$container, 'keydown', 'onKeyDown');

	},

	// --------------------------------------------------------------------

	/**
	 * Get Item Index
	 */
	getItemIndex: function($item) {
		return this.$items.index($item[0]);
	},

	/**
	 * Is Selected?
	 */
	isSelected: function($item) {
		return $item.hasClass('sel');
	},

	/**
	 * Select Item
	 */
	selectItem: function($item) {
		if (! this.settings.multi) {
			this.deselectAll();
		}

		$item.addClass('sel');

		this.$first = this.$last = $item;
		this.first = this.last = this.getItemIndex($item);

		this.totalSelected++;

		this.setCallbackTimeout();
	},

	/**
	 * Select Range
	 */
	selectRange: function($item) {
		if (! this.settings.multi) {
			return this.selectItem($item);
		}

		this.deselectAll();

		this.$last = $item;
		this.last = this.getItemIndex($item);

		// prepare params for $.slice()
		if (this.first < this.last) {
			var sliceFrom = this.first,
				sliceTo = this.last + 1;
		} else { 
			var sliceFrom = this.last,
				sliceTo = this.first + 1;
		}

		this.$items.slice(sliceFrom, sliceTo).addClass('sel');

		this.totalSelected = sliceTo - sliceFrom;

		this.setCallbackTimeout();
	},

	/**
	 * Deselect Item
	 */
	deselectItem: function($item) {
		$item.removeClass('sel');

		var index = this.getItemIndex($item);
		if (this.first === index) this.$first = this.first = null;
		if (this.last === index) this.$last = this.last = null;

		this.totalSelected--;

		this.setCallbackTimeout();
	},

	/**
	 * Deselect All
	 */
	deselectAll: function(clearFirst) {
		this.$items.removeClass('sel');

		if (clearFirst) {
			this.$first = this.first = this.$last = this.last = null;
		}

		this.totalSelected = 0;

		this.setCallbackTimeout();
	},

	/**
	 * Deselect Others
	 */
	deselectOthers: function($item) {
		this.deselectAll();
		this.selectItem($item);
	},

	/**
	 * Toggle Item
	 */
	toggleItem: function($item) {
		if (! this.isSelected($item)) {
			this.selectItem($item);
		} else {
			this.deselectItem($item);
		}
	},

	// --------------------------------------------------------------------

	clearMouseUpTimeout: function() {
		clearTimeout(this.mouseUpTimeout);
	},

	/**
	 * On Mouse Down
	 */
	onMouseDown: function(event) {
		// ignore right clicks
		if (event.button == 2) return;

		this.mousedownX = event.pageX;
		this.mousedownY = event.pageY;

		var $item = $(event.currentTarget);

		if (event.metaKey) {
			this.toggleItem($item);
		}
		else if (this.first !== null && event.shiftKey) {
			this.selectRange($item);
		}
		else if (! this.isSelected($item)) {
			this.deselectAll();
			this.selectItem($item);
		}

		this.$container.focus();
	},

	/**
	 * On Mouse Up
	 */
	onMouseUp: function(event) {
		// ignore right clicks
		if (event.button == 2) return;

		var $item = $(event.currentTarget);

		// was this a click?
		if (! event.metaKey && ! event.shiftKey && blx.utils.getDist(this.mousedownX, this.mousedownY, event.pageX, event.pageY) < 1) {
			this.selectItem($item);

			// wait a moment before deselecting others
			// to give the user a chance to double-click
			this.clearMouseUpTimeout()
			this.mouseUpTimeout = setTimeout($.proxy(function() {
				this.deselectOthers($item);
			}, this), this.mouseUpTimeoutDuration);
		}
	},

	// --------------------------------------------------------------------

	/**
	 * On Key Down
	 */
	onKeyDown: function(event) {
		// ignore if meta key is down
		if (event.metaKey) return;

		// ignore if this pane doesn't have focus
		if (event.target != this.$container[0]) return;

		// ignore if there are no items
		if (! this.$items.length) return;

		var anchor = event.shiftKey ? this.last : this.first;

		switch (event.keyCode) {
			case blx.DOWN_KEY:
				event.preventDefault();

				if (this.first === null) {
					// select the first item
					$item = $(this.$items[0]);
				}
				else if (this.$items.length >= anchor + 2) {
					// select the item after the last selected item
					$item = $(this.$items[anchor+1]);
				}

				break;

			case blx.UP_KEY:
				event.preventDefault();

				if (this.first === null) {
					// select the last item
					$item = $(this.$items[this.$items.length-1]);
				}
				else if (anchor > 0) {
					$item = $(this.$items[anchor-1]);
				}

				break;

			case blx.ESC_KEY:
				this.deselectAll(true);

			default: return;
		};

		if (! $item || ! $item.length) return;

		// -------------------------------------------
		//  Scroll to the item
		// -------------------------------------------

		Assets.scrollContainerToElement(this.$scrollpane, $item);

		// -------------------------------------------
		//  Select the item
		// -------------------------------------------

		if (this.first !== null && event.shiftKey) {
			this.selectRange($item);
		}
		else {
			this.deselectAll();
			this.selectItem($item);
		}
	},

	// --------------------------------------------------------------------

	/**
	 * Get Total Selected
	 */
	getTotalSelected: function() {
		return this.totalSelected;
	},

	/**
	 * Add Items
	 */
	addItems: function(items) {
		var $items = $(items);

		// make a record of it
		this.$items = this.$items.add($items);

		// bind listeners
		this.addListener($items, 'mousedown', 'onMouseDown');
		this.addListener($items, 'mouseup', 'onMouseUp');

		this.addListener($items, 'click', function(event) {
			this.ignoreClick = true;
		});

		this.totalSelected += $items.filter('.sel').length;

		this.updateIndexes();
	},

	/**
	 * Remove Items
	 */
	removeItems: function(items) {
		var $items = $(items);

		for (var i = 0; i < $items.length; i++) {
			var $item = $($items[i]);

			// deselect it first
			if (this.isSelected($item)) {
				this.deselectItem($item);
			}
		};

		// unbind all events
		this.removeAllListeners($items);

		// remove the record of it
		this.$items = this.$items.not($items);

		this.updateIndexes();
	},

	/**
	 * Reset
	 */
	reset: function() {
		// unbind the events
		this.removeAllListeners(this.$items);

		// reset local vars
		this.$items = $();
		this.totalSelected = 0;
		this.$first = this.first = this.$last = this.last = null;

		// clear timeout
		this.clearCallbackTimeout();
	},

	/**
	 * Destroy
	 */
	destroy: function() {
		this.base();

		// clear timeout
		this.clearCallbackTimeout();
	},

	/**
	 * Update First/Last indexes
	 */
	updateIndexes: function() {
		if (this.first !== null) {
			this.first = this.getItemIndex(this.$first);
			this.last = this.getItemIndex(this.$last);
		}
	},

	// --------------------------------------------------------------------

	/**
	 * Clear Callback Timeout
	 */
	clearCallbackTimeout: function() {
		if (this.settings.onSelectionChange) {
			clearTimeout(this.callbackTimeout);
		}
	},

	/**
	 * Set Callback Timeout
	 */
	setCallbackTimeout: function() {
		if (this.settings.onSelectionChange) {
			// clear the last one
			this.clearCallbackTimeout();

			this.callbackTimeout = setTimeout($.proxy(function() {
				this.callbackTimeout = null;
				this.settings.onSelectionChange.call();
			}, this), 300);
		}
	},

	/**
	 * Get Selected Items
	 */
	getSelectedItems: function() {
		return this.$items.filter('.sel');
	}

}, {
	defaults: {
		multiDblClick: false,
		multi: false,
		onSelectionChange: function(){}
	}
});


})(jQuery);
