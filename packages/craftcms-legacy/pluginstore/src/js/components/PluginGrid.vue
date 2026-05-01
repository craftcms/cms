<template>
  <div>
    <div
      class="tw-grid-plugins tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 xl:tw-grid-cols-3 2xl:tw-grid-cols-4 tw-gap-x-8"
      v-if="plugins && plugins.length > 0"
    >
      <div
        class="tw-grid-box sm:tw-flex"
        v-for="(plugin, key) in computedPlugins"
        :key="key"
      >
        <plugin-card
          class="sm:tw-flex-1"
          :plugin="plugin"
          :trialMode="trialMode"
        ></plugin-card>
      </div>
    </div>
  </div>
</template>

<script>
  import PluginCard from './PluginCard';

  export default {
    components: {
      PluginCard,
    },

    props: ['plugins', 'trialMode', 'autoLimit'],

    data() {
      return {
        winWidth: null,
      };
    },

    computed: {
      computedPlugins() {
        return this.plugins.filter((plugin, key) => {
          if (!this.autoLimit || (this.autoLimit && key < this.limit)) {
            return true;
          }

          return false;
        });
      },

      limit() {
        if (this.winWidth > 1536) {
          return 8;
        }

        return 6;
      },
    },

    methods: {
      onWindowResize() {
        this.winWidth = window.innerWidth;
      },
    },

    mounted() {
      this.winWidth = window.innerWidth;
      this.$root.$on('windowResize', this.onWindowResize);
    },

    beforeDestroy() {
      this.$root.$off('windowResize', this.onWindowResize);
    },
  };
</script>
