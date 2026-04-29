<template>
  <div>
    <template v-if="!isPluginEditionFree(edition)">
      <template
        v-if="
          licensedEdition &&
          licensedEdition.handle !== edition.handle &&
          licensedEdition.price > 0 &&
          licenseValidOrAstray
        "
      >
        <del>{{ edition.price | currency }}</del>
        {{ (edition.price - licensedEdition.price) | currency }}
      </template>
      <template v-else>
        {{ edition.price | currency }}
      </template>
    </template>
    <template v-else>
      {{ 'Free' | t('app') }}
    </template>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';
  import licensesMixin from '../mixins/licenses';

  export default {
    mixins: [licensesMixin],

    props: {
      edition: {
        type: Object,
        required: true,
      },
    },

    computed: {
      ...mapGetters({
        isPluginEditionFree: 'pluginStore/isPluginEditionFree',
        getPluginEdition: 'pluginStore/getPluginEdition',
        getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
      }),

      pluginLicenseInfo() {
        if (!this.plugin) {
          return null;
        }

        return this.getPluginLicenseInfo(this.plugin.handle);
      },

      licensedEdition() {
        if (!this.pluginLicenseInfo) {
          return null;
        }

        return this.getPluginEdition(
          this.plugin,
          this.pluginLicenseInfo.licensedEdition
        );
      },
    },
  };
</script>
