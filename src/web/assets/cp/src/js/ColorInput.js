/** global: Craft */
/** global: Garnish */
/**
 * Color input
 */
Craft.ColorInput = Garnish.Base.extend({
    $input: null,
    $colorContainer: null,
    $colorPreview: null,
    $colorInput: null,

    init: function(id) {
        this.$input = $('#'+id);
        this.$colorContainer = this.$input.prev();
        this.$colorPreview = this.$colorContainer.children();

        this.createColorInput();

        this.addListener(this.$input, 'textchange', 'updatePreview');
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
            .addClass('hidden')
            .insertAfter(this.$input);

        this.addListener(this.$colorContainer, 'click', function() {
            this.$colorInput.click();
        });

        this.addListener(this.$colorInput, 'change', 'updateColor');
    },

    updateColor: function() {
        this.$input.val(this.$colorInput.val());
        this.$input.data('garnish-textchange-value', this.$colorInput.val());
        this.updatePreview();
    },

    updatePreview: function() {
        var val = this.$input.val();

        // If empty, set the preview to transparent
        if (!val.length || val === '#') {
            this.$colorPreview.css('background-color', '');
            return;
        }

        // Make sure the value starts with a #
        if (val[0] !== '#') {
            val = '#'+val;
            this.$input.val(val);
            this.$input.data('garnish-textchange-value', val);
        }

        this.$colorPreview.css('background-color', val);
    }
}, {
    _browserSupportsColorInputs: null,

    doesBrowserSupportColorInputs: function()
    {
        if (Craft.ColorInput._browserSupportsColorInputs === null)
        {

        }

        return Craft.ColorInput._browserSupportsColorInputs;
    }
});
