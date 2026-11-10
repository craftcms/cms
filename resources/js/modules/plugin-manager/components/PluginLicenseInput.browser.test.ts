import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import {createApp, type App} from 'vue';
import {createPlugin} from '../fixtures/plugins';
import PluginLicenseInput from './PluginLicenseInput.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
  usePage: () => ({props: {readOnly: false}}),
}));

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  document.body.innerHTML = '';
});

it('formats the license key once focus leaves the field', async () => {
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp(PluginLicenseInput, {
    plugin: createPlugin({licenseKey: null, isComposerInstalled: true}),
  });
  app.mount(container);
  const field = page.getByRole('textbox', {name: 'License Key'});

  await userEvent.fill(field, 'abcd1234efgh');
  await userEvent.tab();

  await expect.element(field).toHaveValue('ABCD-1234-EFGH');
});
