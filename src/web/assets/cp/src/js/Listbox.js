/** global: Craft */
/** global: Garnish */
/**
 * Listbox
 */
Craft.Listbox = Garnish.Base.extend(
  {
    $container: null,
    $options: null,
    $selectedOption: null,
    selectedOptionIndex: null,

    init: function (container, settings) {
      this.$container = $(container);
      this.setSettings(settings, Craft.Listbox.defaults);

      // Is this already a listbox?
      if (this.$container.data('listbox')) {
        console.warn('Double-instantiating a listbox on an element');
        this.$container.data('listbox').destroy();
      }

      this.$container.data('listbox', this);
      // todo: drop [role=option] in Craft 5
      this.$options = this.$container.find('button,[role=option]');

      // is there already a selected option?
      // todo: drop [aria-selected=true] & attr normalization in Craft 5
      this.$selectedOption = this.$options
        .filter('[aria-pressed=true],[aria-selected=true]')
        .removeAttr('aria-selected')
        .attr('aria-pressed', 'true');
      if (this.$selectedOption.length) {
        this.selectedOptionIndex = this.$options.index(this.$selectedOption);
      } else {
        this.$selectedOption = null;
      }

      this.addListener(this.$options, 'click', (ev) => {
        this.select(this.$options.index($(ev.currentTarget)));
        ev.preventDefault();
      });
    },

    select: function (index) {
      if (
        index < 0 ||
        index >= this.$options.length ||
        index === this.selectedOptionIndex
      ) {
        return;
      }

      if (this.$selectedOption) {
        this.$selectedOption
          .removeClass(this.settings.selectedClass)
          .attr('aria-pressed', 'false');
      }

      this.$selectedOption = this.$options
        .eq(index)
        .addClass(this.settings.selectedClass)
        .attr('aria-pressed', 'true');

      this.selectedOptionIndex = index;

      this.settings.onChange(this.$selectedOption, index);
      this.trigger('change', {
        $selectedOption: this.$selectedOption,
        selectedOptionIndex: index,
      });
    },

    disable: function () {
      this.base();
      this.$container.attr('aria-disabled', 'true');
    },

    enable: function () {
      this.base();
      this.$container.removeAttr('aria-disabled');
    },

    destroy: function () {
      this.$container.removeData('listbox');
      this.base();
    },
  },
  {
    defaults: {
      selectedClass: 'active',
      focusClass: 'focus',
      onChange: $.noop,
    },
  }
);
