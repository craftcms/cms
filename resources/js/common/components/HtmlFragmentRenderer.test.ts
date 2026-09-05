import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick, ref, type App} from 'vue';
import {appendBodyHtml, appendElementHtml} from '@craftcms/ui/utilities/dom';
import HtmlFragmentRenderer from './HtmlFragmentRenderer.vue';

vi.mock('@craftcms/ui', () => import('@craftcms/ui/utilities/dom'));

let app: App;
let host: HTMLElement;

afterEach(() => {
  app?.unmount();
  host?.remove();
});

it.each([false, true])(
  'replaces displayed content with custom rendering: %s',
  async (custom) => {
    const fragment = ref({
      html: '<p>Original content</p>',
      headHtml: '',
      bodyHtml: '',
    });
    host = document.createElement('div');
    document.body.append(host);
    app = createApp({
      render: () =>
        h(HtmlFragmentRenderer, {
          fragment: fragment.value,
          render: custom
            ? (value: CraftCms.Cms.View.HtmlFragment, container: HTMLElement) =>
                appendElementHtml(value.html, container)
            : undefined,
        }),
    });
    app.mount(host);

    await vi.waitFor(() => expect(host.textContent).toBe('Original content'));

    fragment.value = {
      ...fragment.value,
      html: '<a href="/entries">View entries</a>',
    };

    await vi.waitFor(() => expect(host.textContent).toBe('View entries'));
    expect(host.querySelector('a')?.getAttribute('href')).toBe('/entries');
  }
);

it('removes fragment content outside its container when leaving the page', async () => {
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    render: () =>
      h(HtmlFragmentRenderer, {
        fragment: {
          html: '<p>Widget</p>',
          headHtml: '',
          bodyHtml: '<aside id="widget-popup">Widget popup</aside>',
        },
      }),
  });
  app.mount(host);

  await vi.waitFor(() =>
    expect(document.querySelector('#widget-popup')?.textContent).toBe(
      'Widget popup'
    )
  );
  app.unmount();

  expect(document.querySelector('#widget-popup')).toBeNull();
});

it('does not leave popup content behind when rendering finishes after leaving the page', async () => {
  let finish!: () => void;
  const pending = new Promise<void>((resolve) => {
    finish = resolve;
  });
  let rendering!: ReturnType<typeof appendBodyHtml>;
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    render: () =>
      h(HtmlFragmentRenderer, {
        fragment: {html: 'Widget', headHtml: '', bodyHtml: ''},
        render: () => {
          rendering = pending.then(() =>
            appendBodyHtml('<aside id="late-widget-popup">Widget popup</aside>')
          );
          return rendering;
        },
      }),
  });
  app.mount(host);
  await nextTick();

  app.unmount();
  finish();
  await rendering;
  await nextTick();

  expect(document.querySelector('#late-widget-popup')).toBeNull();
});
