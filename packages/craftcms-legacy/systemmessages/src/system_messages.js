import './system_messages.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  var SystemMessages = Garnish.Base.extend({
    messages: null,

    init: function () {
      this.messages = [];

      var $container = $('#messages'),
        $messages = $container.find('.message');

      for (var i = 0; i < $messages.length; i++) {
        var message = new Message($messages[i]);
        this.messages.push(message);
      }
    },
  });

  var Message = Garnish.Base.extend({
    $container: null,
    key: null,
    $subject: null,
    $body: null,
    modal: null,

    init: function (container) {
      this.$container = $(container);
      this.key = this.$container.attr('data-key');
      this.$subject = this.$container.find('.subject:first');
      this.$body = this.$container.find('.body:first');

      this.addListener(this.$container, 'click', 'edit');
    },

    edit: function () {
      if (!this.modal) {
        this.modal = new MessageSettingsModal(this);
      } else {
        this.modal.show();
      }
    },

    updateHtmlFromModal: function () {
      var subject = this.modal.$subjectInput.val(),
        body = Craft.escapeHtml(this.modal.$bodyInput.val()).replace(
          /\n/g,
          '<br>'
        );

      this.$subject.text(subject);
      this.$body.html(body);
    },
  });

  var MessageSettingsModal = Garnish.Modal.extend({
    message: null,

    $languageSelect: null,
    $subjectInput: null,
    $bodyInput: null,
    $saveBtn: null,
    $cancelBtn: null,

    loading: false,

    init: function (message) {
      this.message = message;

      this.base(null, {
        resizable: true,
      });

      this.loadContainer();
    },

    loadContainer: function (language) {
      var data = {
        key: this.message.key,
        language: language,
      };

      // If CSRF protection isn't enabled, these won't be defined.
      if (
        typeof Craft.csrfTokenName !== 'undefined' &&
        typeof Craft.csrfTokenValue !== 'undefined'
      ) {
        // Add the CSRF token
        data[Craft.csrfTokenName] = Craft.csrfTokenValue;
      }

      Craft.sendActionRequest('POST', 'system-messages/get-message-modal', {
        data,
      }).then((response) => {
        if (!this.$container) {
          var $container = $(
            '<form class="modal fitted message-settings" accept-charset="UTF-8">' +
              response.data.body +
              '</form>'
          ).appendTo(Garnish.$bod);
          this.setContainer($container);
          this.show();
        } else {
          this.$container.html(response.data.body);
        }

        this.$languageSelect = this.$container.find('.language:first > select');
        this.$subjectInput = this.$container.find('.message-subject:first');
        this.$bodyInput = this.$container.find('.message-body:first');
        this.$saveBtn = this.$container.find('.submit:first');
        this.$cancelBtn = this.$container.find('.cancel:first');

        this.addListener(this.$languageSelect, 'change', 'switchLanguage');
        this.addListener(this.$container, 'submit', 'saveMessage');
        this.addListener(this.$cancelBtn, 'click', 'cancel');

        setTimeout(() => {
          this.$subjectInput.focus();
        }, 100);
      });
    },

    switchLanguage: function () {
      this.loadContainer(this.$languageSelect.val());
    },

    saveMessage: function (event) {
      event.preventDefault();

      if (this.loading) {
        return;
      }

      var data = {
        key: this.message.key,
        language: this.$languageSelect.length
          ? this.$languageSelect.val()
          : Craft.primarySiteLanguage,
        subject: this.$subjectInput.val(),
        body: this.$bodyInput.val(),
      };

      this.$subjectInput.removeClass('error');
      this.$bodyInput.removeClass('error');

      if (!data.subject || !data.body) {
        if (!data.subject) {
          this.$subjectInput.addClass('error');
        }

        if (!data.body) {
          this.$bodyInput.addClass('error');
        }

        Garnish.shake(this.$container);
        return;
      }

      this.loading = true;
      this.$saveBtn.addClass('loading');

      Craft.sendActionRequest('POST', 'system-messages/save-message', {data})
        .then((response) => {
          // Only update the page if we're editing the current language's message
          if (response.data.language === Craft.primarySiteLanguage) {
            this.message.updateHtmlFromModal();
          }

          this.hide();
          Craft.cp.displaySuccess(Craft.t('app', 'Message saved.'));
        })
        .catch((e) => {
          Craft.cp.displayError(e?.response?.data?.message);
        })
        .finally(() => {
          this.$saveBtn.removeClass('loading');
          this.loading = false;
        });
    },

    cancel: function () {
      this.hide();

      if (this.message) {
        this.message.modal = null;
      }
    },
  });

  new SystemMessages();
})(jQuery);
