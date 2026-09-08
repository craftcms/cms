import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import {useDashboard} from '@/modules/dashboard/useDashboard';
import Widget from '@/modules/dashboard/Widget.vue';
import HtmlWidget from '@/modules/dashboard/HtmlWidget.vue';
import type {DashboardWidget} from '@/modules/dashboard/types';
import './dashboard.js';
import $ from 'jquery';
import {buildGarnishCompat} from '@craftcms/garnish/compat';

vi.mock('@craftcms/ui', async () => ({
  ...await import('@craftcms/ui/utilities/dom'),
  actionClient: {post: vi.fn()},
  t: (message: string, params: Record<string, string> = {}) =>
    message.replace(/\{(\w+)\}/g, (_, key) => params[key] ?? key),
}));
vi.mock('@/common/utils/jquery', () => ({
  jq: () => () => ({children: () => ({each() {}}), data() {}}),
}));
vi.mock('@/modules/grid/grid', () => ({
  Grid: class {
    $container = {height() {}};
    $items = {each() {}};
    items = [];
    totalCols = 4;
    setItems() {}
    refreshCols() {}
    destroy() {}
  },
}));

let app: App;
let host: HTMLElement;
const legacyWindow = window as any;
const originalCraft = window.Craft;
const originalGarnish = legacyWindow.Garnish;
const originalJquery = window.jQuery;

afterEach(() => {
  app?.unmount();
  host?.remove();
  vi.unstubAllGlobals();
  delete ($.fn as any).velocity;
  legacyWindow.Craft = originalCraft;
  legacyWindow.Garnish = originalGarnish;
  legacyWindow.jQuery = originalJquery;
});

it('lets an HTML plugin open and close its settings through the Yii API after revisiting the dashboard', async () => {
  vi.stubGlobal('fetch', async () => new Response('<svg xmlns="http://www.w3.org/2000/svg"></svg>'));
  // Complete animations immediately while exercising the real widget handlers.
  ($.fn as any).velocity = function (_properties: unknown, options: {complete: () => void}) {
    options.complete.call(this);
    return this;
  };
  legacyWindow.Craft = {};
  legacyWindow.Garnish = buildGarnishCompat();
  legacyWindow.jQuery = $;
  await import('../../../packages/craftcms-legacy/dashboard/src/Dashboard.js');
  const widget: DashboardWidget = {
    id: 1, type: 'Example', name: 'Example', title: 'Plugin widget', subtitle: null,
    colspan: 1, maxColspan: 4, settings: {}, settingsForm: {scope: ['settings'], nodes: [], values: {}, errors: [], globalErrors: [], refreshable: false},
    component: 'craft:html-widget', data: null,
    fragment: {html: '<button>Configure plugin</button>', headHtml: '', bodyHtml: ''},
  };

  for (let visit = 0; visit < 2; visit++) {
    host = document.createElement('div');
    document.body.append(host);
    app = createApp({
      setup() {
        const dashboard = useDashboard({widgets: [widget], widgetTypes: {}});
        return () => h('div', {ref: dashboard.container}, [
          h(Widget, {widget, ready: dashboard.ready.value}),
        ]);
      },
    });
    app.component('craft:html-widget', HtmlWidget);
    app.mount(host);

    await vi.waitFor(() => expect(host.querySelector('button')?.textContent).toBe('Configure plugin'));
    const configure = host.querySelector('button')!;
    configure.addEventListener('click', () => legacyWindow.dashboard.widgets[1].showSettings());
    configure.click();

    await vi.waitFor(() => expect(host.querySelector('form h2')?.textContent).toBe('Example Settings'));

    const cancel = Array.from(host.querySelectorAll('form craft-button')).find(button => button.textContent?.trim() === 'Cancel')!;
    cancel.dispatchEvent(new MouseEvent('click', {bubbles: true}));

    await vi.waitFor(() => expect(host.querySelector('form')).toBeNull());
    expect(configure.closest('[inert]')).toBeNull();

    host.querySelector('[aria-label="Widget settings"]')!.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await vi.waitFor(() => expect(host.querySelector('form h2')?.textContent).toBe('Example Settings'));
    // Allow the legacy delayed flip to finish before cancelling.
    await new Promise(resolve => setTimeout(resolve, 150));
    Array.from(host.querySelectorAll('form craft-button')).find(button => button.textContent?.trim() === 'Cancel')!.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await vi.waitFor(() => expect(host.querySelector('form')).toBeNull());
    expect(configure.closest('.hidden, [inert]')).toBeNull();

    app.unmount();
    host.remove();
  }
});
