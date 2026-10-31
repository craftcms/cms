<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, shallowRef} from 'vue';
  import {plugin as pluginRoute} from '@actions/PluginStore/PluginStoreController';
  import CpLink from '@/common/components/CpLink.vue';
  import PluginIcon from '../PluginIcon.vue';
  import {useCart} from '../../composables/useCart';
  import {useTrialRows} from '../../composables/useActiveTrials';
  import {pendingTrialRows, showError, type TrialRow} from '../../cart';
  import {formatPrice} from '../../pricing';

  const emit = defineEmits<{navigate: []}>();

  const {cart, busy, add} = useCart();
  const {rows} = useTrialRows();

  const pending = computed(() => pendingTrialRows(rows.value, cart.value));
  const adding = shallowRef(false);

  async function addRows(trials: TrialRow[], failure: string): Promise<void> {
    adding.value = true;

    try {
      await add(trials.map((trial) => trial.item));
    } catch (error) {
      showError(error, failure);
    } finally {
      adding.value = false;
    }
  }
</script>

<template>
  <section v-if="pending.length" class="grid gap-md">
    <div class="flex items-center justify-between gap-md">
      <h2 class="m-0">{{ t('Active Trials') }}</h2>
      <craft-button
        v-if="pending.length > 1"
        size="small"
        .disabled="busy || adding"
        @click="addRows(pending, t('Couldn’t add all items to the cart.'))"
      >
        {{ t('Add all to cart') }}
      </craft-button>
    </div>

    <ul class="cart-trials">
      <li v-for="trial in pending" :key="trial.name" class="cart-trials__row">
        <PluginIcon :url="trial.iconUrl" />
        <div class="flex flex-wrap items-center gap-sm">
          <CpLink
            v-if="trial.pluginHandle"
            :href="pluginRoute({handle: trial.pluginHandle})"
            @click="emit('navigate')"
          >
            <strong>{{ trial.name }}</strong>
          </CpLink>
          <strong v-else>{{ trial.name }}</strong>
          <craft-badge v-if="trial.showEdition" size="small" no-prefix>
            {{ trial.editionName }}
          </craft-badge>
        </div>
        <div>
          <template v-if="trial.upgradePrice !== null">
            <del>{{ formatPrice(trial.price) }}</del>
            <strong>{{ formatPrice(trial.upgradePrice) }}</strong>
          </template>
          <strong v-else>{{ formatPrice(trial.price) }}</strong>
        </div>
        <craft-button
          size="small"
          :aria-label="`${t('Add to cart')} ${trial.name}`"
          .disabled="busy || adding || trial.licenseMismatched"
          @click="addRows([trial], t('Couldn’t add item to cart.'))"
        >
          {{ t('Add to cart') }}
        </craft-button>
      </li>
    </ul>
  </section>
</template>

<style scoped>
  .cart-trials {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .cart-trials__row {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto auto;
    gap: var(--c-spacing-md);
    align-items: center;
    padding-block: var(--c-spacing-sm);
    border-block-start: 1px solid var(--c-color-border-quiet);
  }

  .cart-trials__row :deep(.plugin-icon) {
    --size: 2.5rem;
  }
</style>
