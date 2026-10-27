import {createApp, defineComponent, nextTick, ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {useLivePreview, type ElementPreview} from './useLivePreview';

vi.mock('@craftcms/ui', () => ({actionClient: {post: vi.fn()}}));

function payload(overrides: Partial<ElementPreview> = {}): ElementPreview {
  return {
    targets: [
      {label: 'Entry page', url: 'https://site.test/a', refresh: true},
      {label: 'Listing', url: 'https://site.test/list', refresh: false},
    ],
    createTokenUrl: '/actions/preview/create-token',
    tokenParams: {canonicalId: 1, siteId: 1, previewToken: 'enc-1'},
    token: 'token-1',
    tokenParam: 'token',
    paramValue: 'param-1',
    siteTokenParam: 'siteToken',
    siteToken: null,
    iframeResizerOptions: false,
    ...overrides,
  };
}

describe('useLivePreview', () => {
  let app: ReturnType<typeof createApp> | undefined;
  const post = vi.fn();
  const save = vi.fn();
  const data = ref<ElementPreview | null>(null);
  let pending = false;

  beforeEach(() => {
    localStorage.clear();
    post.mockReset().mockResolvedValue({data: {token: 'token-1'}});
    save.mockReset().mockResolvedValue(undefined);
    data.value = payload();
    pending = false;
  });

  afterEach(() => app?.unmount());

  function mount() {
    let preview!: ReturnType<typeof useLivePreview>;

    app = createApp(
      defineComponent({
        setup() {
          preview = useLivePreview(
            () => data.value,
            {
              save,
              get hasPendingChanges() {
                return pending;
              },
            },
            {post}
          );

          return () => null;
        },
      })
    );
    app.mount(document.createElement('div'));

    return preview;
  }

  function frameUrl(preview: ReturnType<typeof useLivePreview>): URL {
    return new URL(preview.frames.value.at(-1)!.url);
  }

  it('saves pending edits, creates the token, then loads the first target', async () => {
    pending = true;
    const preview = mount();

    await preview.open();

    expect(save).toHaveBeenCalledOnce();
    expect(post).toHaveBeenCalledWith('/actions/preview/create-token', {
      canonicalId: 1,
      siteId: 1,
      previewToken: 'enc-1',
    });
    expect(preview.isOpen.value).toBe(true);

    const url = frameUrl(preview);
    expect(url.pathname).toBe('/a');
    expect(url.searchParams.get('token')).toBe('token-1');
    expect(url.searchParams.get('x-craft-live-preview')).toBe('param-1');
  });

  it('reloads auto-refreshing targets, and others only when asked', async () => {
    const preview = mount();
    await preview.open();
    const newestId = () => preview.frames.value.at(-1)!.id;

    let id = newestId();
    await preview.refresh();
    expect(newestId()).toBeGreaterThan(id);

    preview.switchTarget(1);
    await vi.waitFor(() => expect(frameUrl(preview).pathname).toBe('/list'));

    id = newestId();
    await preview.refresh();
    expect(newestId()).toBe(id);

    await preview.refresh({reload: true});
    expect(newestId()).toBeGreaterThan(id);
  });

  it('keeps one frame once the newest has loaded', async () => {
    const preview = mount();
    await preview.open();
    await preview.refresh();

    const newest = preview.frames.value.at(-1)!;
    preview.frameLoaded(newest.id, document.createElement('iframe'));

    expect(preview.frames.value).toEqual([{...newest, ready: true}]);
  });

  it('keeps its token while the element it points at stays the same', async () => {
    const preview = mount();
    await preview.open();

    data.value = payload({
      token: 'token-2',
      paramValue: 'param-2',
      tokenParams: {canonicalId: 1, siteId: 1, previewToken: 'enc-2'},
    });
    await nextTick();
    await preview.refresh();

    expect(post).toHaveBeenCalledOnce();
    expect(frameUrl(preview).searchParams.get('token')).toBe('token-1');
    expect(frameUrl(preview).searchParams.get('x-craft-live-preview')).toBe(
      'param-2'
    );
  });

  it('creates a new token and reloads when the element changes', async () => {
    const preview = mount();
    await preview.open();

    data.value = payload({
      token: 'token-2',
      tokenParams: {
        canonicalId: 1,
        siteId: 1,
        draftId: 9,
        previewToken: 'enc-2',
      },
    });
    await vi.waitFor(() =>
      expect(frameUrl(preview).searchParams.get('token')).toBe('token-2')
    );

    expect(post).toHaveBeenCalledTimes(2);
  });

  it('closes after saving pending edits', async () => {
    const preview = mount();
    await preview.open();
    pending = true;

    await preview.close();

    expect(save).toHaveBeenCalledOnce();
    expect(preview.isOpen.value).toBe(false);
    expect(preview.frames.value).toEqual([]);
  });

  it('is loading from a refresh until its frame has loaded', async () => {
    const preview = mount();
    expect(preview.isLoading.value).toBe(false);

    const opening = preview.open();
    await nextTick();
    expect(preview.isLoading.value).toBe(true);

    await opening;
    expect(preview.isLoading.value).toBe(true);

    const frame = preview.frames.value.at(-1)!;
    preview.frameLoaded(frame.id, document.createElement('iframe'));
    expect(preview.isLoading.value).toBe(false);
  });

  it('stops loading when the token could not be created', async () => {
    post.mockRejectedValueOnce(new Error('Nope'));
    const preview = mount();

    await preview.open();

    expect(preview.isLoading.value).toBe(false);
  });

  it('reports a token that could not be created', async () => {
    post.mockRejectedValueOnce(new Error('Nope'));
    const preview = mount();

    await preview.open();

    expect(preview.error.value).toBe('Nope');
    expect(preview.frames.value).toEqual([]);

    await preview.refresh({reload: true});
    expect(preview.error.value).toBe(null);
    expect(preview.frames.value).toHaveLength(1);
  });

  describe('events', () => {
    function record(preview: ReturnType<typeof useLivePreview>) {
      const log: Array<[string, unknown]> = [];
      for (const event of [
        'before-open',
        'open',
        'before-close',
        'close',
        'switch-target',
        'before-update-iframe',
        'after-save-draft',
      ] as const) {
        preview.on(event, (payload: unknown) => log.push([event, payload]));
      }
      return log;
    }

    it('announces opening and closing around the change', async () => {
      const preview = mount();
      const log = record(preview);
      preview.on('open', () => log.push(['isOpen', preview.isOpen.value]));

      await preview.open();
      await preview.close();

      expect(log.map(([event]) => event)).toEqual([
        'before-open',
        'open',
        'isOpen',
        'before-update-iframe',
        'before-close',
        'close',
      ]);
      expect(log[2]![1]).toBe(true);
    });

    it('says whether each update will reload the frame', async () => {
      const preview = mount();
      await preview.open();
      const log = record(preview);

      await preview.refresh();
      preview.switchTarget(1);
      await vi.waitFor(() => expect(frameUrl(preview).pathname).toBe('/list'));
      await preview.refresh();
      await preview.refresh({reload: true});

      expect(
        log
          .filter(([event]) => event === 'before-update-iframe')
          .map(([, payload]) => payload)
      ).toEqual([
        {target: data.value!.targets[0], resetScroll: false, refresh: true},
        {target: data.value!.targets[1], resetScroll: true, refresh: true},
        {target: data.value!.targets[1], resetScroll: false, refresh: false},
        {target: data.value!.targets[1], resetScroll: false, refresh: true},
      ]);
    });

    it('keeps the frame when told not to reload', async () => {
      const preview = mount();
      await preview.open();
      const id = preview.frames.value.at(-1)!.id;

      await preview.refresh({reload: false});

      expect(preview.frames.value.at(-1)!.id).toBe(id);
    });

    it('names the target it switched to', async () => {
      const preview = mount();
      await preview.open();
      const log = record(preview);

      preview.switchTarget(1);

      expect(log[0]).toEqual([
        'switch-target',
        {target: data.value!.targets[1]},
      ]);
    });

    it('passes on autosaved drafts', () => {
      const preview = mount();
      const log = record(preview);

      preview.draftSaved({draftId: 5});

      expect(log).toEqual([['after-save-draft', {data: {draftId: 5}}]]);
    });

    it('stops telling a listener once it has unsubscribed', async () => {
      const preview = mount();
      const listener = vi.fn();
      preview.on('before-open', listener)();

      await preview.open();

      expect(listener).not.toHaveBeenCalled();
    });
  });
});
