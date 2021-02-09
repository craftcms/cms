/** global: Craft */
/** global: Garnish */
/**
 * Color input
 */
Craft.ColorInput = Garnish.Base.extend({
    $container: null,
    $input: null,
    $colorContainer: null,
    $colorPreview: null,
    $colorInput: null,

    init: function(container) {
        this.$container = $(container);
        this.$input = this.$container.children('.color-input');
        this.$colorContainer = this.$container.children('.color');
        this.$colorPreview = this.$colorContainer.children('.color-preview');

        this.createColorInput();
        this.handleTextChange();

        this.addListener(this.$input, 'input', 'handleTextChange');
    },

    createColorInput: function() {
        var input = document.createElement('input');
        input.setAttribute('type', 'color');

        if (input.type !== 'color') {
            // The browser doesn't support input[type=color]
            return;
        }

        this.$colorContainer.removeClass('static');
        this.$colorInput = $(input)
            .addClass('color-preview-input')
            .appendTo(this.$colorPreview);

        this.addListener(this.$colorInput, 'click', function (ev) {
            ev.stopPropagation();
        });

        this.addListener(this.$colorContainer, 'click', function() {
            this.$colorInput.trigger('click');
        });

        this.addListener(this.$colorInput, 'input', 'updateColor');
    },

    updateColor: function() {
        this.$input.val(this.$colorInput.val());
        this.handleTextChange();
    },

    handleTextChange: function() {
        var val = this.$input.val();

        // If empty, set the preview to transparent
        if (!val.length || val === '#') {
            this.$colorPreview.css('background-color', '');
            return;
        }

        // Make sure the value starts with a #
        if (val[0] !== '#') {
            val = '#' + val;
            this.$input.val(val);
        }

        this.$colorPreview.css('background-color', val);

        if (this.$colorInput) {
            this.$colorInput.val(val);
        }
    }
}, {
    _browserSupportsColorInputs: null,

    doesBrowserSupportColorInputs: function() {
        if (Craft.ColorInput._browserSupportsColorInputs === null) {
        }

        return Craft.ColorInput._browserSupportsColorInputs;
    }
});
