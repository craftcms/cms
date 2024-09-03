(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.UpdatesWidget = Garnish.Base.extend({
    $widget: null,
    $body: null,
    $btn: null,
    checking: false,

    init: function (widgetId, cached) {
      this.$widget = $('#widget' + widgetId);
      this.$body = this.$widget.find('.body:first');
      this.initBtn();

      if (!cached) {
        this.checkForUpdates(false);
      }
    },

    initBtn: function () {
      this.$btn = this.$body.find('.btn:first');
      this.addListener(this.$btn, 'click', function () {
        this.checkForUpdates(true);
      });
    },

    lookLikeWereChecking: function () {
      this.checking = true;
      this.$widget.addClass('loading');
      this.$btn.addClass('disabled');
    },

    dontLookLikeWereChecking: function () {
      this.checking = false;
      this.$widget.removeClass('loading');
    },

    checkForUpdates: function (forceRefresh) {
      if (this.checking) {
        return;
      }

      this.lookLikeWereChecking();
      Craft.cp.checkForUpdates(
        forceRefresh,
        false,
        this.showUpdateInfo.bind(this),
        () => {
          this.dontLookLikeWereChecking();
          this.$body.empty().append(
            $('<p/>', {
              class: 'centeralign error',
              text: Craft.t('app', 'Unable to fetch updates at this time.'),
            })
          );
        }
      );
    },

    showUpdateInfo: function (info) {
      this.dontLookLikeWereChecking();

      if (info.total) {
        var updateText;

        if (info.total == 1) {
          updateText = Craft.t('app', 'One update available!');
        } else {
          updateText = Craft.t('app', '{total} updates available!', {
            total: info.total,
          });
        }

        this.$body.html(
          '<p class="centeralign">' +
            updateText +
            ' <a class="go nowrap" href="' +
            Craft.getUrl('utilities/updates') +
            '">' +
            Craft.t('app', 'Go to Updates') +
            '</a>' +
            '</p>'
        );
      } else {
        this.$body.html(
          '<p class="centeralign">' +
            Craft.t('app', 'Congrats! You’re up to date.') +
            '</p>' +
            '<p class="centeralign"><button type="button" class="btn" data-icon="refresh" aria-label="' +
            Craft.t('app', 'Check again') +
            '">' +
            Craft.t('app', 'Check again') +
            '</button></p>'
        );

        this.initBtn();
      }

      // Update the control panel header badge
      Craft.cp.updateUtilitiesBadge();
    },
  });
})(jQuery);
