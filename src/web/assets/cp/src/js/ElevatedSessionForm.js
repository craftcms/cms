/** global: Craft */
/** global: Garnish */
/**
 * Elevated Session Form
 */
Craft.ElevatedSessionForm = Garnish.Base.extend(
    {
        $form: null,
        inputs: null,

        init: function(form, inputs) {
            this.$form = $(form);

            // Only check specific inputs?
            if (typeof inputs !== 'undefined') {
                this.inputs = [];
                inputs = $.makeArray(inputs);

                for (var i = 0; i < inputs.length; i++) {
                    var $inputs = $(inputs[i]);

                    for (var j = 0; j < $inputs.length; j++) {
                        var $input = $inputs.eq(j);

                        this.inputs.push({
                            input: $input,
                            val: Garnish.getInputPostVal($input)
                        });
                    }
                }
            }

            this.addListener(this.$form, 'submit', 'handleFormSubmit');
        },

        handleFormSubmit: function(ev) {
            // Ignore if we're in the middle of getting the elevated session timeout
            if (Craft.elevatedSessionManager.fetchingTimeout) {
                ev.preventDefault();
                return;
            }

            // Are we only interested in certain inputs?
            if (this.inputs) {
                var inputsChanged = false;
                var $input;

                for (var i = 0; i < this.inputs.length; i++) {
                    $input = this.inputs[i].input;
                    // Is this a password input?
                    if ($input.data('passwordInput')) {
                        $input = $input.data('passwordInput').$currentInput;
                    }

                    // Has this input's value changed?
                    if (Garnish.getInputPostVal($input) !== this.inputs[i].val) {
                        inputsChanged = true;
                        break;
                    }
                }

                if (!inputsChanged) {
                    // No need to interrupt the submit
                    return;
                }
            }

            // Prevent the form from submitting until the user has an elevated session
            ev.preventDefault();
            Craft.elevatedSessionManager.requireElevatedSession($.proxy(this, 'submitForm'));
        },

        submitForm: function() {
            // Don't let handleFormSubmit() interrupt this time
            this.disable();
            this.$form.trigger('submit');
            this.enable();
        }
    });
