import {useForm, type InertiaForm} from '@inertiajs/vue3';
import {createApp, defineComponent, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  useElementAutosave,
  type ElementAutosaveOptions,
} from './useElementAutosave';

interface AutosaveForm {
  title: string;
}

type AutosaveOverrides = Partial<ElementAutosaveOptions>;

const postSpy = vi.fn();

describe('useElementAutosave', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;

  beforeEach(() => {
    postSpy.mockReset();
    postSpy.mockResolvedValue({data: {draftId: 7, timestamp: 'at 9:00 AM'}});
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  function mount(overrides: AutosaveOverrides = {}) {
    let autosave!: ReturnType<typeof useElementAutosave>;
    let form!: InertiaForm<AutosaveForm>;

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(
      defineComponent({
        setup() {
          form = useForm<AutosaveForm>({title: 'Original'});
          autosave = useElementAutosave<AutosaveForm>(
            form,
            {
              url: '/actions/elements/save-draft',
              elementType: 'craft\\elements\\Entry',
              elementId: 12,
              siteId: 1,
              draftId: null,
              isProvisional: false,
              enabled: true,
              ...overrides,
            },
            {post: postSpy}
          );

          return () => null;
        },
      })
    );
    app.mount(container);

    return {autosave, form};
  }

  it('creates the draft on the first save and targets it afterwards', async () => {
    const {autosave} = mount();

    await autosave.save();

    expect(postSpy).toHaveBeenCalledTimes(1);
    const firstRequest = postSpy.mock.calls[0];
    if (!firstRequest) throw new Error('Expected the first autosave request.');
    expect(firstRequest[1]).toMatchObject({
      elementType: 'craft\\elements\\Entry',
      elementId: 12,
      siteId: 1,
      provisional: 1,
    });
    expect(firstRequest[1].draftId).toBeUndefined();
    expect(autosave.draftId.value).toBe(7);

    await autosave.save();

    // The second save targets the existing draft; `provisional` is dropped
    // because sending it would narrow the server's lookup.
    const secondRequest = postSpy.mock.calls[1];
    if (!secondRequest)
      throw new Error('Expected the second autosave request.');
    expect(secondRequest[1]).toMatchObject({draftId: 7});
    expect(secondRequest[1].provisional).toBeUndefined();
  });

  it('reports status and the saved timestamp', async () => {
    const {autosave} = mount();

    expect(autosave.status.value).toBe('idle');

    await autosave.save();

    expect(autosave.status.value).toBe('saved');
    expect(autosave.savedAt.value).toBe('at 9:00 AM');
  });

  it('records a failure without throwing', async () => {
    postSpy.mockRejectedValue({
      isAxiosError: true,
      response: {data: {message: 'Nope.'}},
    });

    const {autosave} = mount();

    await autosave.save();

    expect(autosave.status.value).toBe('failed');
    expect(autosave.error.value).toBe('Nope.');
  });

  it('does nothing when disabled', async () => {
    const {autosave} = mount({enabled: false});

    await autosave.save();

    expect(postSpy).not.toHaveBeenCalled();
  });

  it('defers to a real submission in progress', async () => {
    const {autosave, form} = mount();

    form.processing = true;
    await autosave.save();

    expect(postSpy).not.toHaveBeenCalled();
  });

  it('coalesces saves requested while one is in flight', async () => {
    const release: Array<() => void> = [];
    postSpy.mockImplementation(
      () =>
        new Promise((resolve) => {
          release.push(() =>
            resolve({data: {draftId: 7, timestamp: 'at 9:00 AM'}})
          );
        })
    );

    const {autosave} = mount();

    const first = autosave.save();
    void autosave.save();
    void autosave.save();

    expect(postSpy).toHaveBeenCalledTimes(1);

    release[0]!();
    await nextTick();
    await nextTick();

    // Both queued requests collapse into a single trailing save.
    expect(postSpy).toHaveBeenCalledTimes(2);

    release[1]!();
    await first;

    expect(postSpy).toHaveBeenCalledTimes(2);
  });

  /**
   * A submission re-seeds the renderers with what it saved, and reconciling
   * emits mutations like any other write — but they land while the visit is
   * still in flight, and arming for them would rebuild the draft an applied
   * save just consumed.
   */
  it('skips scheduling while a real submission is in flight', async () => {
    const {autosave, form} = mount({debounceMs: 5});

    form.processing = true;
    autosave.schedule();
    form.processing = false;

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(postSpy).not.toHaveBeenCalled();
  });

  it('skips scheduling while suspended', async () => {
    const {autosave} = mount();

    autosave.suspend(() => autosave.schedule());
    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(postSpy).not.toHaveBeenCalled();
  });

  it('keeps sending provisional while editing a provisional draft', async () => {
    const {autosave} = mount({draftId: 7, isProvisional: true});

    await autosave.save();

    expect(postSpy.mock.calls[0]![1]).toMatchObject({
      draftId: 7,
      provisional: 1,
    });
  });

  it('adopts a draft id supplied by the server', async () => {
    const {autosave} = mount();

    autosave.setDraftId(99);
    await autosave.save();

    expect(postSpy.mock.calls[0]![1]).toMatchObject({draftId: 99});
  });

  // An autosave moves the element's `dateUpdated`. Without handing the new
  // stamps back, the activity poller reads our own write as an edit from
  // elsewhere and shows "This entry has been updated."
  it('reports the new timestamps so the caller can re-baseline', async () => {
    postSpy.mockResolvedValue({
      data: {
        draftId: 7,
        timestamp: 'at 9:00 AM',
        updatedTimestamp: 1_700_000_100,
        canonicalUpdatedTimestamp: 1_700_000_000,
      },
    });
    const onSaved = vi.fn();
    const {autosave} = mount({onSaved});

    await autosave.save();

    expect(onSaved).toHaveBeenCalledWith({
      element: 1_700_000_100,
      canonical: 1_700_000_000,
    });
  });

  it('reports nulls when the response omits timestamps', async () => {
    const onSaved = vi.fn();
    const {autosave} = mount({onSaved});

    await autosave.save();

    expect(onSaved).toHaveBeenCalledWith({element: null, canonical: null});
  });

  it('does not report a save that failed', async () => {
    postSpy.mockRejectedValue(new Error('nope'));
    const onSaved = vi.fn();
    const {autosave} = mount({onSaved});

    await autosave.save();

    expect(onSaved).not.toHaveBeenCalled();
  });

  it('keeps the layout the server saved', async () => {
    const layout = {scope: [], nodes: [], values: {}, errors: []};
    postSpy.mockResolvedValue({data: {draftId: 7, form: layout}});

    const {autosave} = mount();

    expect(autosave.form.value).toBeNull();

    await autosave.save();

    expect(autosave.form.value).toEqual(layout);

    // A response without one leaves the last known layout in place, rather
    // than blanking the form the renderer is showing.
    postSpy.mockResolvedValue({data: {draftId: 7}});
    await autosave.save();

    expect(autosave.form.value).toEqual(layout);

    autosave.clearSaved();

    expect(autosave.form.value).toBeNull();
  });

  // The screen payload is what tells the client the save turned a canonical
  // element into a provisional draft.
  it('keeps the edit screen payload the server returned', async () => {
    const screen = {
      notice: 'Showing your unsaved changes.',
      canDiscardDraft: true,
      isProvisionalDraft: true,
      draftId: 7,
    };
    postSpy.mockResolvedValue({data: {draftId: 7, screen}});

    const {autosave} = mount();

    expect(autosave.screen.value).toBeNull();

    await autosave.save();

    expect(autosave.screen.value).toEqual(screen);

    // A response without one leaves the last known screen in place, rather
    // than reverting the chrome to the page that loaded.
    postSpy.mockResolvedValue({data: {draftId: 7}});
    await autosave.save();

    expect(autosave.screen.value).toEqual(screen);

    autosave.clearSaved();

    expect(autosave.screen.value).toBeNull();
  });
});
