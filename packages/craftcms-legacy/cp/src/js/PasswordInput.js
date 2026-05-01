/** global: Craft */
/** global: Garnish */
/**
 * Password Input
 */
Craft.PasswordInput = Garnish.Base.extend(
  {
    $passwordWrapper: null,
    $passwordInput: null,
    $textInput: null,
    $currentInput: null,

    $showPasswordToggle: null,
    showingPassword: null,

    init: function (passwordInput, settings) {
      this.$passwordInput = $(passwordInput);
      this.$passwordWrapper = this.$passwordInput.parent('.passwordwrapper');
      this.settings = $.extend({}, Craft.PasswordInput.defaults, settings);

      // Is this already a password input?
      if (this.$passwordInput.data('passwordInput')) {
        console.warn('Double-instantiating a password input on an element');
        this.$passwordInput.data('passwordInput').destroy();
      }

      this.$passwordInput.data('passwordInput', this);

      this.$showPasswordToggle = $(
        '<button type="button" class="invisible" />'
      );
      this.$showPasswordToggle.addClass('password-toggle');
      this.$showPasswordToggle.insertAfter(this.$passwordInput);

      this.initInputFocusEvents(this.$passwordInput);
      this.addListener(this.$showPasswordToggle, 'click', 'onClick');
      this.hidePassword();

      this.addListener(
        this.$passwordWrapper.closest('form'),
        'submit',
        'hidePassword'
      );
    },

    setCurrentInput: function ($input) {
      if (this.$currentInput) {
        // Swap the inputs, while preventing the focus animation
        $input.insertAfter(this.$currentInput);
        this.$currentInput.detach();
        $input.focus();

        // Restore the input value
        $input.val(this.$currentInput.val());
      }

      this.$currentInput = $input;

      this.addListener(
        this.$currentInput,
        'keypress,keyup,change,blur',
        'onInputChange'
      );
    },

    updateToggleLabel: function (label) {
      this.$showPasswordToggle.text(label);
    },

    initInputFocusEvents: function ($input) {
      this.addListener($input, 'focus', function () {
        this.$passwordWrapper.addClass('focus');
      });
      this.addListener($input, 'blur', function () {
        this.$passwordWrapper.removeClass('focus');
      });
    },

    showPassword: function () {
      if (this.showingPassword) {
        return;
      }

      if (!this.$textInput) {
        this.$textInput = this.$passwordInput.clone(true);
        this.$textInput.attr({
          type: 'text',
          autocapitalize: 'off',
        });
        this.initInputFocusEvents(this.$textInput);
      }

      this.setCurrentInput(this.$textInput);
      this.updateToggleLabel(Craft.t('app', 'Hide'));
      this.showingPassword = true;
    },

    hidePassword: function () {
      // showingPassword could be null, which is acceptable
      if (this.showingPassword === false) {
        return;
      }

      this.setCurrentInput(this.$passwordInput);
      this.updateToggleLabel(Craft.t('app', 'Show'));
      this.showingPassword = false;
    },

    togglePassword: function () {
      if (this.showingPassword) {
        this.hidePassword();
      } else {
        this.showPassword();
      }

      this.settings.onToggleInput(this.$currentInput);
    },

    onInputChange: function () {
      if (this.$currentInput.val()) {
        this.$showPasswordToggle.removeClass('invisible');
      } else {
        this.$showPasswordToggle.addClass('invisible');
      }
    },

    onClick: function (ev) {
      if (this.$currentInput[0].setSelectionRange) {
        var selectionStart = this.$currentInput[0].selectionStart,
          selectionEnd = this.$currentInput[0].selectionEnd;

        this.togglePassword();
        this.$currentInput[0].setSelectionRange(selectionStart, selectionEnd);
      } else {
        this.togglePassword();
      }
    },

    destroy: function () {
      this.$passwordInput.removeData('passwordInput');
      this.base();
    },
  },
  {
    defaults: {
      onToggleInput: $.noop,
    },
  }
);
