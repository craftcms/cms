import {effectScope, ref} from 'vue';
import {type InertiaForm, useForm} from '@inertiajs/vue3';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {
  useSettingsSave,
  type SettingsSaveDependencies,
  type UseSettingsSaveOptions,
} from './useSettingsSave';
import type {SlideoutSaveResult} from '@/common/slideouts';

const axiosRequest = vi.fn();
const routerReload = vi.fn();
let slideout: SettingsSaveDependencies['slideout'] = null;
const elevated = {require: vi.fn()};
const redirectUrl = ref<string>();

interface TestFormData {
  name: string;
  fieldLayout?: string;
}

function dependencies(): SettingsSaveDependencies {
  return {
    request: axiosRequest,
    reload: routerReload,
    elevatedSession: elevated,
    slideout,
    redirectUrl,
  };
}

function makeForm(
  data: TestFormData = {name: 'Widgets'}
): InertiaForm<TestFormData> {
  const form = useForm<TestFormData>(data);

  Object.assign(form, {
    clearErrors: vi.fn(() => {
      form.errors = {};
      return form;
    }),
    setError: vi.fn((errors: Record<string, string>) => {
      form.errors = errors;
      return form;
    }),
    transform: vi.fn(() => form),
    submit: vi.fn(),
  });

  return form;
}

const action = () => ({url: '/admin/entry-types/save', method: 'post'});

function useTestSettingsSave(
  form: InertiaForm<TestFormData>,
  options: UseSettingsSaveOptions<TestFormData> = {}
) {
  return useSettingsSave(form, action, options, dependencies());
}

let scope: ReturnType<typeof effectScope>;

function run<T>(fn: () => T): T {
  scope = effectScope();

  const result = scope.run(fn);
  if (result === undefined)
    throw new Error('Expected the effect scope callback to run.');

  return result;
}

beforeEach(() => {
  axiosRequest.mockReset().mockResolvedValue({data: {message: 'Saved.'}});
  routerReload.mockReset();
  elevated.require.mockReset().mockResolvedValue(true);
  slideout = {
    instance: {containerId: 'slideout-1'},
    close: vi.fn(),
    // Nobody listening, so the panel falls back to reloading the page behind.
    saved: vi.fn((_result?: SlideoutSaveResult) => false),
  };
});

afterEach(() => scope?.stop());

describe('useSettingsSave in a slideout', () => {
  it('posts directly instead of making an Inertia visit', async () => {
    const form = makeForm();
    const {save} = run(() => useTestSettingsSave(form));

    save();
    await vi.waitFor(() => expect(axiosRequest).toHaveBeenCalled());

    // An Inertia visit would replace the page behind the panel.
    expect(form.submit).not.toHaveBeenCalled();

    const request = axiosRequest.mock.calls[0];
    if (!request) throw new Error('Expected the slideout request.');
    const req = request[0];
    expect(req.url).toBe('/admin/entry-types/save');
    expect(req.method).toBe('post');
    expect(req.headers['X-Craft-Container-Id']).toBe('slideout-1');
  });

  it('never sends a redirect — a slideout closes instead of navigating', async () => {
    const {save} = run(() => useTestSettingsSave(makeForm()));

    save();
    await vi.waitFor(() => expect(axiosRequest).toHaveBeenCalled());

    const request = axiosRequest.mock.calls[0];
    if (!request) throw new Error('Expected the slideout request.');
    expect(request[0].data).not.toHaveProperty('redirect');
  });

  it('applies the transform to the payload', async () => {
    const form = makeForm({name: 'Widgets'});
    const {save} = run(() =>
      useTestSettingsSave(form, {
        transform: (data: TestFormData) => ({...data, fieldLayout: '[]'}),
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
    slideout = {
      instance: {containerId: 'slideout-1'},
      close,
      saved: vi.fn(() => false),
    };
    const form = makeForm();
    const {save} = run(() => useTestSettingsSave(form));

    save();
    await vi.waitFor(() => expect(routerReload).toHaveBeenCalled());

    expect(close).toHaveBeenCalled();
    expect(form.processing).toBe(false);
  });

  it('keeps the panel open for save-and-continue', async () => {
    const close = vi.fn();
    slideout = {
      instance: {containerId: 'slideout-1'},
      close,
      saved: vi.fn(() => false),
    };
    const {save} = run(() => useTestSettingsSave(makeForm()));

    save({redirect: false});
    await vi.waitFor(() => expect(routerReload).toHaveBeenCalled());

    expect(close).not.toHaveBeenCalled();
  });

  /**
   * An opener that registered `onSaved` refreshes itself, and knows better than
   * the panel does what needs refreshing — so the blanket reload is skipped.
   */
  it('leaves refreshing to the opener when it handles the save', async () => {
    const saved = vi.fn((_result?: SlideoutSaveResult) => true);
    const close = vi.fn();
    slideout = {instance: {containerId: 'slideout-1'}, close, saved};

    const {save} = run(() => useTestSettingsSave(makeForm()));

    save();
    await vi.waitFor(() => expect(saved).toHaveBeenCalled());

    const savedCall = saved.mock.calls[0];
    if (!savedCall) throw new Error('Expected the slideout saved callback.');
    expect(savedCall[0]).toEqual({data: {message: 'Saved.'}});
    expect(routerReload).not.toHaveBeenCalled();
    expect(close).toHaveBeenCalled();
  });

  it('maps a 400 validation failure onto the form', async () => {
    // `asJsonFailure()` answers 400 — not Laravel's usual 422 — and sends
    // `{field: [message, …]}`, which has to flatten to one message per field
    // to match the full-page path.
    axiosRequest.mockRejectedValue({
      isAxiosError: true,
      response: {status: 400, data: {errors: {name: ['Name is required.']}}},
    });

    const form = makeForm();
    const {save} = run(() => useTestSettingsSave(form));

    save();
    await vi.waitFor(() =>
      expect(form.errors).toEqual({name: 'Name is required.'})
    );

    expect(form.errors).toEqual({name: 'Name is required.'});
    expect(form.processing).toBe(false);
    // A failed save must not close the panel or discard the user's input.
    expect(routerReload).not.toHaveBeenCalled();
  });

  it('retries once behind an elevated session on 423', async () => {
    axiosRequest
      .mockRejectedValueOnce({isAxiosError: true, response: {status: 423}})
      .mockResolvedValueOnce({data: {message: 'Saved.'}});

    const {save} = run(() =>
      useTestSettingsSave(makeForm(), {elevatedFields: '*'})
    );

    save();
    await vi.waitFor(() => expect(axiosRequest).toHaveBeenCalledTimes(2));

    expect(elevated.require).toHaveBeenCalled();
  });
});

describe('useSettingsSave on a full page', () => {
  beforeEach(() => {
    slideout = null;
  });

  it('still makes an ordinary Inertia visit', async () => {
    const form = makeForm();
    const {save} = run(() => useTestSettingsSave(form));

    save();

    expect(form.submit).toHaveBeenCalled();
    expect(axiosRequest).not.toHaveBeenCalled();
  });
});
