import jquery from 'jquery';
import {Garnish} from '@craftcms/garnish';
import {createApp, defineComponent, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  useLivePreview,
  type ElementPreview,
} from '@/modules/elements/live-preview/useLivePreview';
import {LegacyPreview, mirrorPreviews} from './legacy-preview';

vi.mock('@craftcms/ui', () => ({actionClient: {post: vi.fn()}}));

const payload: ElementPreview = {
  targets: [
    {label: 'Entry page', url: 'https://site.test/a', refresh: true},
    {label: 'Listing', url: 'https://site.test/list', refresh: false},
  ],
  createTokenUrl: '/actions/preview/create-token',
  tokenParams: {canonicalId: 1, siteId: 1, previewToken: 'enc'},
  token: 'token',
  tokenDuration: 86400,
  tokenParam: 'token',
  paramValue: 'param',
  siteTokenParam: 'siteToken',
  siteToken: null,
  iframeResizerOptions: false,
};

describe('Craft.Preview', () => {
  const apps: Array<ReturnType<typeof createApp>> = [];
  let stopMirroring: () => void;
  const getCpUrl = vi.fn(
    (path: string, params: Record<string, unknown>) =>
      `/admin/${path}?${new URLSearchParams(params as Record<string, string>)}`
  );

  beforeEach(() => {
    vi.stubGlobal('$', jquery);
    vi.stubGlobal('Craft', {getCpUrl});
    stopMirroring = mirrorPreviews();
  });

  afterEach(() => {
    apps.splice(0).forEach((app) => app.unmount());
    stopMirroring();
    vi.unstubAllGlobals();
  });

  function mount() {
    let controller!: ReturnType<typeof useLivePreview>;
    const app = createApp(
      defineComponent({
        setup() {
          controller = useLivePreview(
            () => payload,
            {save: async () => {}, hasPendingChanges: false},
            {post: async () => ({data: {token: 'token'}})}
          );
          return () => null;
        },
      })
    );
    app.mount(document.createElement('div'));
    apps.push(app);
    const preview = LegacyPreview.instances.at(-1)!;

    return {controller, preview, unmount: () => app.unmount()};
  }

  function record(preview: LegacyPreview): Array<[string, unknown]> {
    const log: Array<[string, unknown]> = [];
    preview.on(
      'beforeOpen open slideIn beforeClose close slideOut switchTarget beforeUpdateIframe afterSaveDraft',
      (event) => {
        // `data` is Garnish's own, the listener's registration data.
        const {
          type,
          target: _,
          data: __,
          ...payload
        } = event as Record<string, unknown>;
        log.push([type as string, payload]);
      }
    );
    return log;
  }

  it('mirrors each preview on the page until its editor goes away', () => {
    const first = mount();
    const second = mount();

    expect(LegacyPreview.instances).toEqual([first.preview, second.preview]);

    first.unmount();

    expect(LegacyPreview.instances).toEqual([second.preview]);
  });

  it('reports the open preview', async () => {
    const {preview} = mount();
    expect(LegacyPreview.getActive()).toBeUndefined();

    await preview.open();

    expect(LegacyPreview.getActive()).toBe(preview);
    expect(preview.isActive).toBe(true);
    expect(preview.isVisible).toBe(true);
  });

  it('fires Craft 5’s events around opening and closing', async () => {
    const {preview} = mount();
    const log = record(preview);

    await preview.open();
    await preview.close();

    expect(log.map(([type]) => type)).toEqual([
      'beforeOpen',
      'slideIn',
      'open',
      'beforeUpdateIframe',
      'beforeClose',
      'close',
      'slideOut',
    ]);
  });

  it('tells class-level listeners too', async () => {
    const listener = vi.fn();
    Garnish.on(LegacyPreview, 'open', listener);
    const {preview} = mount();

    await preview.open();
    Garnish.off(LegacyPreview, 'open', listener);

    expect(listener).toHaveBeenCalledOnce();
    expect(listener.mock.calls[0]![0]).toMatchObject({target: preview});
  });

  it('tells listeners added with the legacy Garnish.on() too', async () => {
    const handler = vi.fn();
    vi.stubGlobal('Garnish', {
      _eventHandlers: [
        {target: LegacyPreview, type: 'open', data: {from: 'plugin'}, handler},
        {target: Date, type: 'open', data: {}, handler},
      ],
    });
    const {preview} = mount();

    await preview.open();

    expect(handler).toHaveBeenCalledExactlyOnceWith({
      data: {from: 'plugin'},
      type: 'open',
      target: preview,
    });
  });

  it('passes targets, scroll and refresh in Craft 5’s payload shape', async () => {
    const {preview} = mount();
    await preview.open();
    const log = record(preview);

    preview.switchTarget(1);
    await nextTick();

    expect(log).toEqual([
      ['switchTarget', {previewTarget: payload.targets[1]}],
      [
        'beforeUpdateIframe',
        {previewTarget: payload.targets[1], resetScroll: true, refresh: true},
      ],
    ]);
    expect(preview.activeTarget).toBe(1);
  });

  it('hands over the new frame wrapped in jQuery', async () => {
    const {controller, preview} = mount();
    const listener = vi.fn();
    preview.on('afterUpdateIframe', listener);
    const iframe = document.createElement('iframe');

    controller.emit('after-update-iframe', {
      target: payload.targets[0]!,
      iframe,
    });

    expect(listener.mock.calls[0]![0].previewTarget).toBe(payload.targets[0]);
    expect(listener.mock.calls[0]![0].$iframe[0]).toBe(iframe);
  });

  it('passes autosaved drafts on as a response', () => {
    const {controller, preview} = mount();
    const log = record(preview);

    controller.draftSaved({draftId: 7});

    expect(log).toEqual([['afterSaveDraft', {response: {data: {draftId: 7}}}]]);
  });

  it('reloads the frame or keeps it, as updateIframe asks', async () => {
    const {controller, preview} = mount();
    expect(preview.updateIframe()).toBe(false);

    await preview.open();
    const newestId = () => controller.frames.value.at(-1)!.id;
    let id = newestId();

    preview.updateIframe(false, false);
    await vi.waitFor(() => expect(newestId()).toBe(id));

    preview.updateIframe(true);
    await vi.waitFor(() => expect(newestId()).toBeGreaterThan(id));
    id = newestId();

    preview.updateIframe();
    await vi.waitFor(() => expect(newestId()).toBeGreaterThan(id));
  });

  it('refreshes every preview', async () => {
    const {controller, preview} = mount();
    await preview.open();
    const id = controller.frames.value.at(-1)!.id;

    LegacyPreview.refresh();

    await vi.waitFor(() =>
      expect(controller.frames.value.at(-1)!.id).toBeGreaterThan(id)
    );
  });

  it('switches devices and orientation', () => {
    const {controller, preview} = mount();
    controller.device.value = 'desktop';
    controller.orientation.value = 'portrait';

    expect(preview.switchOrientation()).toBe(false);

    preview.switchDeviceType('tablet');
    preview.switchOrientation();
    expect([preview.currentDeviceType, preview.deviceOrientation]).toEqual([
      'tablet',
      'landscape',
    ]);

    preview.switchDeviceType(jquery('<button>').data('deviceType', 'phone'));
    expect(preview.currentDeviceType).toBe('phone');
  });

  it('opens the standalone preview page when constructed', async () => {
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    const location = {href: ''};
    vi.stubGlobal('location', location);
    const onBeforeLoad = vi.fn(async () => {});

    const preview = new LegacyPreview({
      elementId: 12,
      siteId: 2,
      draftId: null,
      onBeforeLoad,
    });
    await preview.open();

    expect(onBeforeLoad).toHaveBeenCalledOnce();
    expect(location.href).toBe('/admin/preview/12?siteId=2');
    expect(LegacyPreview.instances).not.toContain(preview);
  });
});
