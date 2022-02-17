<template>
  <div
    class="plugin-editions-edition"
    :class="{
      'tw-border tw-border-gray-200 tw-border-solid tw-rounded-md tw-flex tw-flex-col': true,
      'tw-p-8 tw-text-center': context !== 'meta',
      'tw-p-4': context === 'meta'
    }"
  >
    <div class="description tw-flex-1">
      <edition-badge
        v-if="plugin.editions.length > 1"
        :name="edition.name"
        block
        big></edition-badge>
      <div
        class="price tw-text-3xl tw-font-bold"
        :class="{
          'tw-my-8': context !== 'meta',
          'tw-mb-4': context === 'meta'
        }"
      >
        <template v-if="!isPluginEditionFree(edition)">
          <template v-if="licensedEdition && licensedEdition.handle !== edition.handle && licensedEdition.price > 0 && licenseValidOrAstray">
            <del>{{ edition.price|currency }}</del>
            {{ (edition.price - licensedEdition.price)|currency }}
          </template>
          <template v-else>
            {{ edition.price|currency }}
          </template>
        </template>
        <template v-else>
          {{ "Free"|t('app') }}
        </template>
      </div>
      <p
        v-if="!isPluginEditionFree(edition)"
        class="tw--mt-8 tw-py-6 tw-text-gray-700">
        {{ "Price includes 1 year of updates."|t('app') }}
        {{
          "{renewalPrice}/year per site for updates after that."|t('app', {renewalPrice: $options.filters.currency(edition.renewalPrice)})
        }}
      </p>

      <ul
        v-if="plugin.editions.length > 1 && edition.features && edition.features.length > 0"
        class="tw-text-left tw-mb-8"
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
    </div>

    <plugin-actions
      :plugin="plugin"
      :edition="edition"></plugin-actions>
  </div>
</template>

<script>
import {mapState, mapGetters} from 'vuex'
import PluginActions from './PluginActions'
import InfoHud from './InfoHud'
import EditionBadge from './EditionBadge'
import licensesMixin from '../mixins/licenses'

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
    EditionBadge,
  },

  computed: {
    ...mapState({
      cart: state => state.cart.cart,
    }),

    ...mapGetters({
      isPluginEditionFree: 'pluginStore/isPluginEditionFree',
      getPluginEdition: 'pluginStore/getPluginEdition',
      getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
    }),


    pluginLicenseInfo() {
      if (!this.plugin) {
        return null
      }

      return this.getPluginLicenseInfo(this.plugin.handle)
    },

    licensedEdition() {
      if (!this.pluginLicenseInfo) {
        return null
      }

      return this.getPluginEdition(this.plugin, this.pluginLicenseInfo.licensedEdition)
    }
  },
}
</script>

