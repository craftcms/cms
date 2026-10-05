import {computed, createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import '@craftcms/ui/components/switch/switch';
import PreviewTargetsTable from './PreviewTargetsTable.vue';

vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({readOnly: computed(() => false)}),
}));
beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
});
let teardown: (() => void) | undefined;
afterEach(() => {
  teardown?.();
  document.body.innerHTML = '';
  vi.unstubAllGlobals();
});

it('names the auto-refresh switch after its column header', async () => {
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp({
    render: () =>
      h(PreviewTargetsTable, {
        modelValue: [
          {label: 'Primary entry page', urlFormat: '{url}', refresh: true},
        ],
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(host);
  teardown = () => app.unmount();
  await nextTick();

  const switchEl = host.querySelector<
    HTMLElement & {updateComplete: Promise<unknown>}
  >('craft-switch')!;
  await switchEl.updateComplete;

  const button = switchEl.querySelector('craft-switch-button')!;
  const name = (button.getAttribute('aria-labelledby') ?? '')
    .split(/\s+/)
    .filter(Boolean)
    .map((id) => document.getElementById(id)?.textContent?.trim() ?? '')
    .filter(Boolean)
    .join(' ');

  expect(name).toBe('Auto-Refresh');
});
