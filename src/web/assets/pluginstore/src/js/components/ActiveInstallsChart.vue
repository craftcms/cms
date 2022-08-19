<template>
  <div v-if="plugin.installHistory">
    <ClientOnly>
      <div>
        <apex-chart
          type="area"
          height="40"
          :options="chartOptions"
          :series="series"
        />
        <div
          v-if="chartMounted"
          class="tw-h-3 tw-bg-gradient-to-b tw-from-blue-100/100 tw-to-blue-100/0"
        />
      </div>
    </ClientOnly>
  </div>
</template>

<script>
  export default {
    props: {
      plugin: {
        type: Object,
        required: true,
      },
    },
    data() {
      return {
        chartMounted: false,
      };
    },

    computed: {
      chartOptions() {
        return {
          fill: {
            opacity: 1,
            type: 'solid',
            colors: ['var(--chart-fill-color)'],
            gradient: null,
          },
          chart: {
            animations: {
              enabled: false,
            },
            sparkline: {
              enabled: true,
            },
            zoom: {
              enabled: false,
            },
            type: 'area',
            events: {
              mounted: function () {
                this.chartMounted = true;
              }.bind(this),

              mouseLeave: function () {
                this.$emit('updateCurrentDataPoint', null);
              }.bind(this),

              mouseMove: function (event, chartContext, config) {
                let value = null;
                let date = null;

                if (
                  config.globals.series &&
                  config.globals.series[0] &&
                  config.globals.series[0][config.dataPointIndex]
                ) {
                  value = config.globals.series[0][config.dataPointIndex];
                }

                if (
                  config.globals.seriesX &&
                  config.globals.seriesX[0] &&
                  config.globals.seriesX[0][config.dataPointIndex]
                ) {
                  date = new Date(
                    config.globals.seriesX[0][config.dataPointIndex]
                  ).toISOString();
                }

                this.$emit('updateCurrentDataPoint', {
                  value,
                  date,
                });
              }.bind(this),
            },
          },
          dataLabels: {
            enabled: false,
          },
          stroke: {
            curve: 'straight',
          },
          grid: {
            xaxis: {
              lines: {
                show: false,
              },
            },
            yaxis: {
              lines: {
                show: false,
              },
            },
          },
          xaxis: {
            type: 'datetime',
          },
          yaxis: {
            show: false,
          },
          tooltip: {
            enabled: true,
            intersect: false,
            shared: true,

            x: {
              format: 'dd/MM/yy',
            },

            custom() {
              return '';
            },
          },
        };
      },

      series() {
        return [
          {
            name: 'Active Installs',
            data: this.chartData,
          },
        ];
      },

      chartData() {
        if (!this.plugin.installHistory) {
          return [];
        }

        const data = [];

        this.plugin.installHistory.forEach((item) => {
          data.push({
            x: new Date(item.date),
            y: item.activeInstalls,
          });
        });

        return data;
      },
    },
  };
</script>

<style>
  body {
    --chart-fill-color: #dbeafe;
  }
</style>
