import './update.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.Updater = Garnish.Base.extend({
    $graphic: null,
    $status: null,
    error: null,
    data: null,
    actionPrefix: null,

    init: function (actionPrefix) {
      this.actionPrefix = actionPrefix;
      this.$graphic = $('#graphic');
      this.$status = $('#status');
    },

    parseStatus: function (status) {
      return (
        '<p>' +
        Craft.escapeHtml(status)
          .replace(/\n{2,}/g, '</p><p>')
          .replace(/\n/g, '<br>')
          .replace(/`(.*?)`/g, '<code>$1</code>') +
        '</p>'
      );
    },

    showStatus: function (status) {
      this.$status.html(this.parseStatus(status));
    },

    showError: function (error) {
      this.$graphic.removeClass('spinner').addClass('error');
      this.showStatus(error);
    },

    showErrorDetails: function (details) {
      $('<div/>', {
        id: 'error',
        class: 'code',
        tabindex: 0,
        html: this.parseStatus(details),
      }).appendTo(this.$status);
    },

    postActionRequest: function (action) {
      var data = {
        data: this.data,
      };

      Craft.sendActionRequest('POST', `${this.actionPrefix}/${action}`, {data})
        .then((response) => {
          this.setState(response.data);
        })
        .catch(({response}) => {
          this.handleFatalError(response.data);
        });
    },

    setState: function (state) {
      this.$graphic.addClass('spinner').removeClass('error');

      // Data probably won't be set if this is coming from an option
      if (state.data) {
        this.data = state.data;
      }

      if (state.status) {
        this.showStatus(state.status);
      } else if (state.error) {
        this.showError(state.error);
        if (state.errorDetails) {
          this.showErrorDetails(state.errorDetails);
        }
      }

      if (state.nextAction) {
        this.postActionRequest(state.nextAction);
      } else if (state.options) {
        this.showOptions(state);
      } else if (state.finished) {
        this.onFinish(state.returnUrl);
      }
    },

    showOptions: function (state) {
      var $buttonContainer = $('<div/>', {
        id: 'options',
        class: 'buttons',
      }).appendTo(this.$status);

      for (var i = 0; i < state.options.length; i++) {
        var option = state.options[i],
          $button = $('<a/>', {
            class: 'btn big',
            text: option.label,
          }).appendTo($buttonContainer);

        if (option.submit) {
          $button.addClass('submit');
        }

        if (option.email) {
          $button.attr('href', this.getEmailLink(state, option));
        } else if (option.url) {
          $button.attr('href', option.url);
          $button.attr('target', '_blank');
        } else {
          $button.attr('role', 'button');
          this.addListener($button, 'click', option, 'onOptionSelect');
        }
      }
    },

    getEmailLink: function (state, option) {
      var link =
        'mailto:' +
        option.email +
        '?subject=' +
        encodeURIComponent(option.subject || 'Craft update failure');

      var body = 'Describe what happened here.';
      if (state.errorDetails) {
        body +=
          '\n\n-----------------------------------------------------------\n\n' +
          state.errorDetails;
      }
      link += '&body=' + encodeURIComponent(body);

      return link;
    },

    onOptionSelect: function (ev) {
      this.setState(ev.data);
    },

    onFinish: function (returnUrl) {
      this.$graphic.removeClass('spinner').addClass('success');

      // Redirect in a moment
      setTimeout(function () {
        if (returnUrl) {
          window.location = Craft.getUrl(returnUrl);
        } else {
          window.location = Craft.getUrl('dashboard');
        }
      }, 750);
    },

    handleFatalError: function (data) {
      var details =
        Craft.t('app', 'Status:') +
        ' ' +
        data.statusText +
        '\n\n' +
        Craft.t('app', 'Response:') +
        ' ' +
        data.responseText +
        '\n\n';

      this.setState({
        error: Craft.t('app', 'A fatal error has occurred:'),
        errorDetails: details,
        options: [
          {
            label: Craft.t('app', 'Troubleshoot'),
            url: 'https://craftcms.com/knowledge-base/failed-updates',
          },
          {
            label: Craft.t('app', 'Send for help'),
            email: 'support@craftcms.com',
          },
        ],
      });

      // Tell Craft to disable maintenance mode
      Craft.sendActionRequest('POST', this.actionPrefix + '/finish', {
        data: this.data,
      });
    },
  });
})(jQuery);
