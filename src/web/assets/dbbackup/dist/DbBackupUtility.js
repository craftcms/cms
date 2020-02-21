(function($) {
    Craft.DbBackupUtility = Garnish.Base.extend(
        {
            $trigger: null,
            $form: null,

            init: function(formId) {
                this.$form = $('#' + formId);
                this.$trigger = $('input.submit', this.$form);
                this.$status = $('.utility-status', this.$form);

                this.addListener(this.$form, 'submit', 'onSubmit');
            },

            onSubmit: function(ev) {
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
                            opacity: 1
                        },
                        {
                            complete: $.proxy(function() {
                                if (($('#download-backup').prop('checked'))) {
                                    Craft.downloadFromUrl('POST', Craft.getActionUrl('utilities/db-backup-perform-action'), this.$form.serialize())
                                        .then(function() {
                                            this.updateProgressBar();
                                            setTimeout(this.onComplete.bind(this), 300);
                                        }.bind(this))
                                        .catch(function() {
                                            Craft.cp.displayError(Craft.t('app', 'There was a problem backing up your database. Please check the Craft logs.'));
                                            this.onComplete(false);
                                        }.bind(this));
                                } else {
                                    Craft.postActionRequest('utilities/db-backup-perform-action', function(response, textStatus) {
                                        this.updateProgressBar();
                                        if (textStatus === 'success') {
                                            setTimeout(this.onComplete.bind(this), 300);
                                        } else {
                                            Craft.cp.displayError(Craft.t('app', 'There was a problem backing up your database. Please check the Craft logs.'));
                                            this.onComplete(false);
                                        }
                                    }.bind(this))
                                }
                            }.bind(this))
                        });

                    if (this.$allDone) {
                        this.$allDone.css('opacity', 0);
                    }

                    this.$trigger.addClass('disabled');
                    this.$trigger.trigger('blur');
                }
            },

            updateProgressBar: function() {
                var width = 100;
                this.progressBar.setProgressPercentage(width);
            },

            onComplete: function(showAllDone) {
                if (!this.$allDone) {
                    this.$allDone = $('<div class="alldone" data-icon="done" />').appendTo(this.$status);
                    this.$allDone.css('opacity', 0);
                }

                this.progressBar.$progressBar.velocity({opacity: 0}, {
                    duration: 'fast', complete: $.proxy(function() {
                        if (typeof showAllDone === 'undefined' || showAllDone === true) {
                            this.$allDone.velocity({opacity: 1}, {duration: 'fast'});
                        }

                        this.$trigger.removeClass('disabled');
                        this.$trigger.trigger('focus');
                    }, this)
                });
            }
        });
})(jQuery);
