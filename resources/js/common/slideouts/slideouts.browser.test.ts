import {createApp, defineComponent, h, nextTick, type App} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import type CraftPopover from '@craftcms/ui/components/popover/popover';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/popover/popover';
import '../../../css/cp.css';
import {registerPanel, unregisterPanel} from './panel-stack';
import SlideoutPanel from './SlideoutPanel.vue';
import {
  closeAllSlideouts,
  closeSlideout,
  openSlideout,
  setSlideoutPageLoader,
} from './store';

const apps: App[] = [];

afterEach(() => {
  while (apps.length) {
    apps.pop()!.unmount();
  }
  setSlideoutPageLoader();
  closeAllSlideouts();
  document.body.innerHTML = '';
});

it('returns focus to a popover’s button once a slideout opened from inside it closes', async () => {
  setSlideoutPageLoader(
    vi.fn().mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    })
  );
  document.body.innerHTML = `
    <craft-popover>
      <craft-button slot="invoker" type="button">View</craft-button>
      <div slot="content"><button type="button">Edit columns</button></div>
    </craft-popover>
  `;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const opener = popover.querySelector<HTMLElement>('[slot="content"] button')!;
  let panelId = '';
  opener.addEventListener('click', async () => {
    const panel = (await openSlideout('/a', {opener}))!;
    panelId = panel.id;
    // Standing in for `SlideoutPanel`, which focuses itself as it opens.
    const element = document.createElement('div');
    element.dataset.slideoutId = panel.id;
    element.tabIndex = -1;
    document.body.append(element);
    element.focus();
  });
  await popover.updateComplete;

  await userEvent.click(page.getByRole('button', {name: 'View'}));
  await userEvent.click(page.getByRole('button', {name: 'Edit columns'}));

  await expect.poll(() => panelId).not.toBe('');
  await expect.poll(() => popover.open).toBe(false);

  closeSlideout(panelId);

  expect(document.activeElement).toBe(
    popover.querySelector('[slot="invoker"]')
  );
});

describe('stacked panel positions', () => {
  async function mountPanel(): Promise<HTMLElement> {
    setSlideoutPageLoader(
      vi.fn().mockResolvedValue({
        component: defineComponent({render: () => h('div')}),
        props: {},
        url: '/a',
      })
    );
    const instance = (await openSlideout('/a'))!;
    const root = document.createElement('div');
    document.body.append(root);
    const app = createApp(
      defineComponent({render: () => h(SlideoutPanel, {instance})})
    );
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(root);
    apps.push(app);
    await nextTick();
    await nextTick();

    return root.querySelector<HTMLElement>('.slideout-panel')!;
  }

  async function settledRect(panel: HTMLElement): Promise<DOMRect> {
    await new Promise((resolve) =>
      requestAnimationFrame(() => requestAnimationFrame(resolve))
    );
    let last = '';
    await expect
      .poll(() => {
        const rect = JSON.stringify(panel.getBoundingClientRect());
        const settled = rect === last;
        last = rect;
        return settled;
      })
      .toBe(true);

    return panel.getBoundingClientRect();
  }

  it.each([
    {name: 'from the end', dir: 'ltr', position: undefined, edge: 'right'},
    {
      name: 'from the end, right to left',
      dir: 'rtl',
      position: undefined,
      edge: 'left',
    },
    {name: 'from the start', dir: 'ltr', position: 'start', edge: 'left'},
  ] as const)(
    'slides in $name to rest against that edge, and moves aside without layout',
    async ({dir, position, edge}) => {
      await page.viewport(1280, 800);
      document.documentElement.setAttribute('dir', dir);
      const craft = (window as {Craft?: object}).Craft;
      (window as {Craft?: object}).Craft = {
        ...craft,
        ...(position ? {slideoutPosition: position} : {}),
      };

      try {
        const panel = await mountPanel();
        const top = await settledRect(panel);
        const restingBox = panel.offsetLeft;

        expect(top.width).toBeGreaterThan(0);
        expect(
          edge === 'right' ? window.innerWidth - top.right : top.left
        ).toBeCloseTo(0, 0);

        const above = {
          element: document.createElement('div'),
          position: () => {},
          handleShadeClick: () => {},
        };
        registerPanel(above);
        await nextTick();
        const beneath = await settledRect(panel);
        unregisterPanel(above);

        expect(
          edge === 'right' ? window.innerWidth - beneath.right : beneath.left
        ).toBeGreaterThan(top.width / 4);
        expect(beneath.left).toBeGreaterThanOrEqual(0);
        expect(beneath.right).toBeLessThanOrEqual(window.innerWidth);
        expect(panel.offsetLeft).toBe(restingBox);
      } finally {
        (window as {Craft?: object}).Craft = craft;
      }
    }
  );
});
