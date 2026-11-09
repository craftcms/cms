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
