import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {HttpError} from '@craftcms/ui/utilities/api/http';

const request = vi.hoisted(() => vi.fn());

vi.mock('@craftcms/ui/utilities/api/http', async (importOriginal) => ({
  ...(await importOriginal<object>()),
  http: {request},
}));
vi.mock('@actions/InstallController', () => ({
  install: () => ({url: '/admin/install'}),
}));

const {default: InstallingScreen} = await import('./InstallingScreen.vue');
let app: App;
let root: HTMLElement;

afterEach(() => {
  app?.unmount();
  root?.remove();
});

it('shows the message from a failed install response', async () => {
  const message = "SQLSTATE[HY000] [1049] Unknown database 'craft'";
  request.mockRejectedValue(
    new HttpError(
      'Request failed with status code 400',
      'ERR_BAD_REQUEST',
      {} as any,
      {
        data: {message},
        status: 400,
      } as any
    )
  );

  root = document.createElement('div');
  document.body.append(root);
  app = createApp({render: () => h(InstallingScreen, {data: {}})});
  app.mount(root);
  await vi.waitFor(() => expect(root.textContent).toContain('Install failed'));
  await nextTick();

  expect(root.textContent).toContain(message);
  expect(root.textContent).not.toContain('[object Object]');
});
