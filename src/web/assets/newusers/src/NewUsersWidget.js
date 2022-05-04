(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.NewUsersWidget = Garnish.Base.extend(
    {
      settings: null,
      data: null,
      startDate: null,
      endDate: null,

      $widget: null,
      $body: null,

      init: function (widgetId, settings) {
        this.setSettings(settings);

        this.$widget = $('#widget' + widgetId);
        this.$body = this.$widget.find('.body:first');
        this.$chartContainer = $('<div class="chart hidden"></div>').appendTo(
          this.$body
        );
        this.$error = $('<div class="error"/>').appendTo(this.$body);

        var dateRange = this.settings.dateRange;

        switch (dateRange) {
          case 'd7':
            this.startDate = Craft.NewUsersWidget.getDateByDays(6);
            this.endDate = new Date();
            break;

          case 'd30':
            this.startDate = Craft.NewUsersWidget.getDateByDays(30);
            this.endDate = new Date();
            break;

          case 'lastweek':
            this.startDate = Craft.NewUsersWidget.getDateByDays(13);
            this.endDate = Craft.NewUsersWidget.getDateByDays(7);
            break;

          case 'lastmonth':
            this.startDate = Craft.NewUsersWidget.getDateByDays(60);
            this.endDate = Craft.NewUsersWidget.getDateByDays(30);
            break;
        }

        // Request orders report
        var data = {
          startDate: Craft.NewUsersWidget.getDateValue(this.startDate),
          endDate: Craft.NewUsersWidget.getDateValue(this.endDate),
          userGroupId: this.settings.userGroupId,
        };

        Craft.sendActionRequest('POST', 'charts/get-new-users-data', {data})
          .then((response) => {
            this.$chartContainer.removeClass('hidden');

            if (response.data.errors && response.data.errors.length) {
              return Promise.reject();
            }

            // Create chart
            this.chart = new Craft.charts.Area(this.$chartContainer, {
              yAxis: {
                formatter: function (chart) {
                  return function (d) {
                    var format = ',.0f';

                    if (d != Math.round(d)) {
                      format = ',.1f';
                    }

                    return chart.formatLocale.format(format)(d);
                  };
                },
              },
            });

            var chartDataTable = new Craft.charts.DataTable(
              response.data.dataTable
            );

            var chartSettings = {
              orientation: response.data.orientation,
              dataScale: response.data.scale,
              formats: response.data.formats,
            };

            this.chart.draw(chartDataTable, chartSettings);

            // Resize chart when grid is refreshed
            window.dashboard.grid.on(
              'refreshCols',
              this.handleGridRefresh.bind(this)
            );
          })
          .catch(({response}) => {
            var msg =
              response.data.message || Craft.t('A server error occurred.');

            this.$error.html(msg);
            this.$error.removeClass('hidden');
          });

        this.$widget.data('widget').on('destroy', this.destroy.bind(this));

        Craft.NewUsersWidget.instances.push(this);
      },

      handleGridRefresh: function () {
        this.chart.resize();
      },

      destroy: function () {
        Craft.NewUsersWidget.instances.splice(
          $.inArray(this, Craft.NewUsersWidget.instances),
          1
        );
        this.base();
      },
    },
    {
      instances: [],

      getDateByDays: function (days) {
        var date = new Date();
        date = date.getTime() - 60 * 60 * 24 * days * 1000;
        return new Date(date);
      },

      getDateValue: function (date) {
        return Math.floor(date.getTime() / 1000);
      },
    }
  );
})(jQuery);
