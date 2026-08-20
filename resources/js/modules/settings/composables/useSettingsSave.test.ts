import {effectScope} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

const axiosRequest = vi.hoisted(() => vi.fn());
const routerReload = vi.hoisted(() => vi.fn());
const slideout = vi.hoisted(() => ({
  value: null as null | {
    instance: {containerId: string};
    close: () => void;
    saved: (result?: unknown) => boolean;
  },
}));
const elevated = vi.hoisted(() => ({require: vi.fn()}));

vi.mock('axios', () => ({
  default: {request: axiosRequest},
}));

const pageProps = vi.hoisted(() => ({
  value: {} as Record<string, unknown>,
}));

vi.mock('@inertiajs/vue3', () => ({
  router: {reload: routerReload},
  usePage: () => ({props: pageProps.value}),
}));

vi.mock('@/common/slideouts/useSlideout', () => ({
  useSlideout: () => slideout.value,
}));

vi.mock('@/modules/auth/elevated-session', () => ({
  elevatedSessionManager: elevated,
}));

const {useSettingsSave} = await import('./useSettingsSave');

/** A stand-in for Inertia's `useForm` result, with just what the composable drives. */
function makeForm(data: Record<string, unknown> = {name: 'Widgets'}) {
  return {
    processing: false,
    isDirty: false,
    errors: {} as Record<string, string>,
    data: () => data,
    clearErrors: vi.fn(function (this: any) {
      this.errors = {};
      return this;
    }),
    setError: vi.fn(function (this: any, errors: Record<string, string>) {
      this.errors = errors;
      return this;
    }),
    transform: vi.fn(function (this: any) {
      return this;
    }),
    submit: vi.fn(),
  };
}

const action = () => ({url: '/admin/entry-types/save', method: 'post'});

let scope: ReturnType<typeof effectScope>;

function run<T>(fn: () => T): T {
  scope = effectScope();

  return scope.run(fn)!;
}

beforeEach(() => {
  axiosRequest.mockReset().mockResolvedValue({data: {message: 'Saved.'}});
  routerReload.mockReset();
  pageProps.value = {};
  elevated.require.mockReset().mockResolvedValue(true);
  slideout.value = {
    instance: {containerId: 'slideout-1'},
    close: vi.fn(),
    // Nobody listening, so the panel falls back to reloading the page behind.
    saved: vi.fn(() => false),
  };
});

afterEach(() => scope?.stop());

