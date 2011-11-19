(function($) {


var Dashboard = Base.extend({

	constructor: function()
	{
		this.dom = {};
		this._createTable();
		this._getWidgets();
		this.cols = [];

		$(window).on('resizeWidth.dashboard', $.proxy(this, 'setCols'));
		setTimeout($.proxy(this, 'setCols'), 1);

		this.drag = new blx.ui.Sort(this.dom.table, {
			helper: function($draggeeHelper)
				{
					return $draggeeHelper.addClass('dragging');
				},
			insertion: $.proxy(function()
				{
					var div = document.createElement('div');
					div.className = 'widget';
					div.style.height = this.drag.$draggees.height()+'px';
					return div;
				}, this)
		});
		this.drag.addItems(this.$widgets);
	},

	_createTable: function()
	{
		this.dom.table = document.createElement('table');
		this.dom.table.className = 'widgets'
		document.getElementById('main').appendChild(this.dom.table);

		this.dom.tr = document.createElement('tr');
		this.dom.table.appendChild(this.dom.tr);
	},

	_getWidgets: function()
	{
		this.$widgets = $('.widget');

		var widgets = [];

		this.$widgets.each(function() {
			widgets.push($(this));
		});

		this.widgets = widgets;
	},

	setCols: function(event)
	{
		var animate = !!event;

		var totalWidth = blx.windowWidth - Dashboard.gutterWidth,
			totalCols = Math.floor(totalWidth / (Dashboard.minColWidth + Dashboard.gutterWidth));

		if (this.totalCols !== (this.totalCols = totalCols))
		{
			// -------------------------------------------
			//  Record the old widget offsets
			// -------------------------------------------

			if (animate)
			{
				var oldWidgetOffsets = [];

				for (var i = 0; i < this.widgets.length; i++)
				{
					var $widget = this.widgets[i];
					oldWidgetOffsets[i] = $widget.offset();
				}
			}

			// -------------------------------------------
			//  Create the new columns
			// -------------------------------------------

			var oldCols = this.cols;
			this.colWidth = 100 / this.totalCols;
			this.cols = [];

			for (var c = 0; c < totalCols; c++)
			{
				this.cols[c] = new Dashboard.Col(c);
			}

			// -------------------------------------------
			//  Remove the old columns
			// -------------------------------------------

			for (var c in oldCols)
			{
				oldCols[c].remove();
			}

			// -------------------------------------------
			//  Put them in their new places
			// -------------------------------------------

			for (var i = 0; i < this.widgets.length; i++)
			{
				var $widget = this.widgets[i],
					shortestCol = this._getShortestCol();

				shortestCol.addWidget($widget[0]);

				if (animate)
				{
					// clear any current animations
					$widget.stop();

					// get the new settled offset
					$widget.css('position', 'static');
					var settledOffset = $widget.offset();

					// put it back where it was
					$widget.css({
						position: 'relative',
						top: oldWidgetOffsets[i].top - settledOffset.top,
						left: oldWidgetOffsets[i].left - settledOffset.left
					});

					// animate it into place
					$widget.animate({top: 0, left: 0});
				}
			}
		}
	},

	_getShortestCol: function()
	{
		var shortestCol;

		for (c in this.cols)
		{
			if (typeof shortestCol == 'undefined' || this.cols[c].height < shortestCol.height)
			{
				shortestCol = this.cols[c];
			}
		}

		return shortestCol;
	}
},
{
	gutterWidth: 20,
	minColWidth: 280
});


Dashboard.Col = Base.extend({

	constructor: function(index)
	{
		this.index = index;
		this.dom = {};
		this.dom.td = document.createElement('td');
		this.dom.td.className = 'col';
		dashboard.dom.tr.appendChild(this.dom.td);

		this.dom.td.style.width = dashboard.colWidth+'%';

		this.height = 0;
	},

	addWidget: function(widget)
	{
		this.dom.td.appendChild(widget);
		this.height += $(widget).outerHeight();
	},

	getWidth: function()
	{
		return $(this.dom.td).width();
	},

	getLeftPos: function()
	{
		return $(this.dom.td).offset().left;
	},

	remove: function()
	{
		$(this.dom.td).remove();
	}

});


window.dashboard = new Dashboard();


})(jQuery);
