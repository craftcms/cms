(function($) {


Craft.NewUsersWidget = Garnish.Base.extend(
{
    settings: null,
    data: null,
    startDate: null,
    endDate: null,

    $widget: null,
    $body: null,

    init: function(widgetId, settings)
    {
        this.setSettings(settings);

        this.$widget = $('#widget'+widgetId);
        this.$body = this.$widget.find('.body:first');
        this.$chartContainer = $('.chart', this.$widget);
        this.$error = $('<div class="error"/>').prependTo(this.$body);

        var dateRange = this.settings.dateRange;

        switch(dateRange)
        {
            case 'd7':
                this.startDate = this.getDateByDays('7');
            break;

            case 'd30':
                this.startDate = this.getDateByDays('30');
            break;

            case 'lastweek':
                this.startDate = this.getDateByDays('14');
                this.endDate = this.getDateByDays('7');
            break;

            case 'lastmonth':
                this.startDate = this.getDateByDays('60');
                this.endDate = this.getDateByDays('30');
            break;
        }

        // Request orders report
        var requestData = {
            startDate: this.startDate,
            endDate: this.endDate,
            userGroupId: this.settings.userGroupId,
        };

        Craft.postActionRequest('charts/getNewUsersReport', requestData, $.proxy(function(response, textStatus)
        {
            if(textStatus == 'success' && typeof(response.error) == 'undefined')
            {
                // Create chart
                this.chart = new Craft.charts.Area(this.$chartContainer);

                var chartDataTable = new Craft.charts.DataTable(response.dataTable);

                var chartSettings = {
                    orientation: response.orientation,
                    dataScale: response.scale,
                    formats: response.formats,
                };

                this.chart.draw(chartDataTable, chartSettings);

                // Resize chart when grid is refreshed
                window.dashboard.grid.on('refreshCols', $.proxy(this, 'handleGridRefresh'));
            }
            else
            {
                // Error

                var msg = 'An unknown error occured.';

                if(typeof(response) != 'undefined' && response && typeof(response.error) != 'undefined')
                {
                    msg = response.error;
                }

                this.$error.html(msg);
                this.$error.removeClass('hidden');
            }

        }, this));

        this.$widget.data('widget').on('destroy', $.proxy(this, 'destroy'));

        Craft.NewUsersWidget.instances.push(this);
    },

    getDateByDays: function(days)
    {
        var date = new Date();
        date = date.getTime() - (60 * 60 * 24 * days * 1000);
        return new Date(date);
    },

    handleGridRefresh: function()
    {
        this.chart.resize();
    },

    destroy: function()
    {
        Craft.NewUsersWidget.instances.splice($.inArray(this, Craft.NewUsersWidget.instances), 1);
        this.base();
    }
}, {
    instances: []
});


})(jQuery);
