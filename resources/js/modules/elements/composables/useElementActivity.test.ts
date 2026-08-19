import {createApp, defineComponent, ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {useElementActivity} from './useElementActivity';

const {postSpy} = vi.hoisted(() => ({postSpy: vi.fn()}));

vi.mock('@craftcms/ui', () => ({
  actionClient: {post: postSpy},
}));

describe('useElementActivity', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;

  /** What the canonical element reads as, before anyone touches anything. */
  const canonicalStamp = 1000;

  beforeEach(() => {
    postSpy.mockReset();
    postSpy.mockResolvedValue({
      data: {
        activity: [],
        updatedTimestamp: canonicalStamp,
        canonicalUpdatedTimestamp: canonicalStamp,
      },
    });
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  function mount(overrides: Record<string, any> = {}) {
    let activity!: ReturnType<typeof useElementActivity>;

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(
      defineComponent({
        setup() {
          activity = useElementActivity({
            url: '/actions/elements/recent-activity',
            elementType: 'craft\\elements\\Entry',
            elementId: 12,
            draftId: () => null,
            siteId: 1,
            isProvisionalDraft: () => false,
            updatedTimestamps: {
              element: canonicalStamp,
              canonical: canonicalStamp,
            },
            ...overrides,
          });

          return () => null;
        },
      })
    );
    app.mount(container);

    return activity;
  }

  it('reports a change made elsewhere', async () => {
    const activity = mount();

    postSpy.mockResolvedValue({
      data: {
        activity: [],
        updatedTimestamp: canonicalStamp + 60,
        canonicalUpdatedTimestamp: canonicalStamp + 60,
      },
    });

    await activity.poll();

    expect(activity.isStale.value).toBe(true);
    expect(activity.staleType.value).toBe('element');
  });

  it('stays quiet while nothing changes', async () => {
    const activity = mount();

    await activity.poll();

    expect(activity.isStale.value).toBe(false);
  });

  /**
   * The regression: autosave creates a provisional draft partway through a
   * session and re-baselines against *its* timestamp. A poll that kept asking
   * about the canonical would compare the two and report a change the user
   * made themselves.
   */
  it('follows the provisional draft autosave creates', async () => {
    const draftId = ref<number | null>(null);
    const draftStamp = canonicalStamp + 30;

    const activity = mount({
      draftId: () => draftId.value,
      isProvisionalDraft: () => draftId.value !== null,
    });

    // Autosave creates the draft and hands back its stamps. The save reports
    // the same instant the poll reads back, so the stamp carries over as is.
    draftId.value = 99;
    activity.rebase({element: draftStamp, canonical: canonicalStamp});

    postSpy.mockImplementation(
      (_url: string, payload: Record<string, any>) => ({
        data: {
          activity: [],
          // The server answers for whichever element the poll identified.
          updatedTimestamp:
            payload.draftId === 99 ? draftStamp : canonicalStamp,
          canonicalUpdatedTimestamp: canonicalStamp,
        },
      })
    );

    await activity.poll();

    expect(postSpy).toHaveBeenLastCalledWith(
      '/actions/elements/recent-activity',
      expect.objectContaining({draftId: 99, provisional: 1})
    );
    expect(activity.isStale.value).toBe(false);
  });

  it('reports a change to the draft made elsewhere', async () => {
    const draftStamp = canonicalStamp + 30;
    const activity = mount({
      draftId: () => 99,
      isProvisionalDraft: () => false,
    });

    activity.rebase({element: draftStamp, canonical: canonicalStamp});

    postSpy.mockResolvedValue({
      data: {
        activity: [],
        updatedTimestamp: draftStamp + 60,
        canonicalUpdatedTimestamp: canonicalStamp,
      },
    });

    await activity.poll();

    expect(activity.isStale.value).toBe(true);
    expect(activity.staleType.value).toBe('element');
  });

  it('still reports an upstream change to the canonical while on a draft', async () => {
    const draftStamp = canonicalStamp + 30;
    const activity = mount({
      draftId: () => 99,
      isProvisionalDraft: () => true,
    });

    activity.rebase({element: draftStamp, canonical: canonicalStamp});

    postSpy.mockResolvedValue({
      data: {
        activity: [],
        updatedTimestamp: draftStamp,
        canonicalUpdatedTimestamp: canonicalStamp + 120,
      },
    });

    await activity.poll();

    expect(activity.isStale.value).toBe(true);
    expect(activity.staleType.value).toBe('canonical');
  });

  /**
   * A poll that was already in flight when the screen saved describes the
   * state before that save, so its stamps would read as someone else's change.
   */
  it('discards a poll that was in flight when the screen saved', async () => {
    const activity = mount({draftId: () => 99});
    const draftStamp = canonicalStamp + 30;

    let release!: () => void;
    postSpy.mockReturnValue(
      new Promise((resolve) => {
        release = () =>
          resolve({
            data: {
              activity: [],
              // Pre-save state, which no longer matches the saved element.
              updatedTimestamp: canonicalStamp,
              canonicalUpdatedTimestamp: canonicalStamp,
            },
          });
      })
    );

    const inFlight = activity.poll();

    activity.rebase({element: draftStamp, canonical: canonicalStamp});

    release();
    await inFlight;

    expect(activity.isStale.value).toBe(false);

    // …and the discarded response didn't become the baseline either.
    postSpy.mockResolvedValue({
      data: {
        activity: [],
        updatedTimestamp: draftStamp,
        canonicalUpdatedTimestamp: canonicalStamp,
      },
    });

    await activity.poll();

    expect(activity.isStale.value).toBe(false);
  });
});
