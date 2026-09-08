import {createApp, nextTick} from 'vue';
import {
  afterEach,
  beforeEach,
  expect,
  it,
  vi,
  type MockInstance,
} from 'vite-plus/test';
import {router} from '@inertiajs/vue3';
import {index, install} from '@actions/PluginsController';
import {useAnnouncer} from '@/common/composables/useAnnouncer';
import {createPlugin} from '../fixtures/plugins';
import PluginsList from './PluginsList.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
  usePage: () => ({props: {readOnly: false}}),
}));

let app: ReturnType<typeof createApp>;
let container: HTMLElement;
let visit: MockInstance<typeof router.visit>;

async function settle() {
  await nextTick();
  for (const element of container.querySelectorAll('*')) {
    if ('updateComplete' in element) {
      await element.updateComplete;
    }
  }
  await nextTick();
}

async function mount() {
  const pluginInfo = Object.fromEntries(
    ['alpha', 'beta'].map((handle) => [
      handle,
      createPlugin({
        handle,
        name: handle,
        isInstalled: false,
        isEnabled: false,
      }),
    ])
  );
  container = document.createElement('div');
  document.body.append(container);
  app = createApp(PluginsList, {pluginInfo});
  app.mount(container);
  await settle();
}

function installItem(handle = 'alpha') {
  return [...container.querySelectorAll('craft-action-item')].find((item) => {
    const action = item.action;
    return (
      action &&
      typeof action === 'object' &&
      action.type === 'http' &&
      action.url === install({handle}).url
    );
  })!;
}

async function state(handle: string, state: string, message?: string) {
  installItem(handle).dispatchEvent(
    new CustomEvent('action:change-state', {
      bubbles: true,
      composed: true,
      detail: {state, actionType: 'http', message},
    })
  );
  await settle();
}

function messages() {
  return [...container.querySelectorAll('[role="region"]')].map(
    (element) => element.textContent
  );
}

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn(
      async () => new Response('<svg xmlns="http://www.w3.org/2000/svg"></svg>')
    )
  );
  visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
  useAnnouncer().announcement.value = null;
});

afterEach(() => {
  app?.unmount();
  container?.remove();
  vi.clearAllTimers();
  vi.useRealTimers();
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

it('shows complete, safe, accessible diagnostics after the real install menu closes', async () => {
  await mount();
  const item = installItem();
  const menu = item.closest('craft-action-menu')!;
  menu.querySelector('craft-button')!.click();
  await settle();
  expect(menu.opened).toBe(true);
  let resolveRequest!: (response: Response) => void;
  const request = new Promise<Response>((resolve) => {
    resolveRequest = resolve;
  });
  vi.stubGlobal(
    'fetch',
    vi.fn(() => request)
  );
  item.shadowRoot!.querySelector('button')!.click();
  await settle();
  expect(menu.opened).toBe(false);
  const message =
    '<img src=x onerror=alert(1)>\n\n' +
    'x'.repeat(2000) +
    '\nline'.repeat(10000);
  resolveRequest(new Response(JSON.stringify({message}), {status: 500}));
  await vi.waitFor(() => expect(messages()).toEqual([message]));
  const region = container.querySelector('[role="region"]')!;
  expect(region.querySelector('img')).toBeNull();
  expect(region.getAttribute('tabindex')).toBe('0');
  expect(region.getAttribute('aria-label')).toBe('An error occurred.');
  expect(useAnnouncer().announcement.value).toBe('An error occurred.');
  expect(visit).not.toHaveBeenCalled();
});

it('keeps accumulated failures through idle and timeouts until another action starts', async () => {
  await mount();
  vi.useFakeTimers();
  await state('alpha', 'error', 'First');
  await state('beta', 'error', 'Other');
  await state('alpha', 'error', 'Latest');
  await state('alpha', 'idle');
  await vi.advanceTimersByTimeAsync(6000);
  expect(messages()).toEqual(['First', 'Other', 'Latest']);
  expect(visit).not.toHaveBeenCalled();
  await state('beta', 'loading');
  expect(messages()).toEqual([]);
  await state('beta', 'error', 'New failure');
  expect(messages()).toEqual(['New failure']);
});

it('refreshes only pluginInfo on HTTP success but not clipboard success', async () => {
  await mount();
  const clipboardItem = [
    ...container.querySelectorAll('craft-action-item'),
  ].find(
    (item) =>
      typeof item.action === 'object' && item.action?.type === 'clipboard'
  )!;
  clipboardItem.dispatchEvent(
    new CustomEvent('action:change-state', {
      bubbles: true,
      detail: {state: 'success', actionType: 'clipboard'},
    })
  );
  await settle();
  expect(visit).not.toHaveBeenCalled();
  await state('alpha', 'success');
  expect(visit).toHaveBeenCalledExactlyOnceWith(index(), {
    only: ['pluginInfo'],
  });
});

it('uses a fallback when an error has no message', async () => {
  await mount();
  await state('alpha', 'error');
  expect(messages()).toEqual(['Request failed']);
});

it('does not steal focus on failure', async () => {
  await mount();
  const invoker = installItem('beta')
    .closest('craft-action-menu')!
    .querySelector('craft-button')!;
  invoker.focus();
  await state('alpha', 'error', 'Details');
  expect(document.activeElement).toBe(invoker);
});
