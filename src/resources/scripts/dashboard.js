(function($) {


var Dashboard = Base.extend({

	dom: {},
	widgets: [],
	$widgets: null,
	cols: [],
	$sidebarBtn: null,
	$sidebar: null,
	showingSidebar: false,

	constructor: function()
	{
		this.dom.$main = $(document.getElementById('main'));
		this.dom.$sidebarBtn = $(document.getElementById('sidebar-btn'));
		this.dom.$sidebar = $(document.getElementById('sidebar'));

		//this.createTable();
		this.getWidgets();

		$(window).on('resizeWidth.dashboard', $.proxy(this, 'onWindowResize'));
		setTimeout($.proxy(this, 'setCols'), 1);

		this.dom.$sidebarBtn.on('click.dashboard', $.proxy(this, 'toggleSidebar'));
	},

	createTable: function()
	{
		this.dom.table = document.createElement('table');
		this.dom.table.className = 'widgets'
		this.dom.$main.append(this.dom.table);

		this.dom.tr = document.createElement('tr');
		this.dom.table.appendChild(this.dom.tr);
	},

	getWidgets: function()
	{
		this.$widgets = $('.widget', this.dom.$main);

		for (var i = 0; i < this.$widgets.length; i++)
		{
			this.widgets.push($(this.$widgets[i]));
		}
	},

	onWindowResize: function()
	{
		this.setCols(true);
	},

	setCols: function(animate, widgetOffsets)
	{
		var totalCols = Math.floor((this.dom.$main.width() + Dashboard.gutterWidth) / (Dashboard.minColWidth + Dashboard.gutterWidth));

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
		this.dom.$main.stop();
		this.dom.$sidebar.stop();

		if (!this.showingSidebar)
		{
			var targetMainMargin = Dashboard.sidebarWidth + Dashboard.gutterWidth,
				targetSidebarPos = Dashboard.gutterWidth;

			this.dom.$sidebarBtn.addClass('sel');
		}
		else
		{
			var targetMainMargin = 0,
				targetSidebarPos = -Dashboard.sidebarWidth;

			this.dom.$sidebarBtn.removeClass('sel');
		}

		// get the current widget offsets
		var widgetOffsets = this.getWidgetOffsets();

		// record the current main margin, and stage the target one
		var currentMainMargin = this.dom.$main.css('marginRight');
		this.dom.$main.css('marginRight', targetMainMargin);

		// set the new cols if necessary, otherwise just animate the margin
		if (!this.setCols(true, widgetOffsets))
		{
			// restore the current margin, and animate to the target
			this.dom.$main.css('marginRight', currentMainMargin);
			this.dom.$main.animate({marginRight: targetMainMargin});
		}

		// slide the sidebar
		this.dom.$sidebar.stop().animate({right: targetSidebarPos});

		// invert the showingSidebar state
		this.showingSidebar = !this.showingSidebar;
	},
},
{
	gutterWidth: 20,
	minColWidth: 280,
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
		dashboard.dom.$main.append(this.dom.outerDiv);

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
