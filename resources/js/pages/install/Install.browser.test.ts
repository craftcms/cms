import {createApp, type App, type Slots} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import {http as inertiaHttp} from '@inertiajs/core';
import {http} from '@craftcms/ui/utilities/api/http';
import Install from './Install.vue';
import '../../../css/cp.css';

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  Head: {render: () => null},
  Deferred: {
    props: ['data'],
    render(this: {$slots: Slots}) {
      return this.$slots.default?.();
    },
  },
  usePage: () => ({
    props: {baseUrlTextExpanderTriggers: [], languageOptions: []},
  }),
}));

vi.mock('@/common/components/Modal.vue', () => ({
  default: {
    inheritAttrs: false,
    render(this: {$slots: Slots}) {
      return this.$slots.default?.();
    },
  },
}));

let app: App;

afterEach(() => {
  app?.unmount();
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

it.each([false, true])(
  'submits database settings only when database setup is shown (%s)',
  async (showDbScreen) => {
    vi.spyOn(inertiaHttp.getClient(), 'request').mockResolvedValue({
      status: 200,
      data: '{}',
      headers: {},
    });
    const installRequest = vi
      .spyOn(http, 'request')
      .mockImplementation(() => new Promise(() => {}));

    const host = document.createElement('div');
    document.body.append(host);
    app = createApp(Install, {
      showDbScreen,
      dbConfig: {driver: 'sqlite'},
      dbDefaults: {
        sqlite: {database: 'database.sqlite'},
        mysql: {database: ''},
        mariadb: {database: ''},
        pgsql: {database: ''},
      },
      licenseHtml: '<p>License terms</p>',
      localeOptions: [{id: 'en-US', name: 'English', selected: true}],
      defaultSystemName: 'Original site',
      defaultSiteUrl: 'https://example.test',
      defaultSiteLanguage: 'en-US',
    });
    app.mount(host);

    await page.getByRole('button', {name: 'Install Craft CMS'}).click();
    await page.getByRole('button', {name: 'Got it'}).click();
    await page
      .getByRole('textbox', {name: 'Username', exact: true})
      .fill('owner');
    await page
      .getByRole('textbox', {name: 'Email', exact: true})
      .fill('owner@example.test');
    await page
      .getByLabelText('Password', {exact: true})
      .fill('long-enough-password');
    await page.getByRole('button', {name: 'Next', exact: true}).click();

    if (showDbScreen) {
      await page
        .getByRole('textbox', {name: 'Database File Path'})
        .fill('custom.sqlite');
      await page.getByRole('button', {name: 'Next', exact: true}).click();
    }

    await expect
      .element(page.getByRole('heading', {name: 'Set up your site'}))
      .toHaveFocus();
    await page.getByRole('textbox', {name: 'System Name'}).fill('New site');
    if (showDbScreen) {
      await page.getByRole('button', {name: 'Finish up'}).click();
    } else {
      await userEvent.keyboard('{Enter}');
    }

    await expect.poll(() => installRequest.mock.calls.length).toBe(1);
    await expect
      .element(page.getByRole('heading', {name: 'Installing Craft CMS…'}))
      .toHaveFocus();
    const payload = JSON.parse(
      JSON.stringify(installRequest.mock.calls[0]![0].data)
    );
    expect(payload.account).toEqual({
      username: 'owner',
      email: 'owner@example.test',
      password: 'long-enough-password',
    });
    expect(payload.site).toEqual({
      name: 'New site',
      baseUrl: 'https://example.test',
      language: 'en-US',
    });
    if (showDbScreen) {
      expect(payload.db).toEqual({
        driver: 'sqlite',
        database: 'custom.sqlite',
        prefix: '',
      });
    } else {
      expect(payload).not.toHaveProperty('db');
    }
  }
);
