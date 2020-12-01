/** global: Craft */
/** global: Garnish */
/**
 * Delete User Modal
 */
Craft.DeleteUserModal = Garnish.Modal.extend(
    {
        id: null,
        userId: null,

        $deleteActionRadios: null,
        $deleteSpinner: null,

        userSelect: null,
        _deleting: false,

        init: function(userId, settings) {
            this.id = Math.floor(Math.random() * 1000000000);
            this.userId = userId;
            settings = $.extend(Craft.DeleteUserModal.defaults, settings);

            let $form = $(
                    '<form class="modal fitted deleteusermodal" method="post" accept-charset="UTF-8">' +
                    Craft.getCsrfInput() +
                    '<input type="hidden" name="action" value="users/delete-user"/>' +
                    (!Garnish.isArray(this.userId) ? '<input type="hidden" name="userId" value="' + this.userId + '"/>' : '') +
                    (settings.redirect ? '<input type="hidden" name="redirect" value="' + settings.redirect + '"/>' : '') +
                    '</form>'
                ).appendTo(Garnish.$bod);
            let $body = $(
                    '<div class="body">' +
                    '<div class="content-summary">' +
                    '<p>' + Craft.t('app', 'What do you want to do with their content?') + '</p>' +
                    '<ul class="bullets"></ul>' +
                    '</div>' +
                    '<div class="options">' +
                    '<label><input type="radio" name="contentAction" value="transfer"/> ' + Craft.t('app', 'Transfer it to:') + '</label>' +
                    '<div id="transferselect' + this.id + '" class="elementselect">' +
                    '<div class="elements"></div>' +
                    '<button type="button" class="btn add icon dashed">' + Craft.t('app', 'Choose a user') + '</button>' +
                    '</div>' +
                    '</div>' +
                    '<div>' +
                    '<label class="error"><input type="radio" name="contentAction" value="delete"/> ' + Craft.t('app', 'Delete their content') + '</label>' +
                    '</div>' +
                    '</div>'
                ).appendTo($form);
            let $buttons = $('<div class="buttons right"/>').appendTo($body);
            let $cancelBtn = $('<button/>', {
                type: 'button',
                class: 'btn',
                text: Craft.t('app', 'Cancel'),
            }).appendTo($buttons);

            if (settings.contentSummary.length) {
                for (let i = 0; i < settings.contentSummary.length; i++) {
                    $body.find('ul').append($('<li/>', { text: settings.contentSummary[i] }));
                }
            } else {
                $body.find('ul').remove();
            }

            this.$deleteActionRadios = $body.find('input[type=radio]');
            this.$deleteSubmitBtn = $('<button/>', {
                type: 'submit',
                class: 'btn submit disabled',
                text: this._submitBtnLabel(false),
            }).appendTo($buttons);
            this.$deleteSpinner = $('<div class="spinner hidden"/>').appendTo($buttons);

            var idParam;

            if (Garnish.isArray(this.userId)) {
                idParam = ['and'];

                for (let i = 0; i < this.userId.length; i++) {
                    idParam.push('not ' + this.userId[i]);
                }
            }
            else {
                idParam = 'not ' + this.userId;
            }

            this.userSelect = new Craft.BaseElementSelectInput({
                id: 'transferselect' + this.id,
                name: 'transferContentTo',
                elementType: 'craft\\elements\\User',
                criteria: {
                    id: idParam
                },
                limit: 1,
                modalSettings: {
                    closeOtherModals: false
                },
                onSelectElements: $.proxy(function() {
                    this.updateSizeAndPosition();

                    if (!this.$deleteActionRadios.first().prop('checked')) {
                        this.$deleteActionRadios.first().trigger('click');
                    }
                    else {
                        this.validateDeleteInputs();
                    }
                }, this),
                onRemoveElements: $.proxy(this, 'validateDeleteInputs'),
                selectable: false,
                editable: false
            });

            this.addListener($cancelBtn, 'click', 'hide');

            this.addListener(this.$deleteActionRadios, 'change', 'validateDeleteInputs');
            this.addListener($form, 'submit', 'handleSubmit');

            this.base($form, settings);
        },

        _submitBtnLabel: function(withContent) {
            let message = withContent
                ? 'Delete {num, plural, =1{user} other{users}} and content'
                : 'Delete {num, plural, =1{user} other{users}}';

            return Craft.t('app', message, {
                num: Garnish.isArray(this.userId) ? this.userId.length : 1,
            });
        },

        validateDeleteInputs: function() {
            var validates = false;

            if (this.$deleteActionRadios.eq(1).prop('checked')) {
                validates = true;
                this.$deleteSubmitBtn.text(this._submitBtnLabel(true));
            } else {
                this.$deleteSubmitBtn.text(this._submitBtnLabel(false));
                if (this.$deleteActionRadios.eq(0).prop('checked')) {
                    validates = !!this.userSelect.totalSelected;
                }
            }

            this.updateSizeAndPosition();

            if (validates) {
                this.$deleteSubmitBtn.removeClass('disabled');
            }
            else {
                this.$deleteSubmitBtn.addClass('disabled');
            }

            return validates;
        },

        handleSubmit: function(ev) {
            if (this._deleting || !this.validateDeleteInputs()) {
                ev.preventDefault();
                return;
            }

            this.$deleteSubmitBtn.addClass('active');
            this.$deleteSpinner.removeClass('hidden');
            this.disable();
            this.userSelect.disable();
            this._deleting = true;

            // Let the onSubmit callback prevent the form from getting submitted
            try {
                if (this.settings.onSubmit() === false) {
                    ev.preventDefault();
                }
            } catch (e) {
                ev.preventDefault();
                this.$deleteSpinner.addClass('hidden');
                throw e;
            }
        },

        onFadeIn: function() {
            // Auto-focus the first radio
            if (!Garnish.isMobileBrowser(true)) {
                this.$deleteActionRadios.first().trigger('focus');
            }

            this.base();
        }
    },
    {
        defaults: {
            contentSummary: [],
            onSubmit: $.noop,
            redirect: null
        }
    });
