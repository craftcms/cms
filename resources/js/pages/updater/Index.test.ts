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
  state: Pick<CraftCms.Cms.Update.Data.UpdaterState, 'error' | 'errorDetails'>
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
