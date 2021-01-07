(function($) {
    Craft.ClearCachesUtility = Garnish.Base.extend({
        init: function(formId) {
            let $forms = $('form.utility');
            for (let i = 0; i < $forms.length; i++) {
                let $form = $forms.eq(i);
                let $checkboxes = $form.find('input[type=checkbox]');
                let $btn = $form.find('.btn');
                let checkInputs = function() {
                    if ($checkboxes.filter(':checked').length) {
                        $btn.removeClass('disabled');
                    } else {
                        $btn.addClass('disabled');
                    }
                };
                $checkboxes.on('change', checkInputs);
                checkInputs();
                this.addListener($form, 'submit', ev => {
                    ev.preventDefault();
                    if (!$btn.hasClass('disabled')) {
                        this.onSubmit(ev);
                    }
                });
            }
        },

        onSubmit: function(ev) {
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
