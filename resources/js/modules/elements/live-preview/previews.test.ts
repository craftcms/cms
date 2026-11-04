import {createApp, defineComponent, nextTick, shallowRef} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  getActivePreview,
  getPreviews,
  onPreviewEvent,
  refreshPreviews,
  watchPreviews,
} from './previews';
import {useLivePreview, type ElementPreview} from './useLivePreview';

vi.mock('@craftcms/ui', () => ({actionClient: {post: vi.fn()}}));

const payload: ElementPreview = {
  targets: [{label: 'Entry page', url: 'https://site.test/a', refresh: true}],
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

describe('previews', () => {
  const apps: Array<ReturnType<typeof createApp>> = [];

  afterEach(() => {
    apps.splice(0).forEach((app) => app.unmount());
  });

  function mount(data: () => ElementPreview | null = () => payload) {
    let preview!: ReturnType<typeof useLivePreview>;
    const app = createApp(
      defineComponent({
        setup() {
          preview = useLivePreview(
            data,
            {save: async () => {}, hasPendingChanges: false},
            {post: async () => ({data: {token: 'token'}})}
          );
          return () => null;
        },
      })
    );
    app.mount(document.createElement('div'));
    apps.push(app);

    return {preview, unmount: () => app.unmount()};
  }

  it('lists the page’s previews until their editors go away', () => {
    const first = mount();
    const second = mount();

    expect(getPreviews()).toEqual([first.preview, second.preview]);

    first.unmount();

    expect(getPreviews()).toEqual([second.preview]);
  });

  it('leaves out editors with nothing to preview until they have something', async () => {
    const data = shallowRef<ElementPreview | null>(null);
    const {preview} = mount(() => data.value);

    expect(getPreviews()).toEqual([]);

    data.value = payload;
    await nextTick();

    expect(getPreviews()).toEqual([preview]);
  });

  it('finds the open preview', async () => {
    mount();
    const {preview} = mount();
    expect(getActivePreview()).toBeNull();

    await preview.open();

    expect(getActivePreview()).toBe(preview);
  });

  it.each([
    ['when asked', () => refreshPreviews()],
    [
      'when the legacy bundles ask',
      () => window.dispatchEvent(new CustomEvent('craft:refresh-previews')),
    ],
  ])('reloads open previews %s', async (_, ask) => {
    const {preview} = mount();
    await preview.open();
    const id = preview.frames.value.at(-1)!.id;

    ask();

    await vi.waitFor(() =>
      expect(preview.frames.value.at(-1)!.id).toBeGreaterThan(id)
    );
  });

  it('tells listeners about events from every preview, with the preview', async () => {
    const listener = vi.fn();
    const stop = onPreviewEvent('open', listener);
    const {preview} = mount();

    await preview.open();
    stop();

    expect(listener).toHaveBeenCalledWith(undefined, preview);
  });

  it('watches previews already on the page and ones added later', () => {
    const {preview: existing} = mount();
    const removed = vi.fn();
    const seen: unknown[] = [];
    const stop = watchPreviews((preview) => {
      seen.push(preview);
      return () => removed(preview);
    });

    const added = mount();
    added.unmount();
    stop();
    mount();

    expect(seen).toEqual([existing, added.preview]);
    expect(removed).toHaveBeenCalledExactlyOnceWith(added.preview);
  });
});
