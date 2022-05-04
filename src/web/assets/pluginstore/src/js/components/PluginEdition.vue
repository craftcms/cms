<template>
  <div
    class="plugin-editions-edition"
    :class="{
      'tw-flex tw-flex-col': true,
      'tw-border tw-border-gray-200 tw-border-solid tw-rounded-md':
        context !== 'meta' ||
        plugin.editions.length > 1 ||
        !isPluginEditionFree(edition),
      'tw-p-8 tw-text-center': context !== 'meta',
      'tw-p-4':
        context === 'meta' &&
        (plugin.editions.length > 1 || !isPluginEditionFree(edition)),
    }"
  >
    <div class="description tw-flex-1">
      <template v-if="plugin.editions.length > 1">
        <div class="tw-text-xl tw-font-bold tw-mb-4">
          {{ edition.name }}
        </div>
      </template>

      <template v-if="context !== 'meta'">
        <ul
          v-if="
            plugin.editions.length > 1 &&
            edition.features &&
            edition.features.length > 0
          "
          class="tw-text-left tw-mt-8 tw-mb-8"
        >
          <li
            v-for="(feature, key) in edition.features"
            :key="key"
            class="tw-py-2 tw-border-b tw-border-gray-200 tw-border-solid"
            :class="{
              'tw-border-t': key === 0,
            }"
          >
            <c-icon icon="check" />
            {{ feature.name }}

            <info-hud v-if="feature.description">
              {{ feature.description }}
            </info-hud>
          </li>
        </ul>
      </template>
    </div>

    <plugin-actions :plugin="plugin" :edition="edition" />

    <p v-if="!isPluginEditionFree(edition)" class="tw-text-gray-700">
      {{ 'Price includes 1 year of updates.' | t('app') }}
      {{
        '{renewalPrice}/year per site for updates after that.'
          | t('app', {
            renewalPrice: $options.filters.currency(edition.renewalPrice),
          })
      }}
    </p>
  </div>
</template>

<script>
  import {mapState, mapGetters} from 'vuex';
  import PluginActions from './PluginActions';
  import InfoHud from './InfoHud';
  import licensesMixin from '../mixins/licenses';

  export default {
    mixins: [licensesMixin],

    props: {
      edition: {
        type: Object,
        required: true,
      },
      plugin: {
        type: Object,
        required: true,
      },
      context: {
        type: String,
      },
    },

    components: {
      PluginActions,
      InfoHud,
    },

    computed: {
      ...mapState({
        cart: (state) => state.cart.cart,
      }),

      ...mapGetters({
        isPluginEditionFree: 'pluginStore/isPluginEditionFree',
        getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
      }),
    },
  };
</script>
