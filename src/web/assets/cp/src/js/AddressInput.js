/** global: Craft */
/** global: Garnish */
/**
 * Address class
 */
Craft.AddressInput = Garnish.Base.extend({
    initialized: false,

    id: null,
    baseName: null,

    initialData: null,

    $addressCard: null,
    $addressCardHeader: null,
    $addressCardBody: null,
    $addressCardFields: null,
    $addressCardFieldsContent: null,
    slideout: null,

    $body: null,
    $slideoutFieldsContainer: null,

    $footer: null,
    $doneBtn: null,
    $cancelBtn: null,

    init: function(id, baseName, settings) {
        var self = this;
        this.id = id;
        this.baseName = baseName;
        console.log(baseName);
        this.setSettings(settings, Craft.AddressInput.defaults);

        this.$addressCard = $('#' + id);
        this.$addressCardHeader = this.$addressCard.find('.address-card-header');
        this.$addressCardBody = this.$addressCard.find('.address-card-body');
        this.$addressCardFields = this.$addressCard.find('.address-card-fields');
        this.$addressCardFieldsContent = this.$addressCard.find('.address-card-fields-content');

        if (this.settings.static) {
            this.$addressCard.addClass('static');
        }

        // this.initialData = this._getData(this.$addressCardFieldsContent);;

        // address editor body
        this.$body = $('<div/>', {class: 'so-body'});

        // Fields
        this.$slideoutFieldsContainer = $('<div/>', {class: 'fields'}).appendTo(this.$body);

        // Footer
        this.$footer = $('<div/>', {class: 'so-footer'});
        const $spacer = $('<div/>', {class: 'so-spacer'}).appendTo(this.$footer);
        this.$cancelBtn = $('<button/>', {
            type: 'button',
            class: 'btn',
            text: Craft.t('app', 'Cancel'),
        }).appendTo(this.$footer);
        this.$doneBtn = $('<button/>', {
            type: 'submit',
            class: 'btn submit',
            text: Craft.t('app', 'Done'),
        }).appendTo(this.$footer);

        this.$saveSpinner = $('<div/>', {class: 'spinner hidden'}).appendTo(this.$footer);

        let $contents = this.$body.add(this.$footer);

        if (!this.initialized) {
            this.slideout = new Craft.Slideout($contents, {
                containerElement: 'form',
                autoOpen: false,
                closeOnEsc: true,
                closeOnShadeClick: true,
                containerAttributes: {
                    // action: '',
                    // method: 'post',
                    // novalidate: '',
                    class: 'address-editor',
                }
            });

            this.slideout.$container.data('addressEditor', this);

            this.$addressCardFieldsContent.on('change', 'select', function(ev) {
                console.log('TODO: handle country and state to refresh the form');
            });

            // Edit
            this.$addressCardBody.hover(function() {
                $(this).css('cursor', 'pointer');
            });
            this.addListener(this.$addressCardBody, 'click', (ev) => {
                console.log('TODO: handle edit');
                ev.preventDefault();
                this.openSlideout();
            });
            this.$addressCardHeader.find('[data-action=\'edit\']').on('click', (ev) => {
                ev.preventDefault();
                this.openSlideout();
            });

            // Remove
            this.$addressCardHeader.find('[data-action=\'remove\']').on('click', (ev) => {
                ev.preventDefault();
                this.$addressCard.remove();
            });

            Garnish.shortcutManager.registerShortcut(Garnish.ESC_KEY, () => {
                this.maybeCloseSlideout();
            });
            this.addListener(this.$cancelBtn, 'click', () => {
                this.maybeCloseSlideout();
            });
            this.addListener(this.slideout.$shade, 'click', () => {
                this.maybeCloseSlideout();
            });

            this.addListener(this.slideout.$container, 'submit', ev => {
                ev.preventDefault();
                this.done();
            });
        }

        this.initialized = true;
    },
    openSlideout: function() {
        this.$addressCardFieldsContent.appendTo(this.$slideoutFieldsContainer);
        this.slideout.open();
    },
    isDirty: function() {
        var currentData = this._getData(this.slideout.$container[0]);

        return this.initialData !== null && currentData !== this.initialData;
    },
    _getData: function(from) {
        var data = new FormData(from);
        data.append('name', this.baseName);
        data.append('id', this.id);

        return data;
    },
    maybeCloseSlideout: function() {
        if (!this.slideout.isOpen) {
            return;
        }

        if (!this.isDirty() || confirm('Are you sure you want to close the editor? Any changes will be lost.')) {
            this.closeSlideout();
        }
    },
    done: function() {
        this.$saveSpinner.removeClass('hidden');

        var data = this._getData(this.slideout.$container[0]);

        this.sendActionRequest(data).then(response => {
            var $cardBody = $(response.data.fieldHtml).find('.address-card-body');
            this.$addressCardBody.html($cardBody.html());

            Garnish.requestAnimationFrame(() => {
                Craft.appendHeadHtml(response.data.headHtml);
                Craft.appendFootHtml(response.data.footHtml);
                Craft.initUiElements(this.slideout.$container);
            });
            this.$saveSpinner.addClass('hidden');
            this.closeSlideout();

        }).catch(e => {
            console.log(e);
        });
    },
    closeSlideout: function() {
        this.$addressCardFieldsContent.appendTo(this.$addressCardFields);
        this.slideout.close();
    },
    sendActionRequest: function(data) {
        return Craft.sendActionRequest('POST', this.settings.getInputHtmlUrl, {
            data: data
        });
    },
}, {
    defaults: {
        static: false,
        getInputHtmlUrl: Craft.getActionUrl('addresses/get-input-html')
    }
});