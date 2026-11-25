import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';

vi.mock('@inertiajs/vue3', () => ({Head: {render: () => null}}));

const {default: Updater} = await import('./Index.vue');
let app: App;
let root: HTMLElement;

afterEach(() => {
  app?.unmount();
  root?.remove();
});

async function mount(
  state: Pick<
    CraftCms.Cms.Update.Data.UpdaterState,
    'error' | 'errorDetails' | 'options'
  >
): Promise<HTMLElement> {
  const initialState = {data: '', finishUrl: '', ...state};
  root = document.createElement('div');
  document.body.append(root);
  app = createApp({
    render: () => h(Updater, {title: 'Updater', returnUrl: '', initialState}),
  });
  app.mount(root);
  await nextTick();

  return root;
}

it('renders error output as text, keeping the paragraph and code formatting', async () => {
  const root = await mount({
    error: 'Composer failed: <img src=x onerror="alert(1)">',
    errorDetails: 'Run `<b>composer</b>` install\n\nthen retry\nnow',
  });
  const status = root.querySelector('.updater-status')!;

  expect(status.querySelector('img, b')).toBeNull();
  expect(status.querySelector('.error-message')!.textContent).toBe(
    'Composer failed: <img src=x onerror="alert(1)">'
  );

  const paragraphs = status.querySelectorAll('.error-details p');
  expect(paragraphs).toHaveLength(2);
  expect(paragraphs[0]!.querySelector('code')!.textContent).toBe(
    '<b>composer</b>'
  );
  expect(paragraphs[1]!.innerHTML).toBe('then retry<br>now');
});

it('leaves for the option’s return URL when a finished option is chosen', async () => {
  vi.useFakeTimers();
  const location = {href: ''} as Location;
  const locationSpy = vi
    .spyOn(window, 'location', 'get')
    .mockReturnValue(location);

  const root = await mount({
    error: 'The plugin couldn’t be installed.',
    options: [
      {
        label: 'Leave it uninstalled',
        status: 'All done!',
        finished: true,
        returnUrl: '/admin/plugin-store',
      },
      {label: 'Remove it', nextUrl: '/actions/remove'},
    ],
  });

  root.querySelector<HTMLElement>('.updater-options craft-button')!.click();
  await nextTick();

  expect(root.querySelector('.updater-options')).toBeNull();
  vi.advanceTimersByTime(750);
  expect(location.href).toBe('/admin/plugin-store');

  locationSpy.mockRestore();
  vi.useRealTimers();
});
