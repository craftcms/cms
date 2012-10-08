(function($) {


var ReleaseNotes = Blocks.Base.extend({

	$table: null,
	$tbody: null,

	init: function($td, releases, product)
	{
		console.log(releases);
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
})


$.post(Blocks.actionUrl+'update/getAvailableUpdates', function(response) {

	$('#loading').fadeOut('fast', function() {
		if (response.blocks || response.packages)
		{
			var $table = $('#system-updates'),
				$tbody = $table.children('tbody');

			$table.show();

			if (response.blocks)
			{
				var $tr = $('<tr/>').appendTo($tbody),
					$th = $('<th/>').appendTo($tr),
					$td = $('<td class="thin rightalign"/>').appendTo($tr);

				$th.html('Blocks '+response.blocks[0].version +
					' <span class="light">' +
					Blocks.t('build {build}', { build: response.blocks[0].build }) +
					'</span>'
				);

				$td.html('<a class="btn" href="'+Blocks.baseUrl+'updates/blocks">'+Blocks.t('Update')+'</a>');

				var $tr = $('<tr/>').appendTo($tbody),
					$td = $('<td class="notes" colspan="2"/>').appendTo($tr);

				new ReleaseNotes($td, response.blocks, 'Blocks');
			}

			if (response.packages)
			{
				var $tr = $('<tr/>').appendTo($tbody),
					$th = $('<th/>').appendTo($tr),
					$td = $('<td class="thin rightalign"/>').appendTo($tr),
					$btn = $('<a class="btn" href="'+Blocks.baseUrl+'updates/blocks">'+Blocks.t('Install')+'</a>').appendTo($td);

				$th.html(Blocks.t('{packages} upgrades', { packages: response.packages.join(', ') }));

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

		if (response.plugins)
		{
			var $table = $('#plugin-updates'),
				$tbody = $table.children('tbody');

			$table.show();

			for (var i = 0; i < response.plugins.length; i++)
			{
				var plugin = response.plugins[i];

				var $tr = $('<tr/>').appendTo($tbody),
					$th = $('<th/>').appendTo($tr),
					$td = $('<td class="thin rightalign"/>').appendTo($tr);

				$th.html(plugin.name+' '+plugin.releases[0].version);

				$td.html('<a class="btn" href="'+Blocks.baseUrl+'updates/'+plugin['class'].toLowerCase()+'">'+Blocks.t('Update')+'</a>');

				var $tr = $('<tr/>').appendTo($tbody),
					$td = $('<td class="notes" colspan="2"/>').appendTo($tr);

				new ReleaseNotes($td, plugin.releases, plugin.name);
			}
		}
		else
		{
			$('#no-plugin-updates').show();
		}

		$('#updates').fadeIn('fast');

		if (response.blocks || response.packages || response.plugins)
		{
			$('#update-all').fadeIn('fast');
		}
	});

});


})(jQuery);
