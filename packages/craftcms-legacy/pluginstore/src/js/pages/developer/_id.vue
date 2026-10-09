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
            class="developer-card tw-flex tw-pb-6 tw-items-center"
          >
            <template v-if="developer.photoUrl">
              <div
                class="avatar tw-w-28 tw-h-28 tw-inline-block tw-overflow-hidden tw-rounded-full tw-bg-gray-100 tw-mr-8 tw-no-line-height"
              >
                <img :src="developer.photoUrl" class="tw-w-full tw-h-full" />
              </div>
            </template>

            <div class="tw-flex-1">
              <h1 class="tw-text-lg tw-font-bold">
                {{ developer.developerName }}
              </h1>

              <div v-if="developer.location" class="tw-mt-1">
                {{ developer.location }}
              </div>

              <!-- Partner badges -->
              <template
                v-if="
                  developer.partnerInfo &&
                  (developer.partnerInfo.isCraftVerified ||
                    developer.partnerInfo.isCommerceVerified ||
                    developer.partnerInfo.isEnterpriseVerified)
                "
              >
                <div class="tw-mt-4 tw-text-sm">
                  <ul
                    class="xl:tw-flex tw-space-y-2 xl:tw-space-y-0 xl:tw-space-x-6 tw-text-gray-600"
                  >
                    <template
                      v-if="
                        developer.partnerInfo &&
                        developer.partnerInfo.isCraftVerified
                      "
                    >
                      <li class="tw-flex tw-items-center">
                        <partner-badge
                          kind="craft"
                          class="tw-shrink-0 tw-mr-2"
                        />
                        Craft Verified
                      </li>
                    </template>
                    <template
                      v-if="
                        developer.partnerInfo &&
                        developer.partnerInfo.isCommerceVerified
                      "
                    >
                      <li class="tw-flex tw-items-center">
                        <partner-badge
                          kind="commerce"
                          class="tw-shrink-0 tw-mr-2"
                        />
                        Craft Commerce Verified
                      </li>
                    </template>
                    <template
                      v-if="
                        developer.partnerInfo &&
                        developer.partnerInfo.isEnterpriseVerified
                      "
                    >
                      <li class="tw-flex tw-items-center">
                        <partner-badge
                          kind="enterprise"
                          class="tw-shrink-0 tw-mr-2"
                        />
                        Enterprise Verified
                      </li>
                    </template>
                  </ul>
                </div>
              </template>

              <!-- Developer URL and partner profile URL-->
              <template
                v-if="
                  developer.developerUrl ||
                  (developer.partnerInfo && developer.partnerInfo.profileUrl)
                "
              >
                <div class="tw-mt-4 tw-text-sm">
                  <ul
                    class="developer-buttons xl:tw-flex tw-space-y-2 xl:tw-space-y-0 xl:tw-space-x-3 tw-text-gray-600 tw-space-y-2"
                  >
                    <!-- Developer URL -->
                    <template v-if="developer.developerUrl">
                      <li>
                        <c-btn target="_blank" :href="developer.developerUrl"
                          >{{ 'Website' | t('app') }}
                          <c-icon
                            icon="external-link"
                            class="tw-w-3 tw-h-3 tw-text-grey-dark tw-ml-1"
                            :size="null"
                          />
                        </c-btn>
                      </li>
                    </template>

                    <!-- Partner profile URL -->
                    <template
                      v-if="
                        developer.partnerInfo &&
                        developer.partnerInfo.profileUrl
                      "
                    >
                      <li class="tw-inline-block tw-mr-2">
                        <c-btn
                          class="tw-inline-block"
                          target="_blank"
                          :href="developer.partnerInfo.profileUrl"
                        >
                          {{ 'Partner Profile' }}
                          <c-icon
                            icon="external-link"
                            class="tw-w-3 tw-h-3 tw-text-grey-dark tw-ml-1"
                            :size="null"
                          />
                        </c-btn>
                      </li>
                    </template>
                  </ul>
                </div>
              </template>
            </div>
          </div>
        </template>
      </plugin-index>
    </template>
    <template v-else>
      <c-spinner />
    </template>
  </div>
</template>

<script>
  import {mapState} from 'vuex';
  import PluginIndex from '../../components/PluginIndex';
  import PartnerBadge from '../../components/partner/PartnerBadge';

  export default {
    data() {
      return {
        loading: true,
      };
    },

    components: {
      PartnerBadge,
      PluginIndex,
    },

    computed: {
      ...mapState({
        developer: (state) => state.pluginStore.developer,
        plugins: (state) => state.pluginStore.plugins,
      }),

      requestData() {
        return {
          developerId: this.$route.params.id,
        };
      },
    },

    mounted() {
      const developerId = this.$route.params.id;

      // load developer details
      this.$store
        .dispatch('pluginStore/getDeveloper', developerId)
        .then(() => {
          this.loading = false;
        })
        .catch(() => {
          this.loading = false;
        });
    },
  };
</script>
