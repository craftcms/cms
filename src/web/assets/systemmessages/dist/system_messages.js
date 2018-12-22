(function($) {
    /** global: Craft */
    /** global: Garnish */
    var SystemMessages = Garnish.Base.extend(
        {
            messages: null,

            init: function() {
                this.messages = [];

                var $container = $('#messages'),
                    $messages = $container.find('.message');

                for (var i = 0; i < $messages.length; i++) {
                    var message = new Message($messages[i]);
                    this.messages.push(message);
                }
            }

        });


    var Message = Garnish.Base.extend(
        {
            $container: null,
            key: null,
            $subject: null,
            $body: null,
            modal: null,

            init: function(container) {
                this.$container = $(container);
                this.key = this.$container.attr('data-key');
                this.$subject = this.$container.find('.subject:first');
                this.$body = this.$container.find('.body:first');

                this.addListener(this.$container, 'click', 'edit');
            },

            edit: function() {
                if (!this.modal) {
                    this.modal = new MessageSettingsModal(this);
                }
                else {
                    this.modal.show();
                }
            },

            updateHtmlFromModal: function() {
                var subject = this.modal.$subjectInput.val(),
                    body = Craft.escapeHtml(this.modal.$bodyInput.val()).replace(/\n/g, '<br>');

                this.$subject.text(subject);
                this.$body.html(body);
            }

        });


    var MessageSettingsModal = Garnish.Modal.extend(
        {
            message: null,

            $languageSelect: null,
            $subjectInput: null,
            $bodyInput: null,
            $saveBtn: null,
            $cancelBtn: null,
            $spinner: null,

            loading: false,

            init: function(message) {
                this.message = message;

                this.base(null, {
                    resizable: true
                });

                this.loadContainer();
            },

            loadContainer: function(language) {
                var data = {
                    key: this.message.key,
                    language: language
                };

                // If CSRF protection isn't enabled, these won't be defined.
                if (typeof Craft.csrfTokenName !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined') {
                    // Add the CSRF token
                    data[Craft.csrfTokenName] = Craft.csrfTokenValue;
                }

                Craft.postActionRequest('system-messages/get-message-modal', data, $.proxy(function(response, textStatus) {
                    if (textStatus === 'success') {
                        if (!this.$container) {
                            var $container = $('<form class="modal fitted message-settings" accept-charset="UTF-8">' + response.body + '</form>').appendTo(Garnish.$bod);
                            this.setContainer($container);
                            this.show();
                        }
                        else {
                            this.$container.html(response.body);
                        }

                        this.$languageSelect = this.$container.find('.language:first > select');
                        this.$subjectInput = this.$container.find('.message-subject:first');
                        this.$bodyInput = this.$container.find('.message-body:first');
                        this.$saveBtn = this.$container.find('.submit:first');
                        this.$cancelBtn = this.$container.find('.cancel:first');
                        this.$spinner = this.$container.find('.spinner:first');

                        this.addListener(this.$languageSelect, 'change', 'switchLanguage');
                        this.addListener(this.$container, 'submit', 'saveMessage');
                        this.addListener(this.$cancelBtn, 'click', 'cancel');

                        setTimeout($.proxy(function() {
                            this.$subjectInput.trigger('focus');
                        }, this), 100);
                    }

                }, this));
            },

            switchLanguage: function() {
                this.loadContainer(this.$languageSelect.val());
            },

            saveMessage: function(event) {
                event.preventDefault();

                if (this.loading) {
                    return;
                }

                var data = {
                    key: this.message.key,
                    language: (this.$languageSelect.length ? this.$languageSelect.val() : Craft.language),
                    subject: this.$subjectInput.val(),
                    body: this.$bodyInput.val()
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
                this.$saveBtn.addClass('active');
                this.$spinner.show();

                Craft.postActionRequest('system-messages/save-message', data, $.proxy(function(response, textStatus) {
                    this.$saveBtn.removeClass('active');
                    this.$spinner.hide();
                    this.loading = false;

                    if (textStatus === 'success') {
                        if (response.success) {
                            // Only update the page if we're editing the current language's message
                            if (data.language === Craft.primarySiteLanguage) {
                                this.message.updateHtmlFromModal();
                            }

                            this.hide();
                            Craft.cp.displayNotice(Craft.t('app', 'Message saved.'));
                        }
                        else {
                            Craft.cp.displayError();
                        }
                    }

                }, this));
            },

            cancel: function() {
                this.hide();

                if (this.message) {
                    this.message.modal = null;
                }
            }

        });


    new SystemMessages();
})(jQuery);
