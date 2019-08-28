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
                                var postData = Garnish.getPostData(this.$form);

                                // h/t https://nehalist.io/downloading-files-from-post-requests/
                                var request = new XMLHttpRequest();
                                request.open('POST', Craft.getActionUrl(postData.action), true);
                                request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                                request.responseType = postData.downloadBackup ? 'blob' : 'json';

                                request.onload = function() {
                                    this.updateProgressBar();

                                    // Only handle status code 200
                                    if (request.status === 200) {
                                        if (postData.downloadBackup) {
                                            // Try to find out the filename from the content disposition `filename` value
                                            var disposition = request.getResponseHeader('content-disposition');
                                            var matches = /"([^"]*)"/.exec(disposition);
                                            var filename = (matches != null && matches[1] ? matches[1] : 'Backup.zip');

                                            // The actual download
                                            var blob = new Blob([request.response], {type: 'application/zip'});
                                            var link = document.createElement('a');
                                            link.href = window.URL.createObjectURL(blob);
                                            link.download = filename;
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                        }

                                        setTimeout($.proxy(this, 'onComplete'), 300);
                                    } else {
                                        Craft.cp.displayError(Craft.t('app', 'There was a problem backing up your database. Please check the Craft logs.'));
                                        this.onComplete(false);
                                    }
                                }.bind(this);

                                request.send(this.$form.serialize());
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
