/** global: Craft */
/** global: Garnish */
/**
 * Color input
 */
Craft.ColorInput = Garnish.Base.extend(
  {
    $container: null,
    $input: null,
    $colorContainer: null,
    $colorPreview: null,
    $colorInput: null,

    init: function (container) {
      this.$container = $(container);
      this.$input = this.$container.find('.color-input');
      this.$colorContainer = this.$container.children('.color');
      this.$colorPreview = this.$colorContainer.children('.color-preview');

      this.createColorInput();
      this.handleTextChange();

      this.addListener(this.$input, 'input', 'handleTextChange');
    },

    createColorInput: function () {
      var input = document.createElement('input');
      input.setAttribute('type', 'color');

      if (input.type !== 'color') {
        // The browser doesn't support input[type=color]
        return;
      }

      this.$colorContainer.removeClass('static');
      this.$colorInput = $(input)
        .addClass('color-preview-input')
        .attr({
          'aria-controls': this.$input.attr('id'),
          'aria-label': Craft.t('app', 'Color picker'),
        })
        .appendTo(this.$colorPreview);

      this.addListener(this.$colorInput, 'click', function (ev) {
        ev.stopPropagation();
      });

      this.addListener(this.$colorContainer, 'click', function () {
        this.$colorInput.trigger('click');
      });

      this.addListener(this.$colorInput, 'input', 'updateColor');
    },

    updateColor: function () {
      this.$input.val(this.$colorInput.val());
      this.handleTextChange();
    },

    handleTextChange: function () {
      let val = this.$input.val();

      if (val !== (val = Craft.trim(val))) {
        this.$input.val(val);
      }

      // Chop off the #
      if (val.length && val[0] === '#') {
        val = val.substring(1);
        this.$input.val(val);
      }

      // If empty, set the preview to transparent
      if (!val.length) {
        this.$colorPreview.css('background-color', '');
        return;
      }

      // Now normalize it for the UI stuff
      if (val.length === 3) {
        val = val[0].repeat(2) + val[1].repeat(2) + val[2].repeat(2);
      }

      if (val.match(/^[0-9a-f]{6}$/i)) {
        this.$colorPreview.css('background-color', `#${val}`);
        if (this.$colorInput) {
          this.$colorInput.val(`#${val}`);
        }
      } else {
        this.$colorPreview.css('background-color', '');
      }
    },
  },
  {
    _browserSupportsColorInputs: null,

    doesBrowserSupportColorInputs: function () {
      if (Craft.ColorInput._browserSupportsColorInputs === null) {
      }

      return Craft.ColorInput._browserSupportsColorInputs;
    },
  }
);
