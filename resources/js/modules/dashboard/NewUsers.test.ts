import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, reactive, type App} from 'vue';
import NewUsers from './NewUsers.vue';

const state = vi.hoisted(() => ({
  post: vi.fn(),
  draw: vi.fn(),
}));
vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  actionClient: {post: state.post},
  t: (message: string) => message,
}));
vi.mock('@actions/Dashboard/Widgets/NewUsersController', () => ({
  data: {url: () => '/new-users/data'},
}));
vi.mock('@/modules/chart/chart', () => ({
  Area: class {
    draw = state.draw;
    resize() {}
    destroy() {}
  },
  DataTable: class {
    constructor(public rows: unknown) {}
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
    .mockResolvedValueOnce({data: {dataTable: 'before'}})
    .mockResolvedValueOnce({data: {dataTable: 'after'}});
  const props = reactive({data: {dateRange: 'd7', userGroupId: null}});
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({setup: () => () => h(NewUsers, props)});
  app.mount(host);
  await vi.waitFor(() => expect(state.draw).toHaveBeenCalledOnce());

  // A reload brings the same settings, in a new object.
  props.data = {dateRange: 'd7', userGroupId: null};

  await vi.waitFor(() => expect(state.draw).toHaveBeenCalledTimes(2));
  expect(state.draw.mock.lastCall![0]).toEqual({rows: 'after'});
});
