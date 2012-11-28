(function($) {


Blocks.UpdatesWidget = Blocks.Base.extend({

	$widget: null,

	init: function(widgetId)
	{
		this.$widget = $('#widget'+widgetId);
		this.$widget.addClass('loading');

		var data = {
			handle: 'all'
		};

		Blocks.postActionRequest('update/getUpdates', data, $.proxy(function(response) {
			var $tds = this.$widget.find('td');

			for (var i = 0; i < response.updateInfo.length; i++)
			{
				var item = response.updateInfo[i],
					$td = $($tds[i]);

				var html = '<div><strong>'+item.name+'</strong> '+item.version+'</div>';

				if (item.critical)
				{
					html += '<div class="badge warning">'+Blocks.t('Critical')+'</div>';
				}

				var date = new Date(item.releaseDate * 1000);
				var year = date.getFullYear();
				var month = date.getMonth() + 1;
				var day = date.getDate();
				html += '<span class="light nowrap timestamp">'+year+'-'+month+'-'+day+'</span>';

				$td.html(html);
			}

			this.$widget.removeClass('loading');
		}, this));
	}
});


})(jQuery);
