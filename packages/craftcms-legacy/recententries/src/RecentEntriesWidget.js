(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.RecentEntriesWidget = Garnish.Base.extend(
    {
      params: null,
      $widget: null,
      $body: null,
      $container: null,
      $list: null,
      hasEntries: null,

      init: function (widgetId, params) {
        this.params = params;
        this.$widget = $('#widget' + widgetId);
        this.$body = this.$widget.find('.body:first');
        this.$container = this.$body.find('.recententries-container:first');
        this.$list = this.$container.find('ol:first');
        this.hasEntries = !!this.$list.length;

        this.$widget.data('widget').on('destroy', this.destroy.bind(this));

        Craft.RecentEntriesWidget.instances.push(this);
      },

      addEntry: function (entry) {
        if (!this.hasEntries) {
          // Create the list first
          this.$list = $('<ol/>').appendTo(this.$container);
        }

        this.$list.prepend(
          '<li class="widget__list-item">' +
            `<a href="${entry.url}">` +
            Craft.escapeHtml(entry.title) +
            '</a> ' +
            '<span class="light">' +
            Craft.escapeHtml(
              (entry.dateCreated ? Craft.formatDate(entry.dateCreated) : '') +
                (entry.dateCreated &&
                entry.username &&
                Craft.edition !== Craft.Solo
                  ? ', '
                  : '') +
                (entry.username && Craft.edition !== Craft.Solo
                  ? entry.username
                  : '')
            ) +
            '</span>' +
            '</li>'
        );

        // Also animate the "No entries exist" text out of view
        if (!this.hasEntries) {
          this.$container.find('.zilch').remove();
          this.hasEntries = true;
        }
      },

      destroy: function () {
        Craft.RecentEntriesWidget.instances.splice(
          $.inArray(this, Craft.RecentEntriesWidget.instances),
          1
        );
        this.base();
      },
    },
    {
      instances: [],
    }
  );
})(jQuery);
