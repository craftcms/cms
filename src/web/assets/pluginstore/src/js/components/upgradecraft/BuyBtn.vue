<template>
  <div>
    <!-- Show the "Buy" button if this edition is greater than the licensed edition -->
    <template v-if="edition > licensedEdition">
      <template v-if="!isCmsEditionInCart(editionHandle)">
        <c-btn kind="primary" @click="buyCraft(editionHandle)" block large
          >{{ 'Buy now' | t('app') }}
        </c-btn>
      </template>
      <template v-else>
        <c-btn block large submit disabled
          >{{ 'Added to cart' | t('app') }}
        </c-btn>
      </template>
    </template>

    <!-- Show the "Try" button if they're on a testable domain, this is not the current edition, and is greater than the licensed edition -->
    <template
      v-if="
        canTestEditions && edition != CraftEdition && edition > licensedEdition
      "
    >
      <c-btn @click="installCraft(editionHandle)" block large
        >{{ 'Try for free' | t('app') }}
      </c-btn>
    </template>

    <!-- Show the "Reactivate" button if they’re licensed to use this edition but not currently on it -->
    <template v-if="edition == licensedEdition && edition != CraftEdition">
      <c-btn @click="installCraft(editionHandle)" block large
        >{{ 'Reactivate' | t('app') }}
      </c-btn>
    </template>

    <c-spinner v-if="loading" />
  </div>
</template>

<script>
  import {mapState, mapGetters, mapActions} from 'vuex';

  export default {
    props: ['edition', 'edition-handle'],

    data() {
      return {
        loading: false,
      };
    },

    computed: {
      ...mapState({
        canTestEditions: (state) => state.craft.canTestEditions,
        CraftEdition: (state) => state.craft.CraftEdition,
        licensedEdition: (state) => state.craft.licensedEdition,
      }),

      ...mapGetters({
        isCmsEditionInCart: 'cart/isCmsEditionInCart',
      }),
    },

    methods: {
      ...mapActions({
        addToCart: 'cart/addToCart',
        getCraftData: 'craft/getCraftData',
        tryEdition: 'craft/tryEdition',
      }),

      buyCraft(edition) {
        this.loading = true;

        const item = {
          type: 'cms-edition',
          edition: edition,
        };

        this.addToCart([item])
          .then(() => {
            this.loading = false;
            this.$root.openModal('cart');
          })
          .catch(() => {
            this.loading = false;
          });
      },

      installCraft(edition) {
        this.loading = true;

        this.tryEdition(edition)
          .then(() => {
            this.getCraftData().then(() => {
              this.loading = false;
              this.$root.displayNotice('Craft CMS edition changed.');
            });
          })
          .catch(() => {
            this.loading = false;
            this.$root.displayError('Couldn’t change Craft CMS edition.');
          });
      },
    },
  };
</script>
