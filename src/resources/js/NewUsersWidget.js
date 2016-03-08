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
        this.$chartContainer = $('<div class="chart hidden"></div>').appendTo(this.$body);
        this.$error = $('<div class="error"/>').appendTo(this.$body);

        var dateRange = this.settings.dateRange;

        switch(dateRange)
        {
            case 'd7':
                this.startDate = Craft.NewUsersWidget.getDateByDays('7');
                this.endDate = new Date();
            break;

            case 'd30':
                this.startDate = Craft.NewUsersWidget.getDateByDays('30');
                this.endDate = new Date();
            break;

            case 'lastweek':
                this.startDate = Craft.NewUsersWidget.getDateByDays('14');
                this.endDate = Craft.NewUsersWidget.getDateByDays('7');
            break;

            case 'lastmonth':
                this.startDate = Craft.NewUsersWidget.getDateByDays('60');
                this.endDate = Craft.NewUsersWidget.getDateByDays('30');
            break;
        }

        // Request orders report
        var requestData = {
            startDate: Craft.NewUsersWidget.getDateValue(this.startDate),
            endDate: Craft.NewUsersWidget.getDateValue(this.endDate),
            userGroupId: this.settings.userGroupId,
        };

        Craft.postActionRequest('charts/getNewUsersData', requestData, $.proxy(function(response, textStatus)
        {
            if(textStatus == 'success' && typeof(response.error) == 'undefined')
            {
                this.$chartContainer.removeClass('hidden');

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
                var msg = Craft.t('An unknown error occurred.');

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
    instances: [],

    getDateByDays: function(days)
    {
        var date = new Date();
        date = date.getTime() - (60 * 60 * 24 * days * 1000);
        return new Date(date);
    },

    getDateValue: function(date)
    {
        return date.getFullYear()+'-'+(date.getMonth()+1)+'-'+date.getDate();
    }
});


})(jQuery);
