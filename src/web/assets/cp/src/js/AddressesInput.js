/** global: Craft */

/**
 * AddressInput class
 */
Craft.AddressesInput = Garnish.Base.extend(
  {
    $container: null,
    $addBtn: null,
    $addBtnItem: null,
    $cards: null,

    init: function (container, settings) {
      this.$container = $(container);
      this.setSettings(settings, Craft.AddressesInput.defaults);

      // Is this already an address input?
      if (this.$container.data('addresses')) {
        console.warn('Double-instantiating an address input on an element');
        this.$container.data('addresses').destroy();
      }

      this.$container.data('addresses', this);

      this.$addBtn = this.$container.find('.address-cards__add-btn');
      this.$addBtnItem = this.$addBtn.closest('li');
      this.$cards = this.$container.find('> .address-card');

      for (let i = 0; i < this.$cards.length; i++) {
        this.initCard(this.$cards.eq(i));
      }

      this.updateAddButton();

      this.addListener(this.$addBtn, 'click', () => {
        this.createAddress();
      });
    },

    initCard: function ($card) {
      this.addListener($card, 'click', (ev) => {
        if (!$(ev.target).closest('.menubtn').length) {
          this.editAddress($card);
        }
      });

      const $actionBtn = $card.find('.menubtn');
      if ($actionBtn.length) {
        const menu = $actionBtn.data('trigger');
        const $menu = menu.$container;

        // Activate edit button
        const $editBtn = $menu.find('[data-action="edit"]');
        this.addListener($editBtn, 'click', (ev) => {
          ev.stopPropagation();
          this.editAddress($card);
        });

        // Activate delete button
        const $deleteBtn = $menu.find('[data-action="delete"]');
        this.addListener($deleteBtn, 'click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          if (
            confirm(
              Craft.t('app', 'Are you sure you want to delete this address?')
            )
          ) {
            this.$addBtn.addClass('loading');
            const addressId = $card.data('id');
            const draftId = $card.data('draft-id');
            Craft.sendActionRequest('POST', 'elements/delete', {
              data: {
                elementId: addressId,
                draftId: draftId,
              },
            })
              .then(() => {
                $card.remove();
                $menu.remove();
                menu.destroy();
                this.$cards = this.$cards.not($card);
                this.updateAddButton();

                this.trigger('deleteAddress', {
                  addressId,
                  draftId,
                });
              })
              .finally(() => {
                this.$addBtn.removeClass('loading');
              });
          }
        });
      }
    },

    editAddress: function ($card, settings) {
      const slideout = Craft.createElementEditor(
        'craft\\elements\\Address',
        $card,
        settings
      );

      slideout.on('submit', (ev) => {
        this.trigger('saveAddress', {
          data: ev.data,
        });

        Craft.sendActionRequest('POST', 'addresses/card-html', {
          data: {
            addressId: ev.data.id,
          },
        }).then((response) => {
          const $newCard = $(response.data.html);
          if ($card) {
            $card.replaceWith($newCard);
            this.$cards = this.$cards.not($card);
          } else {
            $newCard.insertBefore(this.$addBtnItem);
          }
          Craft.initUiElements($newCard);
          this.initCard($newCard);
          this.$cards = this.$cards.add($newCard);
          this.updateAddButton();
        });
      });
    },

    updateAddButton: function () {
      if (this.canCreateAddress()) {
        this.$addBtn.removeClass('hidden');
      } else {
        this.$addBtn.addClass('hidden');
      }
    },

    canCreateAddress: function () {
      return (
        !this.settings.maxAddresses ||
        this.$cards.length < this.settings.maxAddresses
      );
    },

    createAddress: function () {
      if (!this.canCreateAddress()) {
        throw 'No more addresses can be created.';
      }

      this.$addBtn.addClass('loading');

      Craft.sendActionRequest('POST', 'elements/create', {
        data: {
          elementType: 'craft\\elements\\Address',
          ownerId: this.settings.ownerId,
        },
      })
        .then((ev) => {
          this.editAddress(null, {
            elementId: ev.data.element.id,
            draftId: ev.data.element.draftId,
          });
        })
        .finally(() => {
          this.$addBtn.removeClass('loading');
        });
    },

    destroy: function () {
      this.$container.removeData('addresses');
      this.base();
    },
  },
  {
    ownerId: null,
    defaults: {
      maxAddresses: null,
    },
  }
);
