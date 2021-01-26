/** global: Craft */
/** global: Garnish */
/**
 * Asset selector modal class
 */
Craft.AssetSelectorModal = Craft.BaseElementSelectorModal.extend({
    $selectTransformBtn: null,
    _selectedTransform: null,

    init: function(elementType, settings) {
        settings = $.extend({}, Craft.AssetSelectorModal.defaults, settings);

        this.base(elementType, settings);

        if (settings.transforms.length) {
            this.createSelectTransformButton(settings.transforms);
        }
    },

    createSelectTransformButton: function(transforms) {
        if (!transforms || !transforms.length) {
            return;
        }

        var $btnGroup = $('<div class="btngroup"/>').appendTo(this.$primaryButtons);
        this.$selectBtn.appendTo($btnGroup);

        this.$selectTransformBtn = $('<button/>', {
            type: 'button',
            class: 'btn menubtn disabled',
            text: Craft.t('app', 'Select transform'),
        }).appendTo($btnGroup);

        var $menu = $('<div class="menu" data-align="right"></div>').insertAfter(this.$selectTransformBtn),
            $menuList = $('<ul></ul>').appendTo($menu);

        for (var i = 0; i < transforms.length; i++) {
            $('<li><a data-transform="' + transforms[i].handle + '">' + transforms[i].name + '</a></li>').appendTo($menuList);
        }

        var MenuButton = new Garnish.MenuBtn(this.$selectTransformBtn, {
            onOptionSelect: $.proxy(this, 'onSelectTransform')
        });
        MenuButton.disable();

        this.$selectTransformBtn.data('menuButton', MenuButton);
    },

    onSelectionChange: function(ev) {
        var $selectedElements = this.elementIndex.getSelectedElements(),
            allowTransforms = false;

        if ($selectedElements.length && this.settings.transforms.length) {
            allowTransforms = true;

            for (var i = 0; i < $selectedElements.length; i++) {
                if (!$('.element.hasthumb:first', $selectedElements[i]).length) {
                    break;
                }
            }
        }

        var MenuBtn = null;

        if (this.$selectTransformBtn) {
            MenuBtn = this.$selectTransformBtn.data('menuButton');
        }

        if (allowTransforms) {
            if (MenuBtn) {
                MenuBtn.enable();
            }

            this.$selectTransformBtn.removeClass('disabled');
        } else if (this.$selectTransformBtn) {
            if (MenuBtn) {
                MenuBtn.disable();
            }

            this.$selectTransformBtn.addClass('disabled');
        }

        this.base();
    },

    onSelectTransform: function(option) {
        var transform = $(option).data('transform');
        this.selectImagesWithTransform(transform);
    },

    selectImagesWithTransform: function(transform) {
        // First we must get any missing transform URLs
        if (typeof Craft.AssetSelectorModal.transformUrls[transform] === 'undefined') {
            Craft.AssetSelectorModal.transformUrls[transform] = {};
        }

        var $selectedElements = this.elementIndex.getSelectedElements(),
            imageIdsWithMissingUrls = [];

        for (var i = 0; i < $selectedElements.length; i++) {
            var $item = $($selectedElements[i]),
                elementId = Craft.getElementInfo($item).id;

            if (typeof Craft.AssetSelectorModal.transformUrls[transform][elementId] === 'undefined') {
                imageIdsWithMissingUrls.push(elementId);
            }
        }

        if (imageIdsWithMissingUrls.length) {
            this.showFooterSpinner();

            this.fetchMissingTransformUrls(imageIdsWithMissingUrls, transform, $.proxy(function() {
                this.hideFooterSpinner();
                this.selectImagesWithTransform(transform);
            }, this));
        } else {
            this._selectedTransform = transform;
            this.selectElements();
            this._selectedTransform = null;
        }
    },

    fetchMissingTransformUrls: function(imageIdsWithMissingUrls, transform, callback) {
        var elementId = imageIdsWithMissingUrls.pop();

        var data = {
            assetId: elementId,
            handle: transform
        };

        Craft.postActionRequest('assets/generate-transform', data, $.proxy(function(response, textStatus) {
            Craft.AssetSelectorModal.transformUrls[transform][elementId] = false;

            if (textStatus === 'success') {
                if (response.url) {
                    Craft.AssetSelectorModal.transformUrls[transform][elementId] = response.url;
                }
            }

            // More to load?
            if (imageIdsWithMissingUrls.length) {
                this.fetchMissingTransformUrls(imageIdsWithMissingUrls, transform, callback);
            } else {
                callback();
            }
        }, this));
    },

    getElementInfo: function($selectedElements) {
        var info = this.base($selectedElements);

        if (this._selectedTransform) {
            for (var i = 0; i < info.length; i++) {
                var elementId = info[i].id;

                if (
                    typeof Craft.AssetSelectorModal.transformUrls[this._selectedTransform][elementId] !== 'undefined' &&
                    Craft.AssetSelectorModal.transformUrls[this._selectedTransform][elementId] !== false
                ) {
                    info[i].url = Craft.AssetSelectorModal.transformUrls[this._selectedTransform][elementId];
                }
            }
        }

        return info;
    },

    onSelect: function(elementInfo) {
        this.settings.onSelect(elementInfo, this._selectedTransform);
    }
}, {
    defaults: {
        canSelectImageTransforms: false,
        transforms: []
    },

    transformUrls: {}
});

// Register it!
Craft.registerElementSelectorModalClass('craft\\elements\\Asset', Craft.AssetSelectorModal);
