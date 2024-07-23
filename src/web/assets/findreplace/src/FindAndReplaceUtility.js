(function ($) {
  Craft.FindAndReplaceUtility = Garnish.Base.extend({
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

        this.progressBar.$progressBar.removeClass('hidden');

        this.progressBar.$progressBar.velocity('stop').velocity(
          {
            opacity: 1,
          },
          {
            complete: () => {
              var postData = Garnish.getPostData(this.$form),
                params = Craft.expandPostArray(postData);

              var data = {
                params: params,
              };

              Craft.sendActionRequest('POST', params.action, {data})
                .then((response) => {
                  this.updateProgressBar();
                  setTimeout(this.onComplete.bind(this), 300);
                })
                .catch(({response}) => {
                  Craft.cp.displayError(response.data.message);
                });
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

    onComplete: function () {
      if (!this.$allDone) {
        this.$allDone = $('<div class="alldone" data-icon="done" />').appendTo(
          this.$status
        );
        this.$allDone.css('opacity', 0);
      }

      this.progressBar.$progressBar.velocity(
        {opacity: 0},
        {
          duration: 'fast',
          complete: () => {
            this.$allDone.velocity({opacity: 1}, {duration: 'fast'});
            this.$trigger.removeClass('disabled');
            this.$trigger.focus();
          },
        }
      );

      // Just in case the tool created a new task...
      Craft.cp.trackJobProgress(false, true);
    },
  });
})(jQuery);
