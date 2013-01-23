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
					Blocks.t('build {build}', { build: release.build }) +
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
				$('<td class="thin"><span class="category '+match[1].toLowerCase()+'">'+Blocks.t(match[1])+'</span></td>').appendTo($tr);
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

Blocks.postActionRequest('update/getAvailableUpdates', function(response) {

	$('#loading').fadeOut('fast', function() {
		if (response.errors && response.errors.length > 0)
		{
			var $div = $('#update-error');

			$div.html(response.errors[0]);
			$div.show();
		}
		else
		{
			if ((response.blocks && response.blocks.releases) || response.packages)
			{
				var $table = $('#system-updates'),
					$tbody = $table.children('tbody');

				$table.show();

				if (response.blocks.releases)
				{
					var $tr = $('<tr/>').appendTo($tbody),
						$th = $('<th/>').appendTo($tr),
						$td = $('<td class="thin rightalign"/>').appendTo($tr);

					$th.html('Blocks '+response.blocks.releases[0].version +
						' <span class="light">' +
						Blocks.t('build {build}', { build: response.blocks.releases[0].build }) +
						'</span>' +
						(response.blocks.criticalUpdateAvailable ? '<span class="critical">'+Blocks.t('Critical')+'</span>' : '')
					);

					var handleDownloadClick = function($btn)
					{
						$btn.on('click', function() {
							var src = Blocks.getActionUrl('update/downloadBlocksUpdate');
							$('<iframe/>', { src: src }).appendTo(Garnish.$bod).hide();
						});
					};

					if (response.blocks.manualUpdateRequired)
					{
						var $btn = $('<div class="btn">'+Blocks.t('Download')+'</div>').appendTo($td);
						handleDownloadClick($btn);
					}
					else
					{
						var $btnGroup = $('<div class="btngroup"/>').appendTo($td),
							$updateBtn = $('<a class="btn" href="'+Blocks.getUrl('updates/go/blocks')+'">'+Blocks.t('Update')+'</a>').appendTo($btnGroup),
							$menuBtn = $('<div class="btn menubtn nolabel"/>').appendTo($btnGroup),
							$menu = $('<div class="menu" data-align="right"/>').appendTo($btnGroup),
							$menuUl = $('<ul/>').appendTo($menu),
							$downloadLi = $('<li/>').appendTo($menuUl),
							$downloadBtn = $('<a>'+Blocks.t('Download')+'</a>').appendTo($downloadLi);

						new Garnish.MenuBtn($menuBtn);
						handleDownloadClick($downloadBtn);
					}

					var $tr = $('<tr/>').appendTo($tbody),
						$td = $('<td class="notes" colspan="2"/>').appendTo($tr);

					new ReleaseNotes($td, response.blocks.releases, 'Blocks');
				}

				if (response.packages)
				{
					var $tr = $('<tr/>').appendTo($tbody),
						$th = $('<th/>').appendTo($tr),
						$td = $('<td class="thin rightalign"/>').appendTo($tr),
						$btn = $('<a class="btn" href="'+Blocks.getUrl('updates/go/blocks')+'">'+Blocks.t('Install')+'</a>').appendTo($td);

					var packageValues = { packages: response.packages.join(', ') };
					$th.html(response.packages.length > 1 ? Blocks.t('{packages} upgrades', packageValues) : Blocks.t('{packages} upgrade', packageValues));

					if (response.blocks)
					{
						$btn.addClass('disabled');
						$btn.attr('title', Blocks.t('Blocks update required'));
					}
				}
			}
			else
			{
				$('#no-system-updates').show();
			}

			if (response.plugins && atLeastOnePluginHasARelease(response.plugins))
			{
				var $table = $('#plugin-updates'),
					$tbody = $table.children('tbody');

				$table.show();

				for (var i  in response.plugins)
				{
					var plugin = response.plugins[i];

					if (plugin.releases && plugin.releases.length > 0)
					{
						var $tr = $('<tr/>').appendTo($tbody),
							$th = $('<th/>').appendTo($tr),
							$td = $('<td class="thin rightalign"/>').appendTo($tr);

						$th.html(plugin.displayName+' '+plugin.releases[0].version);

						$td.html('<a class="btn" href="'+Blocks.getUrl('updates/'+plugin['class'].toLowerCase())+'">'+Blocks.t('Update')+'</a>');

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

			$('#updates').fadeIn('fast');

			var count = 0;
			if (response.blocks && response.blocks.releases)
			{
				count++;
			}

			if (response.packages)
			{
				count++;
			}

			if (atLeastOnePluginHasARelease(response.plugins))
			{
				count++;
			}

			if (count > 2)
			{
				$('#update-all').fadeIn('fast');
			}
		}
	});

});


})(jQuery);
