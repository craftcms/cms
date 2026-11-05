import * as d3 from 'd3';
import {Base, isPlainObject} from '@craftcms/garnish';
import {contentSize, resolveElement, type ElementArg} from '@/common/utils/dom';

/** A deep merge with the semantics of jQuery's `$.extend(true, …)`. */
function deepExtend(target: any, ...sources: any[]): any {
  for (const source of sources) {
    if (source == null) {
      continue;
    }

    for (const [key, value] of Object.entries(source)) {
      if (value === undefined || value === target) {
        continue;
      }

      if (Array.isArray(value)) {
        const base = Array.isArray(target[key]) ? target[key] : [];
        target[key] = deepExtend(base, value);
      } else if (isPlainObject(value)) {
        const base = isPlainObject(target[key]) ? target[key] : {};
        target[key] = deepExtend(base, value);
      } else {
        target[key] = value;
      }
    }
  }

  return target;
}

declare global {
  interface Window {
    d3Formats?: Record<string, string>;
    d3FormatLocaleDefinition?: Parameters<typeof d3.formatLocale>[0];
    d3TimeFormatLocaleDefinition?: Parameters<typeof d3.timeFormatLocale>[0];
  }
}

/**
 * Craft.charts.DataTable — parses a `{columns, rows}` payload, coercing each
 * cell by its column type (dates via `d3.timeParse`, percents, numbers).
 */
export class DataTable extends Base {
  columns: any = null;
  rows: any = null;

  constructor(data?: any) {
    super();
    if (new.target === DataTable) {
      this.init(data);
    }
  }

