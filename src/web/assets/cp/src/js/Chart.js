/** global: Craft */
/** global: Garnish */
/**
 * Craft Charts
 */

Craft.charts = {};

// ---------------------------------------------------------------------

/**
 * Class Craft.charts.DataTable
 */
Craft.charts.DataTable = Garnish.Base.extend(
    {
        columns: null,
        rows: null,

        init: function(data) {
            columns = data.columns;
            rows = data.rows;

            rows.forEach($.proxy(function(d) {
                $.each(d, function(cellIndex) {
                    var column = columns[cellIndex];

                    var parseTime;

                    switch (column.type) {
                        case 'date':
                            parseTime = d3.timeParse("%Y-%m-%d");
                            d[cellIndex] = parseTime(d[cellIndex]);
                            break;

                        case 'datetime':
                            parseTime = d3.timeParse("%Y-%m-%d %H:00:00");
                            d[cellIndex] = parseTime(d[cellIndex]);
                            break;

                        case 'percent':
                            d[cellIndex] = d[cellIndex] / 100;
                            break;

                        case 'number':
                            d[cellIndex] = +d[cellIndex];
                            break;

                        default:
                        // do nothing
                    }
                });

            }, this));

            this.columns = columns;
            this.rows = rows;
        }
    });

// ---------------------------------------------------------------------

/**
 * Class Craft.charts.Tip
 */

Craft.charts.Tip = Garnish.Base.extend(
    {
        $container: null,
        $tip: null,

        init: function($container) {
            this.$container = $container;

            this.$tip = $('<div class="tooltip"></div>').appendTo(this.$container);

            this.hide();
        },

        setContent: function(html) {
            this.$tip.html(html);
        },

        setPosition: function(position) {
            this.$tip.css("left", position.left + "px");
            this.$tip.css("top", position.top + "px");
        },

        show: function() {
            this.$tip.css("display", 'block');
        },

        hide: function() {
            this.$tip.css("display", 'none');
        }
    });

// ---------------------------------------------------------------------

/**
 * Class Craft.charts.BaseChart
 */
Craft.charts.BaseChart = Garnish.Base.extend(
    {
        $container: null,
        $chart: null,

        chartBaseClass: 'cp-chart',
        dataTable: null,

        formatLocale: null,
        timeFormatLocale: null,
        orientation: null,

        svg: null,
        width: null,
        height: null,

        init: function(container, settings) {
            this.$container = container;

            this.setSettings(Craft.charts.BaseChart.defaults);
            this.setSettings(settings);

            var globalSettings = {
                formats: window.d3Formats,
                formatLocaleDefinition: window.d3FormatLocaleDefinition,
                timeFormatLocaleDefinition: window.d3TimeFormatLocaleDefinition
            };

            this.setSettings(globalSettings);

            d3.select(window).on('resize', $.proxy(function() {
                this.resize();
            }, this));
        },

        setSettings: function(settings, defaults) {
            var baseSettings = (typeof this.settings === 'undefined' ? {} : this.settings);
            this.settings = $.extend(true, {}, baseSettings, defaults, settings);
        },

        draw: function(dataTable, settings) {
            // Settings and chart attributes

            this.setSettings(settings);

            this.dataTable = dataTable;
            this.formatLocale = d3.formatLocale(this.settings.formatLocaleDefinition);
            this.timeFormatLocale = d3.timeFormatLocale(this.settings.timeFormatLocaleDefinition);
            this.orientation = this.settings.orientation;


            // Set (or reset) the chart element

            if (this.$chart) {
                this.$chart.remove();
            }

            var className = this.chartBaseClass;

            if (this.settings.chartClass) {
                className += ' ' + this.settings.chartClass;
            }

            this.$chart = $('<div class="' + className + '" />').appendTo(this.$container);
        },

        resize: function() {
            this.draw(this.dataTable, this.settings);
        },

        onAfterDrawTicks: function() {
            // White border for ticks' text
            $('.tick', this.$chart).each(function(tickKey, tick) {
                var $tickText = $('text', tick);

                var $clone = $tickText.clone();
                $clone.appendTo(tick);

                $tickText.attr('stroke', '#ffffff');
                $tickText.attr('stroke-width', 3);
            });
        }
    },
    {
        defaults: {
            formatLocaleDefinition: null,
            timeFormatLocaleDefinition: null,
            formats: {
                numberFormat: ',.2f',
                percentFormat: ',.2%',
                currencyFormat: '$,.2f',
                shortDateFormats: {
                    day: "%-m/%-d",
                    month: "%-m/%y",
                    year: "%Y"
                }
            },
            margin: {top: 0, right: 0, bottom: 0, left: 0},
            chartClass: null,
            colors: ["#0594D1", "#DE3800", "#FF9A00", "#009802", "#9B009B"]
        }
    });

