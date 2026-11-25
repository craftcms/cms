import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Checkbox select class
 */
export default Base.extend(
  {
    $container: null,
    $all: null,
    $options: null,

    init: function (container, settings) {
      this.$container = $(container);
      this.setSettings(settings, Garnish.CheckboxSelect.defaults);

      // Is this already a checkbox select?
      if (this.$container.data('checkboxSelect')) {
        console.warn('Double-instantiating a checkbox select on an element');
        this.$container.data('checkboxSelect').destroy();
      }

      this.$container.data('checkboxSelect', this);

      var $checkboxes = this.$container.find('input');
      this.$all = $checkboxes.filter('.all:first');
      this.$options = $checkboxes.not(this.$all);

      this.addListener(this.$all, 'change', 'onAllChange');

      if (this.settings.storageKey) {
        const selectedOptions = Craft.getLocalStorage(this.settings.storageKey);
        if (selectedOptions) {
          // all?
          if (this.$all.length && selectedOptions.includes(this.$all.val())) {
            if (!this.isAllChecked()) {
              this.$all.prop('checked', true).trigger('change');
            }
          } else {
            if (this.isAllChecked()) {
              this.$all.prop('checked', false).trigger('change');
            }
            this.$options.each((i, checkbox) => {
              const included = selectedOptions.includes(checkbox.value);
              const checked = checkbox.checked;
              if (included !== checked) {
                checkbox.checked = included;
                $(checkbox).trigger('change');
              }
            });
          }
        }

        $checkboxes.on('change', () => {
          const selectedOptions = [];
          if (this.$all.prop('checked')) {
            selectedOptions.push(this.$all.val());
          } else {
            this.$options.each((i, checkbox) => {
              if (checkbox.checked) {
                selectedOptions.push(checkbox.value);
              }
            });
          }
          Craft.setLocalStorage(this.settings.storageKey, selectedOptions);
        });
      }
    },

    isAllChecked: function () {
      return this.$all.prop('checked');
    },

    onAllChange: function () {
      const isAllChecked = this.isAllChecked();

      this.$options.prop({
        checked: isAllChecked,
        disabled: isAllChecked,
      });
    },

    /**
     * Destroy
     */
    destroy: function () {
      this.$container.removeData('checkboxSelect');
      this.base();
    },
  },
  {
    defaults: {
      storageKey: null,
    },
  }
);
