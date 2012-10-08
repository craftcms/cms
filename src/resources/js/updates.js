(function($) {


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

				$th.html('Blocks '+response.blocks.version +
					' <span class="light">' +
					Blocks.t('build {build}', { build: response.blocks.build }) +
					'</span>'
				);

				$td.html('<a class="btn" href="'+Blocks.baseUrl+'updates/blocks">'+Blocks.t('Update')+'</a>');

				if (response.blocks.notes)
				{
					var $tr = $('<tr/>').appendTo($tbody),
						$td = $('<td class="notes" colspan="2"/>').appendTo($tr),
						$ul = $('<ul class="bullets"/>').appendTo($td);

					for (var i = 0; i < response.blocks.notes.length; i++)
					{
						var $li = $('<li/>').appendTo($ul);
						$li.text(response.blocks.notes[i]);
					}
				}
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

				if (response.blocks)
				{
					var $tr = $('<tr/>').appendTo($tbody),
						$th = $('<th/>').appendTo($tr),
						$td = $('<td class="thin rightalign"/>').appendTo($tr);

					$th.html(plugin.name+' '+plugin.version);

					$td.html('<a class="btn" href="'+Blocks.baseUrl+'updates/'+plugin['class'].toLowerCase()+'">'+Blocks.t('Update')+'</a>');

					if (plugin.notes)
					{
						var $tr = $('<tr/>').appendTo($tbody),
							$td = $('<td class="notes" colspan="2"/>').appendTo($tr),
							$ul = $('<ul class="bullets"/>').appendTo($td);

						for (var j = 0; j < plugin.notes.length; j++)
						{
							var $li = $('<li/>').appendTo($ul);
							$li.text(response.blocks.notes[j]);
						}
					}
				}
			}
		}
		else
		{
			$('#no-plugin-updates').show();
		}

		$('#updates').fadeIn('fast');
	});

});


})(jQuery);
