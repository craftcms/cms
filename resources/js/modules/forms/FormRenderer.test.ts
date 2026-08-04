import {createApp, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import payload from '../../../../tests/Fixtures/Form/plain-text-settings.json';
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

  it('fails loudly when a payload component is not registered', () => {
    const invalid = structuredClone(payload) as Mutable<FormPayload>;
    invalid.nodes[0]!.component = 'craft:missing';
    const invalidApp = createApp(FormRenderer, {payload: invalid});
    registerFormComponents({
      register: (name, component) => invalidApp.component(name, component),
    });

    expect(() => invalidApp.mount(document.createElement('div'))).toThrow(
      'Form Node component is not registered: craft:missing'
    );
  });

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

  async function mount(formPayload: FormPayload): Promise<void> {
    app = createApp(FormRenderer, {payload: formPayload});
    registerFormComponents({
      register: (name, component) => app.component(name, component),
    });
    app.mount(container);
    await nextTick();
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
