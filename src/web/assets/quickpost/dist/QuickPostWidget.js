(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.QuickPostWidget = Garnish.Base.extend(
        {
            params: null,
            initFields: null,
            $widget: null,
            $form: null,
            $formClone: null,
            $spinner: null,
            $errorList: null,
            loading: false,

            init: function(widgetId, params, initFields) {
                this.params = params;
                this.initFields = initFields;
                this.$widget = $('#widget' + widgetId);

                var $form = this.$widget.find('form:first');
                this.$formClone = $form.clone();
                this.initForm($form);
            },

            initForm: function($form) {
                this.$form = $form;
                this.$spinner = this.$form.find('.spinner');

                this.initFields();

                var $menuBtn = this.$form.find('> .buttons > .btngroup > .menubtn'),
                    $saveAndContinueEditingBtn = $menuBtn.next().find('> ul > li > a');

                $menuBtn.menubtn();

                this.addListener(this.$form, 'submit', 'handleFormSubmit');
                this.addListener($saveAndContinueEditingBtn, 'click', 'saveAndContinueEditing');
            },

            handleFormSubmit: function(event) {
                event.preventDefault();

                this.save($.proxy(this, 'onSave'));
            },

            saveAndContinueEditing: function() {
                this.save($.proxy(this, 'gotoEntry'));
            },

            save: function(callback) {
                if (this.loading) {
                    return;
                }

                this.loading = true;
                this.$spinner.removeClass('hidden');

                var formData = Garnish.getPostData(this.$form),
                    data = $.extend({enabled: 1}, formData, this.params);

                Craft.postActionRequest('entries/save-entry', data, $.proxy(function(response, textStatus) {
                    this.loading = false;
                    this.$spinner.addClass('hidden');

                    if (this.$errorList) {
                        this.$errorList.children().remove();
                    }

                    if (textStatus === 'success') {
                        if (response.success) {
                            Craft.cp.displayNotice(Craft.t('app', 'Entry saved.'));
                            callback(response);
                        }
                        else {
                            Craft.cp.displayError(Craft.t('app', 'Couldn’t save entry.'));

                            if (response.errors) {
                                if (!this.$errorList) {
                                    this.$errorList = $('<ul class="errors"/>').insertAfter(this.$form);
                                }

                                for (var attribute in response.errors) {
                                    if (!response.errors.hasOwnProperty(attribute)) {
                                        continue;
                                    }

                                    for (var i = 0; i < response.errors[attribute].length; i++) {
                                        var error = response.errors[attribute][i];
                                        $('<li>' + error + '</li>').appendTo(this.$errorList);
                                    }
                                }
                            }
                        }
                    }

                }, this));
            },

            onSave: function(response) {
                // Reset the widget
                var $newForm = this.$formClone.clone();
                this.$form.replaceWith($newForm);
                this.initForm($newForm);

                // Are there any Recent Entries widgets to notify?
                if (typeof Craft.RecentEntriesWidget !== 'undefined') {
                    for (var i = 0; i < Craft.RecentEntriesWidget.instances.length; i++) {
                        var widget = Craft.RecentEntriesWidget.instances[i];
                        if (!widget.params.sectionId || widget.params.sectionId == this.params.sectionId) {
                            widget.addEntry({
                                url: response.cpEditUrl,
                                title: response.title,
                                dateCreated: response.dateCreated,
                                username: response.authorUsername
                            });
                        }
                    }
                }
            },

            gotoEntry: function(response) {
                // Redirect to the entry's edit URL
                Craft.redirectTo(response.cpEditUrl);
            }
        });
})(jQuery);
