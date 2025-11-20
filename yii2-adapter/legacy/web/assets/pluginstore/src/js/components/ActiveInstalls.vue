<template>
  <meta-stat>
    <template #title>
      <template v-if="activeInstallsDate !== null">
        {{ activeInstallsDate | formatDate }}
      </template>
      <template v-else>
        {{ 'Active Installs' | t('app') }}
      </template>
    </template>
    <template #content>
      <div class="tw-flex">
        <div class="tw-w-1/3">
          {{ activeInstalls | formatNumber }}
        </div>

        <template v-if="plugin.installHistory">
          <active-installs-chart
            class="tw-flex-1"
            :plugin="plugin"
            @updateCurrentDataPoint="updateActiveInstallsDataPoint"
          />
        </template>
      </div>
    </template>
  </meta-stat>
</template>

<script>
  import MetaStat from './MetaStat';
  import ActiveInstallsChart from './ActiveInstallsChart';
  export default {
    components: {ActiveInstallsChart, MetaStat},

    props: {
      plugin: {
        type: Object,
        required: true,
      },
    },

    data() {
      return {
        activeInstallsValue: null,
        activeInstallsDate: null,
      };
    },

    computed: {
      activeInstalls() {
        if (this.activeInstallsValue !== null) {
          return this.activeInstallsValue;
        }

        return this.plugin.activeInstalls;
      },
    },

    methods: {
      updateActiveInstallsDataPoint(dataPoint) {
        if (dataPoint) {
          this.activeInstallsValue = dataPoint.value;
          this.activeInstallsDate = dataPoint.date;
        } else {
          this.activeInstallsValue = null;
          this.activeInstallsDate = null;
        }
      },
    },
  };
</script>
