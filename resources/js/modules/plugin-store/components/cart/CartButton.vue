<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {onMounted} from 'vue';
  import CartLineItem from './CartLineItem.vue';
  import CartTrials from './CartTrials.vue';
  import {useCart} from '../../composables/useCart';
  import {formatPrice} from '../../pricing';

  const {cart, failed, busy, count, opened, checkoutUrl, load} = useCart();

  function setOpened(value: boolean): void {
    opened.value = value;
  }

  onMounted(() => {
    load().catch(() => {});
  });
</script>

<template>
  <craft-button icon="cart-shopping" @click="setOpened(true)">
    {{ t('Cart') }}
    <craft-badge v-if="count" slot="suffix" size="small" no-prefix>
      {{ count }}
      <span class="sr-only">{{ t('items') }}</span>
    </craft-badge>
  </craft-button>

  <craft-dialog
    class="cart-dialog"
    :label="t('Cart')"
    close-on-outside-click
    .opened="opened"
    @craft-hide="setOpened(false)"
  >
    <div class="cart">
      <craft-callout v-if="failed" variant="danger">
        {{ t('Couldn’t load the cart.') }}
      </craft-callout>

      <div v-else-if="!cart" class="flex justify-center p-lg">
        <craft-spinner :label="t('Loading')"></craft-spinner>
      </div>

      <template v-else>
        <section class="grid gap-md">
          <h2 class="m-0">{{ t('Items in your cart') }}</h2>

          <template v-if="cart.lineItems.length">
            <ul class="cart__items">
              <CartLineItem
                v-for="(lineItem, index) in cart.lineItems"
                :key="`${lineItem.purchasable.type}-${lineItem.purchasable.plugin?.handle ?? lineItem.purchasable.handle}`"
                :line-item="lineItem"
                :index="index"
              />
            </ul>
            <div class="cart__total">
              <strong>{{ t('Total Price') }}</strong>
              <strong>{{ formatPrice(cart.totalPrice) }}</strong>
            </div>
          </template>

          <p v-else class="m-0">{{ t('Your cart is empty.') }}</p>
        </section>

        <CartTrials @navigate="setOpened(false)" />
      </template>
    </div>

    <div slot="footer" class="flex items-center justify-end gap-sm">
      <craft-spinner
        v-if="busy"
        :label="t('Updating the cart')"
      ></craft-spinner>
      <craft-button @click="setOpened(false)">
        {{ t('Continue shopping') }}
      </craft-button>
      <craft-button
        v-if="cart?.lineItems.length && checkoutUrl"
        variant="primary"
        :href="checkoutUrl"
        .disabled="busy"
      >
        {{ t('Checkout') }}
      </craft-button>
    </div>
  </craft-dialog>
</template>

<style scoped>
  .cart-dialog {
    --c-dialog-inline-size: 50vw;
    --c-dialog-max-inline-size: 90vw;
    --c-dialog-block-size: min(48rem, 85dvh);
  }

  .cart {
    display: grid;
    gap: var(--c-spacing-2xl);
  }

  .cart__items {
    margin: 0;
    padding: 0;
    border-block-start: 1px solid var(--c-color-border-quiet);
    list-style: none;
  }

  .cart__total {
    display: flex;
    justify-content: space-between;
    font-size: var(--c-text-lg);
  }
</style>
