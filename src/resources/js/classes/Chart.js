/**
 * Craft Charts
 */

Craft.charts = {};


/**
 * Class Craft.charts.BaseChart
 */
Craft.charts.BaseChart = Garnish.Base.extend(
{
    $container: null,
    $chart: null,

    margin: { top: 0, right: 0, bottom: 0, left: 0 },
    data: null,
    chartClass: 'chart',

    width: null,
    height: null,
    x: null,
    y: null,
    xAxis: null,
    yAxis: null,
    svg: null,

    xTickFormat: function(d) { var format = d3.time.format("%d/%m"); return format(d); },
    yTickFormat: function(d) { return "$" + d; },

    init: function(container)
    {
        this.$container = container;
        this.$chart = $('<div class="'+this.chartClass+'" />').appendTo(this.$container);

        d3.select(window).on('resize', $.proxy(function() {
            this.resize();
        }, this));

    },

    resize: function()
    {
        // only redraw if data is set

        if(this.data)
        {
            this.draw();
        }
    },
});


/**
 * Class Craft.charts.Column
 */
Craft.charts.Column = Craft.charts.BaseChart.extend(
{
    margin: { top: 0, right: 0, bottom: 30, left: 0 },
    chartClass: 'chart column',

    draw: function(data)
    {

        if(typeof(data) != 'undefined')
        {
            this.data = data;

            this.data.forEach(function(d) {
                d.date = d3.time.format("%d-%b-%y").parse(d.date);
                d.close = +d.close;
            });
        }

        this.$chart.html('');

        this.width = this.$chart.width() - this.margin.left - this.margin.right;
        this.height = this.$chart.height() - this.margin.top - this.margin.bottom;

        this.x = d3.scale.ordinal().rangeRoundBands([0, this.width], .05);
        this.y = d3.scale.linear().range([this.height, 0]);

        this.x.domain(this.data.map(function(d) { return d.date; }));
        this.y.domain([0, d3.max(this.data, function(d) { return d.close; })]);

        this.xAxis = d3.svg.axis()
            .scale(this.x)
            // .tickValues(this.x.domain().filter(function(d, i) { return !(i % 2); }))
            .orient("bottom")
            .tickFormat(this.xTickFormat);

        this.yAxis = d3.svg.axis()
            .scale(this.y)
            .orient("right")
            .tickFormat(this.yTickFormat)
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

        /* Initialize tooltip */
        tip = d3.tip().attr('class', 'd3-tip').html(function(d) { return d.close; });

        /* Invoke the tip in the context of your visualization */
        this.svg.call(tip)

        this.svg.selectAll(".bar")
                .data(this.data)
            .enter().append("rect")
                .attr("class", "bar")
                .attr("x", $.proxy(function(d) { return this.x(d.date); }, this))
                .attr("width", this.x.rangeBand())
                .attr("y", $.proxy(function(d) { return this.y(d.close); }, this))
                .attr("height", $.proxy(function(d) { return this.height - this.y(d.close); }, this))
                .on('mouseover', tip.show)
                .on('mouseout', tip.hide);

    }
});

/**
 * Class Craft.charts.Area
 */
Craft.charts.Area = Craft.charts.BaseChart.extend(
{
    margin: { top: 10, right: 0, bottom: 0, left: 0 },
    chartClass: 'chart area',

    enablePlots: true,

    xLines: false,
    yLines: true,

    draw: function(data)
    {
        if(typeof(data) != 'undefined')
        {
            this.data = data;

            this.data.forEach(function(d) {
                d.date = d3.time.format("%d-%b-%y").parse(d.date);
                d.close = +d.close;
            });
        }

        this.$chart.html('');

        this.width = this.$chart.width() - this.margin.left - this.margin.right;
        this.height = this.$chart.height() - this.margin.top - this.margin.bottom;

        this.x = d3.time.scale().range([0, this.width]);
        this.y = d3.scale.linear().range([this.height, 0]);

        this.xAxis = d3.svg.axis()
            .scale(this.x)
            .orient("top")
            .tickFormat(this.xTickFormat)
            .ticks(Math.max(this.width/150, 3));

        this.yAxis = d3.svg.axis()
            .scale(this.y)
            .orient("right")
            .tickFormat(this.yTickFormat)
            .ticks(this.height / 50);

        // area
        this.area = d3.svg.area()
            .x($.proxy(function(d) { return this.x(d.date); }, this))
            .y0(this.height)
            .y1($.proxy(function(d) { return this.y(d.close); }, this));

        // append graph to chart element
        this.svg = d3.select(this.$chart.get(0)).append("svg")
                .attr("width", this.width + (this.margin.left + this.margin.right))
                .attr("height", this.height + (this.margin.top + this.margin.bottom))
            .append("g")
                .attr("transform", "translate(" + this.margin.left + "," + this.margin.top + ")");

        this.x.domain(d3.extent(this.data, function(d) { return d.date; }));
        this.y.domain([0, d3.max(this.data, function(d) { return d.close; })]);


        /* Initialize tooltip */
        tip = d3.tip().attr('class', 'd3-tip').html(function(d) { return d.close; });

        /* Invoke the tip in the context of your visualization */
        this.svg.call(tip)

        // Draw chart
        this.svg.append("path")
            .datum(this.data)
            .attr("class", "area")
            .attr("d", this.area)
            .on('mouseover', tip.show)
            .on('mouseout', tip.hide);

        // Draw the X axis
        this.svg.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + this.height + ")")
            .call(this.xAxis);

        // Draw the Y axis
        this.svg.append("g")
            .attr("class", "y axis")
            .call(this.yAxis);


        // Plots

        if(this.enablePlots)
        {
            var div = d3.select("body").append("div")
                .attr("class", "tooltip")
                .style("opacity", 0);

            // Draw the plots
            this.svg.selectAll("dot")
                .data(this.data)
            .enter().append("circle")
                .attr("r", 5)
                .attr("cx", $.proxy(function(d) { return this.x(d.date); }, this))
                .attr("cy", $.proxy(function(d) { return this.y(d.close); }, this))
                .on("mouseover", function(d)
                {
                    d3.select(this).style("filter", "url(#drop-shadow)");

                    div.transition()
                        .duration(200)
                        .style("opacity", 1);
                    div.html(d.date + "<br/>"  + d.close)
                        .style("left", (d3.event.pageX) + "px")
                        .style("top", (d3.event.pageY - 28) + "px");
                    })
                .on("mouseout", function(d)
                {
                    d3.select(this).style("filter", "");

                    div.transition()
                        .duration(500)
                        .style("opacity", 0);
                });
        }


        // Lines

        if(this.xLines)
        {
            this.xLineAxis = d3.svg.axis()
                .scale(this.x)
                .orient("bottom");

            // draw x lines
            this.svg.append("g")
                .attr("class", "x line")
                .attr("transform", "translate(0," + this.height + ")")
                .call(this.xLineAxis
                    .tickSize(-this.height, 0, 0)
                    .tickFormat("")
                );
        }

        if(this.yLines)
        {

            this.yLineAxis = d3.svg.axis()
                .scale(this.y)
                .orient("left");

            // draw y lines
            this.svg.append("g")
                .attr("class", "y line")
                .call(this.yLineAxis
                    .tickSize(-this.width, 0, 0)
                    .tickFormat("")
                );
        }


        this.shadowFilter();
    },

    shadowFilter: function()
    {
        // filters go in defs element
        var defs = this.svg.append("defs");

        // create filter with id #drop-shadow
        // height=130% so that the shadow is not clipped
        var filter = defs.append("filter")
            .attr("id", "drop-shadow")
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
});
