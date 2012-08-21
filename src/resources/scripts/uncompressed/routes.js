(function($) {


var Routes = blx.Base.extend({

	routes: null,
	$container: null,
	$addRouteBtn: null,
	sorter: null,

	init: function()
	{
		this.routes = [];
		this.$container = $('#routes');

		var $routes = this.getRoutes();
		for (var i = 0; i < $routes.length; i++)
		{
			var route = new Route($routes[i]);
			this.routes.push(route);
		}

		this.sorter = new blx.ui.DragSort($routes, {
			axis: 'y',
			onSortChange: $.proxy(this, 'updateRouteOrder')
		});

		this.$addRouteBtn = $('#add-route-btn');

		this.addListener(this.$addRouteBtn, 'click', 'addRoute');
	},

	getRoutes: function()
	{
		return this.$container.children();
	},

	updateRouteOrder: function()
	{
		var $routes = this.getRoutes(),
			data = {};

		for (var i = 0; i < $routes.length; i++)
		{
			data['routeIds['+i+']'] = $($routes[i]).attr('data-id');
		}

		$.post(blx.actionUrl+'routes/updateRouteOrder', data, $.proxy(function(response, textStatus, jqXHR) {
			if (response.success)
				blx.displayNotice(blx.t('New route order saved.'));
			else
				blx.displayError(blx.t('Couldn’t save new route order.'));
		}, this));
	},

	addRoute: function()
	{
		new RouteSettingsModal();
	}

});


var Route = blx.Base.extend({

	$container: null,
	id: null,
	$url: null,
	$template: null,
	modal: null,

	init: function(container)
	{
		this.$container = $(container);
		this.id = this.$container.attr('data-id');
		this.$url = this.$container.find('.url:first');
		this.$template = this.$container.find('.template:first');

		this.addListener(this.$container, 'click', 'edit');
	},

	edit: function()
	{
		if (!this.modal)
			this.modal = new RouteSettingsModal(this);
		else
			this.modal.show();
	},

	updateHtmlFromModal: function()
	{
		var urlHtml = '';

		for (var i = 0; i < this.modal.urlInput.elements.length; i++)
		{
			var $elem = this.modal.urlInput.elements[i];

			if (this.modal.urlInput.isText($elem))
				urlHtml += $elem.val();
			else
				urlHtml += $elem.prop('outerHTML');
		}

		this.$url.html(urlHtml);
		this.$template.html(this.modal.$templateInput.val());
	}

});


var RouteSettingsModal = blx.ui.Modal.extend({

	route: null,
	$heading: null,
	$urlInput: null,
	urlElements: null,
	$templateInput: null,
	$saveBtn: null,
	$cancelBtn: null,
	$spinner: null,
	$deleteBtn: null,
	loading: false,

	init: function(route)
	{
		this.route = route;

		var $container = $('<form class="modal route-settings">' +
			'<h1></h1>' +
			'<div class="field">' +
				'<div class="heading">' +
					'<label for="url">'+blx.t("If the URL looks like this")+':</label>' +
				'</div>' +
				'<div id="url" class="text url"></div>' +
				'<div class="url-tokens">' +
					'<h4>'+blx.t("Add a token")+'</h4>' +
					'<div class="token" data-name="year" data-value="\\d{4}">'+blx.t("year")+'</div>' +
					'<div class="token" data-name="month" data-value="1?\\d">'+blx.t("month")+'</div>' +
					'<div class="token" data-name="day" data-value="[1-3]?\\d">'+blx.t("day")+'</div>' +
					'<div class="token" data-name="number" data-value="\\d+">'+blx.t("number")+'</div>' +
					'<div class="token" data-name="page" data-value="\\d+">'+blx.t("page")+'</div>' +
				'</div>' +
			'</div>' +
			'<div class="field">' +
				'<div class="heading">' +
					'<label for="template">'+blx.t("Load this template")+':</label>' +
				'</div>' +
				'<div class="textwrapper"><input id="template" type="text" class="text template"></div>' +
			'</div>' +
			'<div class="buttons">' +
				'<input type="submit" class="btn submit" value="'+blx.t("Save")+'"> ' +
				'<input type="button" class="btn cancel" value='+blx.t("Cancel")+'">' +
				'<div class="spinner" style="display: none;"></div>' +
				'<a class="delete">'+blx.t("Delete")+'</a>' +
			'</div>' +
		'</form>');

		$container.appendTo(blx.$body);

		// Find the other elements
		this.$heading = $container.find('h1:first');
		this.$urlInput = $container.find('.url:first');
		this.$templateInput = $container.find('.template:first');
		this.$saveBtn = $container.find('.submit:first');
		this.$cancelBtn = $container.find('.cancel:first');
		this.$spinner = $container.find('.spinner:first');
		this.$deleteBtn = $container.find('.delete:first');

		// Hide the Delete button for new routes
		if (!this.route)
			this.$deleteBtn.hide();

		// Initialize the URL input
		this.urlInput = new blx.ui.MixedInput(this.$urlInput);

		// Set the heading
		if (this.route)
			this.$heading.html(blx.t('Edit Route'));
		else
			this.$heading.html(blx.t('Create a new route'));

		if (this.route)
		{
			// Set the initial URL value
			var urlNodes = this.route.$url.prop('childNodes');
			for (var i = 0; i < urlNodes.length; i++)
			{
				var node = urlNodes[i];
				if (blx.isTextNode(node))
				{
					var text = this.urlInput.addTextElement();
					text.setVal(node.nodeValue);
				}
				else
				{
					this.addUrlVar(node);
				}
			}

			// Focus on the first element
			setTimeout($.proxy(function() {
				var $firstElem = this.urlInput.elements[0];
				this.urlInput.setFocus($firstElem);
				this.urlInput.setCarotPos($firstElem, 0);
			}, this), 1);

			// Set the initial Template value
			var templateVal = this.route.$template.text();
			this.$templateInput.val(templateVal)
		}
		else
		{
			this.$urlInput.focus();
		}

		this.base($container);

		// We must add vars on mousedown, so that text elements don't have a chance
		// to lose focus, thus losing the carot position.
		var $urlVars = this.$container.find('.url-tokens').children('div');
		this.addListener($urlVars, 'mousedown', function(event) {
			this.addUrlVar(event.currentTarget);
		});

		// Save/Cancel/Delete
		this.addListener(this.$container, 'submit', 'saveRoute');
		this.addListener(this.$cancelBtn, 'click', 'cancel');
		this.addListener(this.$deleteBtn, 'click', 'deleteRoute');
	},

	addUrlVar: function(elem)
	{
		var $urlVar = $(elem).clone().attr('tabindex', '0');
		this.urlInput.addElement($urlVar);

		this.addListener($urlVar, 'keydown', function(event) {
			switch (event.keyCode)
			{
				case blx.LEFT_KEY:
				{
					// Select the previous element
					setTimeout($.proxy(function() {
						this.urlInput.focusPreviousElement($urlVar);
					}, this), 1);
					
					break;
				}
				case blx.RIGHT_KEY:
				{
					// Select the next element
					setTimeout($.proxy(function() {
						this.urlInput.focusNextElement($urlVar);
					}, this), 1);

					break;
				}
				case blx.DELETE_KEY:
				{
					// Delete this element
					setTimeout($.proxy(function() {
						this.urlInput.removeElement($urlVar);
					}, this), 1);

					event.preventDefault();
				}
			}
		});
	},

	show: function()
	{
		if (this.route)
		{
			this.$heading.html(blx.t('Edit Route'));
			this.$deleteBtn.show();
		}

		this.base();
	},

	saveRoute: function(event)
	{
		event.preventDefault();

		if (this.loading)
			return;

		var data = {};

		if (this.route)
			data.routeId = this.route.id;

		for (var i = 0; i < this.urlInput.elements.length; i++)
		{
			var $elem = this.urlInput.elements[i];

			if (this.urlInput.isText($elem))
			{
				data['url['+i+']'] = $elem.val();
			}
			else
			{
				data['url['+i+'][0]'] = $elem.attr('data-name');
				data['url['+i+'][1]'] = $elem.attr('data-value');
			}
		}

		data['template'] = this.$templateInput.val();

		this.loading = true;
		this.$saveBtn.addClass('active');
		this.$spinner.show();
	
		$.post(blx.actionUrl+'routes/saveRoute', data, $.proxy(function(response, textStatus, jqXHR) {

			if (response.success)
			{
				// Is this a new route?
				if (!this.route)
				{
					var $route = $('<div class="pane route" data-id="'+response.routeId+'">' +
						'<div class="url"></div>' +
						'<div class="template"></div>' +
					'</div>');

					$route.appendTo('#routes');

					this.route = new Route($route);
					this.route.modal = this;

					blx.routes.sorter.addItems($route);
				}

				this.route.updateHtmlFromModal();
				this.hide();

				blx.displayNotice(blx.t('Route saved.'));
			}
			else
				blx.displayError(blx.t('Couldn’t save route.'));

			this.$saveBtn.removeClass('active');
			this.$spinner.hide();
			this.loading = false;

		}, this));
	},

	cancel: function()
	{
		this.hide();

		if (this.route)
			this.route.modal = null;
	},

	deleteRoute: function()
	{
		if (confirm(blx.t(('Are you sure you want to delete this route?'))))
		{
			$.post(blx.actionUrl+'routes/deleteRoute', { routeId: this.route.id }, function() {
				blx.displayNotice(blx.t('Route deleted.'))
			});

			this.route.$container.remove();
			this.hide();
		}
	}

});


blx.routes = new Routes();


})(jQuery);
