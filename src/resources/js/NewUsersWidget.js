(function($) {


Craft.NewUsersWidget = Garnish.Base.extend(
{
    settings: null,
    data: null,

    $widget: null,
    $body: null,

    init: function(widgetId, settings)
    {
        this.setSettings(settings);

        this.$widget = $('#widget'+widgetId);
        this.$body = this.$widget.find('.body:first');

        // Add the chart to the body
        this.$chartContainer = $('.chart', this.$widget);

        // Error
        this.$error = $('<div class="error"/>').prependTo(this.$body);

        // Request orders report
        var requestData = {
            dateRange: this.settings.dateRange,
            userGroupId: this.settings.userGroupId,
            elementType: 'Commerce_Order'
        };

        Craft.postActionRequest('reports/getNewUsersReport', requestData, $.proxy(function(response, textStatus)
        {
            if(textStatus == 'success' && typeof(response.error) == 'undefined')
            {
                this.chart = new Craft.charts.Chart({
                    bindto: this.$chartContainer.get(0),
                    data: {
                        rows: response.report,
                    },
                    axis: {
                        x: {
                            tick: {
                                format: Craft.charts.getDateFormatFromScale(response.scale),
                            }
                        }
                    },
                    'orientation': this.settings.orientation,
                }, Craft.charts.defaults.area);

                this.chart.load({
                    rows: response.report
                });

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