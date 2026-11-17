import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {Area, DataTable} from './chart';

const data = () => ({
  columns: [
    {type: 'date', label: 'Date'},
    {type: 'number', label: 'Users'},
  ],
  rows: [
    ['2026-10-01', '3'],
    ['2026-10-02', '5'],
  ],
});

const months = Array.from({length: 12}, (_, i) => `M${i + 1}`);
const days = Array.from({length: 7}, (_, i) => `D${i + 1}`);

beforeEach(() => {
  // Set on every page by the D3 asset
  window.d3FormatLocaleDefinition = {
    decimal: '.',
    thousands: ',',
    grouping: [3],
    currency: ['$', ''],
  };
  window.d3TimeFormatLocaleDefinition = {
    dateTime: '%x, %X',
    date: '%-m/%-d/%Y',
    time: '%-I:%M:%S %p',
    periods: ['AM', 'PM'],
    days,
    shortDays: days,
    months,
    shortMonths: months,
  };
  vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(400);
  vi.spyOn(HTMLElement.prototype, 'clientHeight', 'get').mockReturnValue(200);
});

afterEach(() => {
  delete window.d3FormatLocaleDefinition;
  delete window.d3TimeFormatLocaleDefinition;
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

it('coerces each cell by its column type', () => {
  const table = new DataTable(data());

  expect(table.rows[0][0]).toBeInstanceOf(Date);
  expect(table.rows[1][1]).toBe(5);
});

it('draws into a plain element, replacing the previous chart on redraw', () => {
  const container = document.createElement('div');
  document.body.append(container);
  const chart = new Area(container, {orientation: 'ltr'});

  chart.draw(new DataTable(data()), {orientation: 'ltr'});
  chart.draw(new DataTable(data()), {orientation: 'ltr'});

  expect(container.querySelectorAll('.cp-chart.area')).toHaveLength(1);
  expect(container.querySelector('.cp-chart svg')).not.toBeNull();
  // Settings are deep-merged: an override keeps the other default formats
  expect(chart.settings.formats.percentFormat).toBe(',.2%');
});

it('stops redrawing on window resize once destroyed, leaving other charts be', () => {
  const charts = [0, 1].map(() => {
    const container = document.createElement('div');
    document.body.append(container);
    const chart = new Area(container, {orientation: 'ltr'});
    chart.draw(new DataTable(data()), {orientation: 'ltr'});
    return chart;
  });
  const resizes = charts.map((chart) => vi.spyOn(chart, 'resize'));

  charts[1]!.destroy();
  window.dispatchEvent(new Event('resize'));

  expect(resizes[0]).toHaveBeenCalledOnce();
  expect(resizes[1]).not.toHaveBeenCalled();
});

it('draws only the line and area without axes, still with a tooltip trigger per value', () => {
  const container = document.createElement('div');
  document.body.append(container);
  const chart = new Area(container, {orientation: 'ltr'});

  chart.draw(new DataTable(data()), {
    orientation: 'ltr',
    ticks: false,
    plots: false,
    xAxis: {showAxis: false},
    yAxis: {gridlines: false},
    fadeEdges: 16,
  });

  expect(container.querySelector('.chart-line path')).not.toBeNull();
  // The line and area fade out toward the sides
  const mask = container.querySelector('.chart-line')!.getAttribute('mask');
  expect(mask).toMatch(/^url\(#.+\)$/);
  expect(container.querySelector('.chart-area')!.getAttribute('mask')).toBe(
    mask
  );
  expect(
    container.querySelectorAll(
      '.ticks-axis, .axis, .grid-line, circle:not(.hover-plot)'
    )
  ).toHaveLength(0);
  expect(container.querySelectorAll('.tip-trigger')).toHaveLength(2);
});

it('marks the hovered value with a plot when it draws none of its own', () => {
  const container = document.createElement('div');
  document.body.append(container);
  const chart = new Area(container, {orientation: 'ltr'});
  chart.draw(new DataTable(data()), {orientation: 'ltr', plots: false});
  const marker = container.querySelector<SVGCircleElement>('.hover-plot')!;
  const [first, second] = container.querySelectorAll('.tip-trigger');

  expect(marker.style.display).toBe('none');

  second!.dispatchEvent(new MouseEvent('mouseover'));
  expect(marker.style.display).toBe('');
  const secondX = marker.getAttribute('cx');
  first!.dispatchEvent(new MouseEvent('mouseover'));
  expect(marker.getAttribute('cx')).not.toBe(secondX);

  first!.dispatchEvent(new MouseEvent('mouseout'));
  expect(marker.style.display).toBe('none');
});

it('writes tooltips as text with the given function', () => {
  const container = document.createElement('div');
  document.body.append(container);
  const chart = new Area(container, {orientation: 'ltr'});
  chart.draw(new DataTable(data()), {
    orientation: 'ltr',
    tipText: (date: Date, value: number) =>
      `<b>${date.getDate()}</b>: ${value}`,
  });

  container
    .querySelectorAll('.tip-trigger')[1]!
    .dispatchEvent(new MouseEvent('mouseover'));

  const tip = container.querySelector('.tooltip')!;
  expect(tip.textContent).toBe('<b>2</b>: 5');
  expect(tip.querySelector('b')).toBeNull();
});
