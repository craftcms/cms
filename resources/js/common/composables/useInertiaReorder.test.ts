import {beforeEach, expect, it, vi} from 'vite-plus/test';
import {useInertiaReorder} from './useInertiaReorder';

const visit = vi.hoisted(() => vi.fn());
const page = vi.hoisted(() => ({props: {} as Record<string, unknown>}));
vi.mock('@inertiajs/vue3', () => ({router: {visit}, usePage: () => page}));

beforeEach(() => {
  visit.mockClear();
  page.props = {
    routes: [{uid: 'a'}, {uid: 'b'}, {uid: 'c'}],
  };
});

it('posts the new order and moves the row in its prop until the server responds', () => {
  const route = {
    url: '/admin/settings/routes/reorder',
    method: 'post' as const,
  };
  const onReorder = useInertiaReorder({
    url: route,
    prop: 'routes',
    key: 'uid',
    param: 'routeUids',
  });

  onReorder(0, 2);

  const [url, options] = visit.mock.calls[0]!;
  expect(url).toBe(route);
  expect(options).toMatchObject({
    method: 'post',
    data: {routeUids: ['b', 'c', 'a']},
  });
  expect(options.optimistic()).toEqual({
    routes: [{uid: 'b'}, {uid: 'c'}, {uid: 'a'}],
  });
});
