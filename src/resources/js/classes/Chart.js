/**
 * Craft Charts
 */

Craft.charts = {};

/**
 * Class Craft.charts.DataTable
 */
Craft.charts.DataTable = Garnish.Base.extend(
{
    columns: null,
    rows: null,

    init: function(data)
    {
        // parse data

        this.columns = data.columns;
        this.rows = data.rows;

        this.rows.forEach($.proxy(function(d)
        {
            d[0].value = d3.time.format("%d-%b-%y").parse(d[0].value);
            d[1].value = +d[1].value;
        }, this));
    }
});

/**
 * Class Craft.charts.Tip
 */
Craft.charts.Tip = Garnish.Base.extend(
{
    $tip: null,

    init: function(settings)
    {
        this.setSettings(settings, Craft.charts.Tip.defaults);

        this.$tip = d3.select("body")
            .append("div")
            .attr("class", "tooltip")
            .style("opacity", 0);
    },

    tipContentFormat: function(d)
    {
        return this.settings.tipContentFormat(this.settings.locale, d);
    },

    show: function(d)
    {
        this.$tip.transition()
            .duration(200)
            .style("opacity", 1);
        this.$tip.html(this.tipContentFormat(d))
            .style("left", (d3.event.pageX) + "px")
            .style("top", (d3.event.pageY - 28) + "px");
    },

    hide: function()
    {
        this.$tip.transition()
            .duration(500)
            .style("opacity", 0);
    }
},
{
    defaults: {
        locale: null,
        tipContentFormat: function(locale, d)
        {
            var formatTime = locale.timeFormat("%x");
            var formatNumber = locale.numberFormat("n");

            return formatTime(d[0].value)
                        + '<br />'
                        + formatNumber(d[1].value);
        }
    }
});

/**
 * Class Craft.charts.BaseChart
 */
Craft.charts.BaseChart = Garnish.Base.extend(
{
    $container: null,
    $chart: null,

    margin: { top: 0, right: 0, bottom: 0, left: 0 },
    dataTable: null,
    chartClass: 'chart',

    width: null,
    height: null,
    x: null,
    y: null,
    xAxis: null,
    yAxis: null,
    svg: null,

    dataFormat: "%d-%b-%y",

    locale: null,

    init: function(container)
    {
        this.$container = container;

        this.$chart = $('<div class="'+this.chartClass+'" />').appendTo(this.$container);

        d3.select(window).on('resize', $.proxy(function() {
            this.resize();
        }, this));

    },

    draw: function(dataTable, settings)
    {
        console.log('Craft.charts.BaseChart.draw()', dataTable, settings);

        var localeDefinition = window['d3_locale'];

        // set settings
        this.setSettings(settings, Craft.charts.Area.defaults);

        // locale & currency

        if(this.settings.currency)
        {
            localeDefinition.currency = this.settings.currency;
        }

        this.locale = d3.locale(localeDefinition);

        // reset chart element's HTML
        this.$chart.html('');

        // set data table
        this.dataTable = dataTable;

        // chart dimensions
        this.width = this.$chart.width() - this.margin.left - this.margin.right;
        this.height = this.$chart.height() - this.margin.top - this.margin.bottom;
    },

    xTickFormat: function(locale)
    {
        return locale.timeFormat("%x");
    },

    yTickFormat: function(locale)
    {
        switch(this.dataTable.columns[1].dataType)
        {
            case 'currency':
                return locale.numberFormat("$");
                break;

            default:
                return locale.numberFormat("n");
        }

    },

    resize: function()
    {
        // only redraw if data is set
        this.draw(this.dataTable, this.settings);
    },
});

/**
 * Class Craft.charts.Area
 */
