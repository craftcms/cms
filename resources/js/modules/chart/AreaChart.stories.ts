import type {Meta, StoryObj} from '@storybook/vue3-vite';
import AreaChart from './AreaChart.vue';
import type {ChartData} from './types';

// The control panel sets these on every page from the user's locale
const months = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
];
const days = [
  'Sunday',
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday',
];
window.d3FormatLocaleDefinition ??= {
  decimal: '.',
  thousands: ',',
  grouping: [3],
  currency: ['$', ''],
};
window.d3TimeFormatLocaleDefinition ??= {
  dateTime: '%x, %X',
  date: '%-m/%-d/%Y',
  time: '%-I:%M:%S %p',
  periods: ['AM', 'PM'],
  days,
  shortDays: days.map((day) => day.slice(0, 3)),
  months,
  shortMonths: months.map((month) => month.slice(0, 3)),
};

function daily(type: 'number' | 'currency', values: number[]): ChartData {
  return {
    columns: [
      {type: 'date', label: 'Date'},
      {type, label: type === 'currency' ? 'Revenue' : 'Orders'},
    ],
    rows: values.map((value, index) => [
      `2026-10-${String(index + 1).padStart(2, '0')}`,
      value,
    ]),
  };
}

const meta = {
  title: 'CP/AreaChart',
  component: AreaChart,
  args: {
    label: 'Orders',
    data: daily('number', [4, 7, 3, 9, 12, 8, 14, 11, 6, 10]),
    scale: 'day',
  },
  argTypes: {
    scale: {control: 'select', options: ['hour', 'day', 'month', 'year']},
    color: {control: 'color'},
  },
  parameters: {
    docs: {
      description: {
        component:
          'A time-series area chart, as on the New Users widget. `data` takes ' +
          'the `{columns, rows}` that `CraftCms\\Cms\\Dashboard\\Chart::get()` ' +
          'returns: a date column, then a value column. The value column’s ' +
          'type picks the format (`number`, `currency`, `percent` or `time`), ' +
          'unless `valueFormat` gives a d3-format specifier. It fills its ' +
          'width and is 12.5rem tall; give it a CSS height to change that.\n\n' +
          "Plugins import it from the control panel: `import {AreaChart} from '@craftcms/cp';`",
      },
    },
  },
} satisfies Meta<typeof AreaChart>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Counts: Story = {};

export const Currency: Story = {
  args: {
    label: 'Revenue',
    data: daily('currency', [120, 340, 95, 410, 380, 220, 515, 460, 300, 390]),
  },
};

export const CustomFormatAndColor: Story = {
  args: {valueFormat: ',.2f', color: '#9B009B'},
};
