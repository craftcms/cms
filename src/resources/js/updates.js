(function($) {


var ReleaseNotes = Garnish.Base.extend({

	$table: null,
	$tbody: null,

	init: function($td, releases, product)
	{
		this.$table = $('<table/>').appendTo($td);
		this.$tbody = $('<tbody/>').appendTo(this.$table);

		this.addNoteRows(releases[0].notes);

		for (var i = 1; i < releases.length; i++)
		{
			var release = releases[i],
				heading = product+' '+release.version;

			if (release.build)
			{
				heading += ' <span class="light">' +
					Craft.t('build {build}', { build: release.build }) +
					'</span>';
			}

			$('<tr><th colspan="2">'+heading+'</th></tr>').appendTo(this.$tbody);

			this.addNoteRows(release.notes);
		}
	},

	addNoteRows: function(notes)
	{
		notes = notes.split(/[\r\n]+/);

		for (var i = 0; i < notes.length; i++)
		{
			var note = notes[i],
				$tr = $('<tr/>').appendTo(this.$tbody),
				match = note.match(/\[(\w+)\]\s*(.+)/);

			if (match)
			{
				$('<td class="thin"><span class="category '+match[1].toLowerCase()+'">'+Craft.t(match[1])+'</span></td>').appendTo($tr);
				$('<td>'+match[2]+'</td>').appendTo($tr);
			}
			else
			{
				$('<td colspan="2">'+note+'</td>').appendTo($tr);
			}
		}
	}
});

var atLeastOnePluginHasARelease = function(plugins)
{
	for (var i in plugins)
	{
		var plugin = plugins[i];

		if (plugin.releases && plugin.releases.length > 0)
		{
			return true;
		}
	}

	return false;
};

Craft.postActionRequest('update/getAvailableUpdates', function(response) {

	$('#loading').fadeOut('fast', function()
	{
		if (!response.errors && response.error)
		{
			response.errors = [response.error];
		}

		if (response.errors && response.errors.length > 0)
		{
			var $div = $('#update-error');

			$div.html(response.errors[0]);
			$div.show();
		}
		else
		{
			if ((response.app && response.app.releases && response.app.releases.length))
			{
				var $table = $('#system-updates'),
					$tbody = $table.children('tbody');

				$table.show();

				if (response.app.releases)
				{
					var $tr = $('<tr/>').appendTo($tbody),
						$th = $('<th/>').appendTo($tr),
						$td = $('<td class="thin rightalign"/>').appendTo($tr);

					$th.html('@@@appName@@@ '+response.app.releases[0].version +
						' <span class="light">' +
						Craft.t('build {build}', { build: response.app.releases[0].build }) +
						'</span>' +
						(response.app.criticalUpdateAvailable ? '<span class="critical">'+Craft.t('Critical')+'</span>' : '')
					);

					var downloadThat = function() {
						var src = response.app.manualDownloadEndpoint;
						$('<iframe/>', { src: src }).appendTo(Garnish.$bod).hide();
					};

					var autoUpdateThat = function() {
						document.location.href = Craft.getUrl('updates/go/craft');
					};

					// Is a manual update required?
					if (response.app.manualUpdateRequired)
					{
						var $downloadBtn = $('<div class="btn submit">'+Craft.t('Download')+'</div>').appendTo($td);
					}
					else
					{
						var $btnGroup = $('<div class="btngroup"/>').appendTo($td),
							$updateBtn = $('<div class="btn submit">'+Craft.t('Update')+'</div>').appendTo($btnGroup),
							$menuBtn = $('<div class="btn submit menubtn"/>').appendTo($btnGroup),
							$menu = $('<div class="menu" data-align="right"/>').appendTo($btnGroup),
							$menuUl = $('<ul/>').appendTo($menu),
							$downloadLi = $('<li/>').appendTo($menuUl),
							$downloadBtn = $('<a>'+Craft.t('Download')+'</a>').appendTo($downloadLi);

						new Garnish.MenuBtn($menuBtn);
					}

					// Has the license been updated?
					if (response.app.licenseUpdated)
					{
						var hud, $form, $submitBtn, $label, $checkbox, doThat;
						var showLicenseForm = function(originalEvent)
						{
							originalEvent.stopPropagation();

							if (!hud)
							{
								$form = $('<form><p>'+Craft.t('Craft’s <a href="http://buildwithcraft.com/license" target="_blank">Terms and Conditions</a> have been updated.')+'</p></form>');
								$label = $('<label> '+Craft.t('I agree.')+' &nbsp;</label>').appendTo($form);
								$checkbox = $('<input type="checkbox"/>').prependTo($label);
								$submitBtn = $('<input class="btn submit" type="submit"/>').appendTo($form);

								hud = new Garnish.HUD(originalEvent.currentTarget, $form, {
									hudClass: 'hud',
									triggerSpacing: 20,
									tipWidth: 30
								});

								$form.on('submit', function(ev) {
									ev.preventDefault();

									if ($checkbox.prop('checked'))
									{
										doThat();
										hud.hide();
										$checkbox.prop('checked', false);
									}
									else
									{
										Garnish.shake(hud.$hud);
									}
								});
							}
							else
							{
								hud.$trigger = $(originalEvent.currentTarget);
								hud.show();
							}

							if (originalEvent.currentTarget == $downloadBtn[0])
							{
								$submitBtn.attr('value', Craft.t('Seriously, download.'));
								doThat = downloadThat;
							}
							else
							{
								$submitBtn.attr('value', Craft.t('Seriously, update.'));
								doThat = autoUpdateThat;
							}
						};

						$downloadBtn.on('click', showLicenseForm);

						if (typeof $updateBtn != 'undefined')
						{
							$updateBtn.on('click', showLicenseForm);
						}
					}
					else
					{
						$downloadBtn.on('click', downloadThat);

						if (typeof $updateBtn != 'undefined')
						{
							$updateBtn.on('click', autoUpdateThat);
						}
					}

					var $tr = $('<tr/>').appendTo($tbody),
						$td = $('<td class="notes" colspan="2"/>').appendTo($tr);

					new ReleaseNotes($td, response.app.releases, '@@@appName@@@');
				}
			}
			else
			{
				$('#no-system-updates').show();
			}

			/* HIDE */
			if (response.plugins && atLeastOnePluginHasARelease(response.plugins))
			{
				var $table = $('#plugin-updates'),
					$tbody = $table.children('tbody');

				$table.show();

				for (var i in response.plugins)
				{
					var plugin = response.plugins[i];

					if (plugin.releases && plugin.releases.length > 0)
					{
						var $tr = $('<tr/>').appendTo($tbody),
							$th = $('<th/>').appendTo($tr),
							$td = $('<td class="thin rightalign"/>').appendTo($tr);

						$th.html(plugin.displayName+' '+plugin.releases[0].version);

						$td.html('<a class="btn" href="'+Craft.getUrl('updates/'+plugin['class'].toLowerCase())+'">'+Craft.t('Update')+'</a>');

						var $tr = $('<tr/>').appendTo($tbody),
							$td = $('<td class="notes" colspan="2"/>').appendTo($tr);

						new ReleaseNotes($td, plugin.releases, plugin.displayName);
					}
				}
			}
			else
			{
				$('#no-plugin-updates').show();
			}
			/* end HIDE */

			$('#updates').fadeIn('fast');

			var count = 0;
			if (response.app && response.app.releases)
			{
				count++;
			}

			if (atLeastOnePluginHasARelease(response.plugins))
			{
				count++;
			}

			if (count >= 2)
			{
				$('#update-all').fadeIn('fast');
			}
		}
	});

});


})(jQuery);
