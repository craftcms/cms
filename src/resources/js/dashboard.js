(function($) {


Craft.Dashboard = Garnish.Base.extend({

	$alerts: null,
	$container: null,

	widgets: null,
	cols: null,
	colWidth: null,
	loadingWidget: -1,

	init: function()
	{
		this.$alerts = $('#alerts');
		this.$container = $('#widgets');
		this.widgets = [];
		var $widgets = this.$container.children();

		// Set the columns
		this.setCols();

		for (var i = 0; i < $widgets.length; i++)
		{
			var $widget = $($widgets[i]);
			this.placeWidget($widget);
			this.widgets.push($widget);
		}

		// setup events
		this.addListener(Garnish.$win, 'resize', 'onWindowResize');

		// do the version check
		if (typeof window.getAlerts != 'undefined' && window.getAlerts)
		{
			$.getJSON(getAlertsUrl, $.proxy(this, 'displayAlerts'));
		}
	},

	onWindowResize: function()
	{
		this.setCols();
	},

	setCols: function()
	{
		var totalCols = Math.floor(this.$container.width() / Craft.Dashboard.minColWidth);

		if (totalCols == 0)
		{
			totalCols = 1;
		}

		if (totalCols !== this.totalCols)
		{
			this.totalCols = totalCols;
			this.refreshCols();
			return true;
		}

		return false;
	},

	refreshCols: function()
	{
		// Detach the widgets before we remove the columns so they keep their events
		for (var i = 0; i < this.widgets.length; i++)
		{
			this.widgets[i].detach();
		}

		// Delete the old columns
		if (this.cols)
		{
			for (var j = 0; j < this.cols.length; j++)
			{
				this.cols[j].remove();
			}
		}

		// Create the new columns
		this.cols = [];
		this.colWidth = Math.floor(10000 / this.totalCols) / 100;

		for (var k = 0; k < this.totalCols; k++)
		{
			this.cols[k] = new Col(this, k);
		}

		// Place the widgets
		for (var l = 0; l < this.widgets.length; l++)
		{
			this.placeWidget(this.widgets[l]);
		}
	},

	placeWidget: function(widget)
	{
		var shortestCol = this.getShortestCol();
		shortestCol.addWidget(widget);
	},

	getShortestCol: function()
	{
		var shortestCol, shortestColHeight;

		for (var i = 0; i < this.cols.length; i++)
		{
			var col = this.cols[i],
				colHeight = this.cols[i].getHeight();

			if (typeof shortestCol == 'undefined' || colHeight < shortestColHeight)
			{
				shortestCol = col;
				shortestColHeight = colHeight;
			}
		}

		return shortestCol;
	},

	getTallestCol: function()
	{
		var tallestCol, tallestColHeight;

		for (var i = 0; i < this.cols.length; i++)
		{
			var col = this.cols[i],
				colHeight = this.cols[i].getHeight();

			if (typeof tallestCol == 'undefined' || colHeight > tallestColHeight)
			{
				tallestCol = col;
				tallestColHeight = colHeight;
			}
		}

		return tallestCol;
	},

	onWidgetMove: function()
	{
		// update $widgetHandles
		this.getWidgetHandles();

		// Update the z-index's
		for (var i = 0; i < this.dom.$widgetHandles.length; i++)
		{
			// add it to the shortest column
			var widget = this.getWidget(i);
			if (widget)
				widget.elem.css('zIndex', i+1);
		}

		var widget = this.getWidget(this.widgetSort.draggeeIndex);
		if (widget)
		{
			this.refreshCols(true);
		}
	},

	onWidgetRemove: function(event)
	{
		// fade out the handle, and then remove it
		var $handle = $(event.currentTarget).parent(),
			handleHeight = $handle.outerHeight();

		$handle.animate({opacity: 0, marginBottom: -handleHeight}, function() {
			$handle.remove();
		});

		// fade out the widget, and then remove it
		var widget = this.getWidgetFromHandle($handle);
		if (widget)
		{
			var containerOffset = this.$container.offset(),
				widgetOffset = widget.$elem.offset(),
				width = widget.$elem.width();
			widget.$elem.appendTo(this.$container);
			widget.$elem.css({
				position: 'absolute',
				display: 'block',
				zIndex: 0,
				top: widgetOffset.top - containerOffset.top,
				left: widgetOffset.left - containerOffset.left,
				width: width
			});
			widget.$elem.fadeOut($.proxy(function() {
				widget.$elem.remove();
			}, this));
		}

		// remove the handle from $widgetHandles and reset the columns
		var index = $.inArray($handle[0], this.dom.$widgetHandles);
		this.dom.$widgetHandles.splice(index, 1);
		this.refreshCols(true);
	},

	displayAlerts: function(data, textStatus)
	{
		if (data && textStatus == 'success' && data.alerts.length)
		{
			var startHeight = this.$alerts.height(),
				alerts = [];

			// add the alerts w/ opacity:0
			for (var i = 0; i < data.alerts.length; i++)
			{
				var $alert = $('<div class="alert"><p>'+data.alerts[i]+'</p></div>');
				this.$alerts.append($alert);
				$alert.css({opacity: 0});
				$alert.delay((i+1)*100).animate({opacity: 1});
			}

			// make room for them
			var endHeight = this.$alerts.height() + 20;
			this.$alerts.height(startHeight);
			this.$alerts.animate({height: endHeight}, $.proxy(function() {
				this.$alerts.height('auto');
			}, this));
		}
	}
},
{
	minColWidth: 325
});

var Col = Garnish.Base.extend({

	dashboard: null,
	index: null,

	$outerContainer: null,
	$innerContainer: null,

	init: function(dashboard, index)
	{
		this.dashboard = dashboard;
		this.index = index;

		this.$outerContainer = $('<div class="col" style="width: '+this.dashboard.colWidth+'%"/>').appendTo(this.dashboard.$container);
		this.$innerContainer = $('<div class="col-inner">').appendTo(this.$outerContainer);
	},

	getHeight: function()
	{
		this.$innerContainer.height('auto');
		return this.$outerContainer.height();
	},

	setHeight: function(height)
	{
		this.$innerContainer.height(height);
	},

	addWidget: function(widget)
	{
		this.$innerContainer.append(widget);
	},

	remove: function()
	{
		this.$outerContainer.remove();
	}

});


Craft.dashboard = new Craft.Dashboard();


})(jQuery);
