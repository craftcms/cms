(function($) {
    Craft.AssetIndexesUtility = Garnish.Base.extend(
        {
            $trigger: null,
            $form: null,

            totalActions: null,
            completedActions: null,
            loadingActions: null,
            queue: null,

            cacheImages: false,
            sessionId: null,

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
                        this.progressBar = new Craft.ProgressBar(this.$status, true);
                    } else {
                        this.progressBar.resetProgressBar();
                    }

                    this.totalActions = 0;
                    this.loadingActions = 0;
                    this.completedActions = 0;
                    this.queue = [];

                    this.progressBar.$progressBar.removeClass('hidden');
                    this.progressBar.$progressBarStatus.removeClass('hidden');

                    this.progressBar.$progressBar.velocity('stop').velocity(
                        {
                            opacity: 1
                        },
                        {
                            complete: $.proxy(function() {
                                var postData = Garnish.getPostData(this.$form),
                                    params = Craft.expandPostArray(postData);
                                    params.start = true;

                                this.cacheImages = params.cacheImages;

                                Craft.postActionRequest('utilities/asset-index-perform-action', {params: params}, function (response) {
                                    if (response.indexingData) {
                                        this.sessionId = response.indexingData.sessionId;

                                        // Load up all the data
                                        for (var i = 0; i < response.indexingData.volumes.length; i++) {
                                            var volumeData = response.indexingData.volumes[i];

                                            for (var requestCounter = 0; requestCounter < volumeData.total; requestCounter++) {
                                                this.queue.push({
                                                    process: true,
                                                    sessionId: this.sessionId,
                                                    volumeId: volumeData.volumeId,
                                                    cacheImages: this.cacheImages
                                                });
                                                this.totalActions++;
                                            }
                                        }

                                        if (this.totalActions > 0) {
                                            this.processIndexing();
                                        } else {
                                            this.finishIndexing();
                                        }
                                    }
                                }.bind(this));
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
                this.progressBar.setItemCount(this.totalActions);
                this.progressBar.setProcessedItemCount(this.completedActions);
                this.progressBar.updateProgressBar();
            },

            processIndexing: function() {
                if (this.completedActions + this.loadingActions < this.totalActions && this.loadingActions < Craft.AssetIndexesUtility.maxConcurrentActions) {
                    this.loadingActions++;

                    var params = this.queue.shift();
                    Craft.postActionRequest('utilities/asset-index-perform-action', {params: params}, function (response) {
                        this.loadingActions--;
                        this.completedActions++;

                        this.updateProgressBar();

                        if (response && response.error) {
                            alert(response.error);
                        }

                        if (this.completedActions == this.totalActions) {
                            this.finishIndexing();
                        } else {
                            this.processIndexing();
                        }
                    }.bind(this));

                    // Try again, in case we have more space.
                    this.processIndexing();
                }
            },

            showConfirmDialog: function(data) {
                var $modal = $('<form class="modal fitted confirmmodal"/>').appendTo(Garnish.$bod),
                    $body = $('<div class="body"/>').appendTo($modal).html(data.confirm),
                    $footer = $('<footer class="footer"/>').appendTo($modal),
                    $buttons = $('<div class="buttons right"/>').appendTo($footer);

                if (data.showDelete) {
                    let $cancelBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn',
                        text: Craft.t('app', 'Keep them'),
                    }).appendTo($buttons);
                    $('<button/>', {
                        type: 'submit',
                        class: 'btn submit',
                        text: Craft.t('app', 'Delete them'),
                    }).appendTo($buttons);

                    this.addListener($cancelBtn, 'click', function() {
                        modal.hide();
                        this.onComplete();
                    });
                } else {
                    $('<button/>', {
                        type: 'submit',
                        class: 'btn submit',
                        text: Craft.t('app', 'OK'),
                    }).appendTo($buttons);
                }

                Craft.initUiElements($body);

                var modal = new Garnish.Modal($modal, {
                    hideOnEsc: false,
                    hideOnShadeClick: false,
                    onHide: $.proxy(this, 'onActionResponse')
                });

                this.addListener($modal, 'submit', function(ev) {
                    ev.preventDefault();

                    modal.settings.onHide = $.noop;
                    modal.hide();

                    var postData = Garnish.getPostData($body);
                    var params = Craft.expandPostArray(postData);

                    $.extend(params, data.params);
                    params.finish = true;

                    Craft.postActionRequest('utilities/asset-index-perform-action', {params: params}, $.noop);
                    this.onComplete();
                });
            },

            finishIndexing: function() {
                var params = {
                    sessionId: this.sessionId,
                    overview: true
                };

                Craft.postActionRequest('utilities/asset-index-perform-action', {params: params}, function (response) {
                    if (response.confirm) {
                        this.hideProgressBar();
                        this.showConfirmDialog(response);
                    } else {
                        this.onComplete();
                    }
                }.bind(this));
            },

            hideProgressBar: function () {
                this.progressBar.$progressBarStatus.addClass('hidden');
                this.progressBar.$progressBar.velocity({opacity: 0}, {
                    duration: 'fast'
                });
            },

            onComplete: function() {
                this.hideProgressBar();

                if (!this.$allDone) {
                    this.$allDone = $('<div class="alldone" data-icon="done" />').appendTo(this.$status);
                    this.$allDone.css('opacity', 0);
                    this.$allDone.velocity({opacity: 1}, {duration: 'fast'});
                    this.$trigger.removeClass('disabled');
                    this.$trigger.trigger('focus');
                }
            }
        },
        {
            maxConcurrentActions: 3
        });
})(jQuery);
