<template>
  <div class="ps-container">
    <template v-if="activeTab === 'developers'">
      <developer-index class="mb-16" :request-data="requestData">
        <template #header>
          <h1 class="mt-0 mb-0">
            {{
              'Showing results for “{searchQuery}”' | t('app', {searchQuery})
            }}
          </h1>
          <search-tabs
            :active-tab="activeTab"
            @tab-click="activeTab = $event"
          />
        </template>
      </developer-index>
    </template>
    <template v-if="activeTab === 'plugins'">
      <plugin-index
        ref="pluginIndex"
        action="pluginStore/searchPlugins"
        :requestData="requestData"
        :plugins="plugins"
      >
        <template v-slot:header>
          <div>
            <h1>
              {{
                'Showing results for “{searchQuery}”' | t('app', {searchQuery})
              }}
            </h1>

            <search-tabs
              :active-tab="activeTab"
              @tab-click="activeTab = $event"
            />
          </div>
        </template>
      </plugin-index>
    </template>
  </div>
</template>

<script>
  import {mapState} from 'vuex';
  import PluginIndex from '../components/PluginIndex';
  import SearchTabs from '../components/SearchTabs';
  import DeveloperIndex from '../components/DeveloperIndex';

  export default {
    data() {
      return {
        activeTab: 'plugins',
      };
    },

    components: {
      DeveloperIndex,
      SearchTabs,
      PluginIndex,
    },

    watch: {
      searchQuery() {
        this.$router.push({path: '/'});

        this.$nextTick(() => {
          this.$router.push({path: '/search'});
        });
      },
    },

    computed: {
      ...mapState({
        plugins: (state) => state.pluginStore.plugins,
        searchQuery: (state) => state.app.searchQuery,
      }),

      requestData() {
        return {
          searchQuery: this.searchQuery,
        };
      },
    },

    mounted() {
      if (!this.searchQuery) {
        this.$router.push({path: '/'});
        return null;
      }
    },
  };
</script>