// ---------------------------------------------------------------------

/**
 * Class Craft.charts.Area
 */
Craft.charts.Area = Craft.charts.BaseChart.extend(
    {
        tip: null,
        drawingArea: null,

        init: function(container, settings) {
            this.base(container, Craft.charts.Area.defaults);

            this.setSettings(settings);
        },

        draw: function(dataTable, settings) {

            this.base(dataTable, settings);

            if (this.tip) {
                this.tip = null;
            }

            var margin = this.getChartMargin();

            this.width = this.$chart.width() - margin.left - margin.right;
            this.height = this.$chart.height() - margin.top - margin.bottom;


            // Append SVG to chart element

            var svg = {
                width: this.width + (margin.left + margin.right),
                height: this.height + (margin.top + margin.bottom),
                translateX: (this.orientation !== 'rtl' ? (margin.left) : (margin.right)),
                translateY: margin.top
            };

            this.svg = d3.select(this.$chart.get(0)).append("svg")
                .attr("width", svg.width)
                .attr("height", svg.height);

            this.drawingArea = this.svg.append("g")
                .attr("transform", "translate(" + svg.translateX + "," + svg.translateY + ")");


            // Draw elements

            this.drawTicks();
            this.drawAxes();
            this.drawChart();
            this.drawTipTriggers();
        },

        drawTicks: function() {
            // Draw X ticks

            var x = this.getX(true);
            var xTicks = 3;
            var xAxis = d3.axisBottom(x)
                .tickFormat(this.getXFormatter())
                .ticks(xTicks);

            this.drawingArea.append("g")
                .attr("class", "x ticks-axis")
                .attr("transform", "translate(0, " + this.height + ")")
                .call(xAxis);


            // Draw Y ticks

            var y = this.getY();
            var yTicks = 2;
            var yAxis;

            if (this.orientation !== 'rtl') {
                yAxis = d3.axisLeft(y)
                    .tickFormat(this.getYFormatter())
                    .tickValues(this.getYTickValues())
                    .ticks(yTicks);

                this.drawingArea.append("g")
                    .attr("class", "y ticks-axis")
                    .call(yAxis);
            } else {
                yAxis = d3.axisRight(y)
                    .tickFormat(this.getYFormatter())
                    .tickValues(this.getYTickValues())
                    .ticks(yTicks);

                this.drawingArea.append("g")
                    .attr("class", "y ticks-axis")
                    .attr("transform", "translate(" + this.width + ",0)")
                    .call(yAxis);
            }


            // On after draw ticks

            this.onAfterDrawTicks();
        },

        drawAxes: function() {
            if (this.settings.xAxis.showAxis) {
                var x = this.getX();
                var xAxis = d3.axisBottom(x).ticks(0).tickSizeOuter(0);
                this.drawingArea.append("g")
                    .attr("class", "x axis")
                    .attr("transform", "translate(0, " + this.height + ")")
                    .call(xAxis);
            }

            if (this.settings.yAxis.showAxis) {
                var y = this.getY();
                var chartPadding = 0;
                var yAxis;

                if (this.orientation === 'rtl') {
                    yAxis = d3.axisLeft(y).ticks(0);
                    this.drawingArea.append("g")
                        .attr("class", "y axis")
                        .attr("transform", "translate(" + (this.width - chartPadding) + ", 0)")
                        .call(yAxis);
                } else {
                    yAxis = d3.axisRight(y).ticks(0);
                    this.drawingArea.append("g")
                        .attr("class", "y axis")
                        .attr("transform", "translate(" + chartPadding + ", 0)")
                        .call(yAxis);
                }
            }
        },

        drawChart: function() {
            var x = this.getX(true);
            var y = this.getY();


            // X & Y grid lines

            if (this.settings.xAxis.gridlines) {
                var xLineAxis = d3.axisBottom(x);

                this.drawingArea.append("g")
                    .attr("class", "x grid-line")
                    .attr("transform", "translate(0," + this.height + ")")
                    .call(xLineAxis
                        .tickSize(-this.height, 0, 0)
                        .tickFormat("")
                    );
            }

            var yTicks = 2;

            if (this.settings.yAxis.gridlines) {
                var yLineAxis = d3.axisLeft(y);

                this.drawingArea.append("g")
                    .attr("class", "y grid-line")
                    .attr("transform", "translate(0 , 0)")
                    .call(yLineAxis
                        .tickSize(-(this.width), 0)
                        .tickFormat("")
                        .tickValues(this.getYTickValues())
                        .ticks(yTicks)
                    );
            }

            // Line

            var line = d3.line()
                .x(function(d) {
                    return x(d[0]);
                })
                .y(function(d) {
                    return y(d[1]);
                });

            this.drawingArea
                .append("g")
                .attr("class", "chart-line")
                .append("path")
                .datum(this.dataTable.rows)
                .style('fill', 'none')
                .style('stroke', this.settings.colors[0])
                .style('stroke-width', '3px')
                .attr("d", line);


            // Area

            var area = d3.area()
                .x(function(d) {
                    return x(d[0]);
                })
                .y0(this.height)
                .y1(function(d) {
                    return y(d[1]);
                });

            this.drawingArea
                .append("g")
                .attr("class", "chart-area")
                .append("path")
                .datum(this.dataTable.rows)
                .style('fill', this.settings.colors[0])
                .style('fill-opacity', '0.3')
                .attr("d", area);


            // Plots

            if (this.settings.plots) {
                this.drawingArea.append('g')
                    .attr("class", "plots")
                    .selectAll("circle")
                    .data(this.dataTable.rows)
                    .enter()
                    .append("circle")
                    .style('fill', this.settings.colors[0])
                    .attr("class", $.proxy(function(d, index) {
                        return 'plot plot-' + index;
                    }, this))
                    .attr("r", 4)
                    .attr("cx", $.proxy(function(d) {
                        return x(d[0]);
                    }, this))
                    .attr("cy", $.proxy(function(d) {
                        return y(d[1]);
                    }, this));
            }
        },

        drawTipTriggers: function() {
            if (this.settings.tips) {
                if (!this.tip) {
                    this.tip = new Craft.charts.Tip(this.$chart);
                }


                // Define xAxisTickInterval

                var chartMargin = this.getChartMargin();
                var tickSizeOuter = 6;
                var length = this.drawingArea.select('.x path.domain').node().getTotalLength() - chartMargin.left - chartMargin.right - tickSizeOuter * 2;
                var xAxisTickInterval = length / (this.dataTable.rows.length - 1);


                // Tip trigger width

                var tipTriggerWidth = Math.max(0, xAxisTickInterval);


                // Draw triggers

                var x = this.getX(true);
                var y = this.getY();

                this.drawingArea.append('g')
                    .attr("class", "tip-triggers")
                    .selectAll("rect")
                    .data(this.dataTable.rows)
                    .enter().append("rect")
                    .attr("class", "tip-trigger")
                    .style('fill', 'transparent')
                    .style('fill-opacity', '1')
                    .attr("width", tipTriggerWidth)
                    .attr("height", this.height)
                    .attr("x", $.proxy(function(d) {
                        return x(d[0]) - tipTriggerWidth / 2;
                    }, this))
                    .on("mouseover", $.proxy(function(d, index) {
                        // Expand plot

                        this.drawingArea.select('.plot-' + index).attr("r", 5);


                        // Set tip content

                        var $content = $('<div />');
                        var $xValue = $('<div class="x-value" />').appendTo($content);
                        var $yValue = $('<div class="y-value" />').appendTo($content);

                        $xValue.html(this.getXFormatter()(d[0]));
                        $yValue.html(this.getYFormatter()(d[1]));

                        var content = $content.get(0);

                        this.tip.setContent(content);


                        // Set tip position

                        var margin = this.getChartMargin();

                        var offset = 24;
                        var top = (y(d[1]) + offset);
                        var left;

                        if (this.orientation !== 'rtl') {
                            left = (x(d[0]) + margin.left + offset);

                            var calcLeft = (this.$chart.offset().left + left + this.tip.$tip.width());
                            var maxLeft = this.$chart.offset().left + this.$chart.width() - offset;

                            if (calcLeft > maxLeft) {
                                left = x(d[0]) - (this.tip.$tip.width() + offset);
                            }
                        } else {
                            left = (x(d[0]) - (this.tip.$tip.width() + margin.left + offset));
                        }

                        if (left < 0) {
                            left = (x(d[0]) + margin.left + offset);
                        }

                        var position = {
                            top: top,
                            left: left
                        };

                        this.tip.setPosition(position);


                        // Show tip

                        this.tip.show();

                    }, this))
                    .on("mouseout", $.proxy(function(d, index) {
                        // Unexpand Plot
                        this.drawingArea.select('.plot-' + index).attr("r", 4);

                        // Hide tip
                        this.tip.hide();
                    }, this));
            }
        },

        getChartMargin: function() {
            var margin = this.settings.margin;


            // Estimate the max width of y ticks and set it as the left margin

            var values = this.getYTickValues();
            var yTicksMaxWidth = 0;

            $.each(values, $.proxy(function(key, value) {
                var characterWidth = 8;

                var formatter = this.getYFormatter();

                var formattedValue = formatter(value);
                var computedTickWidth = formattedValue.length * characterWidth;

                if (computedTickWidth > yTicksMaxWidth) {
                    yTicksMaxWidth = computedTickWidth;
                }
            }, this));

            yTicksMaxWidth += 10;

            margin.left = yTicksMaxWidth;

            return margin;
        },

        getX: function(padded) {
            var xDomainMin = d3.min(this.dataTable.rows, function(d) {
                return d[0];
            });

            var xDomainMax = d3.max(this.dataTable.rows, function(d) {
                return d[0];
            });

            var xDomain = [xDomainMin, xDomainMax];

            if (this.orientation === 'rtl') {
                xDomain = [xDomainMax, xDomainMin];
            }

            var left = 0;
            var right = 0;

            if (padded) {
                left = 0;
                right = 0;
            }

            var x = d3.scaleTime().range([left, (this.width - right)]);

            x.domain(xDomain);

            return x;
        },

        getY: function() {
            var yDomain = [0, this.getYMaxValue()];

            var y = d3.scaleLinear().range([this.height, 0]);

            y.domain(yDomain);

            return y;
        },

        getXFormatter: function() {
            var formatter;

            if (this.settings.xAxis.formatter !== $.noop) {
                formatter = this.settings.xAxis.formatter(this);
            } else {
                formatter = Craft.charts.utils.getTimeFormatter(this.timeFormatLocale, this.settings);
            }

            return formatter;
        },

        getYFormatter: function() {
            var formatter;

            if (this.settings.yAxis.formatter !== $.noop) {
                formatter = this.settings.yAxis.formatter(this);
            } else {
                formatter = Craft.charts.utils.getNumberFormatter(this.formatLocale, this.dataTable.columns[1].type, this.settings);
            }

            return formatter;
        },

        getYMaxValue: function() {
            return d3.max(this.dataTable.rows, function(d) {
                return d[1];
            });
        },

        getYTickValues: function() {
            var maxValue = this.getYMaxValue();

            if (maxValue > 1) {
                return [(maxValue / 2), maxValue];
            } else {
                return [0, maxValue];
            }
        }
    },
    {
        defaults: {
            chartClass: 'area',
            margin: {top: 25, right: 5, bottom: 25, left: 0},
            plots: true,
            tips: true,
            xAxis: {
                gridlines: false,
                showAxis: true,
                formatter: $.noop
            },
            yAxis: {
                gridlines: true,
                showAxis: false,
                formatter: $.noop
            }
        }
    });

