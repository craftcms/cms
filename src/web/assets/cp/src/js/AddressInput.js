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

        this.setSettings(settings, Craft.AddressInput.defaults);

        this.$addressCard = $('#' + id);
        this.$addressCardHeader = this.$addressCard.find('.address-card-header');
        this.$addressCardBody = this.$addressCard.find('.address-card-body');
        this.$addressCardFields = this.$addressCard.find('.address-card-fields');
        this.$addressCardFieldsContent = this.$addressCard.find('.address-card-fields-content');

        if (this.settings.static) {
            this.$addressCard.addClass('static');
        }

        // address editor body
        this.$body = $('<div/>', {class: 'so-body'});

        // Fields
        this.$slideoutFieldsContainer = $('<div/>', {class: 'fields'}).appendTo(this.$body);

        // Footer
        this.$footer = $('<div/>', {class: 'so-footer'});
        const $spacer = $('<div/>', {class: 'so-spacer'}).appendTo(this.$footer);

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

            this.$addressCardFieldsContent.on('change', 'select', function(ev) {
                self.refreshStandardFields();
            });

            // Edit
            this.$addressCard.hover(function() {
                $(this).css('cursor', 'pointer');
            });
            this.$addressCard.on( 'click', (ev) => {
                ev.preventDefault();
                this.openSlideout();
            });

            this.$addressCardHeader.on( 'click', (ev) => {
                ev.stopPropagation();
            });

            // Remove
            this.$addressCard.find('[data-action=\'remove\']').on( 'click', (ev) => {
                ev.preventDefault();
                this.$addressCard.remove();
            });

            Garnish.shortcutManager.registerShortcut(Garnish.ESC_KEY, () => {
                this.done();
            });
            this.addListener(this.slideout.$shade, 'click', () => {
                this.done();
            });
            this.addListener(this.slideout.$container, 'submit', ev => {
                ev.preventDefault();
                this.done();
            });
        }

        this.initialized = true;
    },
    refreshStandardFields(){
        this.$saveSpinner.removeClass('hidden');
        var data = new FormData(this.slideout.$container[0]);
        data.append('name', this.baseName);
        // for(let [name, value] of data) {
        //     console.log(`${name} = ${value}`);
        // }
        this.sendActionRequest(data).then(response => {
            this.$addressCardFieldsContent.find('.address-standard-fields').html(response.data.fieldHtml);
            Garnish.requestAnimationFrame(() => {
                Craft.appendHeadHtml(response.data.headHtml);
                Craft.appendBodyHtml(response.data.bodyHtml);
                Craft.initUiElements(this.slideout.$container);
            });
            this.$saveSpinner.addClass('hidden');
        }).catch(e => {
            console.log(e);
        });
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
    done: function() {
        this.$saveSpinner.removeClass('hidden');
        this.closeSlideout();
    },
    closeSlideout: function() {
        this.$addressCardFieldsContent.appendTo(this.$addressCardFields);
        this.slideout.close();
    },
    sendActionRequest: function(data) {
        return Craft.sendActionRequest('POST', this.settings.action, {
            data: data
        });
    },
}, {
    defaults: {
        static: false,
        action: 'users/render-address',
    }
});