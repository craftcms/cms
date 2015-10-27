(function($) {


var UpdatesPage = Garnish.Base.extend(
{
	totalAvailableUpdates: 0,
	criticalUpdateAvailable: false,
	allowAutoUpdates: null,

	init: function()
	{
		var $graphic = $('#graphic'),
			$status = $('#status');

		Craft.postActionRequest('update/getAvailableUpdates', $.proxy(function(response, textStatus)
		{
			// Use dummy data?
			if (document.location.search == '?test') {
				textStatus = 'success';
				response = {"allowAutoUpdates":true,"app":{"localBuild":"2697","localVersion":"2.4","latestVersion":"2.4","latestBuild":"2700","latestDate":"2015-10-19 20:19:05","targetVersion":null,"targetBuild":null,"realLatestVersion":"2.4","realLatestBuild":"2700","realLatestDate":"2015-10-19 20:19:05","criticalUpdateAvailable":0,"manualUpdateRequired":0,"breakpointRelease":0,"licenseUpdated":null,"versionUpdateStatus":"UpdateAvailable","manualDownloadEndpoint":"http:\/\/download.buildwithcraft.com\/craft\/2.4\/2.4.2700\/Craft-2.4.2700.zip","releases":[{"version":"2.4","build":"2700","date":"2015-10-19 20:19:05","notes":"<ul>\n<li class=\"added\">Added <a href=\"http:\/\/buildwithcraft.com\/classreference\/helpers\/ArrayHelper#getFirstKey-detail\">ArrayHelper::getFirstKey()<\/a>.<\/li>\n<li class=\"fixed\">Fixed an error that occurred when converting a Channel section to a Structure.<\/li>\n<li class=\"fixed\">Fixed a bug where menu buttons within modal windows would collapse just as soon as they had been expanded.<\/li>\n<li class=\"fixed\">Fixed a protruding shadow next to the Save button on Edit Category pages.<\/li>\n<\/ul>\n","type":null,"critical":0,"manual":0,"breakpoint":0,"__model__":"Craft\\AppNewReleaseModel"},{"version":"2.4","build":"2699","date":"2015-10-16 21:10:35","notes":"<ul>\n<li class=\"fixed\">Fixed a PHP error that could occur when saving a category.<\/li>\n<\/ul>\n","type":null,"critical":0,"manual":0,"breakpoint":0,"__model__":"Craft\\AppNewReleaseModel"},{"version":"2.4","build":"2698","date":"2015-10-15 21:33:29","notes":"<ul>\n<li class=\"fixed\">Fixed a PHP error that occurred in environments running PHP 5.3.<\/li>\n<\/ul>\n","type":null,"critical":0,"manual":0,"breakpoint":0,"__model__":"Craft\\AppNewReleaseModel"}],"__model__":"Craft\\AppUpdateModel"},"plugins":[{"class":"Commerce","localVersion":"0.9.1160","latestVersion":"0.9.1162","latestDate":"2015-10-27 02:08:47","status":"UpdateAvailable","displayName":"Craft Commerce","criticalUpdateAvailable":0,"releases":[{"version":"0.9.1162","date":"2015-10-27 02:08:47","notes":"[Added] Added a new feature.\r\n[Improved] Improved an existing feature.\r\n[Fixed] Fixed a bug.","critical":0,"__model__":"Craft\\PluginNewReleaseModel"},{"version":"0.9.1161","date":"2015-10-23 22:35:14","notes":"[Added] Added a new feature.\r\n[Improved] Improved an existing feature.\r\n[Fixed] Fixed a bug.","critical":0,"__model__":"Craft\\PluginNewReleaseModel"}],"__model__":"Craft\\PluginUpdateModel"}]};
			}

			if (textStatus != 'success' || response.error || response.errors)
			{
				var error = Craft.t('An unknown error occurred.');

				if (response.errors && response.errors.length)
				{
					error = response.errors[0];
				}
				else if (response.error)
				{
					error = response.error;
				}

				$graphic.addClass('error');
				$status.text(error);
			}
			else
			{
				this.allowAutoUpdates = response.allowAutoUpdates;

				// Craft CMS update?
				if (response.app) {
					this.processUpdate(response.app, false);
				}

				// Plugin updates?
				if (response.plugins && response.plugins.length) {
					for (var i = 0; i < response.plugins.length; i++) {
						this.processUpdate(response.plugins[i], true);
					}
				}

				if (this.totalAvailableUpdates) {
					$graphic.remove();
					$status.remove();

					// Add the page title
					var headingText;

					if (this.totalAvailableUpdates == 1)
					{
						headingText = Craft.t('1 update available');
					}
					else
					{
						headingText = Craft.t('{num} updates available', { num: this.totalAvailableUpdates });
					}

					$('<div id="page-title"/>')
						.appendTo(Craft.cp.$pageHeader)
						.append($('<h1/>').text(headingText));

				} else {
					$graphic.addClass('success');
					$status.text(Craft.t('You’re all up-to-date!'));
				}
			}
		}, this));
	},

	processUpdate: function(updateInfo, isPlugin)
	{
		if (!updateInfo.releases || !updateInfo.releases.length)
		{
			return;
		}

		this.totalAvailableUpdates++;

		var update = new Update(updateInfo, isPlugin);
	}
});


var Update = Garnish.Base.extend(
{
	updateInfo: null,
	isPlugin: null,
	displayName: null,
	manualUpdateRequired: null,

	$pane: null,
	$downloadBtn: null,

	licenseHud: null,
	$licenseSubmitBtn: null,
	licenseSubmitAction: null,

	init: function(updateInfo, isPlugin)
	{
		this.updateInfo = updateInfo;
		this.isPlugin = isPlugin;
		this.displayName = this.isPlugin ? this.updateInfo.displayName : 'Craft CMS';
		this.manualUpdateRequired = (!updatesPage.allowAutoUpdates || this.updateInfo.manualUpdateRequired);

		this.createPane();
		this.createDownloadButton();
		this.createHeading();
		this.createReleaseList();

		//this.$container.velocity('fadeIn', { duration: 'fast' });
	},

	createPane: function()
	{
		this.$pane = $('<div class="pane"/>').appendTo(Craft.cp.$main);
	},

	createDownloadButton: function()
	{
		var $buttonContainer = $('<div class="buttons right"/>').appendTo(this.$pane);

		// Is a manual update required?
		if (this.manualUpdateRequired)
		{
			this.$downloadBtn = $('<div class="btn submit">'+Craft.t('Download')+'</div>').appendTo($buttonContainer);
		}
		else
		{
			var $btnGroup = $('<div class="btngroup submit"/>').appendTo($buttonContainer),
				$updateBtn = $('<div class="btn submit">'+Craft.t('Update')+'</div>').appendTo($btnGroup),
				$menuBtn = $('<div class="btn submit menubtn"/>').appendTo($btnGroup),
				$menu = $('<div class="menu" data-align="right"/>').appendTo($btnGroup),
				$menuUl = $('<ul/>').appendTo($menu),
				$downloadLi = $('<li/>').appendTo($menuUl);

			this.$downloadBtn = $('<a>'+Craft.t('Download')+'</a>').appendTo($downloadLi);

			new Garnish.MenuBtn($menuBtn);
		}

		// Has the license been updated?
		if (this.updateInfo.licenseUpdated)
		{
			this.addListener(this.$downloadBtn, 'click', 'showLicenseForm');

			if (!this.manualUpdateRequired)
			{
				this.addListener($updateBtn, 'click', 'showLicenseForm');
			}
		}
		else
		{
			this.addListener(this.$downloadBtn, 'click', 'downloadThat');

			if (!this.manualUpdateRequired)
			{
				this.addListener($updateBtn, 'click', 'autoUpdateThat');
			}
		}
	},

	createHeading: function()
	{
		$('<h2/>', {
			text: this.displayName
		}).appendTo(this.$pane);
	},

	createReleaseList: function()
	{
		for (var i = 0; i < this.updateInfo.releases.length; i++)
		{
			$('<hr/>').appendTo(this.$pane);

			var release = this.updateInfo.releases[i],
				heading = this.displayName+' '+release.version;

			if (release.build)
			{
				heading += '.'+release.build;
			}

			if (release.critical)
			{
				heading += ' <span class="critical">'+Craft.t('Critical')+'</span>'
			}

			$('<h3>'+heading+'</h3>').appendTo(this.$pane);
			$('<div class="notes"/>').appendTo(this.$pane).html(release.notes);
		}
	},

	showLicenseForm: function(originalEvent)
	{
		originalEvent.stopPropagation();

		if (!this.licenseHud)
		{
			var $hudBody = $('<div><p>'+Craft.t('Craft’s <a href="http://buildwithcraft.com/license" target="_blank">Terms and Conditions</a> have changed.')+'</p></div>'),
				$label = $('<label> '+Craft.t('I agree.')+' &nbsp;</label>').appendTo($hudBody),
				$checkbox = $('<input type="checkbox"/>').prependTo($label);

			this.$licenseSubmitBtn = $('<input class="btn submit" type="submit"/>').appendTo($hudBody);

			this.licenseHud = new Garnish.HUD(originalEvent.currentTarget, $hudBody, {
				onSubmit: $.proxy(function()
				{
					if ($checkbox.prop('checked'))
					{
						this.licenseSubmitAction();
						this.licenseHud.hide();
						$checkbox.prop('checked', false);
					}
					else
					{
						Garnish.shake(this.licenseHud.$hud);
					}
				}, this)
			});
		}
		else
		{
			this.licenseHud.$trigger = $(originalEvent.currentTarget);
			this.licenseHud.show();
		}

		if (originalEvent.currentTarget == this.$downloadBtn[0])
		{
			this.$licenseSubmitBtn.attr('value', Craft.t('Seriously, download.'));
			this.licenseSubmitAction = this.downloadThat;
		}
		else
		{
			this.$licenseSubmitBtn.attr('value', Craft.t('Seriously, update.'));
			this.licenseSubmitAction = this.autoUpdateThat;
		}
	},

	downloadThat: function()
	{
		var src = this.updateInfo.manualDownloadEndpoint;

		if (window.location.protocol == 'https:') {
			src = src.replace('http:', 'https:');
		}

		$('<iframe/>', { src: src }).appendTo(Garnish.$bod).hide();
	},

	autoUpdateThat: function()
	{
		window.location.href = Craft.getUrl('updates/go/'+(this.isPlugin ? this.updateInfo.class.toLowerCase() : 'craft'));
	}
});


// Init the updates page!
var updatesPage = new UpdatesPage();


})(jQuery);
