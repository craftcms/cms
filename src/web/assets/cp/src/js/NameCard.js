/** global: Craft */

/**
 * NameCard class
 */
Craft.NameCard = Garnish.Base.extend(
  {
    $container: null,
    $cards: null,
    $editBtn: null,
    $closeBtn: null,

    init: function (container, settings) {
      this.$container = $(container);
      this.setSettings(settings, Craft.NameCard.defaults);

      // Is this already an address input?
      if (this.$container.data('namefields')) {
        console.warn('Double-instantiating an name card on an element');
        this.$container.data('namefields').destroy();
      }

      this.$container.data('namefields', this);

      this.$cards = this.$container.find('> .name-card');

      for (let i = 0; i < this.$cards.length; i++) {
        this.initCard(this.$cards.eq(i));
      }
    },

    initCard: function ($card) {
      this.addListener($card, 'click', (ev) => {
        if (!$(ev.target).closest('.closebtn').length) {
          this.openCard($card);
        }
      });

      this.$editBtn = $card.find('.editbtn');
      this.$closeBtn = $card.find('.closebtn');

      if ($card.find('.name-card-fields').hasClass('hidden')) {
        this.$editBtn.show();
        this.$closeBtn.hide();
      } else {
        this.$editBtn.hide();
        this.$closeBtn.show();
      }

      this.addListener(this.$closeBtn, 'click', (ev) => {
        this.closeCard($card);
      });
    },

    openCard: function ($card) {
      let $nameCardFieldsContainer = $card.find('.name-card-fields');

      // could already be opened if there's a validation error
      if ($nameCardFieldsContainer.hasClass('hidden')) {
        $nameCardFieldsContainer.find('input').enable().removeAttr('disabled');
        $nameCardFieldsContainer.removeClass('hidden');

        this.$editBtn.hide();
        this.$closeBtn.show();
      }
    },

    closeCard: function ($card) {
      let $nameCardFieldsContainer = $card.find('.name-card-fields');

      // could already be opened if there's a validation error
      if (!$nameCardFieldsContainer.hasClass('hidden')) {
        $nameCardFieldsContainer
          .find('input')
          .disable()
          .attr('disabled', 'disabled');
        $nameCardFieldsContainer.addClass('hidden');

        this.$editBtn.show();
        this.$closeBtn.hide();
      }
    },

    destroy: function () {
      this.$container.removeData('namefields');
      this.base();
    },
  },
  {
    defaults: {},
  }
);
