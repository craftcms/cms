(function ($) {
  Craft.DbBackupUtility = Garnish.Base.extend({
    $trigger: null,
    $form: null,

    init: function (formId) {
      this.$form = $('#' + formId);
      this.$trigger = $('input.submit', this.$form);
      this.$status = $('.utility-status', this.$form);

      this.addListener(this.$form, 'submit', 'onSubmit');
    },

    onSubmit: function (ev) {
      ev.preventDefault();

      if (!this.$trigger.hasClass('disabled')) {
        if (!this.progressBar) {
          this.progressBar = new Craft.ProgressBar(this.$status);
        } else {
          this.progressBar.resetProgressBar();
        }

        this.progressBar.showProgressBar();

        this.progressBar.$progressBar.velocity('stop').velocity(
          {
            opacity: 1,
          },
          {
            complete: () => {
              if ($('#download-backup').prop('checked')) {
                Craft.downloadFromUrl(
                  'POST',
                  Craft.getActionUrl('utilities/db-backup-perform-action'),
                  this.$form.serialize()
                )
                  .then(() => {
                    this.updateProgressBar();
                    setTimeout(this.onComplete.bind(this), 300);
                  })
                  .catch(() => {
                    Craft.cp.displayError(
                      Craft.t(
                        'app',
                        'There was a problem backing up your database. Please check the Craft logs.'
                      )
                    );
                    this.onComplete(false);
                  });
              } else {
                Craft.sendActionRequest(
                  'POST',
                  'utilities/db-backup-perform-action'
                )
                  .then((response) => {
                    this.updateProgressBar();
                    setTimeout(this.onComplete.bind(this), 300);
                  })
                  .catch(({response}) => {
                    this.updateProgressBar();
                    Craft.cp.displayError(
                      Craft.t(
                        'app',
                        'There was a problem backing up your database. Please check the Craft logs.'
                      )
                    );
                    this.onComplete(false);
                  });
              }
            },
          }
        );

        if (this.$allDone) {
          this.$allDone.css('opacity', 0);
        }

        this.$trigger.addClass('disabled');
        this.$trigger.trigger('blur');
      }
    },

    updateProgressBar: function () {
      var width = 100;
      this.progressBar.setProgressPercentage(width);
    },

    onComplete: function (showAllDone) {
      if (!this.$allDone) {
        this.$allDone = $('<div class="alldone" data-icon="done" />').appendTo(
          this.$status
        );
        this.$allDone.css('opacity', 0);
      }

      // Add equivalent announcement for screen reader users
      Craft.cp.announce(Craft.t('app', 'Success'));

      this.progressBar.hideProgressBar();
      if (typeof showAllDone === 'undefined' || showAllDone === true) {
        this.$allDone.velocity({opacity: 1}, {duration: 'fast'});
      }

      this.$trigger.removeClass('disabled');
      this.$trigger.focus();
    },
  });
})(jQuery);
