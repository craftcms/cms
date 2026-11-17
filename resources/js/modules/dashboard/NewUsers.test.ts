import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, reactive, type App} from 'vue';
import NewUsers from './NewUsers.vue';
import {provideWidgetHeader} from './useWidgetHeader';
import {provideWidgetColor} from './useWidgetColor';

const table = (...counts: number[]) => ({
  columns: [
    {type: 'date', label: 'Date'},
    {type: 'number', label: 'Users'},
  ],
  rows: counts.map((count, day) => [`2026-11-0${day + 1}`, count]),
});

const state = vi.hoisted(() => ({
  post: vi.fn(),
  draw: vi.fn(),
}));
vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  actionClient: {post: state.post},
}));
vi.mock('@actions/Dashboard/Widgets/NewUsersController', () => ({
  data: {url: () => '/new-users/data'},
}));
vi.mock('@/modules/chart/AreaChart.vue', () => ({
  default: (props: {
    data: unknown;
    color?: string;
    tooltip?: (date: string, value: number) => string;
  }) => {
    state.draw(props.data, props.color, props.tooltip);
    return null;
  },
}));

let app: App;
let host: HTMLElement;

afterEach(() => {
  app?.unmount();
  host?.remove();
  vi.resetAllMocks();
});

it('draws new users again when the dashboard reloads its widgets', async () => {
  state.post
    .mockResolvedValueOnce({data: {dataTable: table(1), scale: 'day'}})
    .mockResolvedValueOnce({data: {dataTable: table(2), scale: 'day'}});
  const props = reactive({data: {dateRange: 'd7', userGroupId: null}});
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({setup: () => () => h(NewUsers, props)});
  app.mount(host);
  await vi.waitFor(() => expect(state.draw).toHaveBeenCalledOnce());

  // A reload brings the same settings, in a new object.
  props.data = {dateRange: 'd7', userGroupId: null};

  await vi.waitFor(() => expect(state.draw).toHaveBeenCalledTimes(2));
  expect(state.draw.mock.lastCall![0]).toEqual(table(2));
});

it.each([
  ['lime', 'var(--c-color-fill-loud)'],
  ['black', undefined],
  ['gray', undefined],
  [null, undefined],
])('draws a %s widget’s chart in %s', async (widgetColor, chartColor) => {
  state.post.mockResolvedValue({data: {dataTable: table(1), scale: 'day'}});
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    setup() {
      provideWidgetColor(() => widgetColor);
      return () => h(NewUsers, {data: {dateRange: 'd7', userGroupId: null}});
    },
  });
  app.mount(host);

  await vi.waitFor(() => expect(state.draw).toHaveBeenCalled());
  expect(state.draw.mock.lastCall![1]).toBe(chartColor);
});

it.each([
  [null, [800, 0, 434], '1,234 new users'],
  [null, [1], '1 new user'],
  ['members', [800, 0, 434], '1,234 new members'],
  ['members', [0, 1], '1 new member'],
])(
  'titles itself with the new users in the %s group (%j) across the range',
  async (userGroupName, counts, text) => {
    state.post.mockResolvedValue({
      data: {dataTable: table(...counts), scale: 'day'},
    });
    host = document.createElement('div');
    document.body.append(host);
    app = createApp({
      setup() {
        // The widget's header, whose title the total stands in for
        provideWidgetHeader('widget-heading-5');
        return () =>
          h(NewUsers, {
            data: {
              dateRange: 'd7',
              userGroupId: userGroupName ? 5 : null,
              userGroupName,
              userGroupNameSingular: userGroupName && 'member',
              dateRangeLabel: 'Last 7 days',
            },
          });
      },
    });
    app.mount(host);

    await vi.waitFor(() =>
      expect(host.querySelector('h2#widget-heading-5')?.textContent).toBe(text)
    );
    expect(host.querySelector('.sparkline-stat__caption')?.textContent).toBe(
      'Last 7 days'
    );
  }
);

it.each([
  [null, '11/4 · 5 users', '11/4 · 1 user'],
  ['members', '11/4 · 5 members', '11/4 · 1 member'],
])(
  'labels a day’s tooltip with the %s group',
  async (userGroupName, five, one) => {
    state.post.mockResolvedValue({data: {dataTable: table(5), scale: 'day'}});
    host = document.createElement('div');
    document.body.append(host);
    app = createApp({
      setup: () => () =>
        h(NewUsers, {
          data: {
            dateRange: 'd7',
            userGroupId: userGroupName ? 5 : null,
            userGroupName,
            userGroupNameSingular: userGroupName && 'member',
          },
        }),
    });
    app.mount(host);

    await vi.waitFor(() => expect(state.draw).toHaveBeenCalled());
    const tooltip = state.draw.mock.lastCall![2];
    expect(tooltip('11/4', 5)).toBe(five);
    expect(tooltip('11/4', 1)).toBe(one);
  }
);

it('leaves the widget’s own title until its total shows', async () => {
  let respond!: (value: unknown) => void;
  state.post
    .mockReturnValueOnce(new Promise((resolve) => (respond = resolve)))
    .mockRejectedValueOnce(new Error('offline'));
  const props = reactive({data: {dateRange: 'd7', userGroupId: null}});
  let header!: ReturnType<typeof provideWidgetHeader>;
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    setup() {
      header = provideWidgetHeader('widget-heading-5');
      return () => h(NewUsers, props);
    },
  });
  app.mount(host);

  // Loading: the dashboard's title stays.
  expect(header.ownTitles.value).toBe(0);

  respond({data: {dataTable: table(1), scale: 'day'}});
  await vi.waitFor(() => expect(header.ownTitles.value).toBe(1));
  expect(host.querySelector('#widget-heading-5')).not.toBeNull();

  // A reload that fails shows its error, under the dashboard's title again.
  props.data = {dateRange: 'd7', userGroupId: null};
  await vi.waitFor(() => expect(header.ownTitles.value).toBe(0));
});