Craft.charts.Area = Craft.charts.BaseChart.extend(
{
    tip: null,

    margin: { top: 10, right: 0, bottom: 0, left: 0 },
    chartClass: 'chart area',

    xTicks: function()
    {
        return 3;
    },

    yTicks: function()
    {
        return 2;
    },

    yTickValues: function()
    {
        return [this.yAxisMaxValue() / 2, this.yAxisMaxValue()];
    },

    yAxisMaxValue: function()
    {
        var maxValue = d3.max(this.dataTable.rows, function(d) { return d[1].value; });
        return Math.ceil(maxValue / Math.pow(10, maxValue.toString().length -1)) * Math.pow(10, maxValue.toString().length - 1);
    },

    xDomain: function()
    {
        return d3.extent(this.dataTable.rows, function(d) { return d[0].value; });
    },

    yDomain: function()
    {
        var yDomainMax = $.proxy(function()
        {
            // make y max higher than max point
            return this.yAxisMaxValue();

        }, this);

        return [0, yDomainMax()];
    },

    draw: function(dataTable, settings)
    {
        this.base(dataTable, settings);

        // x & y
        this.x = d3.time.scale().range([0, this.width]);
        this.y = d3.scale.linear().range([this.height, 0]);


        // X axis
        this.xAxis = d3.svg.axis()
            .scale(this.x)
            .orient("top")
            .tickFormat(this.xTickFormat(this.locale))
            .ticks(this.xTicks());


        // Y axis
        this.yAxis = d3.svg.axis()
            .scale(this.y)
            .orient("right")
            .tickFormat(this.yTickFormat(this.locale))
            .tickValues(this.yTickValues())
            .ticks(this.yTicks());

        // Area
        this.area = d3.svg.area()
            .x($.proxy(function(d) { return this.x(d[0].value); }, this))
            .y0(this.height)
            .y1($.proxy(function(d) { return this.y(d[1].value); }, this));

        this.line = d3.svg.line()
            .x($.proxy(function(d) { return this.x(d[0].value); }, this))
            .y($.proxy(function(d) { return this.y(d[1].value); }, this));

        // Append graph to chart element
        this.svg = d3.select(this.$chart.get(0)).append("svg")
                .attr("width", this.width + (this.margin.left + this.margin.right))
                .attr("height", this.height + (this.margin.top + this.margin.bottom))
            .append("g")
                .attr("transform", "translate(" + this.margin.left + "," + this.margin.top + ")");

        this.x.domain(this.xDomain());
        this.y.domain(this.yDomain());


        // Draw chart
        this.svg.append("path")
            .datum(this.dataTable.rows)
            .attr("class", "area")
            .attr("d", this.area);

        // Draw the X axis
        this.svg.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + this.height + ")")
            .call(this.xAxis);

        // Draw the Y axis
        this.svg.append("g")
            .attr("class", "y axis")
            .call(this.yAxis);

        // Draw lines
        this.drawLegendLines();

        // Draw plots
        this.drawPlots();

        // Draw tip triggers
        this.drawTipTriggers();

        this.svg.append("path")
            .datum(this.dataTable.rows)
            .attr("class", "line")
            .attr("d", this.line);
    },

    drawPlots: function()
    {
        if(this.settings.enablePlots)
        {
            // Draw the plots
            this.svg.selectAll("dot")
                .data(this.dataTable.rows)
            .enter().append("circle")
                .attr("class", "plot")
                .attr("r", 5)
                .attr("cx", $.proxy(function(d) { return this.x(d[0].value); }, this))
                .attr("cy", $.proxy(function(d) { return this.y(d[1].value); }, this));
        }
    },

    drawTipTriggers: function()
    {
        if(this.settings.enableTips)
        {
            // Draw the plots
            this.svg.selectAll("dot")
                .data(this.dataTable.rows)
            .enter().append("circle")
                .attr("class", "tip-trigger")
                .attr("r", 10)
                .attr("cx", $.proxy(function(d) { return this.x(d[0].value); }, this))
                .attr("cy", $.proxy(function(d) { return this.y(d[1].value); }, this));

            // Instantiate tip

            if(!this.tip)
            {
                this.tip = new Craft.charts.Tip({
                    locale: this.locale,
                    tipContentFormat: $.proxy(this, 'tipContentFormat')
                });
            }

            var tip = this.tip;

            // Show tip when hovering tip trigger
            this.svg.selectAll("circle")
                .on("mouseover", function(d)
                {
                    d3.select(this).style("filter", "url(#drop-shadow)"); // show #drop-shadow filter
                    tip.show(d);
                })
                .on("mouseout", function()
                {
                    d3.select(this).style("filter", ""); // hide filter
                    tip.hide();
                });
        }

        // Apply shadow filter
        Craft.charts.utils.applyShadowFilter('drop-shadow', this.svg);
    },

    tipContentFormat: function(locale, d)
    {
        var formatTime = locale.timeFormat("%x");


        switch(this.dataTable.columns[1].dataType)
        {
            case 'currency':
                var formatNumber = locale.numberFormat("$");
                break;

            default:
                var formatNumber = locale.numberFormat("n");
        }

        return formatTime(d[0].value)
                    + '<br />'
                    + this.dataTable.columns[1].label+': '
                    + formatNumber(d[1].value);
    },

    drawLegendLines: function()
    {
        if(this.settings.enableXLines)
        {
            this.xLineAxis = d3.svg.axis()
                .scale(this.x)
                .orient("bottom");

            // draw x lines
            this.svg.append("g")
                .attr("class", "x legend-line")
                .attr("transform", "translate(0," + this.height + ")")
                .call(this.xLineAxis
                    .tickSize(-this.height, 0, 0)
                    .tickFormat("")
                );
        }

        if(this.settings.enableYLines)
        {
            this.yLineAxis = d3.svg.axis()
                .scale(this.y)
                .orient("left");

            // draw y lines
            this.svg.append("g")
                .attr("class", "y legend-line")
                .call(this.yLineAxis
                    .tickSize(-this.width, 0, 0)
                    .tickFormat("")
                    .tickValues(this.yTickValues())
                    .ticks(this.yTicks())
                );
        }
    },
},
{
    defaults: {
        enablePlots: true,
        enableXLines: false,
        enableYLines: true,
        enableTips: true,
    }
});



