/** global: Craft */
/** global: Garnish */
/**
 * Elevated Session Form
 */
Craft.ElevatedSessionForm = Garnish.Base.extend({
  $form: null,
  inputSelectors: null,
  $inputs: null,
  inputs: null,

  init: function (form, inputs) {
    this.$form = $(form);
    this.inputSelectors = [];
    this.$inputs = $();
    this.inputs = [];

    // Only check specific inputs?
    if (typeof inputs !== 'undefined') {
      $.makeArray(inputs).forEach((selector) => {
        if (typeof selector === 'string') {
          this.inputSelectors.push(selector);
        }

        $(selector, this.$form).each((i, input) => {
          this.$inputs = this.$inputs.add(input);
          const $input = $(input);
          this.inputs.push({
            $input,
            val: Garnish.getInputPostVal($input),
          });
        });
      });
    }

    // is this for a slideout?
    const slideout = this.$form.data('slideout');
    if (slideout) {
    } else {
    }
    this.addListener(this.$form, 'submit', 'handleFormSubmit');
  },

  handleFormSubmit: function (ev) {
    // Ignore if we're in the middle of getting the elevated session timeout
    if (Craft.elevatedSessionManager.fetchingTimeout) {
      ev.preventDefault();
      ev.stopImmediatePropagation();
      ev.cancel = true;
      return;
    }

    if (!this.inputsChanged()) {
      return;
    }

    // Prevent the form from submitting until the user has an elevated session
    ev.preventDefault();
    ev.stopImmediatePropagation();
    ev.cancel = true;

    Craft.elevatedSessionManager.requireElevatedSession(
      this.submitForm.bind(this)
    );
  },

  inputsChanged: function () {
    if (!this.inputSelectors.length && !this.inputs.length) {
      // no way to know
      return true;
    }

    // If we have any input selectors, see if there are any new inputs that match them
    for (const selector of this.inputSelectors) {
      const $inputs = $(selector, this.$form);
      for (let i = 0; i < $inputs.length; i++) {
        const input = $inputs[i];
        if (!this.$inputs.is(input)) {
          return true;
        }
      }
    }

    // If we have any inputs, see if their values have changed
    for (let {$input, val} of this.inputs) {
      // Is this a password input?
      if ($input.data('passwordInput')) {
        $input = $input.data('passwordInput').$currentInput;
      }

      // Has this input's value changed?
      if (Garnish.getInputPostVal($input) !== val) {
        return true;
      }
    }

    return false;
  },

  submitForm: function () {
    // Don't let handleFormSubmit() interrupt this time
    this.disable();
    this.$form.trigger('submit');
    this.enable();
  },
});
