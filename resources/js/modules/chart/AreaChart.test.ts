import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, reactive, type App} from 'vue';
import AreaChart from './AreaChart.vue';
import type {ChartData} from './types';

const state = vi.hoisted(() => ({draw: vi.fn()}));
vi.mock('./chart', async () => {
  const actual = await vi.importActual<typeof import('./chart')>('./chart');
  return {
    ...actual,
    Area: class {
      draw = state.draw;
      resize() {}
      destroy() {}
    },
  };
});

let app: App;
let host: HTMLElement;

afterEach(() => {
  app?.unmount();
  host?.remove();
  vi.resetAllMocks();
});

const users = (count: string): ChartData => ({
  columns: [
    {type: 'date', label: 'Date'},
    {type: 'number', label: 'Users'},
  ],
  rows: [['2026-10-01', count]],
});

it('draws the given data without changing it, and redraws when it changes', async () => {
  const props = reactive({data: users('3'), label: 'New users'});
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({setup: () => () => h(AreaChart, props)});
  app.mount(host);

  expect(state.draw).toHaveBeenCalledOnce();
  expect(state.draw.mock.lastCall![0].rows).toEqual([
    [new Date(2026, 9, 1), 3],
  ]);
  // Parsing must not reach the caller's rows, or the next draw parses dates twice
  expect(props.data.rows).toEqual([['2026-10-01', '3']]);

  props.data = users('5');
  await vi.waitFor(() => expect(state.draw).toHaveBeenCalledTimes(2));
  expect(state.draw.mock.lastCall![0].rows[0][1]).toBe(5);
});
