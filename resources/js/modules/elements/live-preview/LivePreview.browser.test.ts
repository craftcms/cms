import '../../../../css/cp.css';
import '@craftcms/ui';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  Teleport,
  useTemplateRef,
  type App,
} from 'vue';
import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import SlideoutHost from '@/common/slideouts/SlideoutHost.vue';
import {closeAllSlideouts, openSlideoutWith} from '@/common/slideouts/store';
import LivePreview from './LivePreview.vue';
import {DEVICES} from './devicePreview';
import {useLivePreview, type ElementPreview} from './useLivePreview';

const payload: ElementPreview = {
  targets: [
    {label: 'Entry page', url: '/live-preview/entry', refresh: true},
    {label: 'Listing', url: '/live-preview/listing', refresh: true},
  ],
  createTokenUrl: '/actions/preview/create-token',
  tokenParams: {canonicalId: 1, siteId: 1, previewToken: 'encrypted'},
  token: 'token',
  tokenDuration: 86400,
  tokenParam: 'token',
  paramValue: 'param',
  siteTokenParam: 'siteToken',
  siteToken: null,
  iframeResizerOptions: false,
};

let app: App | undefined;
let host: HTMLElement | undefined;

beforeEach(async () => {
  localStorage.clear();
  await page.viewport(1440, 900);
});

afterEach(() => {
  closeAllSlideouts();
  app?.unmount();
  host?.remove();
});

/** An element editor's page as far as Live Preview sees it. */
function mount() {
  let controller!: ReturnType<typeof useLivePreview>;

  app = createApp(
    defineComponent({
      setup() {
        controller = useLivePreview(
          () => payload,
          {save: async () => {}, hasPendingChanges: false},
          {post: async () => ({data: {}})}
        );
        const pane = useTemplateRef<InstanceType<typeof LivePreview>>('pane');

        return () => [
          h(
            'button',
            {type: 'button', onClick: () => controller.open()},
            'Preview'
          ),
          // The editor's content moves into the pane, as ElementEditor's does.
          h(
            Teleport,
            {
              to: pane.value?.editorTarget ?? null,
              disabled: !pane.value?.holdsEditor || !pane.value?.editorTarget,
              defer: true,
            },
            h('div', [
              h('label', {for: 'title'}, 'Title'),
              h('input', {
                id: 'title',
                // Stands in for an autosave landing.
                onChange: () => void controller.refresh(),
              }),
              h(
                'button',
                {
                  type: 'button',
                  onClick: () =>
                    openSlideoutWith(
                      defineComponent({
                        render: () => h('p', 'Related entry'),
                      })
                    ),
                },
                'Edit related entry'
              ),
            ])
          ),
          h(LivePreview, {ref: 'pane', controller, title: 'Kitchen sink'}),
          h(SlideoutHost, {assetVersion: 'test'}),
        ];
      },
    })
  );

  host = document.createElement('div');
  document.body.append(host);
  app.mount(host);

  return {controller};
}

const overlay = () => document.querySelector<HTMLElement>('.live-preview')!;
const readyFrame = () =>
  document.querySelector<HTMLIFrameElement>('iframe.lp-frame--ready');
const frameSize = () => {
  const viewport = document.querySelector<HTMLElement>('.lp-device__viewport')!;

  return {
    width: viewport.style.getPropertyValue('--lp-frame-width'),
    height: viewport.style.getPropertyValue('--lp-frame-height'),
  };
};

async function waitForFrame(previous: HTMLIFrameElement | null = null) {
  await expect
    .poll(() => {
      const frame = readyFrame();
      return frame !== null && frame !== previous;
    })
    .toBe(true);

  return readyFrame()!;
}

