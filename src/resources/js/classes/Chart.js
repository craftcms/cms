/**
* Craft Chart
*/

Craft.charts = {};

Craft.charts.LineChart = Garnish.Base.extend({

    $element: null,

    chart: null,

    init: function(element, data)
    {
        this.$element = $('#'+element);

        this.chart = new Chartist.Line('#'+element, data, this.defaultOptions(), this.defaultResponsiveOptions());
    },

    defaultOptions: function()
    {
        return {
            height: 180,
            chartPadding:
            {
                top: 15,
                right: 0,
                bottom: -25,
                left: -40
            },
            axisX: {
                showGrid: false,
                offset: 30,
                labelOffset:
                {
                    x: 0,
                    y: -20
                },
            },
            axisY:
            {
                offset: 40,
                showLabel: true,
                labelOffset:
                {
                    x: 40,
                    y: 0
                },
            },
            showArea: true,
            fullWidth: true,
            series:
            {
                'series-1':
                {
                    lineSmooth: Chartist.Interpolation.none(),
                    showPoint: true
                }
            }
        };
    },

    defaultResponsiveOptions: function()
    {
        return [
            ['screen and (min-width: 641px)', {
                showPoint: false,
                axisX:
                {
                    labelInterpolationFnc: function(value, i)
                    {
                        // Will return Mon, Tue, Wed etc. on medium screens

                        if(i % 5)
                        {
                            return;
                        }
                        else
                        {
                            return value;
                        }
                    }
                }
            }],

            ['screen and (max-width: 640px)', {
                showLine: false,
                axisX:
                {
                    labelInterpolationFnc: function(value, i)
                    {
                        // Will return M, T, W etc. on small screens
                        if(i % 10)
                        {
                            return;
                        }
                        else
                        {
                            return value;
                        }
                    }
                }
            }]
        ];
    }
});
