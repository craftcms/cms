<template>
  <div class="plugin-layout">
    <template v-if="plugin">
      <div ref="pluginDetailsHeader" class="plugin-details-header tw-mt-0">
        <template v-if="plugin">
          <div class="ps-container tw-pb-0">
            <div class="description tw-flex">
              <div class="icon tw-w-28">
                <img
                  v-if="plugin.iconUrl"
                  :alt="plugin.name + ' icon'"
                  :src="plugin.iconUrl"
                />
                <img v-else alt="Default plugin icon" :src="defaultPluginSvg" />
              </div>

              <div class="name tw-ml-8 tw-self-center">
                <h1
                  class="self-center tw-pb-0 tw-border-b-0 tw-mt-0 tw-mb-1 align-middle"
                >
                  {{ plugin.name }}
                </h1>

                <div class="developer tw-flex tw-items-center">
                  <router-link
                    :to="'/developer/' + plugin.developerId"
                    :title="plugin.developerName"
                  >
                    {{ plugin.developerName }}
                  </router-link>

                  <template v-if="plugin.developerPartner">
                    <partner-badge kind="craft" class="tw-ml-2" />
                  </template>
                </div>
              </div>
            </div>

            <div
              class="tabs tw-mt-6 tw-border-b tw-border-solid tw-border-gray-200"
            >
              <ul class="tw--mb-px tw-flex tw-space-x-6">
                <li v-for="(tab, tabKey) in tabs" :key="tabKey">
                  <router-link
                    :class="{
                      'tw-inline-block tw-px-1 tw-py-3 tw-border-solid tw-border-b-2 tw-text-opacity-75 hover:tw-text-opacity-100 hover:tw-no-underline': true,
                      'tw-border-transparent hover:tw-border-separator hover:tw-border-gray-200 tw-text-gray-500':
                        $route.path !==
                        '/' + plugin.handle + (tab.slug ? '/' + tab.slug : ''),
                      'tw-text-blue-600 tw-border-blue-600 tw-text-opacity-100':
                        $route.path ===
                        '/' + plugin.handle + (tab.slug ? '/' + tab.slug : ''),
                    }"
                    :to="'/' + plugin.handle + (tab.slug ? '/' + tab.slug : '')"
                  >
                    {{ tab.name }}
                  </router-link>
                </li>
              </ul>
            </div>
          </div>
        </template>
      </div>

      <div class="ps-container tw-py-8">
        <slot />
      </div>
    </template>
    <template v-else>
      <div class="ps-container tw-py-8">
        <c-spinner />
      </div>
    </template>
  </div>
</template>

<script>
  import {mapState, mapGetters} from 'vuex';
  import {isPluginFree} from '../utils/plugins';
  import PartnerBadge from './partner/PartnerBadge';

  export default {
    components: {PartnerBadge},
    computed: {
      ...mapState({
        plugin: (state) => state.pluginStore.plugin,
      }),

      ...mapGetters({
        isCommercial: 'pluginStore/isCommercial',
        getPluginEditions: 'pluginStore/getPluginEditions',
      }),

      tabs() {
        const tabs = [];

        tabs.push({
          name: this.$options.filters.t('Overview', 'app'),
          slug: '',
        });

        tabs.push({
          name: this.$options.filters.t('Reviews', 'app'),
          slug: 'reviews',
        });

        if (
          !this.isPluginFree(this.plugin) &&
          this.plugin.editions.length > 1
        ) {
          tabs.push({
            name: this.$options.filters.t('Editions', 'app'),
            slug: 'editions',
          });
        }

        tabs.push({
          name: this.$options.filters.t('Changelog', 'app'),
          slug: 'changelog',
        });

        return tabs;
      },

      pluginId() {
        if (this.plugin) {
          return this.plugin.id;
        }

        return null;
      },
    },

    methods: {
      isPluginFree,
    },

    mounted() {
      const pluginHandle = this.$route.params.handle;

      if (this.plugin && this.plugin.handle === pluginHandle) {
        return;
      }

      this.loading = true;

      this.$store.commit('pluginStore/updatePluginDetails', null);

      Promise.all([
        this.$store.dispatch('pluginReviews/getPluginReviews', {
          handle: pluginHandle,
        }),
        this.$store.dispatch(
          'pluginStore/getPluginDetailsByHandle',
          pluginHandle
        ),
      ])
        .then(() => {
          this.loading = false;
        })
        .catch(() => {
          this.loading = false;
        });
    },
  };
</script>
