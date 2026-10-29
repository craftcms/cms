import {createGlobalState} from '@vueuse/core';
import {usePage} from '@inertiajs/vue3';
import {computed, shallowRef} from 'vue';
import useCraftData from '@/common/composables/useCraftData';
import {createCart, getCart, getPluginsByIds, updateCart} from '../api';
import {cartItemsData, newCartItemData, type NewCartItem} from '../cart';
import {usePluginLicenseInfo} from './usePluginStoreData';
import type {
  Cart,
  CartItemData,
  PluginStoreContext,
  PluginSummary,
} from '../types';

/** Shared with the standalone app, so a cart started there carries over. */
const storageKey = 'orderNumber';

function storedOrderNumber(): string | null {
  try {
    return localStorage.getItem(storageKey);
  } catch {
    return null;
  }
}

function storeOrderNumber(number: string | null): void {
  try {
    if (number) {
      localStorage.setItem(storageKey, number);
    } else {
      localStorage.removeItem(storageKey);
    }
  } catch {
    // The cart still works for this visit.
  }
}

/**
 * The cart, kept across visits. It's created on first use; a stored cart that
 * has been checked out or can't be found is replaced with a new one.
 */
export const useCart = createGlobalState(() => {
  const page = usePage<{context: PluginStoreContext}>();
  const {currentUser} = useCraftData();
  const {licenseInfoFor} = usePluginLicenseInfo();

  const cart = shallowRef<Cart | null>(null);
  const plugins = shallowRef<PluginSummary[]>([]);
  const failed = shallowRef(false);
  const pending = shallowRef(0);
  const opened = shallowRef(false);
  let loading: Promise<Cart> | null = null;

  const cmsLicenseKey = () => page.props.context.cmsLicenseKey;

  async function apply(next: Cart): Promise<Cart> {
    const ids = next.lineItems
      .map((lineItem) => lineItem.purchasable.plugin?.id)
      .filter((id): id is number => id !== undefined);

    plugins.value = ids.length ? await getPluginsByIds(ids) : [];
    cart.value = next;
    storeOrderNumber(next.number);
    return next;
  }

  async function fetchCart(): Promise<Cart> {
    const number = storedOrderNumber();

    if (number) {
      try {
        return await apply(await getCart(number));
      } catch {
        // Checked out or expired; start over below.
      }
    }

    return apply(await createCart(currentUser.value?.email ?? null));
  }

  function load(): Promise<Cart> {
    loading ??= fetchCart().catch((error) => {
      failed.value = true;
      loading = null;
      throw error;
    });

    return loading;
  }

  async function save(items: CartItemData[]): Promise<void> {
    const current = await load();
    pending.value++;

    try {
      await apply(await updateCart(current.number, items));
    } finally {
      pending.value--;
    }
  }

  async function add(newItems: NewCartItem[]): Promise<void> {
    // The cart as it is now, which may have changed since it first loaded.
    await load();
    const items = cartItemsData(cart.value, cmsLicenseKey());

    for (const item of newItems) {
      const inCart = items.some((existing) =>
        item.type === 'plugin-edition'
          ? existing.plugin === item.plugin
          : existing.type === 'cms-edition'
      );

      if (!inCart) {
        items.push(
          newCartItemData(
            item,
            item.type === 'plugin-edition' ? licenseInfoFor(item.plugin) : null,
            cmsLicenseKey()
          )
        );
      }
    }

    await save(items);
  }

  async function update(
    index: number,
    changes: Partial<Pick<CartItemData, 'expiryDate' | 'autoRenew'>>
  ): Promise<void> {
    const items = cartItemsData(cart.value, cmsLicenseKey());

    if (items[index]) {
      items[index] = {...items[index], ...changes};
      await save(items);
    }
  }

  async function remove(index: number): Promise<void> {
    const items = cartItemsData(cart.value, cmsLicenseKey());
    items.splice(index, 1);
    await save(items);
  }

  return {
    cart: computed(() => cart.value),
    failed: computed(() => failed.value),
    /** A change is on its way to the API; further edits would race it. */
    busy: computed(() => pending.value > 0),
    count: computed(() => cart.value?.totalQty ?? 0),
    opened,
    pluginFor: (id: number | undefined) =>
      plugins.value.find((plugin) => plugin.id === id) ?? null,
    checkoutUrl: computed(() =>
      cart.value
        ? `${page.props.context.craftIdEndpoint}/cart?orderNumber=${cart.value.number}`
        : null
    ),
    load,
    add,
    update,
    remove,
  };
});
