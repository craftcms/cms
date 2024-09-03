/** global: Craft */
/** global: Garnish */
/**
 * Element Action Trigger
 */
Craft.ElementActionTrigger = Garnish.Base.extend(
  {
    elementIndex: null,
    maxLevels: null,
    newChildUrl: null,
    $trigger: null,
    $selectedItems: null,
    triggerEnabled: true,

    init: function (settings) {
      // Save a reference to the element index that this trigger will be used with
      this.elementIndex = Craft.currentElementIndex;

      // Register the trigger on the element index, so it can be destroyed when the view is updated
      this.elementIndex.triggers.push(this);

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
        `#${this.elementIndex.namespaceId(settings.type)}-actiontrigger`
      ).data('trigger', this);

      // Do we have a custom handler?
      if (this.settings.activate) {
        // Prevent the element index's click handler
        this.$trigger.data('custom-handler', true);

        let $button = this.$trigger.find('button,.btn');
        if (!$button.length) {
          $button = this.$trigger;
        }
        this.addListener($button, 'activate', 'handleTriggerActivation');
      }

      this.updateTrigger();
      this.elementIndex.on('selectionChange', this.updateTrigger.bind(this));
    },

    updateTrigger: function () {
      // Ignore if the last element was just unselected
      if (this.elementIndex.getSelectedElements().length === 0) {
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
     * @returns {boolean}
     */
    validateSelection: function () {
      this.$selectedItems = this.elementIndex.getSelectedElements();

      if (!this.settings.bulk && this.$selectedItems.length > 1) {
        return false;
      }

      if (this.settings.requireId) {
        for (let i = 0; i < this.$selectedItems.length; i++) {
          const $item = this.$selectedItems.eq(i);
          if (!Garnish.hasAttr($item, 'data-id') || $item.is('[data-id=""]')) {
            return false;
          }
        }
      }

      if (typeof this.settings.validateSelection === 'function') {
        return this._call(() =>
          this.settings.validateSelection(
            this.$selectedItems,
            this.elementIndex
          )
        );
      }

      return true;
    },

    enableTrigger: function () {
      if (this.triggerEnabled) {
        return;
      }

      this.$trigger.removeClass('disabled').removeAttr('aria-disabled');
      this.triggerEnabled = true;
    },

    disableTrigger: function () {
      if (!this.triggerEnabled) {
        return;
      }

      this.$trigger.addClass('disabled').attr('aria-disabled', 'true');
      this.triggerEnabled = false;
    },

    handleTriggerActivation: function () {
      if (this.triggerEnabled) {
        this._call(() =>
          this.settings.activate(this.$selectedItems, this.elementIndex)
        );
      }
    },

    _call: function (fn) {
      // temporarily set Craft.elementIndex to the trigger's index instance, for BC
      const globalElementIndex = Craft.elementIndex;
      Craft.elementIndex = this.elementIndex;
      const response = fn();
      Craft.elementIndex = globalElementIndex;
      return response;
    },
  },
  {
    defaults: {
      type: null,
      bulk: true,
      requireId: true,
      validateSelection: null,
      beforeActivate: async ($selectedElements, elementIndex) => {},
      activate: null,
      afterActivate: async ($selectedElements, elementIndex) => {},
    },
  }
);
