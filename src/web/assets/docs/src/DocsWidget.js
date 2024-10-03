import './DocsWidget.scss';

(function ($) {
  Craft.DocsWidget = Garnish.Base.extend({
    widgetId: null,
    $widget: null,
    $results: null,

    init: function (widgetId) {
      this.widgetId = widgetId;
      this.$widget = document.getElementById(`docs-widget-${this.widgetId}`);
      this.$results = document.getElementById(
        `docs-widget-${this.widgetId}-search-results`
      );

      this.$results.addEventListener('click', function (e) {
        if (!(e.target instanceof HTMLAnchorElement)) {
          return;
        }

        // Ensure these attributes are set on all outbound links we inject into the DOM:
        e.target.setAttribute('target', '_blank');
        e.target.setAttribute('rel', 'noopener noreferrer');
      });
    },
  });
})(jQuery);
