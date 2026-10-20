import {createApp} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page} from 'vite-plus/test/browser/context';
import SiteFields from './SiteFields.vue';

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({
    props: {baseUrlTextExpanderTriggers: [], languageOptions: []},
  }),
}));

let teardown: () => void;

afterEach(() => {
  teardown?.();
  document.body.innerHTML = '';
});

it('describes the base URL field with its environment variable and alias hint', async () => {
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp(SiteFields, {
    localeOptions: [],
    modelValue: {name: '', baseUrl: '', language: 'en-US'},
  });
  app.mount(host);
  teardown = () => app.unmount();
  await expect
    .element(page.getByRole('textbox', {name: 'Base URL'}))
    .toHaveAccessibleDescription(
      /^The base URL for the site\.\s+Type \$ to choose an environment variable, or @ to choose an alias\.\s+Learn more$/
    );
});
