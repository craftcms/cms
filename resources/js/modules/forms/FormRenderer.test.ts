import {
  createApp,
  defineComponent,
  h,
  nextTick,
  ref,
  shallowRef,
  type Ref,
} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import payload from '../../../../tests/Fixtures/Form/plain-text-settings.json';
import {registerTestPluginFormComponents} from '../../../../tests/TestClasses/TestPlugin/resources/js/register-form-components';
import {
  createCpComponentRegistry,
  type CpComponentRegistration,
} from '@/bootstrap/components';
import FormRenderer from './FormRenderer.vue';
import {registerFormComponents} from './register';
import type {FormPayload} from './types';

type Mutable<T> = T extends object
  ? {-readonly [Key in keyof T]: Mutable<T[Key]>}
  : T;

describe('FormRenderer', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  let form: HTMLFormElement;
  let currentPayload: Ref<FormPayload>;
  let renderer: {
    advanceBaseline: () => void;
  };

  beforeEach(async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));
    form = document.createElement('form');
    container = document.createElement('div');
    form.append(container);
    document.body.append(form);
    await mount(structuredClone(payload) as FormPayload);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    app.unmount();
    form.remove();
  });

  it('renders the shared payload with equivalent names, values, and errors', () => {
    const placeholder = container.querySelector<HTMLInputElement>(
      'input[name="settings[placeholder]"]'
    )!;
    const mode = container.querySelector<HTMLSelectElement>(
      'select[name="settings[uiMode]"]'
    )!;

    expect(placeholder.value).toBe('Submitted placeholder');
    expect(placeholder.getAttribute('aria-invalid')).toBe('true');
    expect(placeholder.labels?.[0]?.textContent).toBe('Placeholder Text');
    const placeholderField = placeholder.closest('craft-field')!;
    expect(
      placeholderField.querySelector(':scope > [slot="help-text"]')?.textContent
    ).toBe('The text that will be shown if the field doesn’t have a value.');
    expect(
      placeholderField.querySelector(':scope > [slot="feedback"]')?.textContent
    ).toContain('Placeholder is invalid.');
    expect(mode.value).toBe('enlarged');
    expect(container.querySelector('craft-field craft-input')).not.toBeNull();
    expect(container.querySelector('craft-field craft-select')).not.toBeNull();
    expect(container.querySelector('craft-field craft-switch')).not.toBeNull();
    expect(container.querySelector('craft-field-group')).not.toBeNull();
    expect(container.querySelector('fieldset legend')?.textContent).toBe(
      'Field Limit'
    );
    expect(container.querySelector('[role="alert"]')?.textContent).toContain(
      'The settings could not be saved.'
    );
  });

  it('renders server-localized copy unchanged', async () => {
    const localized = structuredClone(payload) as Mutable<FormPayload>;
    localized.nodes[0]!.props.label = 'UI-Modus';
    app.unmount();
    await mount(localized);

    expect(container.querySelector('label')?.textContent).toBe('UI-Modus');
  });

  it('invalidates the complete Form when a payload component is not registered', async () => {
    const invalid = structuredClone(payload) as Mutable<FormPayload>;
    invalid.nodes[0]!.component = 'craft:missing';
    app.unmount();
    await mount(invalid);

    const submission = new Event('submit', {cancelable: true});

    expect(container.querySelector('[role="alert"]')?.textContent).toContain(
      'craft:missing'
    );
    expect(container.querySelector('[role="alert"]')?.textContent).toContain(
      invalid.nodes[0]!.type
    );
    expect(container.querySelector('input')).toBeNull();
    expect(form.dispatchEvent(submission)).toBe(false);
  });

  it('renders and submits test plugin Node and Control types', async () => {
    const pluginPayload = structuredClone(payload) as Mutable<FormPayload>;
    pluginPayload.nodes[0] = {
      type: 'CraftCms\\Cms\\Tests\\TestClasses\\TestPlugin\\src\\Form\\Nodes\\Notice',
      component: 'test-plugin:notice',
      props: {message: 'Provided by the test plugin'},
      uid: 'plugin-notice',
      children: [],
    };
    pluginPayload.nodes[1]!.control!.type =
      'CraftCms\\Cms\\Tests\\TestClasses\\TestPlugin\\src\\Form\\Controls\\Slug';
    pluginPayload.nodes[1]!.control!.component = 'test-plugin:slug';
    pluginPayload.nodes[1]!.control!.props = {placeholder: 'plugin-slug'};
    app.unmount();
    await mount(pluginPayload, {
      registerComponents: registerTestPluginFormComponents,
    });

    const input = container.querySelector<HTMLInputElement>(
      '[data-test-plugin-control]'
    )!;

    expect(
      container.querySelector('[data-test-plugin-notice]')?.textContent
    ).toContain('Provided by the test plugin');
    expect(input.name).toBe('settings[placeholder]');
    expect(input.placeholder).toBe('plugin-slug');
    input.value = 'plugin-submitted';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();
    expect(Object.fromEntries(new FormData(form))).toMatchObject({
      'settings[placeholder]': 'plugin-submitted',
    });
  });

  it.each([
    [
      'loader failures',
      () => Promise.reject(new Error('Test plugin loader failed.')),
      'Test plugin loader failed.',
    ],
    [
      'renderer exceptions',
      defineComponent({
        setup() {
          throw new Error('Test plugin renderer failed.');
        },
      }),
      'Test plugin renderer failed.',
    ],
  ] as Array<[string, CpComponentRegistration, string]>)(
    'invalidates the complete Form on %s',
    async (_, component, message) => {
      const invalid = structuredClone(payload) as Mutable<FormPayload>;
      invalid.nodes[0]!.component = 'test-plugin:broken';
      app.unmount();
      await mount(invalid, {
        components: {'test-plugin:broken': component},
      });

      await vi.waitFor(() => {
        expect(
          container.querySelector('[role="alert"]')?.textContent
        ).toContain(message);
      });

      const submission = new Event('submit', {cancelable: true});

      expect(container.querySelector('input')).toBeNull();
      expect(form.dispatchEvent(submission)).toBe(false);
    }
  );

  it('keeps the ordinary nested submission shape after edits', async () => {
    const placeholder = container.querySelector<HTMLInputElement>(
      'input[name="settings[placeholder]"]'
    )!;
    placeholder.value = 'Changed in Vue';
    placeholder.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(Object.fromEntries(new FormData(form))).toMatchObject({
      'settings[placeholder]': 'Changed in Vue',
      'settings[uiMode]': 'enlarged',
      'settings[code]': '1',
    });
  });

  it('refreshes with the complete current scope after typing settles', async () => {
    vi.useFakeTimers();
    const refresh = vi.fn(async (values: FormPayload['values']) => ({
      ...payload,
      values,
    })) as unknown as (values: FormPayload['values']) => Promise<FormPayload>;
    app.unmount();
    const refreshable = structuredClone(payload) as Mutable<FormPayload>;
    refreshable.refreshable = true;
    await mount(refreshable, {refresh});

    const placeholder = container.querySelector<HTMLInputElement>(
      'input[name="settings[placeholder]"]'
    )!;
    placeholder.value = 'Changed in Vue';
    placeholder.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    await vi.advanceTimersByTimeAsync(999);
    expect(refresh).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(1);

    expect(refresh).toHaveBeenCalledOnce();
    expect(refresh).toHaveBeenCalledWith({
      settings: expect.objectContaining({placeholder: 'Changed in Vue'}),
    });

    placeholder.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();
    await vi.advanceTimersByTimeAsync(1000);
    expect(refresh).toHaveBeenCalledOnce();
    vi.useRealTimers();
  });

  it('waits 100 milliseconds for discrete refreshes', async () => {
    vi.useFakeTimers();
    const refresh = vi.fn(async (values: FormPayload['values']) => ({
      ...payload,
      values,
    })) as unknown as (values: FormPayload['values']) => Promise<FormPayload>;
    app.unmount();
    const refreshable = structuredClone(payload) as Mutable<FormPayload>;
    refreshable.refreshable = true;
    await mount(refreshable, {refresh});

    const lightswitch = container.querySelector('craft-switch')!;
    lightswitch.checked = false;
    lightswitch.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();

    await vi.advanceTimersByTimeAsync(99);
    expect(refresh).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(1);
    expect(refresh).toHaveBeenCalledOnce();
    vi.useRealTimers();
  });

  it('ignores stale refreshes and retains the last valid presentation on failure', async () => {
    vi.useFakeTimers();
    const requests: Array<{
      resolve: (payload: FormPayload) => void;
      reject: () => void;
    }> = [];
    const refresh = vi.fn(
      () =>
        new Promise<FormPayload>((resolve, reject) => {
          requests.push({resolve, reject});
        })
    );
    const refreshable = structuredClone(payload) as Mutable<FormPayload>;
    refreshable.refreshable = true;
    app.unmount();
    await mount(refreshable, {refresh});

    async function changePlaceholder(value: string, settle = true) {
      const input = container.querySelector<HTMLInputElement>(
        'input[name="settings[placeholder]"]'
      )!;
      input.value = value;
      input.dispatchEvent(new Event('input', {bubbles: true}));
      await nextTick();

      if (settle) {
        await vi.advanceTimersByTimeAsync(1000);
      }
    }
    await changePlaceholder('First');
    await changePlaceholder('Second', false);

    const stale = structuredClone(payload) as Mutable<FormPayload>;
    stale.nodes[1]!.props.label = 'Stale presentation';
    requests[0]!.resolve(stale);
    await Promise.resolve();
    await nextTick();
    expect(container.textContent).not.toContain('Stale presentation');

    await vi.advanceTimersByTimeAsync(1000);

    const newest = structuredClone(payload) as Mutable<FormPayload>;
    newest.refreshable = true;
    newest.nodes[1]!.props.label = 'Newest presentation';
    requests[1]!.resolve(newest);
    await Promise.resolve();
    await nextTick();

    expect(container.textContent).toContain('Newest presentation');
    expect(
      container.querySelector<HTMLInputElement>(
        'input[name="settings[placeholder]"]'
      )?.value
    ).toBe('Second');

    await changePlaceholder('Third');
    requests[2]!.reject();
    await Promise.resolve();
    await nextTick();
    expect(container.textContent).toContain('Newest presentation');
    vi.useRealTimers();
  });

  it('keeps current and hidden values while submitting changed visible groups', async () => {
    let mutation: FormPayload['values'] = {};
    app.unmount();
    await mount(structuredClone(payload) as FormPayload, {
      onMutation: (value) => (mutation = value),
    });

    const placeholder = container.querySelector<HTMLInputElement>(
      'input[name="settings[placeholder]"]'
    )!;
    placeholder.value = 'Unsaved';
    placeholder.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    const refreshed = structuredClone(payload) as Mutable<FormPayload>;
    refreshed.nodes = refreshed.nodes.filter(
      (node) => node.control?.path.at(-1) !== 'placeholder'
    );
    (refreshed.values.settings as Record<string, unknown>).uiMode = 'normal';
    currentPayload.value = refreshed;
    await nextTick();

    expect(mutation).not.toHaveProperty('settings.placeholder');

    currentPayload.value = structuredClone(payload) as FormPayload;
    await nextTick();
    expect(
      container.querySelector<HTMLInputElement>(
        'input[name="settings[placeholder]"]'
      )?.value
    ).toBe('Unsaved');
    expect(
      container
        .querySelector('input[name="settings[placeholder]"]')
        ?.closest('[data-form-touched]')
        ?.getAttribute('data-form-touched')
    ).toBe('true');

    renderer.advanceBaseline();
    expect(mutation).toEqual({});
  });

  it('submits complete atomic groups when one member changes', async () => {
    let mutation: FormPayload['values'] = {};
    const atomic = structuredClone(payload) as Mutable<FormPayload>;
    (atomic.values.settings as Record<string, unknown>).canonical = {
      serverOwned: true,
    };
    app.unmount();
    await mount(atomic, {
      onMutation: (value) => (mutation = value),
    });

    const maximum = container.querySelector<HTMLInputElement>(
      'input[name="settings[fieldLimit]"]'
    )!;
    maximum.value = '240';
    maximum.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(mutation.settings).toMatchObject({
      fieldLimit: '240',
      limitUnit: 'chars',
      canonical: {serverOwned: true},
    });
  });

  it('preserves focus and keyed component state during reconciliation', async () => {
    const tabs = defineComponent({
      setup() {
        const selected = ref('First tab');

        return () =>
          h(
            'button',
            {onClick: () => (selected.value = 'Second tab')},
            selected.value
          );
      },
    });
    const withTabs = structuredClone(payload) as Mutable<FormPayload>;
    withTabs.nodes.unshift({
      type: 'TestTabs',
      component: 'test:tabs',
      props: {},
      uid: 'settings-tabs',
    });
    app.unmount();
    await mount(withTabs, {components: {'test:tabs': tabs}});

    container.querySelector<HTMLButtonElement>('button')!.click();
    const input = container.querySelector<HTMLInputElement>(
      'input[name="settings[placeholder]"]'
    )!;
    input.focus();
    currentPayload.value = structuredClone(withTabs) as FormPayload;
    await nextTick();

    expect(container.querySelector('button')?.textContent).toBe('Second tab');
    expect(
      container.querySelector<HTMLInputElement>(
        'input[name="settings[placeholder]"]'
      )
    ).toBe(input);
    expect(document.activeElement).toBe(input);
  });

  it('passes switch configuration to craft-switch', async () => {
    const configured = structuredClone(payload) as Mutable<FormPayload>;

    visitControls(configured.nodes, (control) => {
      if (control.component === 'craft:lightswitch') {
        Object.assign(control.props, {
          indeterminate: true,
          size: 'small',
          checkedValue: 'yes',
          indeterminateValue: 'maybe',
          onLabel: 'On',
          offLabel: 'Off',
        });
      }
    });
    (configured.values.settings as Record<string, unknown>).code = false;
    app.unmount();
    await mount(configured);

    const switchElement = container.querySelector('craft-switch')!;
    await switchElement.updateComplete;

    expect(switchElement.indeterminate).toBe(true);
    expect(switchElement.size).toBe('small');
    expect(switchElement.checkedValue).toBe('yes');
    expect(switchElement.indeterminateValue).toBe('maybe');
    expect(switchElement.onLabel).toBe('On');
    expect(switchElement.offLabel).toBe('Off');
    expect(
      switchElement.querySelector<HTMLInputElement>(
        'input[name="settings[code]"]'
      )?.value
    ).toBe('maybe');
  });

  it('renders and submits scalar and choice Controls', async () => {
    const controlsPayload = structuredClone(payload) as Mutable<FormPayload>;
    const controls = [
      [
        'craft:textarea',
        'Textarea',
        'summary',
        {rows: 4, maxLength: 120, placeholder: '<write>'},
        '<summary>',
      ],
      [
        'craft:choice',
        'Choice',
        'choice',
        {
          options: [
            {label: '<None>', value: ''},
            {label: 'One', value: 'one'},
            {label: 'Enabled', value: true},
          ],
          multiple: false,
          presentation: 'select',
        },
        true,
      ],
      [
        'craft:choice',
        'Choice',
        'tags',
        {
          options: [
            {label: 'Alpha', value: 'a'},
            {label: 'Beta', value: 'b'},
          ],
          multiple: true,
          presentation: 'checkboxes',
        },
        [],
      ],
      [
        'craft:choice',
        'Choice',
        'radio',
        {
          options: [
            {label: 'First', value: 'first'},
            {label: 'Second', value: 'second'},
          ],
          multiple: false,
          presentation: 'radios',
        },
        'first',
      ],
      [
        'craft:number',
        'Number',
        'number',
        {inputType: 'number', min: 0, max: 10, step: 0.5, size: 5},
        '',
      ],
      [
        'craft:range',
        'Range',
        'range',
        {inputType: 'range', min: 1, max: 5, step: 1},
        3,
      ],
      [
        'craft:date',
        'Date',
        'date',
        {inputType: 'date', min: '2026-01-01', max: '2026-12-31'},
        '2026-08-04',
      ],
      ['craft:time', 'Time', 'time', {inputType: 'time', step: 60}, '14:30'],
      ['craft:color', 'Color', 'color', {presets: ['#ff0000']}, 'ff0000'],
      [
        'craft:money',
        'Money',
        'price',
        {currency: 'EUR', locale: 'nl_BE', showCurrency: true},
        {value: '12,50', locale: 'nl_BE'},
      ],
    ] as const;
    controlsPayload.nodes = controls.map(([component, type, path, props]) => ({
      type,
      component: 'craft:field',
      props: {label: path, instructions: null, required: false},
      control: {
        type,
        component,
        props,
        path: ['settings', path],
        mode: 'editable',
        deltaGroup: ['settings', path],
      },
    }));
    controlsPayload.values = {
      settings: Object.fromEntries(
        controls.map(([, , path, , value]) => [path, value])
      ),
    };
    controlsPayload.errors = [
      {path: ['settings', 'number'], messages: ['Enter a number.']},
    ];
    controlsPayload.globalErrors = [];
    app.unmount();
    await mount(controlsPayload);

    const textarea = container.querySelector<HTMLTextAreaElement>(
      'textarea[name="settings[summary]"]'
    )!;
    expect(textarea.value).toBe('<summary>');
    expect(textarea.getAttribute('rows')).toBe('4');
    expect(textarea.maxLength).toBe(120);
    expect(textarea.getAttribute('placeholder')).toBe('<write>');
    expect(
      container.querySelector<HTMLSelectElement>(
        'select[name="settings[choice]"]'
      )?.value
    ).toBe('1');
    expect(
      container
        .querySelector('select[name="settings[choice]"]')
        ?.closest('craft-select')
    ).not.toBeNull();
    expect(
      container.querySelectorAll(
        'input[type="checkbox"][name="settings[tags][]"]'
      )
    ).toHaveLength(2);
    expect(container.querySelectorAll('craft-checkbox')).toHaveLength(2);
    expect(container.querySelector('craft-checkbox-group')).not.toBeNull();
    expect(container.querySelectorAll('craft-radio')).toHaveLength(2);
    expect(container.querySelector('craft-radio-group')).not.toBeNull();
    await vi.waitFor(() => {
      expect(
        container
          .querySelector<HTMLInputElement>('input[name="settings[number]"]')
          ?.getAttribute('aria-invalid')
      ).toBe('true');
    });
    const number = container.querySelector<HTMLInputElement>(
      'input[name="settings[number]"]'
    )!;
    expect(number.closest('craft-input')).not.toBeNull();
    expect(number.min).toBe('0');
    expect(number.max).toBe('10');
    expect(number.step).toBe('0.5');
    expect(number.size).toBe(5);
    expect(
      container.querySelector<HTMLInputElement>('input[name="settings[date]"]')
        ?.value
    ).toBe('2026-08-04');
    expect(
      container.querySelector<HTMLInputElement>(
        'input[name="settings[price][value]"]'
      )?.value
    ).toBe('12,50');
    expect(
      container
        .querySelector('input[name="settings[price][value]"]')
        ?.closest('craft-input-money')
    ).not.toBeNull();
    const beta = container.querySelector<HTMLInputElement>(
      'input[name="settings[tags][]"][value="b"]'
    )!;
    beta.checked = true;
    beta.dispatchEvent(new Event('change', {bubbles: true}));

    for (const [name, value] of [
      ['number', '2.5'],
      ['range', '4'],
      ['date', '2026-08-05'],
      ['time', '15:00'],
    ] as const) {
      const input = container.querySelector<HTMLInputElement>(
        `input[name="settings[${name}]"]`
      )!;
      input.value = value;
      input.dispatchEvent(new Event('input', {bubbles: true}));
    }

    const money = container.querySelector<HTMLInputElement>(
      'input[name="settings[price][value]"]'
    )!;
    money.value = '13,75';
    money.dispatchEvent(new Event('input', {bubbles: true}));

    const color = container.querySelector('craft-input-color')!;
    color.modelValue = '00ff00';
    color.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();
    expect(new FormData(form).getAll('settings[tags][]')).toEqual(['b']);
    expect(Object.fromEntries(new FormData(form))).toMatchObject({
      'settings[summary]': '<summary>',
      'settings[choice]': '1',
      'settings[tags]': '',
      'settings[radio]': 'first',
      'settings[number]': '2.5',
      'settings[range]': '4',
      'settings[date]': '2026-08-05',
      'settings[time]': '15:00',
      'settings[color]': '00ff00',
      'settings[price][value]': '13,75',
      'settings[price][locale]': 'nl_BE',
    });

    for (const mode of ['readOnly', 'disabled'] as const) {
      visitControls(controlsPayload.nodes, (control) => (control.mode = mode));
      app.unmount();
      await mount(controlsPayload);

      expect(Array.from(new FormData(form))).toEqual([]);
      expect(
        container.querySelector<HTMLTextAreaElement>('textarea')?.value
      ).toBe('<summary>');
      expect(
        container.querySelector<HTMLInputElement>('input[inputmode="decimal"]')
          ?.value
      ).toBe('12,50');
      expect(container.querySelector<HTMLSelectElement>('select')?.value).toBe(
        '1'
      );
      expect(
        container.querySelector<HTMLInputElement>('input[type="range"]')?.value
      ).toBe('3');
      expect(
        container.querySelector<HTMLInputElement>('input[type="date"]')?.value
      ).toBe('2026-08-04');
      expect(
        container.querySelector<HTMLInputElement>('input[type="time"]')?.value
      ).toBe('14:30');
      expect(container.querySelector('craft-input-color')?.modelValue).toBe(
        'ff0000'
      );
    }
  });

  it.each(['readOnly', 'disabled'] as const)(
    'displays values without names in %s mode',
    async (mode) => {
      const nonEditable = structuredClone(payload) as Mutable<FormPayload>;
      visitControls(nonEditable.nodes, (control) => (control.mode = mode));
      app.unmount();
      await mount(nonEditable);

      expect(container.querySelector<HTMLInputElement>('input')?.value).toBe(
        'Submitted placeholder'
      );
      expect(Array.from(new FormData(form))).toEqual([]);
    }
  );

  async function mount(
    formPayload: FormPayload,
    options: {
      refresh?: (values: FormPayload['values']) => Promise<FormPayload>;
      onMutation?: (mutation: FormPayload['values']) => void;
      components?: Record<string, CpComponentRegistration>;
      registerComponents?: (
        components: Pick<
          ReturnType<typeof createCpComponentRegistry>,
          'register'
        >
      ) => void;
    } = {}
  ): Promise<void> {
    currentPayload = shallowRef(formPayload);
    const rendererRef = ref();
    app = createApp({
      setup: () => () =>
        h(FormRenderer, {
          ref: rendererRef,
          payload: currentPayload.value,
          refresh: options.refresh,
          'onUpdate:mutation': options.onMutation,
        }),
    });
    const components = createCpComponentRegistry();
    registerFormComponents(components);
    options.registerComponents?.(components);
    Object.entries(options.components ?? {}).forEach(([name, component]) =>
      components.register(name, component)
    );
    components.install(app);
    app.mount(container);
    await nextTick();
    renderer = rendererRef.value;
  }
});

function visitControls(
  nodes: Mutable<FormPayload>['nodes'],
  visit: (
    control: NonNullable<Mutable<FormPayload>['nodes'][number]['control']>
  ) => void
) {
  for (const node of nodes) {
    if (node.control) {
      visit(node.control);
    }

    if (node.children) {
      visitControls(node.children, visit);
    }
  }
}
