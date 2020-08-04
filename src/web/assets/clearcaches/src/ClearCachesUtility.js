(function($) {
    Craft.ClearCachesUtility = Garnish.Base.extend(
        {
            init: function(formId) {
                this.addListener($('form.utility'), 'submit', 'onSubmit');
            },

            onSubmit: function(ev) {
                ev.preventDefault();
                let $form = $(ev.currentTarget);
                let $trigger = $form.find('button.submit');
                let $status = $form.find('.utility-status');

                if ($trigger.hasClass('disabled')) {
                    return;
                }

                let progressBar, $allDone;
                if (!$form.data('progressBar')) {
                    progressBar = new Craft.ProgressBar($status);
                    $form.data('progressBar', progressBar);
                } else {
                    progressBar = $form.data('progressBar');
                    progressBar.resetProgressBar();
                    $allDone = $form.data('allDone');
                }

                progressBar.$progressBar.removeClass('hidden');

                progressBar.$progressBar.velocity('stop').velocity({
                    opacity: 1
                }, {
                    complete: $.proxy(function() {
                        let postData = Garnish.getPostData($form);
                        let params = Craft.expandPostArray(postData);

                        Craft.postActionRequest(params.action, params, (response, textStatus) => {
                            if (response && response.error) {
                                alert(response.error);
                            }

                            progressBar.setProgressPercentage(100);

                            setTimeout(() => {
                                if (!$allDone) {
                                    $allDone = $('<div class="alldone" data-icon="done" />').appendTo($status);
                                    $allDone.css('opacity', 0);
                                    $form.data('allDone', $allDone);
                                }

                                progressBar.$progressBar.velocity({opacity: 0}, {
                                    duration: 'fast', complete: () => {
                                        $allDone.velocity({opacity: 1}, {duration: 'fast'});
                                        $trigger.removeClass('disabled');
                                        $trigger.trigger('focus');
                                    },
                                });
                            }, 300);
                        }, {
                            complete: $.noop
                        });
                    }, this)
                });

                if ($allDone) {
                    $allDone.css('opacity', 0);
                }

                $trigger.addClass('disabled');
                $trigger.trigger('blur');
            },
        });
})(jQuery);
