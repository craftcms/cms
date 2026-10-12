import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page} from 'vite-plus/test/browser/context';
import {createApp, h, nextTick, type App} from 'vue';
import SlideoutPanel from './SlideoutPanel.vue';
import type {SlideoutInstance} from './types';
import '../../../css/cp.css';

let app: App | null = null;
let container: HTMLElement | null = null;

afterEach(() => {
  app?.unmount();
  app = null;
  container?.remove();
  container = null;
  vi.unstubAllGlobals();
});

it.each([
  {viewport: 320, width: '800px', expectedWidth: 320},
  {viewport: 640, width: '800px', expectedWidth: 640},
  {viewport: 1024, width: '800px', expectedWidth: 800},
  {viewport: 320, width: '40rem', expectedWidth: 320},
  {viewport: 480, width: '40rem', expectedWidth: 480},
  {viewport: 1024, width: '40rem', expectedWidth: 640},
  {viewport: 1024, width: null, expectedWidth: 563.2},
])(
  'keeps stacked $width panels inside a $viewport px viewport',
  async ({viewport, width, expectedWidth}) => {
    await page.viewport(viewport, 800);
    vi.stubGlobal('Craft', {slideoutPosition: 'end'});
    container = document.createElement('div');
    document.body.append(container);

    const instances: SlideoutInstance[] = [0, 1].map((index) => ({
      id: `width-panel-${index}`,
      containerId: `width-panel-${index}`,
      href: '/screen',
      width,
      component: null,
      props: {},
      loading: false,
      error: 'Could not load editor.',
      opener: null,
      onSaved: null,
    }));

    app = createApp({
      render: () => instances.map((instance) => h(SlideoutPanel, {instance})),
    });
    app.mount(container);
    await nextTick();

    const panels = [
      ...container.querySelectorAll<HTMLElement>('.slideout-panel'),
    ];

    await expect
      .poll(() => panels[1]!.getBoundingClientRect().right)
      .toBeCloseTo(viewport, 1);

    for (const [index, panel] of panels.entries()) {
      const rect = panel.getBoundingClientRect();
      expect(rect.width).toBeCloseTo(expectedWidth, 1);
      expect(rect.left).toBeCloseTo(
        ((viewport - expectedWidth) * (index + 1)) / 2,
        1
      );
      expect(rect.left).toBeGreaterThanOrEqual(0);
      expect(rect.right).toBeLessThanOrEqual(viewport + 0.1);
    }
  }
);
