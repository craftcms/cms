import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick, ref, type App} from 'vue';
import {provideHtmlWidgets} from './htmlWidgets';
import HtmlWidget from './HtmlWidget.vue';
import type {DashboardWidget} from './types';

vi.mock('@craftcms/ui', async () => ({
  ...(await import('@craftcms/ui/utilities/dom')),
  t: (message: string) => message,
}));

let app: App;
let host: HTMLElement;

afterEach(() => {
  app?.unmount();
  host?.remove();
});

it('keeps remaining HTML widgets styled when the widget providing their stylesheet is removed', async () => {
  const ids = ref([1, 2]);
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    setup() {
      provideHtmlWidgets();
      return () =>
        h(
          'div',
          ids.value.map((id) =>
            h(HtmlWidget, {
              key: id,
              widget: {
                fragment: {
                  html: `<p class="plugin-content">Widget ${id}</p>`,
                  headHtml:
                    id === 1
                      ? '<style>.plugin-content { color: rgb(12, 34, 56); }</style>'
                      : '',
                  bodyHtml: '',
                },
              } as DashboardWidget,
            })
          )
        );
    },
  });
  app.mount(host);

  await vi.waitFor(() => expect(host.querySelectorAll('p')).toHaveLength(2));
  expect(getComputedStyle(host.querySelectorAll('p')[1]!).color).toBe(
    'rgb(12, 34, 56)'
  );

  ids.value = [2];
  await nextTick();

  expect(host.textContent).toContain('Widget 2');
  expect(host.textContent).not.toContain('Widget 1');
  expect(getComputedStyle(host.querySelector('p')!).color).toBe(
    'rgb(12, 34, 56)'
  );
});