/**
 * Class Craft.charts.Pie
 */
Craft.charts.Pie = Craft.charts.BaseChart.extend(
{
    width: null,
    height: null,
    radius: null,

    draw: function(dataTable, settings)
    {
        this.base(dataTable, settings);

        svg = d3.select(this.$chart.get(0)).append("svg").append("g");

        svg.append("g").attr("class", "slices");
        // svg.append("g").attr("class", "labels");

        this.width = this.$chart.width() - this.margin.left - this.margin.right;
        this.height = this.$chart.height() - this.margin.top - this.margin.bottom;
        this.radius = Math.min(this.width, this.height) / 2;

        var pie = d3.layout.pie()
            .sort(null)
            .value(function(d) {
                return d.value;
            });

        var arc = d3.svg.arc().outerRadius(this.radius * 0.8).innerRadius(this.radius * 0.4);

        var outerArc = d3.svg.arc().innerRadius(this.radius * 0.9).outerRadius(this.radius * 0.9);

        svg.attr("transform", "translate(" + this.width / 2 + "," + this.height / 2 + ")");

        var key = function(d){ return d.data.label; };

        var color = d3.scale.ordinal()
            .domain(["Lorem ipsum", "dolor sit", "amet", "consectetur", "adipisicing", "elit", "sed", "do", "eiusmod", "tempor", "incididunt"])
            .range(["#98abc5", "#8a89a6", "#7b6888", "#6b486b", "#a05d56", "#d0743c", "#ff8c00"]);


        change(randomData());

        function randomData()
        {
            var labels = color.domain();
            return labels.map(function(label){
                return { label: label, value: Math.random() }
            });
        }

        function change(data)
        {

            /* ------- PIE SLICES -------*/
            var slice = svg.select(".slices").selectAll("path.slice")
                .data(pie(data), key);

            slice.enter()
                .insert("path")
                .style("fill", function(d) { return color(d.data.label); })
                .attr("class", "slice");

            slice
                .transition().duration(1000)
                .attrTween("d", function(d) {
                    this._current = this._current || d;
                    var interpolate = d3.interpolate(this._current, d);
                    this._current = interpolate(0);
                    return function(t) {
                        return arc(interpolate(t));
                    };
                })

            slice.exit()
                .remove();
        };

    }
});


/**
 * Class Craft.charts.Column
 */
