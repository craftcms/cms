import {afterEach, expect, it, vi} from 'vite-plus/test';

const {handlers, page, showMessage} = vi.hoisted(() => ({
  handlers: new Map<string, (event: unknown) => void>(),
  page: {props: {} as Record<string, unknown>},
  showMessage: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
  router: {
    on: (name: string, handler: (event: unknown) => void) =>
      handlers.set(name, handler),
  },
  usePage: () => page,
}));

vi.mock('./index', () => ({installMessages: vi.fn(), showMessage}));

const {setUpInertiaMessages} = await import('./inertia');

afterEach(() => {
  vi.useRealTimers();
  showMessage.mockClear();
  delete (window as {Craft?: unknown}).Craft;
});

it('shows the messages a visit that failed validation came back with', () => {
  vi.useFakeTimers();
  (window as {Craft?: unknown}).Craft = {};
  setUpInertiaMessages();
  const message = {id: '1', type: 'error', message: 'Couldn’t save entry.'};
  page.props = {messages: [message]};

  handlers.get('error')!({detail: {errors: {title: 'Required.'}}});
  vi.runAllTimers();

  expect(showMessage).toHaveBeenCalledWith(message, 0, [message]);
});
