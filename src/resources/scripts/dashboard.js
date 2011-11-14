(function($) {


var Dashboard = Base.extend({

	constructor: function() {
		this.widgets = [];
		var $widgets = $('.widget');
		for (var w = 0; w < $widgets.length; w++) {
			this.widgets.push(new Dashboard.Widget($widgets[w]));
		}

		this.cols = [];

		$window.resize($.proxy(this, '_updateMainWidth'));
		this._updateMainWidth(false);
	},

	_updateMainWidth: function(animate) {
		if (this.mainWidth !== (this.mainWidth = $main.width())) {
			this._setCols(animate !== false);
		}
	},

	_setCols: function(animate) {
		var totalCols = Math.floor((this.mainWidth) / (Dashboard.gutterWidth + Dashboard.minColWidth)),
			newColWidth = Math.floor(((this.mainWidth) / totalCols) - Dashboard.gutterWidth);

		if (this.totalCols !== (this.totalCols = totalCols)) {

			// -------------------------------------------
			//  Cancel the current transitions
			// -------------------------------------------

			if (this.transition && this.transition.playing) {
				this.transition.stop();
			}

			// -------------------------------------------
			//  Create the new columns
			// -------------------------------------------

			var oldCols = this.cols;
			this.cols = [];

			for (var c = 0; c < totalCols; c++) {
				this.cols[c] = new Dashboard.Col(c);
				this.cols[c].setWidth(newColWidth);
			}

			// -------------------------------------------
			//  Record the old widget offsets
			// -------------------------------------------

			if (animate) {
				this.mainOffset = $main.offset();
				var oldWidgetPositions = this._getWidgetPositions();
			}

			// -------------------------------------------
			//  Put them in their new places
			// -------------------------------------------

			for (var w in this.widgets) {
				this.widgets[w].appendToCol(this._getShortestCol());
			}

			// -------------------------------------------
			//  Remove the old columns
			// -------------------------------------------

			for (var c in oldCols) {
				oldCols[c].remove();
			}

			// -------------------------------------------
			//  Animate the widgets into place
			// -------------------------------------------

			if (animate) {
				var targetWidgetPositions = this._getWidgetPositions();

				var widgetTransitions = [];

				for (var w in this.widgets) {
					var widget = this.widgets[w];

					widget.$elem.css({
						position: 'absolute',
						top: oldWidgetPositions[w].top,
						left: oldWidgetPositions[w].left,
						width: this.colWidth+'px'
					});

					widgetTransitions[w] = new blx.Transition(widget.$elem, {
						top: targetWidgetPositions[w].top,
						left: targetWidgetPositions[w].left,
						width: newColWidth
					}, {
						inBatch: true
					});
				}

				this.transition = new blx.BatchTransition(widgetTransitions, {
					onFinish: $.proxy(function() {
						for (var w in this.widgets) {
							this.widgets[w].$elem.css({
								position: 'relative',
								top: '',
								left: '',
								width: ''
							});
						}
					}, this)
				});
			}
		}
		else {

			// -------------------------------------------
			//  Update the column widths
			// -------------------------------------------

			for (var c in this.cols) {
				this.cols[c].setWidth(newColWidth);
			}

			// -------------------------------------------
			//  Update the transitions
			// -------------------------------------------

			if (this.transition && this.transition.playing) {
				for (var w in this.widgets) {
					var widget = this.widgets[w];
					this.transition.transitions[w].targets.left = widget.col.getLeftPos();
					this.transition.transitions[w].targets.width = newColWidth;
				}
			}
		}

		this.colWidth = newColWidth;
	},

	_getShortestCol: function() {
		var shortestCol,
			shortestColHeight;

		for (c in this.cols) {
			var colHeight = this.cols[c].getHeight();

			if (typeof shortestCol == 'undefined' || colHeight < shortestColHeight) {
				shortestCol = this.cols[c];
				shortestColHeight = colHeight;
			}
		}

		return shortestCol;
	},

	_getWidgetPositions: function() {
		var positions = [];

		for (var w in this.widgets) {
			var widget = this.widgets[w],
				offset = widget.$elem.offset();

			positions[w] = {
				top: offset.top - this.mainOffset.top,
				left: offset.left - this.mainOffset.left
			};
		}

		return positions;
	}
},
{
	gutterWidth: 40,
	minColWidth: 280
});


Dashboard.Col = Base.extend({

	constructor: function(index) {
		this.index = index;
		this.$elem = $('<div class="col" />').appendTo($main);
	},

	setWidth: function(width) {
		this.width = width;
		this.$elem.width(width);
	},

	getHeight: function() {
		return this.$elem.height();
	},

	getLeftPos: function() {
		return (this.width * this.index) + (Dashboard.gutterWidth * (this.index));
	},

	remove: function() {
		this.$elem.remove();
	}

});


Dashboard.Widget = Base.extend({

	constructor: function(elem) {
		this.$elem = $(elem);
	},

	appendToCol: function(col) {
		this.col = col;
		this.$elem.appendTo(col.$elem);
	}

});


dashboard = new Dashboard();


})(jQuery);
