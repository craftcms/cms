(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.FeedWidget = Garnish.Base.extend(
        {
            $widget: null,

            init: function(widgetId, url, limit) {
                this.$widget = $('#widget' + widgetId);
                this.$widget.addClass('loading');

                var data = {
                    url: url,
                    limit: limit
                };

                Craft.postActionRequest('dashboard/get-feed-items', data, $.proxy(function(response, textStatus) {
                    this.$widget.removeClass('loading');

                    if (textStatus === 'success') {
                        this.$widget.find('table')
                            .attr('dir', response.dir);

                        var $tds = this.$widget.find('td');

                        for (var i = 0; i < response.items.length; i++) {
                            var item = response.items[i],
                                $td = $($tds[i]);

                            var widgetHtml = $('<a/>', {
                                href: item.permalink,
                                target: '_blank',
                                text: item.title
                            }).get(0).outerHTML + ' ';

                            if (item.date) {
                                widgetHtml += '<span class="light nowrap">' + item.date + '</span>';
                            }

                            $td.html(widgetHtml);
                        }
                    }

                }, this));
            }
        });
})(jQuery);
