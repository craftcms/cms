<template>
  <plugin-layout>
    <div class="plugin-details">
      <template v-if="!loading && plugin">
        <!-- body -->
        <div class="plugin-details-body">
          <template v-if="!loading">
            <template v-if="plugin.abandoned">
              <div
                class="error tw-mb-6 tw-px-4 tw-py-3 tw-rounded tw-border tw-border-solid tw-border-red-500 tw-flex tw-flex-nowrap tw-text-base tw-items-center"
              >
                <c-icon icon="alert" class="tw-w-8 tw-h-8 tw-mr-2" />

                <div class="tw-flex-1 tw-mb-0">
                  <strong
                    >{{ 'This plugin is no longer maintained.' | t('app') }}
                  </strong>
                  <span
                    v-if="recommendedLabel"
                    v-html="recommendedLabel"
                  ></span>
                </div>
              </div>
            </template>

            <!-- Screenshots -->
            <template v-if="plugin.thumbnailUrls.length">
              <div
                class="tw-border-b tw-border-solid tw-border-gray-200 tw-mb-8"
              >
                <plugin-screenshots
                  :thumbnails="plugin.thumbnailUrls"
                  :images="plugin.screenshotUrls"
                />
              </div>
            </template>

            <div class="xl:tw-flex">
              <div class="xl:tw-flex-1 xl:tw-pr-8 xl:tw-mr-4">
                <div
                  v-if="longDescription"
                  v-html="longDescription"
                  class="readable"
                ></div>
                <div
                  v-else-if="plugin.shortDescription"
                  v-html="plugin.shortDescription"
                  class="readable"
                ></div>
                <p v-else>No description.</p>
              </div>
              <div class="xl:tw-ml-4 xl:tw-w-60 tw-mt-8 xl:tw-mt-0">
                <plugin-meta :plugin="plugin" />
              </div>
            </div>

            <template v-if="licenseMismatched">
              <hr />

              <div class="tw-py-8">
                <div class="tw-mx-auto tw-max-w-sm tw-px-8">
                  <div class="tw-flex items-center">
                    <svg
                      version="1.1"
                      xmlns="http://www.w3.org/2000/svg"
                      x="0px"
                      y="0px"
                      viewBox="0 0 256 448"
                      xml:space="preserve"
                      class="tw-text-blue-600 tw-fill-current tw-w-8 tw-h-8 tw-mr-4 tw-flex tw-items-center tw-shrink-0"
                    >
                      <path
                        fill="currentColor"
                        d="M184,144c0,4.2-3.8,8-8,8s-8-3.8-8-8c0-17.2-26.8-24-40-24c-4.2,0-8-3.8-8-8s3.8-8,8-8C151.2,104,184,116.2,184,144z
                          M224,144c0-50-50.8-80-96-80s-96,30-96,80c0,16,6.5,32.8,17,45c4.8,5.5,10.2,10.8,15.2,16.5C82,226.8,97,251.8,99.5,280h57
                          c2.5-28.2,17.5-53.2,35.2-74.5c5-5.8,10.5-11,15.2-16.5C217.5,176.8,224,160,224,144z M256,144c0,25.8-8.5,48-25.8,67
                          s-40,45.8-42,72.5c7.2,4.2,11.8,12.2,11.8,20.5c0,6-2.2,11.8-6.2,16c4,4.2,6.2,10,6.2,16c0,8.2-4.2,15.8-11.2,20.2
                          c2,3.5,3.2,7.8,3.2,11.8c0,16.2-12.8,24-27.2,24c-6.5,14.5-21,24-36.8,24s-30.2-9.5-36.8-24c-14.5,0-27.2-7.8-27.2-24
                          c0-4,1.2-8.2,3.2-11.8c-7-4.5-11.2-12-11.2-20.2c0-6,2.2-11.8,6.2-16c-4-4.2-6.2-10-6.2-16c0-8.2,4.5-16.2,11.8-20.5
                          c-2-26.8-24.8-53.5-42-72.5S0,169.8,0,144C0,76,64.8,32,128,32S256,76,256,144z"
                      />
                    </svg>
                    <div>
                      <div v-html="licenseMismatchedMessage"></div>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </template>
          <template v-else>
            <c-spinner />
          </template>
        </div>
      </template>
      <template v-else>
        <c-spinner />
      </template>
    </div>
  </plugin-layout>
</template>

<script>
  /* global Craft */

  import {mapState, mapGetters, mapActions} from 'vuex';
  import PluginScreenshots from '../../components/PluginScreenshots';
  import licensesMixin from '../../mixins/licenses';
  import PluginMeta from '../../components/PluginMeta';
  import PluginLayout from '../../components/PluginLayout';

  export default {
    mixins: [licensesMixin],

    components: {
      PluginLayout,
      PluginScreenshots,
      PluginMeta,
    },

    data() {
      return {
        actionsLoading: false,
        loading: false,
      };
    },

    computed: {
      ...mapState({
        categories: (state) => state.pluginStore.categories,
        plugin: (state) => state.pluginStore.plugin,
        showingScreenshotModal: (state) => state.app.showingScreenshotModal,
      }),

      ...mapGetters({
        getPluginEdition: 'pluginStore/getPluginEdition',
        getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
      }),

      longDescription() {
        if (
          this.plugin.longDescription &&
          this.plugin.longDescription.length > 0
        ) {
          return this.plugin.longDescription;
        }

        return null;
      },

      pluginCategories() {
        return this.categories.filter((c) => {
          return this.plugin.categoryIds.find((pc) => pc == c.id);
        });
      },

      licenseLabel() {
        switch (this.plugin.license) {
          case 'craft':
            return 'Craft';

          case 'mit':
            return 'MIT';
        }

        return null;
      },

      lastUpdate() {
        const date = new Date(this.plugin.lastUpdate.replace(/\s/, 'T'));
        return Craft.formatDate(date);
      },

      pluginLicenseInfo() {
        if (!this.plugin) {
          return null;
        }

        return this.getPluginLicenseInfo(this.plugin.handle);
      },

      licenseMismatchedMessage() {
        return this.$options.filters.t(
          'This license is tied to another Craft install. Visit {accountLink} to detach it, or buy a new license.',
          'app',
          {
            accountLink:
              '<a href="https://id.craftcms.com" rel="noopener" target="_blank">id.craftcms.com</a>',
          }
        );
      },

      recommendedLabel() {
        if (!this.plugin.replacementHandle) {
          return null;
        }

        return this.$options.filters.t(
          'The developer recommends using <a href="{url}">{name}</a> instead.',
          'app',
          {
            name: this.plugin.replacementName,
            url: Craft.getCpUrl(
              'plugin-store/' + this.plugin.replacementHandle
            ),
          }
        );
      },
    },

    methods: {
      ...mapActions({
        addToCart: 'cart/addToCart',
      }),
    },

    mounted() {
      const pluginHandle = this.$route.params.handle;

      if (this.plugin && this.plugin.handle === pluginHandle) {
        return;
      }

      this.loading = true;

      this.$store
        .dispatch('pluginStore/getPluginDetailsByHandle', pluginHandle)
        .then(() => {
          this.loading = false;
        })
        .catch(() => {
          this.loading = false;
        });
    },

    beforeDestroy() {
      this.$store.dispatch('pluginStore/cancelRequests');
    },

    beforeRouteLeave(to, from, next) {
      if (this.showingScreenshotModal) {
        this.$store.commit('app/updateShowingScreenshotModal', false);
      } else {
        next();
      }
    },
  };
</script>
