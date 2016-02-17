/**
 * Craft Charts
 */

Craft.charts = {};


/**
 * Class Craft.charts.Chart
 */
Craft.charts.Chart = Garnish.Base.extend(
{
    _chart: null,

    init: function(options, defaultOptions)
    {
        var baseChartOptions = {
            orientation: 'ltr',
            color: {
                pattern: ["#0594D1", "#DE3800", "#FF9A00", "#009802", "#9B009B"],
            },

            axis: {
                y: {
                    inner: true
                }
            }
        };

        var c3Options = baseChartOptions;

        $.extend(true, c3Options, defaultOptions);
        $.extend(true, c3Options, options);


        this._chart = c3.generate(c3Options);

        this.onAfterGenerateChart();
    },

    load: function(targets, args)
    {
        return this._chart.load(targets, args);
    },

    onAfterGenerateChart: function()
    {
        // White border for Y ticks' text

        $('.c3-axis-y .tick, .c3-axis-y2 .tick', $(this._chart.element)).each(function(tickKey, tick)
        {
            var $tickText = $('text', tick);

            var $clone = $tickText.clone();
            $clone.appendTo(tick);

            $tickText.css('stroke', '#ffffff');
            $tickText.css('stroke-width', 3);
        });
    },

    resize: function(size)
    {
        return this._chart.resize(size);
    }
}, {
    defaults: {

    }
});


/**
 * Class Craft.charts.getLocale
 */
Craft.charts.getLocale = function(options)
{
    var localeDefinition = window['d3_locale'];

    if(options)
    {
        $.extend(true, localeDefinition, options);
    }

    return d3.locale(localeDefinition);
}


/**
 * Class Craft.charts.getDateFormatFromScale
 */
Craft.charts.getDateFormatFromScale = function(scale)
{
    var locale = Craft.charts.getLocale();

    switch(scale)
    {
        case 'month':
            return locale.timeFormat("%B %Y");
            break;
        default:
            return locale.timeFormat("%e %b");
    }
}


/**
 * Class Craft.charts.getCurrencyFormat
 */
Craft.charts.getCurrencyFormat = function(format)
{
    var locale = Craft.charts.getLocale({
        currency: format
    });

    return locale.numberFormat("$");
}


/**
 * Class Craft.charts.defaults
 */
Craft.charts.defaults = {
    area: {
        transition: {
            duration: null
        },
        grid: {
            focus: {
                show: false
            },
            y: {
                show: true
            }
        },
        data: {
            type: 'area',
        },
        axis: {
            x: {
                type: 'timeseries',
                tick: {
                    culling: {
                        max: 5
                    },
                }
            },
            y: {
                tick: {
                    count: 3
                }
            }
        }
    }
};