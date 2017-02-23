(function($) {


var Routes = Garnish.Base.extend(
{
	tokens: null,
	routes: null,
	$container: null,
	$addRouteBtn: null,
	sorter: null,

	init: function()
	{
		this.tokens = {};
		this.routes = [];
		this.$container = $('#routes');

		var $routes = this.getRoutes();

		for (var i = 0; i < $routes.length; i++)
		{
			var route = new Route($routes[i]);
			this.routes.push(route);
		}

		this.sorter = new Garnish.DragSort($routes, {
			axis:         Garnish.Y_AXIS,
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

		Craft.postActionRequest('routes/updateRouteOrder', data, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				if (response.success)
				{
					Craft.cp.displayNotice(Craft.t('New route order saved.'));
				}
				else
				{
					Craft.cp.displayError(Craft.t('Couldn’t save new route order.'));
				}
			}

		}, this));
	},

	addRoute: function()
	{
		new RouteSettingsModal();
	}

});


var Route = Garnish.Base.extend(
{
	$container: null,
	id: null,
	locale: null,
	$locale: null,
	$url: null,
	$template: null,
	modal: null,

	init: function(container)
	{
		this.$container = $(container);
		this.id         = this.$container.data('id');
		this.locale     = this.$container.data('locale');
		this.$locale    = this.$container.find('.locale:first');
		this.$url       = this.$container.find('.url:first');
		this.$template  = this.$container.find('.template:first');

		this.addListener(this.$container, 'click', 'edit');
	},

	edit: function()
	{
		if (!this.modal)
		{
			this.modal = new RouteSettingsModal(this);
		}
		else
		{
			this.modal.show();
		}
	},

	updateHtmlFromModal: function()
	{
		if (Craft.routes.locales)
		{
			if (this.locale)
			{
				this.$locale.text(this.locale);
			}
			else
			{
				this.$locale.text(Craft.t('Global'));
			}
		}

		var urlHtml = '';

		for (var i = 0; i < this.modal.urlInput.elements.length; i++)
		{
			var $elem = this.modal.urlInput.elements[i];

			if (this.modal.urlInput.isText($elem))
			{
				urlHtml += Craft.escapeHtml($elem.val());
			}
			else
			{
				urlHtml += $elem.prop('outerHTML');
			}
		}

		this.$url.html(urlHtml);
		this.$template.text(this.modal.$templateInput.val());
	}

});


var RouteSettingsModal = Garnish.Modal.extend(
{
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

		var tokenHtml = '<h4>'+Craft.t('Add a token')+'</h4>';

		for (var name in Craft.routes.tokens)
		{
			var pattern = Craft.routes.tokens[name];
			tokenHtml += '<div class="token" data-name="'+name+'" data-value="'+pattern+'"><span>'+name+'</span></div>';
		}

		var containerHtml =
			'<form class="modal fitted route-settings" accept-charset="UTF-8">' +
				'<div class="header">' +
					'<h1></h1>' +
				'</div>' +
				'<div class="body">' +
					'<div class="field">' +
						'<div class="heading">' +
							'<label for="url">'+Craft.t('If the URI looks like this')+':</label>' +
						'</div>';

		if (Craft.routes.locales)
		{
			containerHtml +=
						'<table class="inputs fullwidth">' +
							'<tr>' +
								'<td>';
		}

		containerHtml += '<div id="url" class="text url ltr"></div>';

		if (Craft.routes.locales)
		{
			containerHtml +=
								'</td>' +
								'<td class="thin">' +
									'<div class="select">' +
										'<select class="locale">' +
											'<option value="">'+Craft.t('Global')+'</option>';

			for (var i = 0; i < Craft.routes.locales.length; i++)
			{
				var locale = Craft.routes.locales[i];
				containerHtml += '<option value="'+locale+'">'+locale+'</option>';
			}

			containerHtml +=
										'</select>' +
									'</div>' +
								'</td>' +
							'</tr>' +
						'</table>';
		}

		containerHtml +=
					'<div class="url-tokens">' +
						tokenHtml +
					'</div>' +
				'</div>' +
				'<div class="field">' +
					'<div class="heading">' +
						'<label for="template">'+Craft.t('Load this template')+':</label>' +
					'</div>' +
					'<input id="template" type="text" class="text fullwidth template ltr">' +
				'</div>' +
			'</div>' +
			'<div class="footer">' +
				'<div class="buttons right last">' +
					'<input type="button" class="btn cancel" value="'+Craft.t('Cancel')+'">' +
					'<input type="submit" class="btn submit" value="'+Craft.t('Save')+'"> ' +
					'<div class="spinner" style="display: none;"></div>' +
				'</div>' +
				'<a class="delete">'+Craft.t('Delete')+'</a>' +
			'</div>' +
		'</form>';

		var $container = $(containerHtml).appendTo(Garnish.$bod);

		// Find the other elements
		this.$heading       = $container.find('h1:first');
		this.$localeInput   = $container.find('.locale:first');
		this.$urlInput      = $container.find('.url:first');
		this.$templateInput = $container.find('.template:first');
		this.$saveBtn       = $container.find('.submit:first');
		this.$cancelBtn     = $container.find('.cancel:first');
		this.$spinner       = $container.find('.spinner:first');
		this.$deleteBtn     = $container.find('.delete:first');

		// Hide the Delete button for new routes
		if (!this.route)
		{
			this.$deleteBtn.hide();
		}

		// Initialize the URL input
		this.urlInput = new Garnish.MixedInput(this.$urlInput, {
			dir: 'ltr'
		});

		// Set the heading
		if (this.route)
		{
			this.$heading.html(Craft.t('Edit Route'));
		}
		else
		{
			this.$heading.html(Craft.t('Create a new route'));
		}

		if (this.route)
		{
			// Set the locale
			this.$localeInput.val(this.route.locale);

			// Set the initial URL value
			var urlNodes = this.route.$url.prop('childNodes');

			for (var i = 0; i < urlNodes.length; i++)
			{
				var node = urlNodes[i];

				if (Garnish.isTextNode(node))
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
			setTimeout($.proxy(function()
			{
				var $firstElem = this.urlInput.elements[0];
				this.urlInput.setFocus($firstElem);
				this.urlInput.setCarotPos($firstElem, 0);
			}, this), 1);

			// Set the initial Template value
			var templateVal = this.route.$template.text();
			this.$templateInput.val(templateVal);
		}
		else
		{
			setTimeout($.proxy(function()
			{
				this.$urlInput.focus();
			}, this), 100);
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

		this.addListener($urlVar, 'keydown', function(event)
		{
			switch (event.keyCode)
			{
				case Garnish.LEFT_KEY:
				{
					// Select the previous element
					setTimeout($.proxy(function()
					{
						this.urlInput.focusPreviousElement($urlVar);
					}, this), 1);

					break;
				}
				case Garnish.RIGHT_KEY:
				{
					// Select the next element
					setTimeout($.proxy(function()
					{
						this.urlInput.focusNextElement($urlVar);
					}, this), 1);

					break;
				}
				case Garnish.DELETE_KEY:
				{
					// Delete this element
					setTimeout($.proxy(function()
					{
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
			this.$heading.html(Craft.t('Edit Route'));
			this.$deleteBtn.show();
		}

		this.base();
	},

	saveRoute: function(event)
	{
		event.preventDefault();

		if (this.loading)
		{
			return;
		}

		var data = {
			locale: this.$localeInput.val()
		};

		if (this.route)
		{
			data.routeId = this.route.id;
		}

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

		data.template = this.$templateInput.val();

		this.loading = true;
		this.$saveBtn.addClass('active');
		this.$spinner.show();

		Craft.postActionRequest('routes/saveRoute', data, $.proxy(function(response, textStatus)
		{
			this.$saveBtn.removeClass('active');
			this.$spinner.hide();
			this.loading = false;

			if (textStatus == 'success')
			{
				if (response.success)
				{
					// Is this a new route?
					if (!this.route)
					{
						var routeHtml =
							'<div class="pane route" data-id="'+response.routeId+'"'+(response.locale ? ' data-locale="'+response.locale+'"' : '')+'>' +
								'<div class="url-container">';

						if (Craft.routes.locales)
						{
							routeHtml += '<span class="locale"></span>';
						}

						routeHtml +=
									'<span class="url" dir="ltr"></span>' +
								'</div>' +
								'<div class="template" dir="ltr"></div>' +
							'</div>';

						var $route = $(routeHtml);

						$route.appendTo('#routes');

						this.route = new Route($route);
						this.route.modal = this;

						Craft.routes.sorter.addItems($route);

						// Was this the first one?
						if (Craft.routes.sorter.$items.length == 1)
						{
							$('#noroutes').addClass('hidden');
						}
					}

					this.route.locale = response.locale;
					this.route.updateHtmlFromModal();
					this.hide();

					Craft.cp.displayNotice(Craft.t('Route saved.'));
				}
				else
				{
					Craft.cp.displayError(Craft.t('Couldn’t save route.'));
				}
			}

		}, this));
	},

	cancel: function()
	{
		this.hide();

		if (this.route)
		{
			this.route.modal = null;
		}
	},

	deleteRoute: function()
	{
		if (confirm(Craft.t(('Are you sure you want to delete this route?'))))
		{
			Craft.postActionRequest('routes/deleteRoute', { routeId: this.route.id }, function(response, textStatus)
			{
				if (textStatus == 'success')
				{
					Craft.cp.displayNotice(Craft.t('Route deleted.'));
				}
			});

			Craft.routes.sorter.removeItems(this.route.$container);
			this.route.$container.remove();
			this.hide();

			// Was this the last one?
			if (Craft.routes.sorter.$items.length == 0)
			{
				$('#noroutes').removeClass('hidden');
			}
		}
	}

});


Craft.routes = new Routes();


})(jQuery);
