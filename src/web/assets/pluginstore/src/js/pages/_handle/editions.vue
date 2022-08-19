<template>
  <plugin-layout>
    <div>
      <plugin-editions :plugin="plugin"></plugin-editions>
    </div>
  </plugin-layout>
</template>

<script>
  import PluginLayout from '../../components/PluginLayout';
  import {mapState} from 'vuex';
  import PluginEditions from '../../components/PluginEditions';

  export default {
    components: {PluginEditions, PluginLayout},

    computed: {
      ...mapState({
        plugin: (state) => state.pluginStore.plugin,
      }),

      pluginId() {
        if (this.plugin) {
          return this.plugin.id;
        }

        return null;
      },
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
  };
</script>
