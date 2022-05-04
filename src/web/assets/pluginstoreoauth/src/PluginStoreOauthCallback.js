import './pluginstore-oauth-callback.scss';

(function ($) {
  Craft.PluginStoreOauthCallback = Garnish.Base.extend({
    $graphic: null,
    $status: null,
    errorDetails: null,
    data: null,
    settings: null,

    init: function (settings) {
      this.setSettings(settings);

      this.$graphic = $('#graphic');
      this.$status = $('#status');

      if (!this.settings.error) {
        setTimeout(() => {
          this.postActionRequest();
        }, 500);
      } else {
        var errorMsg = this.settings.message
          ? this.settings.message
          : this.settings.error;
        this.$status.html(errorMsg);

        setTimeout(() => {
          window.location = this.settings.redirectUrl;
        }, 1000);
      }
    },

    postActionRequest: function () {
      var fragmentString = window.location.hash.substring(1);
      var fragments = $.parseFragmentString(fragmentString);

      Craft.sendActionRequest('POST', 'plugin-store/save-token', {
        data: fragments,
      })
        .then((response) => {
          this.updateStatus('<p>' + Craft.t('app', 'Connected!') + '</p>');
          this.$graphic.addClass('success');

          // Redirect to the Dashboard in half a second
          setTimeout(() => {
            if (typeof this.settings.redirectUrl != 'undefined') {
              window.location = this.settings.redirectUrl;
            } else {
              window.location = Craft.getCpUrl('plugin-store');
            }
          }, 500);
        })
        .catch(({response}) => {
          if (response.data && response.data.message) {
            this.showError(response.data.message);
          } else {
            this.showFatalError(response);
          }
        });
    },

    showFatalError: function (response) {
      this.$graphic.addClass('error');
      var statusHtml =
        '<p>' +
        Craft.t('app', 'A fatal error has occurred:') +
        '</p>' +
        '<div id="error" class="code">' +
        '<p><strong class="code">' +
        Craft.t('app', 'Status:') +
        '</strong> ' +
        Craft.escapeHtml(response.statusText) +
        '</p>' +
        '<p><strong class="code">' +
        Craft.t('app', 'Response:') +
        '</strong> ' +
        Craft.escapeHtml(response.text()) +
        '</p>' +
        '</div>' +
        '<a class="btn submit big" href="mailto:support@craftcms.com' +
        '?subject=' +
        encodeURIComponent('Craft update failure') +
        '&body=' +
        encodeURIComponent(
          'Describe what happened here.\n\n' +
            '-----------------------------------------------------------\n\n' +
            'Status: ' +
            response.statusText +
            '\n\n' +
            'Response: ' +
            response.text()
        ) +
        '">' +
        Craft.t('app', 'Send for help') +
        '</a>';

      this.updateStatus(statusHtml);
    },

    updateStatus: function (html) {
      this.$status.html(html);
    },

    showError: function (msg) {
      this.$graphic.addClass('error');
      this.updateStatus('<p>' + msg + '</p>');

      const $buttonContainer = $('<div id="junction-buttons"/>').appendTo(
        this.$status
      );

      $('<a/>', {
        class: 'btn big',
        href: Craft.getCpUrl('plugin-store'),
        text: 'Cancel',
      }).appendTo($buttonContainer);

      $('<a/>', {
        class: 'btn big',
        href: Craft.getActionUrl('plugin-store/connect'),
        text: 'Try again',
      }).appendTo($buttonContainer);
    },
  });
})(jQuery);
