/** global: Craft */
/** global: Garnish */
/**
 * Input Generator
 */
Craft.BaseInputGenerator = Garnish.Base.extend(
    {
        $source: null,
        $target: null,
        $form: null,
        settings: null,

        listening: null,
        timeout: null,

        init: function(source, target, settings) {
            this.$source = $(source);
            this.$target = $(target);
            this.$form = this.$source.closest('form');

            this.setSettings(settings);

            this.startListening();
        },

        setNewSource: function(source) {
            var listening = this.listening;
            this.stopListening();

            this.$source = $(source);

            if (listening) {
                this.startListening();
            }
        },

        startListening: function() {
            if (this.listening) {
                return;
            }

            this.listening = true;

            this.addListener(this.$source, 'input', 'onSourceTextChange');
            this.addListener(this.$target, 'input', 'onTargetTextChange');
            this.addListener(this.$form, 'submit', 'onFormSubmit');
        },

        stopListening: function() {
            if (!this.listening) {
                return;
            }

            this.listening = false;

            if (this.timeout) {
                clearTimeout(this.timeout);
            }

            this.removeAllListeners(this.$source);
            this.removeAllListeners(this.$target);
            this.removeAllListeners(this.$form);
        },

        onSourceTextChange: function() {
            if (this.timeout) {
                clearTimeout(this.timeout);
            }

            this.timeout = setTimeout($.proxy(this, 'updateTarget'), 250);
        },

        onTargetTextChange: function() {
            if (this.$target.get(0) === document.activeElement) {
                this.stopListening();
            }
        },

        onFormSubmit: function() {
            if (this.timeout) {
                clearTimeout(this.timeout);
            }

            this.updateTarget();
        },

        updateTarget: function() {
            if (!this.$target.is(':visible')) {
                return;
            }

            var sourceVal = this.$source.val();

            if (typeof sourceVal === 'undefined') {
                // The source input may not exist anymore
                return;
            }

            var targetVal = this.generateTargetValue(sourceVal);

            this.$target.val(targetVal);
            this.$target.trigger('change');
            this.$target.trigger('input');

            // If the target already has focus, select its whole value to mimic
            // the behavior if the value had already been generated and they just tabbed in
            if (this.$target.is(':focus')) {
                Craft.selectFullValue(this.$target);
            }
        },

        generateTargetValue: function(sourceVal) {
            return sourceVal;
        }
    });
