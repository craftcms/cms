<template>
  <plugin-layout v-if="plugin">
    <template v-if="loading">
      <c-spinner class="tw-mt-4" />
    </template>

    <template v-else>
      <div class="releases">
        <template v-for="(release, key) in pluginChangelog">
          <changelog-release :key="key" :release="release"></changelog-release>
        </template>
      </div>
    </template>
  </plugin-layout>
</template>

<script>
  import PluginLayout from '../../components/PluginLayout';
  import {mapState} from 'vuex';
  import ChangelogRelease from '../../components/ChangelogRelease';

  export default {
    components: {ChangelogRelease, PluginLayout},

    data() {
      return {
        loading: false,
      };
    },

    computed: {
      ...mapState({
        plugin: (state) => state.pluginStore.plugin,
        pluginChangelog: (state) => state.pluginStore.pluginChangelog,
        pluginChangelogPluginId: (state) =>
          state.pluginStore.pluginChangelogPluginId,
      }),

      pluginId() {
        if (this.plugin) {
          return this.plugin.id;
        }

        return null;
      },
    },

    methods: {
      getPluginChangelog() {
        if (!this.pluginId) {
          return null;
        }

        this.$store.dispatch('pluginStore/getPluginChangelog', this.pluginId);
      },

      initPlugin() {
        const pluginHandle = this.$route.params.handle;

        if (
          !this.plugin ||
          (this.plugin && this.plugin.handle !== pluginHandle)
        ) {
          this.loading = true;

          this.$store
            .dispatch('pluginStore/getPluginDetailsByHandle', pluginHandle)
            .then(() => {
              this.loading = false;

              this.initChangelog();
            })
            .catch(() => {
              this.loading = false;
            });
        } else {
          this.initChangelog();
        }
      },

      initChangelog() {
        if (
          this.plugin &&
          this.plugin.id &&
          !(
            this.pluginChangelogPluginId &&
            this.pluginChangelogPluginId === this.plugin.id
          )
        ) {
          this.loading = true;

          this.$store
            .dispatch('pluginStore/getPluginChangelog', this.pluginId)
            .then(() => {
              this.loading = false;
            });
        }
      },
    },

    mounted() {
      this.initPlugin();
    },
  };
</script>
