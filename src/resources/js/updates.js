(function($) {


Craft.UpdateInfo = Garnish.Base.extend(
{
	appUpdateInfo: null,

	$container: null,
	$downloadBtn: null,

	licenseHud: null,
	$licenseSubmitBtn: null,
	licenseSubmitAction: null,

	allowAutoUpdates: null,

	init: function(allowAutoUpdates)
	{
		this.allowAutoUpdates = allowAutoUpdates;

		var $graphic = $('#graphic'),
			$status = $('#status');

		Craft.postActionRequest('update/getAvailableUpdates', $.proxy(function(response, textStatus)
		{
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
				var info = {
					total: (response.app && response.app.releases && response.app.releases.length ? 1 : 0),
					critical: (response.app && response.criticalUpdateAvailable)
				};

				if (!info.total)
				{
					$graphic.addClass('success');
					$status.text(Craft.t('You’re all up-to-date!'));
				}
				else
				{
					$graphic.fadeOut('fast');
					$status.fadeOut('fast', $.proxy(function()
					{
						$graphic.remove();
						$status.remove();

						this.appUpdateInfo = response.app;
						this.showAvailableUpdates();
					}, this));
				}

				// Update the CP header badge
				Craft.cp.displayUpdateInfo(info);
			}
		}, this));
	},

	showAvailableUpdates: function()
	{
		this.$container = $('<div/>').appendTo(Craft.cp.$main).hide();

		var $headerPane = $('<div class="pane clearafter"/>').appendTo(this.$container),
			$heading = $('<h2 class="heading">'+Craft.t('You’ve got updates!')+'</h2>').appendTo($headerPane),
			$buttonContainer = $('<div class="buttons"/>').appendTo($headerPane);

		// Is a manual update required?
		if (this.appUpdateInfo.manualUpdateRequired)
		{
			this.$downloadBtn = $('<div class="btn submit">'+Craft.t('Download')+'</div>').appendTo($buttonContainer);
		}
		else
		{
			if (this.allowAutoUpdates)
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
		}

		if (this.allowAutoUpdates)
		{
			// Has the license been updated?
			if (this.appUpdateInfo.licenseUpdated)
			{
				this.addListener(this.$downloadBtn, 'click', 'showLicenseForm');

				if (!this.appUpdateInfo.manualUpdateRequired)
				{
					this.addListener($updateBtn, 'click', 'showLicenseForm');
				}
			}
			else
			{
				this.addListener(this.$downloadBtn, 'click', 'downloadThat');

				if (!this.appUpdateInfo.manualUpdateRequired)
				{
					this.addListener($updateBtn, 'click', 'autoUpdateThat');
				}
			}
		}

		this.showReleases(this.appUpdateInfo.releases, 'Craft');
		this.$container.fadeIn('fast');
	},

	showLicenseForm: function(originalEvent)
	{
		originalEvent.stopPropagation();

		if (!this.licenseHud)
		{
			var $form = $('<form><p>'+Craft.t('Craft’s <a href="http://buildwithcraft.com/license" target="_blank">Terms and Conditions</a> have changed.')+'</p></form>'),
				$label = $('<label> '+Craft.t('I agree.')+' &nbsp;</label>').appendTo($form),
				$checkbox = $('<input type="checkbox"/>').prependTo($label);

			this.$licenseSubmitBtn = $('<input class="btn submit" type="submit"/>').appendTo($form);

			this.licenseHud = new Garnish.HUD(originalEvent.currentTarget, $form);

			this.addListener($form, 'submit', function(ev)
			{
				ev.preventDefault();

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

	showReleases: function(releases, product)
	{
		for (var i = 0; i < releases.length; i++)
		{
			var $releasePane = $('<div class="pane"/>').appendTo(this.$container),
				release = releases[i],
				heading = product+' '+release.version;

			if (release.build)
			{
				heading += ' <span class="light">' +
					Craft.t('build {build}', { build: release.build }) +
					'</span>';
			}

			if (release.critical)
			{
				heading += ' <span class="critical">'+Craft.t('Critical')+'</span>'
			}

			$('<h2>'+heading+'</h2>').appendTo($releasePane);
			$('<div class="notes"/>').appendTo($releasePane).html(release.notes);
		}
	},

	downloadThat: function()
	{
		var src = this.appUpdateInfo.manualDownloadEndpoint;

		if (window.location.protocol == 'https:') {
			src = src.replace('http:', 'https:');
		}

		$('<iframe/>', { src: src }).appendTo(Garnish.$bod).hide();
	},

	autoUpdateThat: function()
	{
		window.location.href = Craft.getUrl('updates/go/craft');
	}
});

})(jQuery);
