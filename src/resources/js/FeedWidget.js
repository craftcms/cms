Blocks.FeedWidget = Garnish.Base.extend({

	$widget: null,

	init: function(widgetId, url, limit)
	{
		this.$widget = $('#widget'+widgetId);
		this.$widget.addClass('loading');

		var data = {
			url: url,
			limit: limit
		};

		Blocks.postActionRequest('dashboard/getFeedItems', data, $.proxy(function(response) {
			var $tds = this.$widget.find('td');

			for (var i = 0; i < response.items.length; i++)
			{
				var item = response.items[i],
					$td = $($tds[i]);

				var widgetHtml = '<a href="'+item.permalink+'" target="_blank">'+item.title+'</a> ';

				if (item.date) {
					widgetHtml += '<span class="light nowrap">'+item.date+'</span>';
				}

				$td.html(widgetHtml);
			}

			this.$widget.removeClass('loading');
		}, this));
	}
});