describe('useSettingsSave in a slideout', () => {
  it('posts directly instead of making an Inertia visit', async () => {
    const form = makeForm();
    const {save} = run(() => useSettingsSave(form as any, action));

    save();
    await vi.waitFor(() => expect(axiosRequest).toHaveBeenCalled());

    // An Inertia visit would replace the page behind the panel.
    expect(form.submit).not.toHaveBeenCalled();

    const req = axiosRequest.mock.calls[0]![0];
    expect(req.url).toBe('/admin/entry-types/save');
    expect(req.method).toBe('post');
    expect(req.headers['X-Craft-Container-Id']).toBe('slideout-1');
  });

  it('never sends a redirect — a slideout closes instead of navigating', async () => {
    const {save} = run(() => useSettingsSave(makeForm() as any, action));

    save();
    await vi.waitFor(() => expect(axiosRequest).toHaveBeenCalled());

    expect(axiosRequest.mock.calls[0]![0].data).not.toHaveProperty('redirect');
  });

  it('applies the transform to the payload', async () => {
    const form = makeForm({name: 'Widgets'});
    const {save} = run(() =>
      useSettingsSave(form as any, action, {
        transform: (data: any) => ({...data, fieldLayout: '[]'}),
      })
    );

    save();
    await vi.waitFor(() => expect(axiosRequest).toHaveBeenCalled());

    expect(axiosRequest.mock.calls[0]![0].data).toMatchObject({
      name: 'Widgets',
      fieldLayout: '[]',
    });
  });

  it('closes the panel and refreshes the page behind on success', async () => {
    const close = vi.fn();
    slideout.value = {
      instance: {containerId: 'slideout-1'},
      close,
      saved: vi.fn(() => false),
    };
    const form = makeForm();
    const {save} = run(() => useSettingsSave(form as any, action));

    save();
    await vi.waitFor(() => expect(routerReload).toHaveBeenCalled());

    expect(close).toHaveBeenCalled();
    expect(form.processing).toBe(false);
  });

  it('keeps the panel open for save-and-continue', async () => {
    const close = vi.fn();
    slideout.value = {
      instance: {containerId: 'slideout-1'},
      close,
      saved: vi.fn(() => false),
    };
    const {save} = run(() => useSettingsSave(makeForm() as any, action));

    save({redirect: false});
    await vi.waitFor(() => expect(routerReload).toHaveBeenCalled());

    expect(close).not.toHaveBeenCalled();
  });

  /**
   * An opener that registered `onSaved` refreshes itself, and knows better than
   * the panel does what needs refreshing — so the blanket reload is skipped.
   */
  it('leaves refreshing to the opener when it handles the save', async () => {
    const saved = vi.fn((_result?: unknown) => true);
    const close = vi.fn();
    slideout.value = {instance: {containerId: 'slideout-1'}, close, saved};

    const {save} = run(() => useSettingsSave(makeForm() as any, action));

    save();
    await vi.waitFor(() => expect(saved).toHaveBeenCalled());

    expect(saved.mock.calls[0]![0]).toEqual({data: {message: 'Saved.'}});
    expect(routerReload).not.toHaveBeenCalled();
    expect(close).toHaveBeenCalled();
  });

  it('maps a 400 validation failure onto the form', async () => {
    // `asJsonFailure()` answers 400 — not Laravel's usual 422 — and sends
    // `{field: [message, …]}`, which has to flatten to one message per field
    // to match the full-page path.
    axiosRequest.mockRejectedValue({
      response: {status: 400, data: {errors: {name: ['Name is required.']}}},
    });

    const form = makeForm();
    const {save} = run(() => useSettingsSave(form as any, action));

    save();
    await vi.waitFor(() => expect(form.setError).toHaveBeenCalled());

    expect(form.errors).toEqual({name: 'Name is required.'});
    expect(form.processing).toBe(false);
    // A failed save must not close the panel or discard the user's input.
    expect(routerReload).not.toHaveBeenCalled();
  });

  it('retries once behind an elevated session on 423', async () => {
    axiosRequest
      .mockRejectedValueOnce({response: {status: 423}})
      .mockResolvedValueOnce({data: {message: 'Saved.'}});

    const {save} = run(() =>
      useSettingsSave(makeForm() as any, action, {elevatedFields: '*'})
    );

    save();
    await vi.waitFor(() => expect(axiosRequest).toHaveBeenCalledTimes(2));

    expect(elevated.require).toHaveBeenCalled();
  });
});

describe('useSettingsSave on a full page', () => {
  beforeEach(() => {
    slideout.value = null;
  });

  it('still makes an ordinary Inertia visit', async () => {
    const form = makeForm();
    const {save} = run(() => useSettingsSave(form as any, action));

    save();

    expect(form.submit).toHaveBeenCalled();
    expect(axiosRequest).not.toHaveBeenCalled();
  });

  /** The submitted payload, as the composable's `transform` builds it. */
  function submittedData(form: ReturnType<typeof makeForm>) {
    const [transform] = form.transform.mock.calls[0] as unknown as [
      (data: Record<string, unknown>) => Record<string, unknown>,
    ];

    return transform(form.data());
  }

  it('sends the screen’s redirect when the save asked for one', () => {
    pageProps.value = {redirectUrl: '/admin/entry-types'};

    const form = makeForm();
    const {save} = run(() => useSettingsSave(form as any, action));

    save();

    expect(submittedData(form).redirect).toBe('/admin/entry-types');
  });

  /**
   * `save({redirect: false})` means "don't layer this screen's redirect on
   * top" — not "drop the one the caller supplied". The element editor's
   * "Create a draft" rides on that: its own redirect points at the draft it
   * creates, so clobbering it strands the user on the canonical element.
   */
  it('keeps a redirect the caller’s transform supplied', () => {
    pageProps.value = {redirectUrl: '/admin/entry-types'};

    const form = makeForm();
    const {save} = run(() =>
      useSettingsSave(form as any, action, {
        transform: (data) => ({...data, redirect: 'encrypted-cp-edit-url'}),
      })
    );

    save({redirect: false});

    expect(submittedData(form).redirect).toBe('encrypted-cp-edit-url');
  });

  it('sends no redirect at all for a plain save-and-continue', () => {
    pageProps.value = {redirectUrl: '/admin/entry-types'};

    const form = makeForm();
    const {save} = run(() => useSettingsSave(form as any, action));

    save({redirect: false});

    expect(submittedData(form)).not.toHaveProperty('redirect');
  });
});
