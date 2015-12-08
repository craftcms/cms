// Craft charts

Craft.charts = {};

Craft.charts.Area = Garnish.Base.extend(
{
    chart: null,
    graph: null,
    data: null,

    margin: 30,
    width: null,
    height: null,

    x: { axis: null, scale: null },
    y: { axis: null, scale: null },

    $chart: null,

    init: function(chart, params, data)
    {
        this.$chart = d3.select(chart);

        this.width = parseInt(this.$chart.style("width")) - this.margin * 2,
        this.height = parseInt(this.$chart.style("height")) - this.margin * 2;

        this.initChart();

        this.loadData(data);

        d3.select(window).on('resize', $.proxy(function() {
            this.resize();
        }, this));

        setTimeout($.proxy(function() {
            this.resize();
        }, this), 100);
    },

    initChart: function()
    {
        this.initScale();
        this.initAxis();

        // area
        this.chart = d3.svg.area()
            .x($.proxy(function(d) { return this.x.scale(d.date); }, this))
            .y0(this.height)
            .y1($.proxy(function(d) { return this.y.scale(d.close); }, this));

        // append graph to chart element
        this.graph = this.$chart
                .attr("width", this.width + this.margin * 2)
                .attr("height", this.height + this.margin * 2)
            .append("g")
                .attr("transform", "translate(" + this.margin + "," + this.margin + ")");
    },

    loadData: function(data)
    {
        this.data = data;

        // format data

        this.data.forEach(function(d) {
            d.date = d3.time.format("%d-%b-%y").parse(d.date);
            d.close = +d.close;
        });

        this.render();
    },

    render: function()
    {
        // draw chart

        this.x.scale.domain(d3.extent(this.data, function(d) { return d.date; }));
        this.y.scale.domain([0, d3.max(this.data, function(d) { return d.close; })]);

        this.graph.append("path")
            .datum(this.data)
            .attr("class", "area")
            .attr("d", this.chart);

        this.graph.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + this.height + ")")
            .call(this.x.axis);

        this.graph.append("g")
                .attr("class", "y axis")
                .call(this.y.axis);
    },

    initScale: function()
    {
        this.x.scale = d3.time.scale()
            .range([0, this.width]);

        this.y.scale = d3.scale.linear()
            .range([this.height, 0]);
    },

    initAxis: function()
    {
        this.x.axis = d3.svg.axis()
            .scale(this.x.scale)
            .orient("bottom").tickFormat(d3.time.format("%d/%m"));

        this.y.axis = d3.svg.axis()
            .scale(this.y.scale)
            .orient("right");
    },

    resize: function()
    {
        this.width = parseInt(this.$chart.style("width")) - this.margin * 2,
        this.height = parseInt(this.$chart.style("height")) - this.margin * 2;


        // ticks
        this.x.axis.ticks(Math.max(this.width/150, 3));
        this.y.axis.ticks(this.height / 50);

        // Update the range of the scale with new width/height
        this.x.scale.range([0, this.width]);
        this.y.scale.range([this.height, 0]);

        // Update the axis with the new scale
        this.graph.select('.x.axis')
            .attr("transform", "translate(0," + this.height + ")")
            .call(this.x.axis);

        this.graph.select('.y.axis')
            .call(this.y.axis);

        // Force D3 to recalculate and update the area
        this.graph.selectAll('.area')
            .attr("d", this.chart);
    }
});