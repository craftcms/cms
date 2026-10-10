import {createApp} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import DeleteButton from './DeleteButton.vue';

const visit = vi.hoisted(() => vi.fn());
vi.mock('@inertiajs/vue3', () => ({router: {visit}}));

const container = document.createElement('div');
let app: ReturnType<typeof createApp>;

afterEach(() => {
  app.unmount();
  container.replaceChildren();
  vi.unstubAllGlobals();
  visit.mockClear();
});

it('emits clicks only after confirmation', () => {
  const confirm = vi.fn((): boolean => false);
  const onClick = vi.fn();
  vi.stubGlobal('confirm', confirm);
  app = createApp(DeleteButton, {
    confirm: 'Delete this item?',
    onClick,
  });
  app.mount(container);

  const button = container.querySelector('craft-button');
  if (!button) throw new Error('Expected a delete button.');

  button.click();
  expect(confirm).toHaveBeenCalledWith('Delete this item?');
  expect(onClick).not.toHaveBeenCalled();

  confirm.mockReturnValue(true);
  button.click();
  expect(onClick).toHaveBeenCalledOnce();
});

it('visits its action once confirmed, as a DELETE or with the route’s method', () => {
  const confirm = vi.fn((): boolean => false);
  vi.stubGlobal('confirm', confirm);
  const optimistic = () => ({});
  app = createApp(DeleteButton, {
    confirm: 'Delete this item?',
    action: '/admin/widgets/1',
    options: {optimistic},
  });
  app.mount(container);
  const button = container.querySelector('craft-button') as HTMLElement;

  button.click();
  expect(visit).not.toHaveBeenCalled();

  confirm.mockReturnValue(true);
  button.click();
  expect(visit).toHaveBeenCalledWith(
    '/admin/widgets/1',
    expect.objectContaining({method: 'delete', optimistic})
  );

  app.unmount();
  const route = {url: '/admin/widgets/1/archive', method: 'post' as const};
  app = createApp(DeleteButton, {action: route});
  app.mount(container);
  (container.querySelector('craft-button') as HTMLElement).click();
  expect(visit).toHaveBeenLastCalledWith(
    route,
    expect.objectContaining({method: 'post'})
  );
});