// ---------------------------------------------------------------------

/**
 * Class Craft.charts.Utils
 */
Craft.charts.utils = {

    getDuration: function(seconds) {
        var secondsNum = parseInt(seconds, 10);

        var duration = {
            hours: (Math.floor(secondsNum / 3600)),
            minutes: (Math.floor((secondsNum - (duration.hours * 3600)) / 60)),
            seconds: (secondsNum - (duration.hours * 3600) - (duration.minutes * 60))
        };

        if (duration.hours < 10) {
            duration.hours = "0" + duration.hours;
        }

        if (duration.minutes < 10) {
            duration.minutes = "0" + duration.minutes;
        }

        if (duration.seconds < 10) {
            duration.seconds = "0" + duration.seconds;
        }

        return duration.hours + ':' + duration.minutes + ':' + duration.seconds;
    },

    getTimeFormatter: function(timeFormatLocale, chartSettings) {
        switch (chartSettings.dataScale) {
            case 'year':
                return timeFormatLocale.format('%Y');

            case 'month':
                return timeFormatLocale.format(chartSettings.formats.shortDateFormats.month);

            case 'hour':
                return timeFormatLocale.format(chartSettings.formats.shortDateFormats.day + " %H:00:00");

            default:
                return timeFormatLocale.format(chartSettings.formats.shortDateFormats.day);
        }
    },

    getNumberFormatter: function(formatLocale, type, chartSettings) {
        switch (type) {
            case 'currency':
                return formatLocale.format(chartSettings.formats.currencyFormat);

            case 'percent':
                return formatLocale.format(chartSettings.formats.percentFormat);

            case 'time':
                return Craft.charts.utils.getDuration;

            case 'number':
                return formatLocale.format(chartSettings.formats.numberFormat);
        }
    }
};
