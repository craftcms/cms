<template>
  <div class="ps-container">
    <template v-if="!loading">
      <plugin-index
        action="pluginStore/getPluginsByDeveloperId"
        :requestData="requestData"
        :plugins="plugins"
      >
        <template v-slot:header>
          <div
            v-if="developer"
            class="developer-card tw-flex tw-pb-6 tw-items-center">
            <div class="avatar tw-w-32   tw-h-32   tw-inline-block tw-overflow-hidden tw-rounded-full tw-bg-grey tw-mr-6 tw-no-line-height">
              <img
                :src="developer.photoUrl"
                class="tw-w-full tw-h-full"
              />
            </div>

            <div class="tw-flex-1">
              <h1 class="tw-text-lg tw-font-bold">
                {{ developer.developerName }}</h1>

              <div
                v-if="developer.location">{{ developer.location }}</div>

              <div class="tw-mt-4">
                <ul class="tw-flex tw-gap-6">
                  <li class="tw-flex tw-items-center">
                    <craft-verified-icon
                      class="tw-w-6 tw-h-6 tw-mr-2"
                    />
                    Craft Verified
                  </li>
                  <li class="tw-flex tw-items-center">
                    <craft-commerce-verified-icon
                      class="tw-w-6 tw-h-6 tw-mr-2"
                    />
                    Craft Commerce Verified
                  </li>
                  <li class="tw-flex tw-items-center">
                    <enterprise-verified-icon
                      class="tw-w-6 tw-h-6 tw-mr-2"
                    />
                    Enterprise Verified
                  </li>
                </ul>
              </div>

              <div class="tw-mt-4" v-if="developer.developerUrl">
                <c-btn
                  :href="developer.developerUrl"
                >{{ "Website"|t('app') }}
                </c-btn>
              </div>
            </div>
          </div>
        </template>
      </plugin-index>
    </template>
    <template v-else>
      <c-spinner/>
    </template>
  </div>
</template>

<script>
import {mapState} from 'vuex'
import PluginIndex from '../../components/PluginIndex'
import CraftVerifiedIcon from '../../components/partner/icons/CraftVerifiedIcon';
import CraftCommerceVerifiedIcon from '../../components/partner/icons/CraftCommerceVerifiedIcon';
import EnterpriseVerifiedIcon from '../../components/partner/icons/EnterpriseVerifiedIcon';

export default {
  data() {
    return {
      loading: true,
    }
  },

  components: {
    EnterpriseVerifiedIcon,
    CraftCommerceVerifiedIcon,
    CraftVerifiedIcon,
    PluginIndex,
  },

  computed: {
    ...mapState({
      developer: state => state.pluginStore.developer,
      plugins: state => state.pluginStore.plugins,
    }),

    requestData() {
      return {
        developerId: this.$route.params.id,
      }
    },
  },

  mounted() {
    const developerId = this.$route.params.id

    // load developer details
    this.$store.dispatch('pluginStore/getDeveloper', developerId)
      .then(() => {
        this.loading = false
      })
      .catch(() => {
        this.loading = false
      })
  },
}
</script>