Craft.charts.Column = Craft.charts.BaseChart.extend(
{
    tip: null,

    currency: null,

    margin: { top: 0, right: 0, bottom: 30, left: 0 },
    chartClass: 'chart column',

    enableTips: true,

    draw: function(dataTable, settings)
    {
        this.base(dataTable, settings);

        this.x = d3.scale.ordinal().rangeRoundBands([0, this.width], .05);
        this.y = d3.scale.linear().range([this.height, 0]);

        this.x.domain(this.dataTable.rows.map(function(d) { return d[0].value; }));
        this.y.domain([0, d3.max(this.dataTable.rows, function(d) { return d[1].value; })]);

        this.xAxis = d3.svg.axis()
            .scale(this.x)
            // .tickValues(this.x.domain().filter(function(d, i) { return !(i % 2); }))
            .orient("bottom")
            .tickFormat(this.xTickFormat(this.locale));

        this.yAxis = d3.svg.axis()
            .scale(this.y)
            .orient("right")
            .tickFormat(this.yTickFormat(this.locale))
            .ticks(this.height / 50);

        this.svg = d3.select(this.$chart.get(0)).append("svg")
                .attr("width", this.width + this.margin.left + this.margin.right)
                .attr("height", this.height + this.margin.top + this.margin.bottom)
            .append("g")
                .attr("transform", "translate(" + this.margin.left + "," + this.margin.top + ")");

        this.svg.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + this.height + ")")
            .call(this.xAxis);

        this.svg.append("g")
                .attr("class", "y axis")
                .call(this.yAxis)
            .append("text")
                .attr("transform", "rotate(-90)")
                .attr("y", 6)
                .attr("dy", ".71em");

        this.svg.selectAll(".bar")
                .data(this.dataTable.rows)
            .enter().append("rect")
                .attr("class", "bar")
                .attr("x", $.proxy(function(d) { return this.x(d[0].value); }, this))
                .attr("width", this.x.rangeBand())
                .attr("y", $.proxy(function(d) { return this.y(d[1].value); }, this))
                .attr("height", $.proxy(function(d) { return this.height - this.y(d[1].value); }, this));

        if(this.enableTips)
        {
            if(!this.tip)
            {
                this.tip = new Craft.charts.Tip({
                    locale: this.locale,
                });
            }

            // Show tip when hovering plots
            this.svg.selectAll(".bar")
                .on("mouseover", $.proxy(this.tip, 'show'))
                .on("mouseout", $.proxy(this.tip, 'hide'));
        }
    }
});

/**
 * Class Craft.charts.Utils
 */
Craft.charts.utils = {
    /**
     * arrayToDataTable
     */
    arrayToDataTable: function(twoDArray)
    {

        var data = {
            columns: [],
            rows: []
        };

        $.each(twoDArray, function(k, v) {
            if(k == 0)
            {
                data.columns = v;
            }
            else
            {
                data.rows.push(v);
            }
        });

        var dataTable = new Craft.charts.DataTable(data);

        return dataTable;
    },

    applyShadowFilter: function(id, svg)
    {
        // filters go in defs element
        var defs = svg.append("defs");

        // create filter with id #{id}
        // height=130% so that the shadow is not clipped
        var filter = defs.append("filter")
            .attr("id", id)
            .attr("width", "200%")
            .attr("height", "200%")
            .attr("x", "-50%")
            .attr("y", "-50%");

        // SourceAlpha refers to opacity of graphic that this filter will be applied to
        // convolve that with a Gaussian with standard deviation 3 and store result
        // in blur
        filter.append("feGaussianBlur")
            .attr("in", "SourceAlpha")
            .attr("stdDeviation", 1)
            .attr("result", "blur");

        // translate output of Gaussian blur to the right and downwards with 2px
        // store result in offsetBlur
        filter.append("feOffset")
            .attr("in", "blur")
            .attr("dx", 0)
            .attr("dy", 0)
            .attr("result", "offsetBlur");

        // overlay original SourceGraphic over translated blurred opacity by using
        // feMerge filter. Order of specifying inputs is important!
        var feMerge = filter.append("feMerge");

        feMerge.append("feMergeNode")
            .attr("in", "offsetBlur")
        feMerge.append("feMergeNode")
            .attr("in", "SourceGraphic");
    }
};