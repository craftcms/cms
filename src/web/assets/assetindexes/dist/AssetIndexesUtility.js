(function($) {

    Craft.AssetIndexesUtility = Garnish.Base.extend(
        {
            $trigger: null,
            $form: null,

            totalActions: null,
            completedActions: null,
            loadingActions: null,
            queue: null,

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
                    }
                    else {
                        this.progressBar.resetProgressBar();
                    }

                    this.totalActions = 1;
                    this.completedActions = 0;
                    this.queue = [];

                    this.loadingActions = 0;
                    this.currentBatchQueue = [];

                    this.progressBar.$progressBar.removeClass('hidden');

                    this.progressBar.$progressBar.velocity('stop').velocity(
                        {
                            opacity: 1
                        },
                        {
                            complete: $.proxy(function() {
                                var postData = Garnish.getPostData(this.$form),
                                    params = Craft.expandPostArray(postData);
                                params.start = true;

                                this.loadAction({
                                    params: params
                                });

                            }, this)
                        });

                    if (this.$allDone) {
                        this.$allDone.css('opacity', 0);
                    }

                    this.$trigger.addClass('disabled');
                    this.$trigger.trigger('blur');
                }
            },

            updateProgressBar: function() {
                var width = (100 * this.completedActions / this.totalActions);
                this.progressBar.setProgressPercentage(width);
            },

            loadAction: function(data) {
                this.loadingActions++;

                if (typeof data.confirm !== 'undefined' && data.confirm) {
                    this.showConfirmDialog(data);
                }
                else {
                    this.postActionRequest(data.params);
                }
            },

            showConfirmDialog: function(data) {
                var $modal = $('<form class="modal fitted confirmmodal"/>').appendTo(Garnish.$bod),
                    $body = $('<div class="body"/>').appendTo($modal).html(data.confirm),
                    $footer = $('<footer class="footer"/>').appendTo($modal),
                    $buttons = $('<div class="buttons right"/>').appendTo($footer),
                    $cancelBtn = $('<div class="btn">' + Craft.t('app', 'Cancel') + '</div>').appendTo($buttons),
                    $okBtn = $('<input type="submit" class="btn submit" value="' + Craft.t('app', 'OK') + '"/>').appendTo($buttons);

                Craft.initUiElements($body);

                var modal = new Garnish.Modal($modal, {
                    onHide: $.proxy(this, 'onActionResponse')
                });

                this.addListener($cancelBtn, 'click', function() {
                    modal.hide();
                });

                this.addListener($modal, 'submit', function(ev) {
                    ev.preventDefault();

                    modal.settings.onHide = $.noop;
                    modal.hide();

                    var postData = Garnish.getPostData($body);
                    var params = Craft.expandPostArray(postData);

                    $.extend(params, data.params);

                    this.postActionRequest(params);
                });
            },

            postActionRequest: function(params) {
                var data = {
                    params: params
                };

                Craft.postActionRequest('utilities/asset-index-perform-action', data, $.proxy(this, 'onActionResponse'),
                    {
                        complete: $.noop
                    });
            },

            onActionResponse: function(response, textStatus) {
                this.loadingActions--;
                this.completedActions++;

                // Add any new batches to the queue?
                if (textStatus === 'success' && response && response.batches) {
                    for (var i = 0; i < response.batches.length; i++) {
                        if (response.batches[i].length) {
                            this.totalActions += response.batches[i].length;
                            this.queue.push(response.batches[i]);
                        }
                    }
                }

                if (response && response.error) {
                    alert(response.error);
                }

                this.updateProgressBar();

                // Load as many additional items in the current batch as possible
                while (this.loadingActions < Craft.AssetIndexesUtility.maxConcurrentActions && this.currentBatchQueue.length) {
                    this.loadNextAction();
                }

                // Was that the end of the batch?
                if (!this.loadingActions) {
                    // Is there another batch?
                    if (this.queue.length) {
                        this.currentBatchQueue = this.queue.shift();
                        this.loadNextAction();
                    }
                    else {
                        // Quick delay so things don't look too crazy.
                        setTimeout($.proxy(this, 'onComplete'), 300);
                    }
                }
            },

            loadNextAction: function() {
                var data = this.currentBatchQueue.shift();
                this.loadAction(data);
            },

            onComplete: function() {
                if (!this.$allDone) {
                    this.$allDone = $('<div class="alldone" data-icon="done" />').appendTo(this.$status);
                    this.$allDone.css('opacity', 0);
                }

                this.progressBar.$progressBar.velocity({opacity: 0}, {
                    duration: 'fast', complete: $.proxy(function() {
                        this.$allDone.velocity({opacity: 1}, {duration: 'fast'});
                        this.$trigger.removeClass('disabled');
                        this.$trigger.trigger('focus');
                    }, this)
                });
            }
        },
        {
            maxConcurrentActions: 3
        });

})(jQuery);
