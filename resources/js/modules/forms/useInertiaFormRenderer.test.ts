import {useForm, type InertiaForm} from '@inertiajs/vue3';
import {
  createApp,
  defineComponent,
  nextTick,
  ref,
  type ComputedRef,
  type ShallowRef,
} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import type {FormChangeKind, FormPayload, FormValue, FormValues} from './types';
import {useInertiaFormRenderer} from './useInertiaFormRenderer';

const payload: FormPayload = {
  scope: ['settings'],
  refreshable: false,
  nodes: [],
  values: {
    settings: {
      editable: 'original',
      readOnly: 'preserved for host logic',
    },
  },
  errors: [],
  globalErrors: [],
};

const rootPayload: FormPayload = {
  ...payload,
  scope: [],
  values: {
    title: 'Original',
    enabled: true,
  },
};

interface TestIntegration {
  advanceBaseline(): void;
  errors: ComputedRef<Array<{path: string[]; messages: string[]}>>;
  onMutation(mutation: FormValues): boolean;
  renderer: ShallowRef<{
    advanceBaseline(): void;
    currentValues(): FormValues;
    resetValues(): void;
    setValue(path: string[], value: FormValue, kind?: FormChangeKind): void;
  } | null>;
  values: ShallowRef<FormValues>;
}

type TestSettings = Record<string, string | boolean>;

interface SharedFormValues {
  title?: string;
  fields?: {email: string};
  slug?: string;
  enabled?: boolean;
}

