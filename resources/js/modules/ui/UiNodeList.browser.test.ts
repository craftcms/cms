import '../../../css/cp.css';
import '@craftcms/ui/components/tabs/tabs';
import '@craftcms/ui/components/tab/tab';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {registerUiComponents} from './register';
import type {UiNodePayload} from './types';
import UiRenderer from './UiRenderer.vue';

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
  history.replaceState(null, '', location.pathname);
});

const tab = (uid: string, label: string, sidebar = false): UiNodePayload => ({
  type: 'CraftCms\\Cms\\Ui\\Nodes\\Tab',
  component: 'craft:tab',
  uid,
  props: sidebar ? {label, sidebar: true} : {label},
  children: [],
});

it('pushes the sidebar tabs it shows as ordinary tabs to the end of the strip', async () => {
  const container = document.createElement('div');
  container.style.width = '800px';
  document.body.append(container);
  app = createApp({
    render: () =>
      h(UiRenderer, {
        payload: {
          scope: [],
          refreshable: false,
          nodes: [
            tab('content', 'Content'),
            tab('related', 'Related'),
            tab('visit', 'Visit', true),
            tab('seo', 'SEO', true),
          ],
          values: {},
          errors: [],
          globalErrors: [],
        },
      }),
  });
  const components = createCpComponentRegistry();
  registerUiComponents(components);
  components.install(app);
  app.mount(container);

  const strip = await vi.waitFor(() => {
    const element = container.querySelector('craft-tabs');
    expect(
      element?.shadowRoot?.querySelector('[part="tab-group"]')
    ).toBeTruthy();

    return element!;
  });
  const tabGroup = strip.shadowRoot!.querySelector('[part="tab-group"]')!;
  const box = (label: string) =>
    [...strip.querySelectorAll('craft-tab')]
      .find((element) => element.textContent?.trim() === label)!
      .getBoundingClientRect();

  await vi.waitFor(() => {
    const group = tabGroup.getBoundingClientRect();

    expect(Math.round(box('Content').left)).toBe(Math.round(group.left));
    expect(Math.round(box('SEO').right)).toBe(Math.round(group.right));
    expect(box('Visit').left - box('Related').right).toBeGreaterThan(100);
  });
});
