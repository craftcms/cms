/** global: Craft */
/** global: Garnish */
/**
 * Element Action Trigger
 */
Craft.ElementActionTrigger = Garnish.Base.extend(
  {
    maxLevels: null,
    newChildUrl: null,
    $trigger: null,
    $selectedItems: null,
    triggerEnabled: true,

    init: function (settings) {
      if (!$.isPlainObject(settings)) {
        settings = {};
      }

      // batch => bulk
      if (typeof settings.batch !== 'undefined') {
        settings.bulk = settings.batch;
        delete settings.batch;
      }
      Object.defineProperty(settings, 'batch', {
        get() {
          return this.bulk;
        },
        set(v) {
          this.bulk = v;
        },
      });

      this.setSettings(settings, Craft.ElementActionTrigger.defaults);

      this.$trigger = $(
        '#' + settings.type.replace(/[\[\]\\]+/g, '-') + '-actiontrigger'
      );

      // Do we have a custom handler?
      if (this.settings.activate) {
        // Prevent the element index's click handler
        this.$trigger.data('custom-handler', true);

        // Is this a custom trigger?
        if (this.$trigger.prop('nodeName') === 'FORM') {
          this.addListener(this.$trigger, 'submit', 'handleTriggerActivation');
        } else {
          this.addListener(this.$trigger, 'click', 'handleTriggerActivation');
        }
      }

      this.updateTrigger();
      Craft.elementIndex.on('selectionChange', this.updateTrigger.bind(this));
    },

    updateTrigger: function () {
      // Ignore if the last element was just unselected
      if (Craft.elementIndex.getSelectedElements().length === 0) {
        return;
      }

      if (this.validateSelection()) {
        this.enableTrigger();
      } else {
        this.disableTrigger();
      }
    },

    /**
     * Determines if this action can be performed on the currently selected elements.
     *
     * @return boolean
     */
    validateSelection: function () {
      this.$selectedItems = Craft.elementIndex.getSelectedElements();

      if (!this.settings.bulk && this.$selectedItems.length > 1) {
        return false;
      }

      if (this.settings.requireId && this.$selectedItems.is('[data-id=""]')) {
        return false;
      }

      if (typeof this.settings.validateSelection === 'function') {
        return this.settings.validateSelection(this.$selectedItems);
      }

      return true;
    },

    enableTrigger: function () {
      if (this.triggerEnabled) {
        return;
      }

      this.$trigger.removeClass('disabled');
      this.triggerEnabled = true;
    },

    disableTrigger: function () {
      if (!this.triggerEnabled) {
        return;
      }

      this.$trigger.addClass('disabled');
      this.triggerEnabled = false;
    },

    handleTriggerActivation: function (ev) {
      ev.preventDefault();
      ev.stopPropagation();

      if (this.triggerEnabled) {
        this.settings.activate(this.$selectedItems);
      }
    },
  },
  {
    defaults: {
      type: null,
      bulk: true,
      requireId: true,
      validateSelection: null,
      activate: null,
    },
  }
);
