/** global: Craft */

/**
 * AddressInput class
 */
Craft.AddressesInput = Garnish.Base.extend({
    ownerId: null,
    $container: null,
    $addBtn: null,

    init: function(ownerId, container) {
        this.ownerId = ownerId;
        this.$container = $(container);

        // Is this already an address input?
        if (this.$container.data('addresses')) {
            console.warn('Double-instantiating an address input on an element');
            this.$container.data('addresses').destroy();
        }

        this.$container.data('addresses', this);

        this.$addBtn = this.$container.find('> .btn.add');

        const $cards = this.$container.find('> .address-card');
        for (let i = 0; i < $cards.length; i++) {
            this.initCard($cards.eq(i));
        }

        this.addListener(this.$addBtn, 'click', () => {
            this.createAddress();
        });
    },

    initCard: function($card) {
        this.addListener($card, 'click', () => {
            this.editAddress($card);
        });

        const $actionBtn = $card.find('.menubtn');
        if ($actionBtn.length) {
            const $menu = $actionBtn.data('trigger').$container;
            const $deleteBtn = $menu.find('[data-action="delete"]');
            this.addListener($deleteBtn, 'click', ev => {
                ev.preventDefault();
                ev.stopPropagation();
                if (confirm(Craft.t('app', 'Are you sure you want to delete this address?'))) {
                    this.$addBtn.addClass('loading');
                    Craft.sendActionRequest('POST', 'elements/delete', {
                        data: {
                            elementId: $card.data('id'),
                            draftId: $card.data('draft-id'),
                        },
                    }).then(() => {
                        $card.remove();
                    }).finally(() => {
                        this.$addBtn.removeClass('loading');
                    });
                }
            });
        }
    },

    editAddress: function($card, settings) {
        const slideout = Craft.createElementEditor('craft\\elements\\Address', $card, settings);

        slideout.on('submit', ev => {
            Craft.sendActionRequest('POST', 'addresses/card-html', {
                data: {
                    addressId: ev.data.id,
                }
            }).then(response => {
                const $newCard = $(response.data.html);
                if ($card) {
                    $card.replaceWith($newCard);
                } else {
                    $newCard.insertBefore(this.$addBtn);
                }
                Craft.initUiElements($newCard);
                this.initCard($newCard);
            });
        });
    },

    createAddress: function() {
        this.$addBtn.addClass('loading');

        Craft.sendActionRequest('POST', 'elements/create', {
            data: {
                elementType: 'craft\\elements\\Address',
                ownerId: this.ownerId,
            },
        }).then(ev => {
            this.editAddress(null, {
                elementId: ev.data.element.id,
                draftId: ev.data.element.draftId,
            });
        }).finally(() => {
            this.$addBtn.removeClass('loading');
        });
    }
});
