<template>
  <div>
    <ClientOnly>
      <apex-chart
        type="area"
        height="40"
        :options="chartOptions"
        :series="series"
      />
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
      series: [
        {
          name: 'Installs',
          data: this.plugin.activeInstallsStats.data
        }
      ],
    }
  },

  computed: {
    chartOptions() {
      return {
        chart: {
          sparkline: {
            enabled: true,
          },
          zoom: {
            enabled: false
          },
          type: 'area',
          events: {
            mouseLeave: function() {
              this.$emit('updateCurrentDataPoint', null)
            }.bind(this),

            mouseMove: function(event, chartContext, config) {
              let value = null
              let date = null

              if (config.globals.series && config.globals.series[0] && config.globals.series[0][config.dataPointIndex]) {
                value = config.globals.series[0][config.dataPointIndex]
              }

              if (config.globals.seriesX && config.globals.seriesX[0] && config.globals.seriesX[0][config.dataPointIndex]) {
                date = new Date(config.globals.seriesX[0][config.dataPointIndex]).toISOString()
              }

              this.$emit('updateCurrentDataPoint', {
                value,
                date,
              })
            }.bind(this),
          }
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'straight'
        },
        grid: {
          xaxis: {
            lines: {
              show: false,
            }
          },
          yaxis: {
            lines: {
              show: false,
            }
          }
        },
        xaxis: {
          type: 'datetime',
          categories: this.plugin.activeInstallsStats.categories,
        },
        yaxis: {
          show: false,
        },
        tooltip: {
          enabled: true,
          intersect: false,
          shared: true,

          x: {
            format: 'dd/MM/yy'
          },

          custom() {
            return ''
          }
        },
      }
    }
  }
}
</script>