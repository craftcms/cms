(function($) {


var Dashboard = Base.extend({

	dom: {},
	widgets: {},
	cols: [],
	totalWidgets: 0,
	showingSidebar: false,

	constructor: function()
	{
		this.dom.$alerts = $(document.getElementById('alerts'));
		this.dom.$container = $(document.getElementById('widgets'));
		this.dom.$sidebarBtn = $(document.getElementById('sidebar-btn'));
		this.dom.$sidebar = $(document.getElementById('sidebar'));

		this.getWidgetHandles();

		for (var i = 0; i < this.dom.$widgetHandles.length; i++)
		{
			var widgetId = $(this.dom.$widgetHandles[i]).attr('data-widget-id'),
				elem = document.getElementById(widgetId);

			if (elem)
			{
				this.widgets[widgetId] = new Dashboard.Widget(elem);
				this.totalWidgets++;
			}
		}

		this.widgetSort = new blx.ui.DragSort(this.dom.$widgetHandles, {
			axis: 'y',
			helper: '<ul class="widget-handles dragging" />',
			onSortChange: $.proxy(this, 'onWidgetMove')
		});

		// setup events
		$('a.remove', this.dom.$widgetHandles).on('click', $.proxy(this, 'onWidgetRemove'))
		this.dom.$sidebarBtn.on('click.dashboard', $.proxy(this, 'toggleSidebar'));
		$(window).on('resizeWidth.dashboard', $.proxy(this, 'onWindowResize'));

		// set the columns
		this.setCols();

		// do the version check
		if (getAlerts)
			$.getJSON(baseUrl+'?c=dashboard&a=getAlerts', $.proxy(this, 'displayAlerts'));
	},

	getWidgetHandles: function()
	{
		this.dom.$widgetHandles = $('ul.widget-handles > li', this.dom.$sidebar);
	},

	getWidget: function(i)
	{
		return typeof this.dom.$widgetHandles[i] != 'undefined' ? this.getWidgetFromHandle(this.dom.$widgetHandles[i]) : false;
	},

	getWidgetFromHandle: function(handle)
	{
		var widgetId = $(handle).attr('data-widget-id');
		return typeof this.widgets[widgetId] != 'undefined' ? this.widgets[widgetId] : false;
	},

	onWindowResize: function()
	{
		this.setCols(true);
	},

	setCols: function(animate, widgetOffsets)
	{
		var totalCols = Math.floor((this.dom.$container.width() + Dashboard.gutterWidth) / (Dashboard.minColWidth + Dashboard.gutterWidth));

		if (totalCols > this.totalWidgets)
			totalCols = this.totalWidgets;

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
		for (var i in this.widgets)
		{
			this.widgets[i].$elem.detach();
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
			this.cols[i] = new Dashboard.Col(this, i);
		}

		// Place the widgets in the order of the handles
		for (var i = 0; i < this.dom.$widgetHandles.length; i++)
		{
			// add it to the shortest column
			var widget = this.getWidget(i);
			if (widget)
			{
				var shortestCol = this.getShortestCol();
				shortestCol.addWidget(widget.elem);
			}
		}

		this.relaxWidgets();

		// animate the widgets into place
		if (animate)
		{
			var $lastVisibleHandle = this.dom.$widgetHandles.not('.hidden').last(),
				lastIndex = $.inArray($lastVisibleHandle[0], this.dom.$widgetHandles);

			for (var i = 0; i <= lastIndex; i++)
			{
				var widget = this.getWidget(i);
				if (widget)
				{
					// clear any current animations
					widget.$elem.stop();

					// get the new settled offset and width
					var settledOffset = widget.$elem.offset(),
						settledWidth = widget.$elem.width();

					// put it back where it was
					widget.$elem.css({
						position: 'relative',
						top: widgetOffsets[i].top - settledOffset.top,
						left: widgetOffsets[i].left - settledOffset.left,
						width: widgetOffsets[i].width
					});

					var onComplete = (i == lastIndex) ? $.proxy(this, 'relaxWidgets') : null;

					widget.$elem.animate({top: 0, left: 0, width: settledWidth}, onComplete);
				}
			}
		}
	},

	relaxWidgets: function()
	{
		for (var i in this.widgets)
		{
			this.widgets[i].$elem.css({position: 'static', width: 'auto'});
		}
	},

	getWidgetOffsets: function()
	{
		this.relaxWidgets();

		var widgetOffsets = [];

		for (var i = 0; i < this.dom.$widgetHandles.length; i++)
		{
			var widget = this.getWidget(i);
			if (widget)
			{
				widgetOffsets[i] = widget.$elem.offset();
				widgetOffsets[i].width = widget.$elem.width();
			}
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
		// update $widgetHandles
		this.getWidgetHandles();

		var widget = this.getWidget(this.widgetSort.draggeeIndex);
		if (widget)
		{
			this.refreshCols(true);
		}
	},

	onWidgetRemove: function(event)
	{
		// fade out the handle, and then remove it
		var $handle = $(event.currentTarget).parent().parent(),
			handleHeight = $handle.outerHeight();
		$handle.animate({opacity: 0, marginBottom: -handleHeight}, function() {
			$handle.remove();
		});

		// fade out the widget, and then remove it
		var widget = this.getWidgetFromHandle($handle);
		if (widget)
		{
			var containerOffset = this.dom.$container.offset(),
				widgetOffset = widget.$elem.offset(),
				width = widget.$elem.width();
			widget.$elem.appendTo(this.dom.$container);
			widget.$elem.css({
				position: 'absolute',
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
		this.dom.$widgetHandles.splice(i, 1);
		this.refreshCols(true);
	},

	displayAlerts: function(data, textStatus)
	{
		if (data && textStatus == 'success')
		{
			var startHeight = this.dom.$alerts.height(),
				alerts = [];

			// add the alerts w/ opacity:0
			for (var i = 0; i < data.alerts.length; i++)
			{
				var $alert = $('<div class="alert pane"><p>'+data.alerts[i]+'</p></div>');
				this.dom.$alerts.append($alert);
				$alert.css({opacity: 0});
				$alert.delay((i+1)*blx.fx.delay).animate({opacity: 1});
			}

			// make room for them
			var endHeight = this.dom.$alerts.height();
			this.dom.$alerts.height(startHeight);
			this.dom.$alerts.animate({height: endHeight}, $.proxy(function() {
				this.dom.$alerts.height('auto');
			}, this));
		}
	}
},
{
	gutterWidth: 20,
	minColWidth: 300,
	sidebarWidth: 200
});


Dashboard.Col = Base.extend({

	constructor: function(dashboard, index)
	{
		this.dashboard = dashboard;
		this.index = index;
		this.dom = {};

		this.dom.outerDiv = document.createElement('div');
		this.dom.outerDiv.className = 'col';
		this.dom.outerDiv.style.width = this.dashboard.colWidth+'%';
		this.dashboard.dom.$container.append(this.dom.outerDiv);

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


Dashboard.Widget = Base.extend({

	elem: null,
	$elem: null,
	id: null,
	dom: null,
	expanded: false,

	constructor: function(elem, i)
	{
		this.elem = elem;
		this.$elem = $(elem);
		this.id = this.$elem.attr('id');

		this.$elem.css('zIndex', i+1);

		this.dom = {};
		this.dom.$settingsBtn = $('.head .settings-btn', this.$elem);

		if (this.dom.$settingsBtn.length)
		{
			this.dom.$settingsOuterContainer = $('.settings-outer-container', this.$elem);
			this.dom.$settingsInnerContainer = this.dom.$settingsOuterContainer.children();
			this.dom.$settings = this.dom.$settingsInnerContainer.children();
			this.dom.$saveBtn = $('.btn.submit', this.dom.$settings);
			this.dom.$cancelBtn = $('.btn.cancel', this.dom.$settings);

			this.dom.$settingsBtn.on('click.widget', $.proxy(this, 'toggleSettings'));
			this.dom.$saveBtn.on('click.widget', $.proxy(this, 'saveSettings'));
			this.dom.$cancelBtn.on('click.widget', $.proxy(this, 'hideSettings'));
		}
	},

	toggleSettings: function()
	{
		if (!this.expanded)
			this.showSettings();
		else
			this.hideSettings();
	},

	showSettings: function()
	{
		this.dom.$settingsOuterContainer.addClass('expanded');
		var height = this.dom.$settingsInnerContainer.height();
		this.dom.$settingsInnerContainer.css('position', 'absolute');
		this.dom.$settingsOuterContainer.stop().animate({height: height}, $.proxy(function() {
			this.dom.$settingsInnerContainer.css('position', 'static');
			this.dom.$settingsOuterContainer.height('auto');
		}, this));

		this.dom.$settingsBtn.addClass('sel');
		this.expanded = true;
	},

	hideSettings: function()
	{
		var height = this.dom.$settingsInnerContainer.height();
		this.dom.$settingsOuterContainer.height(height);
		this.dom.$settingsInnerContainer.css('position', 'absolute')
		this.dom.$settingsOuterContainer.stop().animate({height: 0}, $.proxy(function() {
			this.dom.$settingsOuterContainer.removeClass('expanded');
		}, this));
		this.dom.$settingsBtn.removeClass('sel');
		this.expanded = false;
	},

	saveSettings: function()
	{
		this.hideSettings();
	}

});


// initialize the dashboard
window.dashboard = new Dashboard();


})(jQuery);
