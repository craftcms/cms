<template>
  <meta-stat>
    <template #title>
      <template v-if="activeInstallsDate !== null">
        {{ activeInstallsDate | formatDate }}
      </template>
      <template v-else>
        {{ "Active Installs"|t('app') }}
      </template>
    </template>
    <template #content>
      <div class="flex">
        <div class="w-1/3">
          {{ activeInstalls | formatNumber }}
        </div>

        <active-installs-chart
          class="flex-1"
          :plugin="plugin"
          @updateCurrentDataPoint="updateActiveInstallsDataPoint"
        />
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
    }
  },

  computed: {
    activeInstalls() {
      if (this.activeInstallsValue !== null) {
        return this.activeInstallsValue;
      }

      return this.plugin.activeInstalls
    },
  },

  methods: {
    updateActiveInstallsDataPoint(dataPoint) {
      if (dataPoint) {
        this.activeInstallsValue = dataPoint.value
        this.activeInstallsDate = dataPoint.date
      } else {
        this.activeInstallsValue = null
        this.activeInstallsDate = null
      }
    }
  },
}
</script>