it('tours Live Preview: devices, targets, and a slideout in the editor', async () => {
  const {controller} = mount();

  // Opening it slides in the editor beside the previewed page.
  await page.getByRole('button', {name: 'Preview'}).click();
  await expect
    .element(page.getByRole('button', {name: 'Close Preview'}))
    .toBeVisible();
  let frame = await waitForFrame();
  expect(frame.src).toContain('/live-preview/entry');
  expect(frame.src).toContain('token=token');
  await expect.element(page.getByLabelText('Title')).toBeVisible();

  // An edit reloads the page behind the current one, then swaps it in.
  await userEvent.fill(page.getByLabelText('Title').element(), 'Edited');
  await userEvent.keyboard('{Tab}');
  frame = await waitForFrame(frame);

  // Tablet, rotated, then phone, rotated: the page is rendered at the device's size.
  await page.getByRole('button', {name: 'Tablet', exact: true}).click();
  await expect.poll(frameSize).toEqual({
    width: `${DEVICES.tablet.width}px`,
    height: `${DEVICES.tablet.height}px`,
  });
  await page.getByRole('button', {name: 'Rotate'}).click();
  await expect.poll(frameSize).toEqual({
    width: `${DEVICES.tablet.height}px`,
    height: `${DEVICES.tablet.width}px`,
  });
  await page.getByRole('button', {name: 'Mobile', exact: true}).click();
  await expect.poll(frameSize).toEqual({
    width: `${DEVICES.phone.height}px`,
    height: `${DEVICES.phone.width}px`,
  });
  await page.getByRole('button', {name: 'Rotate'}).click();
  await expect.poll(frameSize).toEqual({
    width: `${DEVICES.phone.width}px`,
    height: `${DEVICES.phone.height}px`,
  });
  await page.getByRole('button', {name: 'Desktop', exact: true}).click();
  await expect.poll(() => frameSize().width).toBe('');
  expect(controller.device.value).toBe('desktop');

  // Another target loads from the top.
  await page.getByRole('button', {name: 'Entry page'}).click();
  await page.getByRole('button', {name: 'Listing'}).click();
  frame = await waitForFrame(frame);
  expect(frame.src).toContain('/live-preview/listing');
  await page.getByRole('button', {name: 'Listing'}).click();
  await page.getByRole('button', {name: 'Entry page'}).click();
  frame = await waitForFrame(frame);
  expect(frame.src).toContain('/live-preview/entry');

  // A slideout opened from the editor covers only the editor's pane.
  await page.getByRole('button', {name: 'Edit related entry'}).click();
  await expect
    .element(page.getByText('Related entry', {exact: true}))
    .toBeVisible();
  const panel = document.querySelector<HTMLElement>('.slideout-panel')!;
  expect(panel.classList).toContain('slideout-panel--in-preview');
  const editor = document.querySelector('.lp-editor')!.getBoundingClientRect();
  await expect
    .poll(() => panel.getBoundingClientRect().right)
    .toBeLessThanOrEqual(editor.right);
  expect(
    document.querySelector('.cp-slideout-shade')!.getBoundingClientRect().width
  ).toBeLessThanOrEqual(editor.width);

  // Escape closes the slideout, not the preview.
  await userEvent.keyboard('{Escape}');
  await expect.poll(() => document.querySelector('.slideout-panel')).toBeNull();
  expect(controller.isOpen.value).toBe(true);

  // Closing slides it out and hands the editor's content back to the page.
  await page.getByRole('button', {name: 'Close Preview'}).click();
  await expect.poll(() => getComputedStyle(overlay()).display).toBe('none');
  await nextTick();
  expect(host!.querySelector('#title')).not.toBeNull();
});

it.each([false, true])(
  'mirrors the Rotate icon for right-to-left (%s)',
  async (rtl) => {
    document.documentElement.dir = rtl ? 'rtl' : '';
    mount();

    await page.getByRole('button', {name: 'Preview'}).click();
    await waitForFrame();
    await page.getByRole('button', {name: 'Tablet', exact: true}).click();
    const button = document.querySelector<HTMLElement>('.lp-rotate')!;

    expect(
      getComputedStyle(button.shadowRoot!.querySelector('[part="prefix"]')!)
        .scale
    ).toBe(rtl ? '-1 1' : 'none');

    document.documentElement.dir = '';
  }
);

it('centers a device that’s wider than the stage', async () => {
  mount();

  await page.getByRole('button', {name: 'Preview'}).click();
  await waitForFrame();
  await page.getByRole('button', {name: 'Tablet', exact: true}).click();
  await page.getByRole('button', {name: 'Rotate'}).click();
  await expect.poll(frameSize).toEqual({
    width: `${DEVICES.tablet.height}px`,
    height: `${DEVICES.tablet.width}px`,
  });

  const center = (selector: string) => {
    const rect = document.querySelector(selector)!.getBoundingClientRect();
    return {x: rect.x + rect.width / 2, y: rect.y + rect.height / 2};
  };
  const stage = document.querySelector('.lp-preview__stage')!;
  const device = document.querySelector<HTMLElement>('.lp-device')!;
  expect(device.offsetWidth).toBeGreaterThan(stage.clientWidth);

  await expect
    .poll(() =>
      Math.abs(center('.lp-device').x - center('.lp-preview__stage').x)
    )
    .toBeLessThan(1);
  expect(
    Math.abs(center('.lp-device').y - center('.lp-preview__stage').y)
  ).toBeLessThan(1);
});

it('moves the editor in once the pane has slid in, and back once it has slid out', async () => {
  mount();
  const title = () => document.getElementById('title');
  const spinner = () => document.querySelector('.lp-editor__loading');

  await page.getByRole('button', {name: 'Preview'}).click();
  expect(host!.contains(title())).toBe(true);
  expect(spinner()).not.toBeNull();

  await expect
    .poll(() => document.querySelector('.lp-editor')!.contains(title()))
    .toBe(true);
  expect(spinner()).toBeNull();

  await page.getByRole('button', {name: 'Close Preview'}).click();
  expect(document.querySelector('.lp-editor')!.contains(title())).toBe(true);

  await expect.poll(() => host!.contains(title())).toBe(true);
});

it('lets go of the page when it goes away while open', async () => {
  mount();
  await page.getByRole('button', {name: 'Preview'}).click();
  await expect
    .poll(() => document.documentElement.classList.contains('lp-open'))
    .toBe(true);

  app!.unmount();
  app = undefined;

  expect(document.documentElement.classList.contains('lp-open')).toBe(false);
  expect(host!.closest('[aria-hidden="true"]')).toBeNull();
});
