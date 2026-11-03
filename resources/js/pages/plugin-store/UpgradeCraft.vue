<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
  import {usePage} from '@inertiajs/vue3';
  import {computed, onScopeDispose, shallowRef} from 'vue';
  import EditionController from '@actions/EditionController';
  import StoreContainer from '@/modules/plugin-store/components/StoreContainer.vue';
  import {getCmsEditions} from '@/modules/plugin-store/api';
  import {showError} from '@/modules/plugin-store/cart';
  import {
    cmsEditionDescription,
    cmsEditionFeatures,
    resolveCmsEditionActions,
  } from '@/modules/plugin-store/cmsEditions';
  import {useCart} from '@/modules/plugin-store/composables/useCart';
  import {usePluginStoreLayout} from '@/modules/plugin-store/composables/usePluginStoreLayout';
  import {formatPrice} from '@/modules/plugin-store/pricing';
  import type {
    CmsEdition,
    PluginStoreContext,
  } from '@/modules/plugin-store/types';

  usePluginStoreLayout();

  const page = usePage<{context: PluginStoreContext}>();
  const {cart, opened: cartOpened, add} = useCart();

  const editions = shallowRef<CmsEdition[] | null>(null);
  const failed = shallowRef(false);
  const pending = shallowRef<string | null>(null);
  const controller = new AbortController();
  onScopeDispose(() => controller.abort());

  getCmsEditions(controller.signal)
    .then((response) => (editions.value = response.editions))
    .catch(() => (failed.value = !controller.signal.aborted));

  const cards = computed(() =>
    (editions.value ?? []).map((edition, index, all) => {
      const inCart = !!cart.value?.lineItems.some(
        (lineItem) =>
          lineItem.purchasable.type === 'cms-edition' &&
          lineItem.purchasable.handle === edition.handle
      );

      return {
        edition,
        price: Number(edition.price ?? 0),
        previous: all[index - 1] ?? null,
        description: cmsEditionDescription(edition.handle),
        features: cmsEditionFeatures[edition.handle] ?? [],
        actions: resolveCmsEditionActions(
          edition.handle,
          Number(edition.price ?? 0),
          page.props.context,
          inCart
        ),
      };
    })
  );

  const statusLabels = {
    installed: t('Installed'),
    'installed-trial': t('Installed as a trial'),
    licensed: t('Licensed'),
  };

  function openCart(): void {
    cartOpened.value = true;
  }

  async function buy(edition: CmsEdition): Promise<void> {
    pending.value = edition.handle;

    try {
      await add([{type: 'cms-edition', edition: edition.handle}]);
      openCart();
    } catch (error) {
      showError(error, t('Couldn’t add item to cart.'));
    } finally {
      pending.value = null;
    }
  }

  // A full reload rather than an Inertia one: the edition rides along in the
  // API headers the store has already cached, and the whole CP reflects it.
  async function switchTo(edition: CmsEdition): Promise<void> {
    pending.value = edition.handle;

    try {
      await actionClient.post(EditionController.tryEdition().url, {
        edition: edition.handle,
      });
      window.location.reload();
    } catch (error) {
      pending.value = null;
      showError(error, t('Couldn’t change Craft CMS edition.'));
    }
  }
</script>

<template>
  <StoreContainer>
    <craft-callout v-if="failed" variant="danger">
      {{ t('Couldn’t load CMS editions.') }}
    </craft-callout>

    <div v-else-if="!editions" class="flex justify-center p-lg">
      <craft-spinner :label="t('Loading')"></craft-spinner>
    </div>

    <ul v-else class="cms-editions">
      <li v-for="card in cards" :key="card.edition.handle" class="cms-edition">
        <div class="grid gap-sm">
          <h2 class="m-0">{{ card.edition.name }}</h2>
          <p v-if="card.description" class="m-0">{{ card.description }}</p>
        </div>

        <div class="grid gap-xs">
          <div class="cms-edition__price">
            {{ card.price ? formatPrice(card.price) : t('Free') }}
          </div>
          <p v-if="card.price" class="cms-edition__renewal">
            {{
              t('Plus {renewalPrice}/year for updates after one year.', {
                renewalPrice: formatPrice(card.edition.renewalPrice),
              })
            }}
          </p>
          <p v-else class="cms-edition__renewal">
            {{ t('No purchase necessary.') }}
          </p>
        </div>

        <ul class="cms-edition__features">
          <li v-if="card.previous" class="cms-edition__quiet">
            {{
              t('Everything in {edition}, plus…', {edition: card.previous.name})
            }}
          </li>
          <li v-for="feature in card.features" :key="feature.name">
            <craft-icon name="check"></craft-icon>
            {{ feature.name }}
            <craft-info-icon>{{ feature.description }}</craft-info-icon>
          </li>
        </ul>

        <div class="grid gap-sm">
          <craft-badge v-if="card.actions.status === 'licensed'" fill="blue">
            {{ statusLabels.licensed }}
          </craft-badge>

          <craft-button
            v-if="card.actions.purchase === 'buy'"
            variant="primary"
            .loading="pending === card.edition.handle"
            .disabled="pending !== null"
            @click="buy(card.edition)"
          >
            {{ t('Buy now') }}
          </craft-button>
          <craft-button
            v-else-if="card.actions.purchase === 'in-cart'"
            variant="primary"
            icon="check"
            @click="openCart"
          >
            {{ t('Added to cart') }}
          </craft-button>

          <!-- The edition already running takes the place of the button that would switch to it. -->
          <craft-button
            v-if="
              card.actions.status === 'installed' ||
              card.actions.status === 'installed-trial'
            "
            icon="check"
            disabled
          >
            {{ statusLabels[card.actions.status] }}
          </craft-button>
          <craft-button
            v-else-if="card.actions.switchTo"
            .loading="pending === card.edition.handle"
            .disabled="pending !== null"
            @click="switchTo(card.edition)"
          >
            {{
              card.actions.switchTo === 'try'
                ? t('Try for free')
                : t('Reactivate')
            }}
          </craft-button>
        </div>
      </li>
    </ul>
  </StoreContainer>
</template>

<style scoped>
  .cms-editions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
    gap: var(--c-spacing-lg);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .cms-edition {
    display: grid;
    grid-template-rows: auto auto 1fr auto;
    gap: var(--c-spacing-lg);
    padding: var(--c-spacing-lg);
    border: 1px solid var(--c-color-border-quiet);
    border-radius: var(--c-radius-md);
  }

  .cms-edition__price {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.1;
  }

  .cms-edition__renewal {
    margin: 0;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }

  .cms-edition__quiet {
    margin: 0;
    color: var(--c-text-quiet);
  }

  .cms-edition__features {
    display: grid;
    gap: var(--c-spacing-sm);
    align-content: start;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .cms-edition__features li {
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: center;
  }
</style>
