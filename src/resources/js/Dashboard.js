/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.resources
 */

(function($) {


/**
 * Dashboard class
 */
Craft.Dashboard = Garnish.Base.extend(
{
	$grid: null,
	$widgetManagerBtn: null,

	widgets: null,
	widgetManager: null,
	widgetAdminTable: null,
	widgetSettingsModal: null,

	init: function()
	{
		this.widgets = {};

		this.$widgetManagerBtn = $('#widgetManagerBtn');

		this.addListener(this.$newWidgetMenuItems, 'click', 'newWidget');
		this.addListener(this.$widgetManagerBtn, 'click', 'showWidgetManager');

		Garnish.$doc.ready($.proxy(function() {
			this.$grid = $('#main > .grid');
			$('#newwidgetmenubtn').data('menubtn').menu.on('optionselect', $.proxy(this, 'handleNewWidgetOptionSelect'));
		}, this));
	},

	handleNewWidgetOptionSelect: function(e)
	{
		var $option = $(e.selectedOption),
			type = $option.data('type'),
			colspan = $option.data('colspan'),
			settingsNamespace = 'newwidget'+Math.floor(Math.random()*1000000000)+'-settings',
			settingsHtml = $option.data('settings-html').replace(/__NAMESPACE__/g, settingsNamespace),
			settingsJs = $option.data('settings-js').replace(/__NAMESPACE__/g, settingsNamespace),
			$gridItem = $('<div class="item" data-colspan="'+colspan+'" style="display: block">'),
			$container = $(
				'<div class="widget new loading-new scaleout '+type.toLowerCase()+'">' +
					'<div class="front">' +
						'<div class="pane">' +
							'<div class="spinner body-loading"/>' +
							'<div class="settings icon hidden"/>' +
							'<h2/>' +
							'<div class="body"/>' +
						'</div>' +
					'</div>' +
					'<div class="back">' +
						'<form class="pane">' +
							'<input type="hidden" name="type" value="'+type+'"/>' +
							'<input type="hidden" name="settingsNamespace" value="'+settingsNamespace+'"/>' +
							'<h2>'+Craft.t('{type} Settings', { type: Craft.escapeHtml($option.text()) })+'</h2>' +
							'<div class="settings"/>' +
							'<hr/>' +
							'<div class="buttons clearafter">' +
								'<input type="submit" class="btn submit" value="'+Craft.t('Save')+'"/>' +
								'<div class="btn" role="button">'+Craft.t('Cancel')+'</div>' +
								'<div class="spinner hidden"/>' +
							'</div>' +
						'</form>' +
					'</div>' +
				'</div>').appendTo($gridItem);

		if (settingsHtml)
		{
			$container.addClass('flipped');
		}
		else
		{
			$container.addClass('loading');
		}

		var widget = new Craft.Widget($container, settingsHtml.replace(/__NAMESPACE__/g, settingsNamespace), function() {
			eval(settingsJs)
		});

		// Append the new widget after the last one
		// (can't simply append it to the grid container, since that will place it after the resize listener object)
		var grid = this.$grid.data('grid');

		if (grid.$items.length)
		{
			$gridItem.insertAfter(grid.$items.last());
		}
		else
		{
			$gridItem.prependTo(grid.$container);
		}

		grid.addItems($gridItem);
		Garnish.scrollContainerToElement($gridItem);

		$container.removeClass('scaleout');

		if (!settingsHtml)
		{
			var data = {
				type: type
			};

			Craft.postActionRequest('dashboard/createWidget', data, function(response, textStatus)
			{
				if (textStatus == 'success' && response.success)
				{
					widget.update(response);
				}
				else
				{
					widget.destroy();
				}
			});
		}
	},

	showWidgetManager: function()
	{
		if (!this.widgetManager)
		{
			var $widgets = this.$grid.find('> .item > .widget'),
				$form = $(
					'<form method="post" accept-charset="UTF-8">' +
						'<input type="hidden" name="action" value="widgets/saveWidget"/>' +
					'</form>'
				).appendTo(Garnish.$bod),
				$noWidgets = $('<p id="nowidgets"'+($widgets.length ? ' class="hidden"' : '')+'>'+Craft.t('You don’t have any widgets yet.')+'</p>').appendTo($form),
				$table = $('<table class="data'+(!$widgets.length ? ' hidden' : '')+'"/>').appendTo($form),
				$tbody = $('<tbody/>').appendTo($table);

			for (var i = 0; i < $widgets.length; i++)
			{
				var $widget = $widgets.eq(i);

				// Make sure it's actually saved
				if (!$widget.data('id'))
				{
					continue;
				}

				this.widgets[$widget.data('id')].getManagerRow().appendTo($tbody);
			}

			this.widgetManager = new Garnish.HUD(this.$widgetManagerBtn, $form, {
				hudClass: 'hud widgetmanagerhud',
				onShow: $.proxy(function() {
					this.$widgetManagerBtn.addClass('active');
				}, this),
				onHide: $.proxy(function() {
					this.$widgetManagerBtn.removeClass('active');
				}, this)
			});

			this.widgetAdminTable = new Craft.AdminTable({
				tableSelector: $table,
				noObjectsSelector: $noWidgets,
				sortable: true,
				reorderAction: 'dashboard/reorderUserWidgets',
				deleteAction: 'dashboard/deleteUserWidget',
				onAfterReorderObjects: $.proxy(function(ids)
				{
					var lastWidget;

					for (var i = 0; i < ids.length; i++)
					{
						var widget = this.widgets[ids[i]];

						if (!lastWidget)
						{
							widget.$gridItem.prependTo(this.$grid);
						}
						else
						{
							widget.$gridItem.insertAfter(lastWidget.$gridItem);
						}

						lastWidget = widget;
					}

					this.$grid.data('grid').resetItemOrder();

				}, this),
				onDeleteObject: $.proxy(function(id)
				{
					var widget = this.widgets[id];

					widget.destroy();
				}, this)
			});
		}
		else
		{
			this.widgetManager.show();
		}
	}
});


/**
 * Dashboard Widget class
 */
Craft.Widget = Garnish.Base.extend(
{
	$container: null,
	$gridItem: null,
	$grid: null,

	$front: null,
	$settingsBtn: null,
	$title: null,
	$bodyContainer: null,

	$back: null,
	$settingsForm: null,
	$settingsContainer: null,
	$settingsSpinner: null,
	$settingsErrorList: null,

	settingsHtml: null,
	initSettingsFn: null,
	showingSettings: false,

	init: function(container, settingsHtml, initSettingsFn)
	{
		this.$container = $(container);
		this.$gridItem = this.$container.parent();
		this.$grid = $('#main > .grid');

		this.$container.data('widget', this);

		if (this.$container.data('id'))
		{
			window.dashboard.widgets[this.$container.data('id')] = this;
		}

		this.$front = this.$container.children('.front');
		this.$settingsBtn = this.$front.find('> .pane > .icon.settings');
		this.$title = this.$front.find('> .pane > h2');
		this.$bodyContainer = this.$front.find('> .pane > .body');

		this.setSettingsHtml(settingsHtml, initSettingsFn);

		if (!this.$container.hasClass('flipped'))
		{
			this.onShowFront();
		}
		else
		{
			this.initBackUi();
			this.refreshSettings();
			this.onShowBack();
		}

		this.addListener(this.$settingsBtn, 'click', 'showSettings');
	},

	initBackUi: function()
	{
		this.$back = this.$container.children('.back');
		this.$settingsForm = this.$back.children('form');
		this.$settingsContainer = this.$settingsForm.children('.settings');
		var $btnsContainer = this.$settingsForm.children('.buttons');
		this.$settingsSpinner = $btnsContainer.children('.spinner');

		this.addListener($btnsContainer.children('.btn:nth-child(2)'), 'click', 'cancelSettings');
		this.addListener(this.$settingsForm, 'submit', 'saveSettings');
	},

	setSettingsHtml: function(settingsHtml, initSettingsFn)
	{
		this.settingsHtml = settingsHtml;
		this.initSettingsFn = initSettingsFn;

		if (this.settingsHtml)
		{
			this.$settingsBtn.removeClass('hidden');
		}
		else
		{
			this.$settingsBtn.addClass('hidden');
		}
	},

	refreshSettings: function()
	{
		this.$settingsContainer.html(this.settingsHtml);
		Craft.initUiElements(this.$settingsContainer);
		this.initSettingsFn();
	},

	showSettings: function()
	{
		if (!this.$back)
		{
			this.initBackUi();
		}

		// Refresh the settings every time
		this.refreshSettings();

		this.$container
			.addClass('flipped')
			.velocity({ height: this.$back.height() }, {
				complete: $.proxy(this, 'onShowBack')
			});
	},

	hideSettings: function()
	{
		this.$container
			.removeClass('flipped')
			.velocity({ height: this.$front.height() }, {
				complete: $.proxy(this, 'onShowFront')
			});
	},

	saveSettings: function(e)
	{
		e.preventDefault();
		this.$settingsSpinner.removeClass('hidden');

		var action = this.$container.hasClass('new') ? 'dashboard/createWidget' : 'dashboard/saveWidgetSettings',
			data = this.$settingsForm.serialize();

		Craft.postActionRequest(action, data, $.proxy(function(response, textStatus)
		{
			this.$settingsSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (this.$settingsErrorList)
				{
					this.$settingsErrorList.remove();
					this.$settingsErrorList = null;
				}

				if (response.success)
				{
					Craft.cp.displayNotice(Craft.t('Widget saved.'));

					// Make sure the widget is still allowed to be shown, just in case
					if (!response.info)
					{
						this.destroy();
					}
					else
					{
						this.update(response);
						this.hideSettings();
					}
				}
				else
				{
					Craft.cp.displayError(Craft.t('Couldn’t save widget.'));

					if (response.errors)
					{
						this.$settingsErrorList = Craft.ui.createErrorList(response.errors)
							.insertAfter(this.$settingsContainer);
					}
				}
			}
		}, this));
	},

	update: function(response)
	{
		this.$container.data('title', response.info.title);

		// Is this a new widget?
		if (this.$container.hasClass('new'))
		{
			this.$container
				.attr('id', 'widget'+response.info.id)
				.data('id', response.info.id)
				.data('name', response.info.name)
				.removeClass('new loading-new');

			if (this.$settingsForm)
			{
				this.$settingsForm.prepend('<input type="hidden" name="widgetId" value="'+response.info.id+'"/>')
			}

			window.dashboard.widgets[response.info.id] = this;

			if (window.dashboard.widgetAdminTable)
			{
				window.dashboard.widgetAdminTable.addRow(this.getManagerRow());
			}
		}
		else
		{
			if (window.dashboard.widgetAdminTable)
			{
				window.dashboard.widgetAdminTable.$tbody.children('[data-id="'+this.$container.data('id')+'"]:first').children('td:nth-child(2)').html(this.getManagerRowLabel());
			}
		}

		this.$title.text(response.info.title);
		this.$bodyContainer.html(response.info.bodyHtml);

		// New colspan?
		if (response.info.colspan != this.$gridItem.data('colspan'))
		{
			this.$gridItem.data('colspan', response.info.colspan);
			this.$grid.data('grid').refreshCols(true);
			Garnish.scrollContainerToElement(this.$gridItem);
		}

		Craft.initUiElements(this.$bodyContainer);
		Craft.appendHeadHtml(response.headHtml);
		Craft.appendFootHtml(response.footHtml);

		this.setSettingsHtml(response.info.settingsHtml, function() {
			eval(response.info.settingsJs);
		});
	},

	cancelSettings: function()
	{
		if (this.$container.data('id'))
		{
			this.hideSettings();
		}
		else
		{
			this.destroy();
		}
	},

	onShowFront: function()
	{
		this.showingSettings = false;
		this.removeListener(this.$back, 'resize');
		this.addListener(this.$front, 'resize', 'updateContainerHeight');
	},

	onShowBack: function()
	{
		this.showingSettings = true;
		this.removeListener(this.$front, 'resize');
		this.addListener(this.$back, 'resize', 'updateContainerHeight');

		// Focus on the first input
		setTimeout($.proxy(function() {
			this.$settingsForm.find(':focusable:first').focus();
		}, this), 1);
	},

	updateContainerHeight: function()
	{
		this.$container.height((this.showingSettings ? this.$back : this.$front).height());
	},

	getManagerRow: function()
	{
		var id = this.$container.data('id'),
			title = this.$container.data('title'),
			iconUrl = this.$container.data('icon-url'),
			maxColspan = this.$container.data('max-colspan');

		if(!iconUrl)
		{
			iconUrl = Craft.getResourceUrl('images/widgets/default.svg');
		}

		var $row = $(
			'<tr data-id="'+id+'" data-name="'+title+'">' +
				'<td><img src="'+iconUrl+'" /></td>' +
				'<td>'+this.getManagerRowLabel()+'</td>' +
				'<td class="thin"><div class="colspan-picker"></div></td>' +
				'<td class="thin"><a class="move icon" title="'+Craft.t('Reorder')+'" role="button"></a></td>' +
				'<td class="thin"><a class="delete icon" title="'+Craft.t('Delete')+'" role="button"></a></td>' +
			'</tr>'
		);

		$colspanPicker = $('.colspan-picker', $row);

		if(!maxColspan)
		{
			maxColspan = 1;
		}

		if(maxColspan > 1)
		{
			for(i=1; i <= maxColspan; i++)
			{
				$('<a title="'+i+' Column" data-colspan="'+i+'" role="button">'+i+'</a>').appendTo($colspanPicker);
			}

			this.addListener($('a', $colspanPicker), 'click', $.proxy(function(ev) {

				var colspan = $(ev.currentTarget).data('colspan');

				this.$gridItem.data('colspan', colspan);
				this.$grid.data('grid').refreshCols(true);

				var data = {
					id: id,
					colspan:colspan
				};

				Craft.postActionRequest('dashboard/changeWidgetColspan', data, function(response, textStatus)
				{
					if (textStatus == 'success' && response.success)
					{
						Craft.cp.displayNotice(Craft.t('Widget’s colspan changed.'));
					}
					else
					{
						Craft.cp.displayError(Craft.t('Couldn’t change widget’s colspan.'));
					}
				});

			}, this));
		}

		return $row;
	},

	getManagerRowLabel: function()
	{
		var title = this.$container.data('title'),
			name = this.$container.data('name');

		return title+(title != name ? ' <span class="light">('+name+')</span>' : '')
	},

	destroy: function()
	{
		delete window.dashboard.widgets[this.$container.data('id')];
		this.$container.addClass('scaleout');
		this.base();

		setTimeout($.proxy(function() {
			this.$grid.data('grid').removeItems(this.$gridItem);
			this.$gridItem.remove();
		}, this), 200);
	}
});

window.dashboard = new Craft.Dashboard();

})(jQuery)
