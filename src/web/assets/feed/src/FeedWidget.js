(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.FeedWidget = Garnish.Base.extend({
    $widget: null,

    init: function (widgetId, url, limit) {
      this.$widget = $('#widget' + widgetId);
      this.$widget.addClass('loading');

      // Get the feed data
      axios
        .get('https://feed-proxy.craftcms.com/', {
          params: {
            url: url,
          },
        })
        .then((response) => {
          this.$widget.removeClass('loading');
          this.$widget.find('table').attr('dir', response.data.direction);
          let $tds = this.$widget.find('td');
          // Filter out any invalid links
          response.data.items = (response.data.items || []).filter((item) =>
            item.permalink.match(/^https?:\/\//)
          );
          response.data.items.forEach((item, i) => {
            const $td = $($tds[i]);
            let widgetHtml =
              $('<a/>', {
                href: item.permalink,
                target: '_blank',
                text: item.title,
              }).get(0).outerHTML + ' ';

            if (item.date) {
              widgetHtml +=
                '<span class="light nowrap">' +
                Craft.formatDate(item.date) +
                '</span>';
            }

            $td.html(widgetHtml);
          });

          // Now cache the data
          Craft.sendActionRequest('POST', 'dashboard/cache-feed-data', {
            data: {
              url: url,
              data: response.data,
            },
          });
        })
        .catch(() => {
          this.$widget.removeClass('loading');
          Craft.cp.displayError(Craft.t('app', 'Could not load the feed'));
        });
    },
  });
})(jQuery);
