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
    sourceVal: null,

    init: function (source, target, settings) {
      this.$source = $(source);
      this.$target = $(target);
      this.$form = this.$source.closest('form');

      this.setSettings(settings, Craft.BaseInputGenerator.defaults);
      this.setSettings(settings);

      this.startListening();
    },

    setNewSource: function (source) {
      var listening = this.listening;
      this.stopListening();

      this.$source = $(source);

      if (listening) {
        this.startListening();
      }
    },

    startListening: function () {
      if (this.listening) {
        return;
      }

      this.listening = true;
      this.sourceVal = this.$source.val();

      this.addListener(this.$source, 'input', 'onSourceTextChange');
      this.addListener(this.$target, 'input', 'onTargetTextChange');
      this.addListener(this.$form, 'submit', 'updateTarget');
    },

    stopListening: function () {
      if (!this.listening) {
        return;
      }

      this.listening = false;

      this.removeAllListeners(this.$source);
      this.removeAllListeners(this.$target);
      this.removeAllListeners(this.$form);
    },

    onSourceTextChange: function () {
      const val = this.$source.val();
      if (this.sourceVal !== (this.sourceVal = val)) {
        this.updateTarget();
      }
    },

    onTargetTextChange: function () {
      if (this.$target.get(0) === document.activeElement) {
        this.stopListening();
      }
    },

    updateTarget: function () {
      if (
        !this.$target.is(':visible') &&
        this.settings.updateWhenHidden == false
      ) {
        return;
      }

      var sourceVal = this.$source.val();

      if (typeof sourceVal === 'undefined') {
        // The source input may not exist anymore
        return;
      }

      let targetVal = this.generateTargetValue(sourceVal);
      if (targetVal) {
        targetVal = `${this.settings.prefix}${targetVal}${this.settings.suffix}`;
      }

      this.$target.val(targetVal);

      for (let i = 0; i < this.$target.length; i++) {
        this.$target[i].dispatchEvent(
          new InputEvent('input', {
            inputType: 'insertText',
          })
        );
        this.$target[i].dispatchEvent(new Event('input'));
      }

      // If the target already has focus, select its whole value to mimic
      // the behavior if the value had already been generated and they just tabbed in
      if (this.$target.is(':focus')) {
        Craft.selectFullValue(this.$target);
      }
    },

    generateTargetValue: function (sourceVal) {
      return sourceVal;
    },
  },
  {
    defaults: {
      updateWhenHidden: false,
      prefix: '',
      suffix: '',
    },
  }
);