  init(data: any): void {
    const columns = data.columns;
    const rows = data.rows;

    rows.forEach((d: any) => {
      d.forEach((_cell: any, cellIndex: number) => {
        const column = columns[cellIndex];
        let parseTime;

        switch (column.type) {
          case 'date':
            parseTime = d3.timeParse('%Y-%m-%d');
            d[cellIndex] = parseTime(d[cellIndex]);
            break;

          case 'datetime':
            parseTime = d3.timeParse('%Y-%m-%d %H:00:00');
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
    });

    this.columns = columns;
    this.rows = rows;
  }
}

/**
 * Craft.charts.Tip — a positioned tooltip element inside the chart container.
 */
export class Tip extends Base {
  container: HTMLElement | null = null;
  tip!: HTMLElement;

  constructor(container?: ElementArg) {
    super();
    if (new.target === Tip) {
      this.init(container);
    }
  }

  init(container: ElementArg): void {
    this.container = resolveElement(container);
    this.tip = document.createElement('div');
    this.tip.className = 'tooltip';
    this.container?.append(this.tip);
    this.hide();
  }

  setContent(content: string | Node): void {
    if (typeof content === 'string') {
      this.tip.innerHTML = content;
    } else {
      this.tip.replaceChildren(content);
    }
  }

  setPosition(position: {left: number; top: number}): void {
    this.tip.style.left = `${position.left}px`;
    this.tip.style.top = `${position.top}px`;
  }

  show(): void {
    this.tip.style.display = 'block';
  }

  hide(): void {
    this.tip.style.display = 'none';
  }
}

/**
 * Craft.charts.BaseChart — the shared chart lifecycle: settings (deep-merged),
 * a `<div>` chart element appended to the container, and window-resize redraw.
 */
export class BaseChart extends Base {
  static defaults: any = {
    formatLocaleDefinition: null,
    timeFormatLocaleDefinition: null,
    formats: {
      numberFormat: ',.2f',
      percentFormat: ',.2%',
      currencyFormat: '$,.2f',
      shortDateFormats: {
        day: '%-m/%-d',
        month: '%-m/%y',
        year: '%Y',
      },
    },
    margin: {top: 0, right: 0, bottom: 0, left: 0},
    chartClass: null,
    colors: ['#0594D1', '#DE3800', '#FF9A00', '#009802', '#9B009B'],
  };

  // Charts use a deeply-merged, loosely-typed settings bag (see setSettings);
  // loosen the inherited `Base` `settings: S | null` to `any`.
  declare settings: any;

  container: HTMLElement | null = null;
  chart: HTMLElement | null = null;

  chartBaseClass = 'cp-chart';
  dataTable: any = null;

  formatLocale: any = null;
  timeFormatLocale: any = null;
  orientation: any = null;

  svg: any = null;
  width: any = null;
  height: any = null;

  constructor(container?: any, settings?: any) {
    super();
    if (new.target === BaseChart) {
      this.init(container, settings);
    }
  }

  init(container: ElementArg, settings?: any): void {
    this.container = resolveElement(container);

    this.setSettings(BaseChart.defaults);
    this.setSettings(settings);

    const globalSettings = {
      formats: window.d3Formats,
      formatLocaleDefinition: window.d3FormatLocaleDefinition,
      timeFormatLocaleDefinition: window.d3TimeFormatLocaleDefinition,
    };

    this.setSettings(globalSettings);

    d3.select(window).on('resize', () => {
      this.resize();
    });
  }

  // Note: unlike garnish `Base.setSettings`, this accumulates via a deep merge —
  // it's called repeatedly to layer defaults + overrides + global config.
  override setSettings(settings?: any, defaults?: any): void {
    const baseSettings = this.settings ?? {};
    this.settings = deepExtend({}, baseSettings, defaults, settings);
  }

  draw(dataTable: any, settings?: any): void {
    // Settings and chart attributes
    this.setSettings(settings);

    this.dataTable = dataTable;
    this.formatLocale = d3.formatLocale(this.settings.formatLocaleDefinition);
    this.timeFormatLocale = d3.timeFormatLocale(
      this.settings.timeFormatLocaleDefinition
    );
    this.orientation = this.settings.orientation;

    // Set (or reset) the chart element
    this.chart?.remove();

    let className = this.chartBaseClass;
    if (this.settings.chartClass) {
      className += ' ' + this.settings.chartClass;
    }

    this.chart = document.createElement('div');
    this.chart.className = className;
    this.container?.append(this.chart);
  }

  resize(): void {
    this.draw(this.dataTable, this.settings);
  }

  onAfterDrawTicks(): void {
    // White border for ticks' text
    for (const tick of this.chart?.querySelectorAll('.tick') ?? []) {
      const texts = Array.from(tick.querySelectorAll('text'));

      for (const text of texts) {
        tick.append(text.cloneNode(true));
        text.setAttribute('stroke', '#ffffff');
        text.setAttribute('stroke-width', '3');
      }
    }
  }
}

/**
 * Craft.charts.Area — a time-series area/line chart (axes, gridlines, line +
 * filled area, plot points, and hover tooltips), built with d3.
 */
export class Area extends BaseChart {
  static override defaults: any = {
    chartClass: 'area',
    margin: {top: 25, right: 5, bottom: 25, left: 0},
    plots: true,
    tips: true,
    xAxis: {
      gridlines: false,
      showAxis: true,
      formatter: NOOP,
    },
    yAxis: {
      gridlines: true,
      showAxis: false,
      formatter: NOOP,
    },
  };

  tip: any = null;
  drawingArea: any = null;

  constructor(container?: any, settings?: any) {
    super();
    if (new.target === Area) {
      this.init(container, settings);
    }
  }

  override init(container: any, settings?: any): void {
    super.init(container, Area.defaults);
    this.setSettings(settings);
  }

  override draw(dataTable: any, settings?: any): void {
    super.draw(dataTable, settings);

    if (this.tip) {
      this.tip = null;
    }

    const margin = this.getChartMargin();

    const chartSize = contentSize(this.chart!);
    this.width = chartSize.width - margin.left - margin.right;
    this.height = chartSize.height - margin.top - margin.bottom;

    // Append SVG to chart element
    const svg = {
      width: this.width + (margin.left + margin.right),
      height: this.height + (margin.top + margin.bottom),
      translateX: this.orientation !== 'rtl' ? margin.left : margin.right,
      translateY: margin.top,
    };

    this.svg = d3
      .select(this.chart)
      .append('svg')
      .attr('width', svg.width)
      .attr('height', svg.height);

    this.drawingArea = this.svg
      .append('g')
      .attr('transform', `translate(${svg.translateX},${svg.translateY})`);

    // Draw elements
    this.drawTicks();
    this.drawAxes();
    this.drawChart();
    this.drawTipTriggers();
  }

  drawTicks(): void {
    // Draw X ticks
    const x = this.getX(true);
    const xTicks = 3;
    const xAxis = d3
      .axisBottom(x)
      .tickFormat(this.getXFormatter())
      .ticks(xTicks);

    this.drawingArea
      .append('g')
      .attr('class', 'x ticks-axis')
      .attr('transform', `translate(0, ${this.height})`)
      .call(xAxis);

    // Draw Y ticks
    const y = this.getY();
    const yTicks = 2;
    let yAxis;

    if (this.orientation !== 'rtl') {
      yAxis = d3
        .axisLeft(y)
        .tickFormat(this.getYFormatter())
        .tickValues(this.getYTickValues())
        .ticks(yTicks);

      this.drawingArea.append('g').attr('class', 'y ticks-axis').call(yAxis);
    } else {
      yAxis = d3
        .axisRight(y)
        .tickFormat(this.getYFormatter())
        .tickValues(this.getYTickValues())
        .ticks(yTicks);

      this.drawingArea
        .append('g')
        .attr('class', 'y ticks-axis')
        .attr('transform', `translate(${this.width},0)`)
        .call(yAxis);
    }

    // On after draw ticks
    this.onAfterDrawTicks();
  }

  drawAxes(): void {
    if (this.settings.xAxis.showAxis) {
      const x = this.getX();
      const xAxis = d3.axisBottom(x).ticks(0).tickSizeOuter(0);
      this.drawingArea
        .append('g')
        .attr('class', 'x axis')
        .attr('transform', `translate(0, ${this.height})`)
        .call(xAxis);
    }

    if (this.settings.yAxis.showAxis) {
      const y = this.getY();
      const chartPadding = 0;
      let yAxis;

      if (this.orientation === 'rtl') {
        yAxis = d3.axisLeft(y).ticks(0);
        this.drawingArea
          .append('g')
          .attr('class', 'y axis')
          .attr('transform', `translate(${this.width - chartPadding}, 0)`)
          .call(yAxis);
      } else {
        yAxis = d3.axisRight(y).ticks(0);
        this.drawingArea
          .append('g')
          .attr('class', 'y axis')
          .attr('transform', `translate(${chartPadding}, 0)`)
          .call(yAxis);
      }
    }
  }

  drawChart(): void {
    const x = this.getX(true);
    const y = this.getY();

    // X & Y grid lines
    if (this.settings.xAxis.gridlines) {
      const xLineAxis = d3.axisBottom(x);

      this.drawingArea
        .append('g')
        .attr('class', 'x grid-line')
        .attr('transform', `translate(0,${this.height})`)
        .call(xLineAxis.tickSize(-this.height, 0, 0).tickFormat(''));
    }

    const yTicks = 2;

    if (this.settings.yAxis.gridlines) {
      const yLineAxis = d3.axisLeft(y);

      this.drawingArea
        .append('g')
        .attr('class', 'y grid-line')
        .attr('transform', 'translate(0 , 0)')
        .call(
          yLineAxis
            .tickSize(-this.width, 0)
            .tickFormat('')
            .tickValues(this.getYTickValues())
            .ticks(yTicks)
        );
    }

    // Line
    const line = d3
      .line()
      .x((d: any) => x(d[0]))
      .y((d: any) => y(d[1]));

    this.drawingArea
      .append('g')
      .attr('class', 'chart-line')
      .append('path')
      .datum(this.dataTable.rows)
      .style('fill', 'none')
      .style('stroke', this.settings.colors[0])
      .style('stroke-width', '3px')
      .attr('d', line);

    // Area
    const area = d3
      .area()
      .x((d: any) => x(d[0]))
      .y0(this.height)
      .y1((d: any) => y(d[1]));

    this.drawingArea
      .append('g')
      .attr('class', 'chart-area')
      .append('path')
      .datum(this.dataTable.rows)
      .style('fill', this.settings.colors[0])
      .style('fill-opacity', '0.3')
      .attr('d', area);

    // Plots
    if (this.settings.plots) {
      this.drawingArea
        .append('g')
        .attr('class', 'plots')
        .selectAll('circle')
        .data(this.dataTable.rows)
        .enter()
        .append('circle')
        .style('fill', this.settings.colors[0])
        .attr('class', (_d: any, index: any) => `plot plot-${index}`)
        .attr('r', 4)
        .attr('cx', (d: any) => x(d[0]))
        .attr('cy', (d: any) => y(d[1]));
    }
  }

  drawTipTriggers(): void {
    if (!this.settings.tips) {
      return;
    }

    if (!this.tip) {
      this.tip = new Tip(this.chart);
    }

    // Define xAxisTickInterval
    const chartMargin = this.getChartMargin();
    const tickSizeOuter = 6;
    const length =
      this.drawingArea.select('.x path.domain').node().getTotalLength() -
      chartMargin.left -
      chartMargin.right -
      tickSizeOuter * 2;
    const xAxisTickInterval = length / (this.dataTable.rows.length - 1);

    // Tip trigger width
    const tipTriggerWidth = Math.max(0, xAxisTickInterval);

    // Draw triggers
    const x = this.getX(true);
    const y = this.getY();

    this.drawingArea
      .append('g')
      .attr('class', 'tip-triggers')
      .selectAll('rect')
      .data(this.dataTable.rows)
      .enter()
      .append('rect')
      .attr(
        'class',
        (_d: any, index: any) => `tip-trigger tip-trigger-${index}`
      )
      .attr('data-index', (_d: any, index: any) => index)
      .style('fill', 'transparent')
      .style('fill-opacity', '1')
      .attr('width', tipTriggerWidth)
      .attr('height', this.height)
      .attr('x', (d: any) => x(d[0]) - tipTriggerWidth / 2)
      .on('mouseover', (event: any, dataValue: any) => {
        const index = d3.select(event.target).attr('data-index');

        // Expand plot
        this.drawingArea.select('.plot-' + index).attr('r', 5);

        // Set tip content
        const content = document.createElement('div');
        const xValue = document.createElement('div');
        xValue.className = 'x-value';
        xValue.innerHTML = this.getXFormatter()(dataValue[0]);
        const yValue = document.createElement('div');
        yValue.className = 'y-value';
        yValue.innerHTML = this.getYFormatter()(dataValue[1]);
        content.append(xValue, yValue);

        this.tip.setContent(content);

        // Set tip position
        const margin = this.getChartMargin();

        // Show the tip before measuring it: a hidden tip has no width
        this.tip.show();

        const offset = 24;
        const top = y(dataValue[1]) + offset;
        let left;

        if (this.orientation !== 'rtl') {
          left = x(dataValue[0]) + margin.left + offset;

          const chartLeft = this.chart!.getBoundingClientRect().left;
          const calcLeft = chartLeft + left + contentSize(this.tip.tip).width;
          const maxLeft = chartLeft + contentSize(this.chart!).width - offset;

          if (calcLeft > maxLeft) {
            left = x(dataValue[0]) - (contentSize(this.tip.tip).width + offset);
          }
        } else {
          left =
            x(dataValue[0]) -
            (contentSize(this.tip.tip).width + margin.left + offset);
        }

        if (left < 0) {
          left = x(dataValue[0]) + margin.left + offset;
        }

        this.tip.setPosition({top, left});
      })
      .on('mouseout', (event: any) => {
        const index = d3.select(event.target).attr('data-index');

        // Unexpand Plot
        this.drawingArea.select('.plot-' + index).attr('r', 4);

        // Hide tip
        this.tip.hide();
      });
  }

  getChartMargin(): any {
    const margin = this.settings.margin;

    // Estimate the max width of y ticks and set it as the left margin
    const values = this.getYTickValues();
    let yTicksMaxWidth = 0;

    values.forEach((value: any) => {
      const characterWidth = 8;

      const formatter = this.getYFormatter();
      const formattedValue = formatter(value);
      const computedTickWidth = formattedValue.length * characterWidth;

      if (computedTickWidth > yTicksMaxWidth) {
        yTicksMaxWidth = computedTickWidth;
      }
    });

    yTicksMaxWidth += 10;

    margin.left = yTicksMaxWidth;

    return margin;
  }

  getX(padded?: boolean): any {
    const xDomainMin = d3.min(this.dataTable.rows, (d: any) => d[0]);
    const xDomainMax = d3.max(this.dataTable.rows, (d: any) => d[0]);

    let xDomain = [xDomainMin, xDomainMax].filter(
      (value): value is Date => value instanceof Date
    );

    if (this.orientation === 'rtl') {
      xDomain = [xDomainMax, xDomainMin];
    }

    let left = 0;
    let right = 0;

    if (padded) {
      left = 0;
      right = 0;
    }

    const x = d3.scaleTime().range([left, this.width - right]);
    x.domain(xDomain);

    return x;
  }

  getY(): any {
    const yDomain = [0, this.getYMaxValue()];

    const y = d3.scaleLinear().range([this.height, 0]);
    y.domain(yDomain);

    return y;
  }

  getXFormatter(): any {
    if (this.settings.xAxis.formatter !== NOOP) {
      return this.settings.xAxis.formatter(this);
    }
    return chartsUtils.getTimeFormatter(this.timeFormatLocale, this.settings);
  }

  getYFormatter(): any {
    if (this.settings.yAxis.formatter !== NOOP) {
      return this.settings.yAxis.formatter(this);
    }
    return chartsUtils.getNumberFormatter(
      this.formatLocale,
      this.dataTable.columns[1].type,
      this.settings
    );
  }

  getYMaxValue(): any {
    let max = d3.max(this.dataTable.rows, (d: any) => d[1]);
    if (max === 0) {
      max = 1;
    }
    return max;
  }

  getYTickValues(): any {
    const maxValue = this.getYMaxValue();
    if (maxValue > 1) {
      return [maxValue / 2, maxValue];
    }
    return [0, maxValue];
  }
}

/** The formatter sentinel (legacy `$.noop`) — a no-op used as the default. */
function NOOP(): void {}

/** Craft.charts.utils — formatting helpers. */
export const chartsUtils = {
  getDuration(seconds: any): string {
    const secondsNum = parseInt(seconds, 10);

    // (Legacy computed these in a single object literal that referenced itself
    // before assignment — a bug; computed sequentially here.)
    const hours = Math.floor(secondsNum / 3600);
    const minutes = Math.floor((secondsNum - hours * 3600) / 60);
    const secs = secondsNum - hours * 3600 - minutes * 60;

    const pad = (n: number): string => (n < 10 ? `0${n}` : `${n}`);
    return `${pad(hours)}:${pad(minutes)}:${pad(secs)}`;
  },

  getTimeFormatter(timeFormatLocale: any, chartSettings: any): any {
    switch (chartSettings.dataScale) {
      case 'year':
        return timeFormatLocale.format('%Y');

      case 'month':
        return timeFormatLocale.format(
          chartSettings.formats.shortDateFormats.month
        );

      case 'hour':
        return timeFormatLocale.format(
          `${chartSettings.formats.shortDateFormats.day} %H:00:00`
        );

      default:
        return timeFormatLocale.format(
          chartSettings.formats.shortDateFormats.day
        );
    }
  },

  getNumberFormatter(formatLocale: any, type: any, chartSettings: any): any {
    switch (type) {
      case 'currency':
        return formatLocale.format(chartSettings.formats.currencyFormat);

      case 'percent':
        return formatLocale.format(chartSettings.formats.percentFormat);

      case 'time':
        return chartsUtils.getDuration;

      case 'number':
        return formatLocale.format(chartSettings.formats.numberFormat);
    }
  },
};