describe('useInertiaFormRenderer', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('keeps full values separate from the submitted mutation', async () => {
    const currentPayload = ref(payload);
    let form!: InertiaForm<{title: string; settings: TestSettings}>;
    let integration!: TestIntegration;
    let currentValues = structuredClone(payload.values);

    mount(() => {
      form = useForm({title: 'Example', settings: {stale: true}});
      integration = useInertiaFormRenderer(form, currentPayload, {
        mutationKey: 'settings',
      });
      integration.renderer.value = {
        advanceBaseline: vi.fn(() => integration.onMutation({})),
        currentValues: () => structuredClone(currentValues),
        resetValues: vi.fn(),
        setValue: vi.fn(),
      };
    });

    expect(form.settings).toEqual({});
    expect(form.isDirty).toBe(false);
    expect(integration.values.value).toEqual(payload.values);

    currentValues = {
      settings: {
        editable: 'changed',
        readOnly: 'preserved for host logic',
      },
    };
    integration.onMutation({settings: {editable: 'changed'}});
    await nextTick();

    expect(form.data().settings).toEqual({editable: 'changed'});
    expect(form.isDirty).toBe(true);
    expect(integration.values.value).toEqual(currentValues);

    integration.onMutation({});
    await nextTick();

    expect(form.data().settings).toEqual({});
    expect(form.isDirty).toBe(false);
  });

  it('advances the renderer and Inertia baselines together', async () => {
    let form!: InertiaForm<{title: string; settings: TestSettings}>;
    let integration!: TestIntegration;
    const advanceRendererBaseline = vi.fn(() => integration.onMutation({}));

    mount(() => {
      form = useForm({title: 'Original', settings: {}});
      integration = useInertiaFormRenderer(form, payload, {
        mutationKey: 'settings',
      });
      integration.renderer.value = {
        advanceBaseline: advanceRendererBaseline,
        currentValues: () => payload.values,
        resetValues: vi.fn(),
        setValue: vi.fn(),
      };
    });

    form.title = 'Saved';
    integration.onMutation({settings: {editable: 'changed'}});
    await nextTick();
    expect(form.isDirty).toBe(true);

    integration.advanceBaseline();
    await nextTick();

    expect(advanceRendererBaseline).toHaveBeenCalledOnce();
    expect(form.title).toBe('Saved');
    expect(form.settings).toEqual({});
    expect(form.isDirty).toBe(false);

    integration.onMutation({settings: {editable: 'changed again'}});
    await nextTick();
    expect(form.isDirty).toBe(true);
  });

  it('maps only scoped Laravel errors by default', async () => {
    let form!: InertiaForm<{title: string; settings: TestSettings}>;
    let integration!: TestIntegration;

    mount(() => {
      form = useForm({title: '', settings: {}});
      integration = useInertiaFormRenderer(form, payload, {
        mutationKey: 'settings',
      });
    });

    form.setError({
      title: 'A title is required.',
      'settings.editable': 'The setting is invalid.',
    });
    await nextTick();

    expect(integration.errors.value).toEqual([
      {
        path: ['settings', 'editable'],
        messages: ['The setting is invalid.'],
      },
    ]);
  });

  /**
   * A validator keys an element's field error by its handle, but the control
   * sits under `fields`. The server resolves that when it renders; errors that
   * come back from a save have to be resolved the same way or they match no
   * control and read as global.
   */
  it('resolves an error key onto the control that owns it', async () => {
    const layoutPayload: FormPayload = {
      ...rootPayload,
      nodes: [
        {
          uid: 'n1',
          type: 'field',
          control: {path: ['fields', 'assets']},
        },
      ] as unknown as FormPayload['nodes'],
    };
    let form!: InertiaForm<{title: string; settings: TestSettings}>;
    let integration!: TestIntegration;

    mount(() => {
      form = useForm({title: '', settings: {}});
      integration = useInertiaFormRenderer(form, layoutPayload, {
        mutationKey: 'settings',
      });
    });

    form.setError({assets: 'Choose a valid asset.'} as never);
    await nextTick();

    expect(integration.errors.value).toEqual([
      {path: ['fields', 'assets'], messages: ['Choose a valid asset.']},
    ]);
  });

  it('leaves an unowned error key alone, so it stays global', async () => {
    const layoutPayload: FormPayload = {
      ...rootPayload,
      nodes: [
        {
          uid: 'n1',
          type: 'field',
          control: {path: ['fields', 'assets']},
        },
      ] as unknown as FormPayload['nodes'],
    };
    let form!: InertiaForm<{title: string; settings: TestSettings}>;
    let integration!: TestIntegration;

    mount(() => {
      form = useForm({title: '', settings: {}});
      integration = useInertiaFormRenderer(form, layoutPayload, {
        mutationKey: 'settings',
      });
    });

    form.setError({somethingElse: 'Nothing owns this.'} as never);
    await nextTick();

    expect(integration.errors.value).toEqual([
      {path: ['somethingElse'], messages: ['Nothing owns this.']},
    ]);
  });

  it('supports screen-specific Laravel error paths', async () => {
    let form!: InertiaForm<{name: string; settings: TestSettings}>;
    let integration!: TestIntegration;

    mount(() => {
      form = useForm({name: '', settings: {}});
      integration = useInertiaFormRenderer(form, payload, {
        mutationKey: 'settings',
        mapErrorPath: (path) =>
          path === 'name' ? null : ['settings', ...path.split('.')],
      });
    });

    Object.assign(form.errors, {
      name: 'A name is required.',
      endpoint: 'The endpoint is invalid.',
    });
    await nextTick();

    expect(integration.errors.value).toEqual([
      {
        path: ['settings', 'endpoint'],
        messages: ['The endpoint is invalid.'],
      },
    ]);
  });

  it('replaces the root Inertia data with the current mutation', async () => {
    let form!: InertiaForm<{title: string; enabled: boolean}>;
    let integration!: TestIntegration;

    mount(() => {
      form = useForm({title: 'Original', enabled: true});
      integration = useInertiaFormRenderer(form, rootPayload);
      integration.renderer.value = {
        advanceBaseline: vi.fn(() => integration.onMutation({})),
        currentValues: () => rootPayload.values,
        resetValues: vi.fn(),
        setValue: vi.fn(),
      };
    });

    expect('title' in form).toBe(false);
    expect('enabled' in form).toBe(false);
    expect(form.isDirty).toBe(false);

    integration.onMutation({title: 'Changed'});
    await nextTick();

    expect(form.title).toBe('Changed');
    expect('enabled' in form).toBe(false);
    expect(form.isDirty).toBe(true);

    integration.onMutation({introducedByRefresh: 'New value'});
    await nextTick();

    expect('title' in form).toBe(false);
    expect(
      Object.getOwnPropertyDescriptor(form, 'introducedByRefresh')?.value
    ).toBe('New value');
    expect(form.data()).toEqual({
      title: undefined,
      enabled: undefined,
      introducedByRefresh: 'New value',
    });
    expect(form.isDirty).toBe(true);

    integration.onMutation({});
    await nextTick();

    expect('title' in form).toBe(false);
    expect('enabled' in form).toBe(false);
    expect('introducedByRefresh' in form).toBe(false);
    expect(form.isDirty).toBe(false);
  });

  it('maps all Laravel errors for a root form', async () => {
    let form!: InertiaForm<{title: string; enabled: boolean}>;
    let integration!: TestIntegration;

    mount(() => {
      form = useForm({title: 'Original', enabled: true});
      integration = useInertiaFormRenderer(form, rootPayload);
    });

    form.setError({
      title: 'The title is invalid.',
      enabled: 'The status is invalid.',
    });
    await nextTick();

    expect(integration.errors.value).toEqual([
      {path: ['title'], messages: ['The title is invalid.']},
      {path: ['enabled'], messages: ['The status is invalid.']},
    ]);
  });

  it('advances the Inertia baseline without a mounted renderer', async () => {
    let form!: InertiaForm<{title: string; settings: TestSettings}>;
    let integration!: TestIntegration;

    mount(() => {
      form = useForm({title: 'Original', settings: {}});
      integration = useInertiaFormRenderer(form, payload, {
        mutationKey: 'settings',
      });
    });

    form.title = 'Saved';
    await nextTick();
    expect(form.isDirty).toBe(true);

    integration.advanceBaseline();
    await nextTick();

    expect(form.isDirty).toBe(false);
  });

  it('lets two root-scoped bridges share one form without clobbering each other', async () => {
    const layoutPayload: FormPayload = {
      scope: [],
      refreshable: false,
      nodes: [],
      values: {title: 'Original', fields: {email: ''}},
      errors: [],
      globalErrors: [],
    };
    const sidebarPayload: FormPayload = {
      scope: [],
      refreshable: false,
      nodes: [],
      values: {slug: 'original-slug', enabled: true},
      errors: [],
      globalErrors: [],
    };

    let form!: InertiaForm<SharedFormValues>;
    let layout!: TestIntegration;
    let sidebar!: TestIntegration;

    mount(() => {
      form = useForm<SharedFormValues>({});
      layout = useInertiaFormRenderer(form, layoutPayload);
      sidebar = useInertiaFormRenderer(form, sidebarPayload);
    });

    layout.onMutation({title: 'Changed', fields: {email: 'a@b.c'}});
    sidebar.onMutation({slug: 'changed-slug', enabled: false});
    await nextTick();

    expect(form.data()).toEqual({
      title: 'Changed',
      fields: {email: 'a@b.c'},
      slug: 'changed-slug',
      enabled: false,
    });

    layout.onMutation({title: 'Changed again', fields: {email: 'a@b.c'}});
    await nextTick();

    expect(form.data()).toEqual({
      title: 'Changed again',
      fields: {email: 'a@b.c'},
      slug: 'changed-slug',
      enabled: false,
    });

    sidebar.onMutation({slug: 'final-slug', enabled: true});
    await nextTick();

    expect(form.data()).toEqual({
      title: 'Changed again',
      fields: {email: 'a@b.c'},
      slug: 'final-slug',
      enabled: true,
    });
  });

  function mount(setup: () => void): void {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(
      defineComponent({
        setup() {
          setup();

          return () => null;
        },
      })
    );
    app.mount(container);
  }
});
