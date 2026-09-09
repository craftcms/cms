import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, ref, type App} from 'vue';
import {http} from '@inertiajs/vue3';
import WidgetSettings from './WidgetSettings.vue';
import type {DashboardWidget} from './types';

vi.mock('@craftcms/ui', () => ({
  actionClient: {post: vi.fn()},
  t: (message: string) => message,
}));

let app: App;
let host: HTMLElement;

afterEach(() => {
  app?.unmount();
  host?.remove();
  vi.restoreAllMocks();
});

it('shows validation errors and closes settings after a successful retry', async () => {
  const widget: DashboardWidget = {
    id: 1,
    type: 'Example',
    name: 'Example',
    title: 'Example widget',
    subtitle: null,
    colspan: 1,
    maxColspan: 4,
    settings: {title: 'Example widget'},
    settingsForm: null,
    component: null,
    data: null,
    fragment: {html: '', headHtml: '', bodyHtml: ''},
  };
  const response = {
    status: 422,
    statusText: 'Unprocessable Content',
    headers: {},
    data: JSON.stringify({
      errors: {title: ['Title is required.', 'Choose a different title.']},
    }),
  };
  vi.spyOn(http.getClient(), 'request')
    .mockResolvedValueOnce(response)
    .mockResolvedValueOnce({
      ...response,
      status: 200,
      data: JSON.stringify({info: widget}),
    });

  const saved = ref<DashboardWidget | false>();
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    render: () =>
      saved.value
        ? h('h2', saved.value.title!)
        : h(WidgetSettings, {
            widget,
            onSaved: (value: DashboardWidget | false) => {
              saved.value = value;
            },
          }),
  });
  app.mount(host);

  host
    .querySelector('form')!
    .dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));

  await vi.waitFor(() => {
    expect(host.querySelector('[role=alert]')?.textContent).toContain(
      'Title is required.'
    );
    expect(host.querySelector('[role=alert]')?.textContent).toContain(
      'Choose a different title.'
    );
  });

  host
    .querySelector('form')!
    .dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));

  await vi.waitFor(() =>
    expect(host.querySelector('h2')?.textContent).toBe('Example widget')
  );
  expect(host.querySelector('form')).toBeNull();
});
