(function($) {


var Dashboard = Base.extend({

	dom: {},
	widgets: [],
	$widgets: null,
	cols: [],
	showingSidebar: false,

	constructor: function()
	{
		this.dom.$container = $(document.getElementById('widgets'));
		this.dom.$sidebarBtn = $(document.getElementById('sidebar-btn'));
		this.dom.$sidebar = $(document.getElementById('sidebar'));

		this.getWidgets();

		$(window).on('resizeWidth.dashboard', $.proxy(this, 'onWindowResize'));
		setTimeout($.proxy(this, 'setCols'), 1);

		this.dom.$sidebarBtn.on('click.dashboard', $.proxy(this, 'toggleSidebar'));

		// set up the widget sorting
		this.dom.$widgetHandles = $('ul.widget-handles > li', this.dom.$sidebar);

		this.widgetSort = new blx.ui.DragSort(this.dom.$widgetHandles, {
			axis: 'y',
			helper: '<ul class="widget-handles dragging" />',
			onSortChange: $.proxy(this, 'onWidgetMove')
		});

		$('a.remove', this.dom.$widgetHandles).on('click', $.proxy(this, 'onWidgetRemove'))
	},

	getWidgets: function()
	{
		this.$widgets = $('.widget', this.dom.$container);

		for (var i = 0; i < this.$widgets.length; i++)
		{
			this.widgets.push($(this.$widgets[i]));
			this.widgets[i].css('zIndex', i+1);
		}
	},

	onWindowResize: function()
	{
		this.setCols(true);
	},

	setCols: function(animate, widgetOffsets)
	{
		var totalCols = Math.floor((this.dom.$container.width() + Dashboard.gutterWidth) / (Dashboard.minColWidth + Dashboard.gutterWidth));

		if (this.totalCols !== (this.totalCols = totalCols))
		{
			this.refreshCols(animate, widgetOffsets);
			return true;
		}

		return false;
	},

	refreshCols: function(animate, widgetOffsets)
	{
		// Record the old widget offsets and widths
		if (animate && typeof widgetOffsets == 'undefined')
		{
			widgetOffsets = this.getWidgetOffsets();
		}

		// Detach the widgets before we remove the columns so they keep their events
		for (var i = 0; i < this.widgets.length; i++)
		{
			this.widgets[i].detach();
		}

		// Remove the old columns
		for (var i = 0; i < this.cols.length; i++)
		{
			this.cols[i].remove();
		}

		// Create the new columns
		this.cols = [];
		this.colWidth = Math.floor(10000 / this.totalCols) / 100;

		for (var i = 0; i < this.totalCols; i++)
		{
			this.cols[i] = new Dashboard.Col(i);
		}

		// Place the widgets
		for (var i = 0; i < this.widgets.length; i++)
		{
			// add it to the shortest column
			var shortestCol = this.getShortestCol();
			shortestCol.addWidget(this.$widgets[i]);
		}

		this.relaxWidgets();

		// animate the widgets into place
		if (animate)
		{
			for (var i = 0; i < this.widgets.length; i++)
			{
				// clear any current animations
				this.widgets[i].stop();

				// get the new settled offset and width
				var settledOffset = this.widgets[i].offset(),
					settledWidth = this.widgets[i].width();

				// put it back where it was
				this.widgets[i].css({
					position: 'relative',
					top: widgetOffsets[i].top - settledOffset.top,
					left: widgetOffsets[i].left - settledOffset.left,
					width: widgetOffsets[i].width
				});

				var onComplete = (i == this.widgets.length-1) ? $.proxy(this, 'relaxWidgets') : null;

				this.widgets[i].animate({top: 0, left: 0, width: settledWidth}, onComplete);
			}
		}
	},

	relaxWidgets: function()
	{
		this.$widgets.css({position: 'static', width: 'auto'});
	},

	getWidgetOffsets: function()
	{
		this.relaxWidgets();

		var widgetOffsets = [];

		for (var i = 0; i < this.widgets.length; i++)
		{
			var $widget = this.widgets[i];
			widgetOffsets[i] = $widget.offset();
			widgetOffsets[i].width = $widget.width();
		}

		return widgetOffsets;
	},

	getShortestCol: function()
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
	},

	toggleSidebar: function()
	{
		// stop any current animations
		this.dom.$container.stop();
		this.dom.$sidebar.stop();

		if (!this.showingSidebar)
		{
			var targetContainerMargin = Dashboard.sidebarWidth + Dashboard.gutterWidth,
				targetSidebarPos = 10;

			this.dom.$sidebarBtn.addClass('sel');
		}
		else
		{
			var targetContainerMargin = 0,
				targetSidebarPos = -(Dashboard.sidebarWidth + 11);

			this.dom.$sidebarBtn.removeClass('sel');
		}

		// get the current widget offsets
		var widgetOffsets = this.getWidgetOffsets();

		// record the current container margin, and stage the target one
		var currentContainerMargin = this.dom.$container.css('marginRight');
		this.dom.$container.css('marginRight', targetContainerMargin);

		// set the new cols if necessary, otherwise just animate the margin
		if (!this.setCols(true, widgetOffsets))
		{
			// restore the current margin, and animate to the target
			this.dom.$container.css('marginRight', currentContainerMargin);
			this.dom.$container.animate({marginRight: targetContainerMargin});
		}

		// slide the sidebar
		this.dom.$sidebar.stop().animate({right: targetSidebarPos});

		// invert the showingSidebar state
		this.showingSidebar = !this.showingSidebar;
	},

	onWidgetMove: function()
	{
		this.moveWidget(this.widgetSort.draggeeStartIndex, this.widgetSort.draggeeIndex);
	},

	moveWidget: function(from, to)
	{
		// capture the widget that was sorted
		var widget = this.$widgets[from],
			handle = this.dom.$widgetHandles[from];

		// sort our internal widget array & jQuery object to match the new order
		this.$widgets.splice(from, 1);
		this.widgets.splice(from, 1);
		this.dom.$widgetHandles.splice(from, 1);
		this.$widgets.splice(to, 0, widget);
		this.widgets.splice(to, 0, $(widget));
		this.dom.$widgetHandles.splice(to, 0, handle);

		// update the columns
		this.refreshCols(true);
	},

	onWidgetRemove: function(event)
	{
		// figure out which widget to remove
		var $widgetHandle = $(event.currentTarget).parent().parent(),
			widgetIndex = $.inArray($widgetHandle[0], this.dom.$widgetHandles);

		console.log(widgetIndex);

		if (widgetIndex != -1)
			this.removeWidget(widgetIndex);
	},

	removeWidget: function(i)
	{
		var $widget = this.widgets[i],
			$handle = $(this.dom.$widgetHandles[i]),
			handleHeight = $handle.outerHeight(),
			containerOffset = this.dom.$container.offset(),
			widgetOffset = $widget.offset(),
			width = $widget.width();

		// remove it from our internal widget arrays
		this.$widgets.splice(i, 1);
		this.widgets.splice(i, 1);
		this.dom.$widgetHandles.splice(i, 1);

		// update the columns
		this.refreshCols(true);

		this.widgetSort.removeItems($handle);

		// remove the handle
		$handle.animate({opacity: 0, marginBottom: -handleHeight}, function() {
			$handle.remove();
		});

		// fade out the widget, and then remove it
		$widget.appendTo(this.dom.$container);
		$widget.css({
			position: 'absolute',
			zIndex: 0,
			top: widgetOffset.top - containerOffset.top,
			left: widgetOffset.left - containerOffset.left,
			width: width
		});
		$widget.fadeOut($.proxy(function() {
			$widget.remove();
		}, this));
	}
},
{
	gutterWidth: 20,
	minColWidth: 300,
	sidebarWidth: 200
});


Dashboard.Col = Base.extend({

	constructor: function(index)
	{
		this.index = index;
		this.dom = {};

		this.dom.outerDiv = document.createElement('div');
		this.dom.outerDiv.className = 'col';
		this.dom.outerDiv.style.width = dashboard.colWidth+'%';
		dashboard.dom.$container.append(this.dom.outerDiv);

		this.dom.innerDiv = document.createElement('div');
		this.dom.innerDiv.className = 'col-padding';
		this.dom.outerDiv.appendChild(this.dom.innerDiv);

		this.height = 0;
	},

	addWidget: function(widget)
	{
		this.dom.innerDiv.appendChild(widget);
		this.height += $(widget).outerHeight();
	},

	remove: function()
	{
		$(this.dom.outerDiv).remove();
	}

});


window.dashboard = new Dashboard();


})(jQuery);
