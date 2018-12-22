/** global: Craft */
/** global: Garnish */
/**
 * Element editor
 */
Craft.BaseElementEditor = Garnish.Base.extend(
    {
        $element: null,
        elementId: null,
        siteId: null,

        $form: null,
        $fieldsContainer: null,
        $cancelBtn: null,
        $saveBtn: null,
        $spinner: null,

        $siteSelect: null,
        $siteSpinner: null,

        hud: null,

        init: function(element, settings) {
            // Param mapping
            if (typeof settings === 'undefined' && $.isPlainObject(element)) {
                // (settings)
                settings = element;
                element = null;
            }

            this.$element = $(element);
            this.setSettings(settings, Craft.BaseElementEditor.defaults);

            this.loadHud();
        },

        setElementAttribute: function(name, value) {
            if (!this.settings.attributes) {
                this.settings.attributes = {};
            }

            if (value === null) {
                delete this.settings.attributes[name];
            }
            else {
                this.settings.attributes[name] = value;
            }
        },

        getBaseData: function() {
            var data = $.extend({}, this.settings.params);

            if (this.settings.siteId) {
                data.siteId = this.settings.siteId;
            }
            else if (this.$element && this.$element.data('site-id')) {
                data.siteId = this.$element.data('site-id');
            }

            if (this.settings.elementId) {
                data.elementId = this.settings.elementId;
            }
            else if (this.$element && this.$element.data('id')) {
                data.elementId = this.$element.data('id');
            }

            if (this.settings.elementType) {
                data.elementType = this.settings.elementType;
            }

            if (this.settings.attributes) {
                data.attributes = this.settings.attributes;
            }

            return data;
        },

        loadHud: function() {
            this.onBeginLoading();
            var data = this.getBaseData();
            data.includeSites = this.settings.showSiteSwitcher;
            Craft.postActionRequest('elements/get-editor-html', data, $.proxy(this, 'showHud'));
        },

        showHud: function(response, textStatus) {
            this.onEndLoading();

            if (textStatus === 'success') {
                var $hudContents = $();

                if (response.sites) {
                    var $header = $('<div class="hud-header"/>'),
                        $siteSelectContainer = $('<div class="select"/>').appendTo($header);

                    this.$siteSelect = $('<select/>').appendTo($siteSelectContainer);
                    this.$siteSpinner = $('<div class="spinner hidden"/>').appendTo($header);

                    for (var i = 0; i < response.sites.length; i++) {
                        var siteInfo = response.sites[i];
                        $('<option value="' + siteInfo.id + '"' + (siteInfo.id == response.siteId ? ' selected="selected"' : '') + '>' + siteInfo.name + '</option>').appendTo(this.$siteSelect);
                    }

                    this.addListener(this.$siteSelect, 'change', 'switchSite');

                    $hudContents = $hudContents.add($header);
                }

                this.$form = $('<div/>');
                this.$fieldsContainer = $('<div class="fields"/>').appendTo(this.$form);

                this.updateForm(response);

                this.onCreateForm(this.$form);

                var $footer = $('<div class="hud-footer"/>').appendTo(this.$form),
                    $buttonsContainer = $('<div class="buttons right"/>').appendTo($footer);
                this.$cancelBtn = $('<div class="btn">' + Craft.t('app', 'Cancel') + '</div>').appendTo($buttonsContainer);
                this.$saveBtn = $('<input class="btn submit" type="submit" value="' + Craft.t('app', 'Save') + '"/>').appendTo($buttonsContainer);
                this.$spinner = $('<div class="spinner hidden"/>').appendTo($buttonsContainer);

                $hudContents = $hudContents.add(this.$form);

                if (!this.hud) {
                    var hudTrigger = (this.settings.hudTrigger || this.$element);

                    this.hud = new Garnish.HUD(hudTrigger, $hudContents, {
                        bodyClass: 'body elementeditor',
                        closeOtherHUDs: false,
                        onShow: $.proxy(this, 'onShowHud'),
                        onHide: $.proxy(this, 'onHideHud'),
                        onSubmit: $.proxy(this, 'saveElement')
                    });

                    this.hud.$hud.data('elementEditor', this);

                    this.hud.on('hide', $.proxy(function() {
                        delete this.hud;
                    }, this));
                }
                else {
                    this.hud.updateBody($hudContents);
                    this.hud.updateSizeAndPosition();
                }

                // Focus on the first text input
                $hudContents.find('.text:first').trigger('focus');

                this.addListener(this.$cancelBtn, 'click', function() {
                    this.hud.hide();
                });
            }
        },

        switchSite: function() {
            var newSiteId = this.$siteSelect.val();

            if (newSiteId == this.siteId) {
                return;
            }

            this.$siteSpinner.removeClass('hidden');

            this.reloadForm({ siteId: newSiteId }, $.proxy(function(textStatus) {
                this.$siteSpinner.addClass('hidden');
                if (textStatus !== 'success') {
                    // Reset the site select
                    this.$siteSelect.val(this.siteId);
                }
            }, this));
        },

        reloadForm: function(data, callback) {
            data = $.extend(this.getBaseData(), data);

            Craft.postActionRequest('elements/get-editor-html', data, $.proxy(function(response, textStatus) {
                if (textStatus === 'success') {
                    this.updateForm(response);
                }

                if (callback) {
                    callback(textStatus);
                }
            }, this));
        },

        updateForm: function(response) {
            this.siteId = response.siteId;

            this.$fieldsContainer.html(response.html);

            // Swap any instruction text with info icons
            var $instructions = this.$fieldsContainer.find('> .meta > .field > .heading > .instructions');

            for (var i = 0; i < $instructions.length; i++) {

                $instructions.eq(i)
                    .replaceWith($('<span/>', {
                        'class': 'info',
                        'html': $instructions.eq(i).children().html()
                    }))
                    .infoicon();
            }

            Garnish.requestAnimationFrame($.proxy(function() {
                Craft.appendHeadHtml(response.headHtml);
                Craft.appendFootHtml(response.footHtml);
                Craft.initUiElements(this.$fieldsContainer);
            }, this));
        },

        saveElement: function() {
            var validators = this.settings.validators;

            if ($.isArray(validators)) {
                for (var i = 0; i < validators.length; i++) {
                    if ($.isFunction(validators[i]) && !validators[i].call()) {
                        return false;
                    }
                }
            }

            this.$spinner.removeClass('hidden');

            var data = $.param(this.getBaseData()) + '&' + this.hud.$body.serialize();
            Craft.postActionRequest('elements/save-element', data, $.proxy(function(response, textStatus) {
                this.$spinner.addClass('hidden');

                if (textStatus === 'success') {
                    if (response.success) {
                        if (this.$element && this.siteId == this.$element.data('site-id')) {
                            // Update the label
                            var $title = this.$element.find('.title'),
                                $a = $title.find('a');

                            if ($a.length && response.cpEditUrl) {
                                $a.attr('href', response.cpEditUrl);
                                $a.text(response.newTitle);
                            }
                            else {
                                $title.text(response.newTitle);
                            }
                        }

                        this.closeHud();
                        this.onSaveElement(response);
                    }
                    else {
                        this.updateForm(response);
                        Garnish.shake(this.hud.$hud);
                    }
                }
            }, this));
        },

        closeHud: function() {
            this.hud.hide();
            delete this.hud;
        },

        // Events
        // -------------------------------------------------------------------------

        onShowHud: function() {
            this.settings.onShowHud();
            this.trigger('showHud');
        },

        onHideHud: function() {
            this.settings.onHideHud();
            this.trigger('hideHud');
        },

        onBeginLoading: function() {
            if (this.$element) {
                this.$element.addClass('loading');
            }

            this.settings.onBeginLoading();
            this.trigger('beginLoading');
        },

        onEndLoading: function() {
            if (this.$element) {
                this.$element.removeClass('loading');
            }

            this.settings.onEndLoading();
            this.trigger('endLoading');
        },

        onSaveElement: function(response) {
            this.settings.onSaveElement(response);
            this.trigger('saveElement', {
                response: response
            });
        },

        onCreateForm: function($form) {
            this.settings.onCreateForm($form);
        }
    },
    {
        defaults: {
            hudTrigger: null,
            showSiteSwitcher: true,
            elementId: null,
            elementType: null,
            siteId: null,
            attributes: null,
            params: null,
            elementIndex: null,

            onShowHud: $.noop,
            onHideHud: $.noop,
            onBeginLoading: $.noop,
            onEndLoading: $.noop,
            onCreateForm: $.noop,
            onSaveElement: $.noop,

            validators: []
        }
    });
