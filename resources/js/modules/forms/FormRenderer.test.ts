import {
  createApp,
  defineComponent,
  h,
  nextTick,
  reactive,
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
import {actionClient} from '@craftcms/ui';
import type CraftPermissionTree from '@craftcms/ui/components/permission-tree/permission-tree';
import FormRenderer from './FormRenderer.vue';
import {registerFormComponents} from './register';
import type {FormChange, FormControlOverrideProps, FormPayload} from './types';

const elementSelectMocks = vi.hoisted(() => {
  const base = vi.fn();
  const entry = vi.fn();
  const asset = vi.fn();
  let removeSelectedElement: ((id: number) => void) | null = null;

  class BaseElementSelectInputMock {
    protected readonly settings: Record<string, any>;
    protected readonly container: HTMLElement;
    private selectedIds: number[];

    constructor(settings: Record<string, any>) {
      base(settings);
      this.settings = settings;
      this.container = document.getElementById(settings.id)!;
      this.selectedIds = [
        ...this.container.querySelectorAll<HTMLElement>('craft-chip.element'),
      ].map((chip) => Number(chip.dataset.id));
      removeSelectedElement = (id) => this.removeElement(id);
      this.container
        .querySelector<HTMLButtonElement>('.btn.add')
        ?.addEventListener('click', () => this.showModal());
    }

    getSelectedElementIds(): number[] {
      return [...this.selectedIds];
    }

    destroy(): void {
      removeSelectedElement = null;
    }

    removeElement(id: number): void {
      this.selectedIds = this.selectedIds.filter(
        (selectedId) => selectedId !== id
      );
      this.container.dispatchEvent(
        new CustomEvent('removeElements', {bubbles: true})
      );
    }

    protected showModal(): void {
      Craft.createElementSelectorModal(this.settings.elementType, {
        ...this.settings.modalSettings,
        criteria: this.settings.criteria,
        disabledElementIds: this.getSelectedElementIds(),
        showSiteMenu: this.settings.showSiteMenu,
        sources: this.settings.sources,
        onSelect: (elements: Array<Record<string, any>>) => {
          this.selectedIds.push(
            ...elements.map((element) => Number(element.id))
          );
          this.container.dispatchEvent(
            new CustomEvent('selectElements', {
              bubbles: true,
              detail: {elements},
            })
          );
        },
      });
    }
  }

  class EntrySelectInputMock extends BaseElementSelectInputMock {
    constructor(settings: Record<string, any>) {
      super(settings);
      entry(settings);
    }
  }

  class AssetSelectInputMock extends BaseElementSelectInputMock {
    constructor(settings: Record<string, any>) {
      super(settings);
      asset(settings);
    }
  }

  return {
    asset,
    AssetSelectInputMock,
    base,
    BaseElementSelectInputMock,
    entry,
    EntrySelectInputMock,
    removeElement(id: number) {
      removeSelectedElement?.(id);
    },
  };
});

vi.mock('@/modules/element-select-input/base-element-select-input', () => ({
  BaseElementSelectInput: elementSelectMocks.BaseElementSelectInputMock,
}));

vi.mock('@/modules/element-select-input/entry-select-input', () => ({
  EntrySelectInput: elementSelectMocks.EntrySelectInputMock,
}));

vi.mock('@/modules/asset-select-input/asset-select-input', () => ({
  AssetSelectInput: elementSelectMocks.AssetSelectInputMock,
}));

vi.mock('../markdown-field/markdown-field', () => {
  if (!customElements.get('craft-markdown-field')) {
    customElements.define(
      'craft-markdown-field',
      class extends HTMLElement {
        private currentValue: string | null = null;

        static get observedAttributes(): string[] {
          return ['disabled', 'name'];
        }

        get value(): string {
          return (
            this.querySelector('textarea')?.value ??
            this.currentValue ??
            this.textContent ??
            ''
          );
        }

        set value(value: string) {
          this.currentValue = value;

          const textarea = this.querySelector('textarea');
          if (textarea) {
            textarea.value = value;
          }
        }

        connectedCallback() {
          const textarea = document.createElement('textarea');
          textarea.value = this.value;
          this.replaceChildren(textarea);
          this.syncTextarea();
        }

        attributeChangedCallback() {
          this.syncTextarea();
        }

        private syncTextarea() {
          const textarea = this.querySelector('textarea');
          if (textarea) {
            textarea.name = this.getAttribute('name') ?? '';
            textarea.disabled = this.hasAttribute('disabled');
          }
        }
      }
    );
  }

  return {};
});

vi.mock('../editable-table', () => ({
  EditableTable: class EditableTableMock {
    constructor(
      id: string,
      baseName: string,
      columns: Record<
        string,
        {type: string; textExpanderTriggers?: Record<string, unknown>}
      >,
      settings: {minRows?: number | null} = {}
    ) {
      const body = document.querySelector<HTMLTableSectionElement>(
        `#${id} tbody`
      )!;

      while (body.children.length < (settings.minRows ?? 0)) {
        EditableTableMock.createRow(
          String(body.children.length),
          columns,
          baseName,
          {}
        ).appendTo(body);
      }
    }

    static createRow(
      rowId: string,
      columns: Record<
        string,
        {type: string; textExpanderTriggers?: Record<string, unknown>}
      >,
      baseName: string,
      values: Record<string, unknown>
    ) {
      const row = document.createElement('tr');
      row.dataset.id = rowId;

      for (const [key, column] of Object.entries(columns)) {
        const cell = row.insertCell();
        const value = values[key];
        const stringValue =
          typeof value === 'string' ||
          typeof value === 'number' ||
          typeof value === 'boolean'
            ? String(value)
            : '';
        if (['autosuggest', 'template'].includes(column.type)) {
          if (column.textExpanderTriggers) {
            const input = document.createElement('input');
            input.name = `${baseName}[${rowId}][${key}]`;
            input.value = stringValue;
            cell.append(input);
            continue;
          }

          const combobox = document.createElement(
            'craft-combobox'
          ) as HTMLElement & {
            modelValue: string;
            name: string;
          };
          combobox.name = `${baseName}[${rowId}][${key}]`;
          combobox.modelValue = stringValue;
          cell.append(combobox);
          continue;
        }

        const input = document.createElement(
          column.type === 'checkbox' ? 'input' : 'textarea'
        );
        input.name = `${baseName}[${rowId}][${key}]`;
        if (input instanceof HTMLInputElement) {
          input.type = 'checkbox';
          input.checked = Boolean(value);
        } else {
          input.value = stringValue;
        }
        cell.append(input);
      }

      return {appendTo: (parent: HTMLElement) => parent.append(row)};
    }

    destroy() {}
  },
}));

type Mutable<T> = T extends object
  ? {-readonly [Key in keyof T]: Mutable<T[Key]>}
  : T;

const attachInternals = Object.getOwnPropertyDescriptor(
  HTMLElement.prototype,
  'attachInternals'
);

describe('FormRenderer', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  let form: HTMLFormElement;
  let currentPayload: Ref<FormPayload>;
  let renderer: {
    advanceBaseline: () => void;
    currentValues: () => FormPayload['values'];
    resetValues: (payload?: FormPayload) => void;
    setValue: (
      path: string[],
      value: unknown,
      kind?: FormChange['kind']
    ) => void;
  };

  beforeEach(async () => {
    Object.defineProperty(HTMLElement.prototype, 'attachInternals', {
      configurable: true,
      value: () => ({setFormValue: vi.fn()}),
    });
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
    if (attachInternals) {
      Object.defineProperty(
        HTMLElement.prototype,
        'attachInternals',
        attachInternals
      );
    } else {
      delete (HTMLElement.prototype as Partial<HTMLElement>).attachInternals;
    }
  });

  it('badges the fields the server reports as modified', async () => {
    app.unmount();
    await mount(structuredClone(payload) as FormPayload, {
      modified: ['settings.placeholder'],
    });

    const field = (name: string) =>
      container
        .querySelector<HTMLInputElement>(`[name="settings[${name}]"]`)!
        .closest('craft-field')!;

    expect(field('placeholder').getAttribute('status')).toBe('modified');
    expect(field('placeholder').getAttribute('status-label')).toBe(
      'This field has been modified.'
    );
    // Matched on the delta group, so a field the server didn't report stays clean.
    expect(field('uiMode').getAttribute('status')).toBeNull();
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
    const placeholderField = placeholder.closest('craft-field')!;
    const placeholderLabel = placeholderField.querySelector<HTMLElement>(
      ':scope > [slot="label"]'
    )!;
    expect(placeholder.getAttribute('aria-labelledby')?.split(/\s+/)).toContain(
      placeholderLabel.id
    );
    expect(placeholderLabel.textContent).toContain('Placeholder Text');
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

  it('displays a combobox option label for its initial value', async () => {
    const status: FormPayload = {
      scope: ['settings'],
      refreshable: false,
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
          component: 'craft:field',
          props: {label: 'System Status', required: true},
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\Combobox',
            component: 'craft:combobox',
            props: {
              options: [
                {label: 'Online', value: '1'},
                {label: 'Offline', value: '0'},
              ],
            },
            path: ['settings', 'live'],
            mode: 'editable',
            deltaGroup: ['settings', 'live'],
          },
        },
      ],
      values: {settings: {live: '1'}},
      errors: [],
      globalErrors: [],
    };
    app.unmount();
    await mount(status);

    const combobox = container.querySelector<
      HTMLElement & {modelValue: string}
    >('craft-combobox')!;
    await vi.waitFor(() => {
      expect(combobox.modelValue).toBe('1');
      expect(combobox.querySelector('input')?.value).toBe('Online');
    });
  });

  it('includes editable table text-expander input changes in current values', async () => {
    const table: FormPayload = {
      scope: [],
      refreshable: false,
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
          component: 'craft:field',
          props: {},
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\Table',
            component: 'craft:table',
            props: {
              columns: {
                fromEmail: {
                  type: 'autosuggest',
                  textExpanderTriggers: [
                    {
                      trigger: '$',
                      boundary: 'start',
                      options: [{label: '$SITE_EMAIL', value: '$SITE_EMAIL'}],
                    },
                  ],
                },
              },
              keyed: true,
            },
            path: ['siteOverrides'],
            mode: 'editable',
            deltaGroup: ['siteOverrides'],
          },
        },
      ],
      values: {
        siteOverrides: {
          'site-uid': {fromEmail: ''},
        },
      },
      errors: [],
      globalErrors: [],
    };
    app.unmount();
    await mount(table);

    const input = container.querySelector<HTMLInputElement>(
      'input[name="siteOverrides[site-uid][fromEmail]"]'
    )!;
    input.value = '$SITE_EMAIL';
    input.dispatchEvent(
      new InputEvent('input', {bubbles: true, composed: true})
    );

    await vi.waitFor(() =>
      expect(renderer.currentValues()).toEqual({
        siteOverrides: {
          'site-uid': {fromEmail: '$SITE_EMAIL'},
        },
      })
    );
  });

  it('renders table cell errors beside scalar values', async () => {
    const table: FormPayload = {
      scope: [],
      refreshable: false,
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
          component: 'craft:field',
          props: {},
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\Table',
            component: 'craft:table',
            props: {
              columns: {
                handle: {type: 'singleline'},
              },
              keyed: true,
              errors: {
                first: {handle: true},
                third: {handle: true},
              },
            },
            path: ['columns'],
            mode: 'editable',
            deltaGroup: ['columns'],
          },
        },
      ],
      values: {
        columns: {
          first: {handle: 'invalid-handle'},
          second: {handle: 'validHandle'},
          third: {handle: 'col3'},
        },
      },
      errors: [],
      globalErrors: [],
    };
    app.unmount();
    await mount(table);

    const inputs = [
      ...container.querySelectorAll<HTMLTextAreaElement>('tbody textarea'),
    ];

    expect(inputs.map((input) => input.value)).toEqual([
      'invalid-handle',
      'validHandle',
      'col3',
    ]);
    expect(
      inputs.map((input) => input.closest('td')?.classList.contains('error'))
    ).toEqual([true, false, true]);
    expect(renderer.currentValues()).toEqual(table.values);

    const successful = structuredClone(table);
    successful.nodes[0]!.control!.props.errors = {};
    currentPayload.value = successful;
    await nextTick();

    expect(container.querySelector('td.error')).toBeNull();
    expect(renderer.currentValues()).toEqual(table.values);
  });

  it('reports control changes and applies external value updates', async () => {
    const derived: FormPayload = {
      scope: [],
      refreshable: false,
      nodes: ['name', 'summary'].map((path) => ({
        type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
        component: 'craft:field',
        props: {label: path},
        control: {
          type: 'CraftCms\\Cms\\Form\\Controls\\Combobox',
          component: 'craft:combobox',
          props: {options: []},
          path: [path],
          mode: 'editable',
          deltaGroup: [path],
        },
      })),
      values: {name: '', summary: ''},
      errors: [],
      globalErrors: [],
    };
    app.unmount();
    let mutation: FormPayload['values'] = {};
    const onChange = vi.fn(
      (change: FormChange, values: FormPayload['values']) => {
        renderer.setValue(
          ['summary'],
          `Derived from ${String(values.name)}`,
          change.kind
        );
      }
    );
    await mount(derived, {
      onChange,
      onMutation: (current) => (mutation = current),
    });

    const [name] = container.querySelectorAll<
      HTMLElement & {modelValue: string}
    >('craft-combobox');
    name!.modelValue = 'My Site';
    name!.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();

    expect(onChange).toHaveBeenCalledOnce();
    expect(onChange).toHaveBeenCalledWith(
      {
        kind: 'typing',
        path: ['name'],
        scope: [],
        refreshable: false,
      },
      {name: 'My Site', summary: ''}
    );
    expect(renderer.currentValues()).toEqual({
      name: 'My Site',
      summary: 'Derived from My Site',
    });
    expect(mutation).toEqual({
      name: 'My Site',
      summary: 'Derived from My Site',
    });
  });

  it('uses a path slot to override a control without replacing Form behavior', async () => {
    const overridden: FormPayload = {
      scope: [],
      refreshable: false,
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
          component: 'craft:field',
          props: {label: 'Mode', required: true},
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\Choice',
            component: 'craft:choice',
            props: {options: []},
            path: ['mode'],
            mode: 'editable',
            deltaGroup: ['mode'],
          },
        },
      ],
      values: {mode: 'crop'},
      errors: [{path: ['mode'], messages: ['Choose a mode.']}],
      globalErrors: [],
    };
    const onChange = vi.fn();
    app.unmount();
    await mount(overridden, {
      onChange,
      slots: {
        mode: (slot) =>
          h(
            'button',
            {
              'data-mode-override': '',
              'data-invalid': String(slot.invalid),
              onClick: () => slot.setValue('fit'),
            },
            String(slot.value)
          ),
      },
    });

    const button = container.querySelector<HTMLButtonElement>(
      '[data-mode-override]'
    )!;

    expect(button.textContent).toBe('crop');
    expect(button.dataset.invalid).toBe('true');
    expect(container.querySelector('craft-select')).toBeNull();
    expect(container.querySelector('[slot="feedback"]')?.textContent).toContain(
      'Choose a mode.'
    );

    button.click();
    await nextTick();

    expect(renderer.currentValues()).toEqual({mode: 'fit'});
    expect(onChange).toHaveBeenCalledWith(
      {
        kind: 'discrete',
        path: ['mode'],
        scope: [],
        refreshable: false,
      },
      {mode: 'fit'}
    );
  });

  it('accepts override values derived from reactive arrays', async () => {
    const overridden: FormPayload = {
      scope: [],
      refreshable: false,
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
          component: 'craft:field',
          props: {label: 'Preview Targets'},
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\Table',
            component: 'craft:table',
            props: {columns: {}},
            path: ['previewTargets'],
            mode: 'editable',
            deltaGroup: ['previewTargets'],
          },
        },
      ],
      values: {
        previewTargets: [{label: 'Primary', urlFormat: '{url}', refresh: true}],
      },
      errors: [],
      globalErrors: [],
    };
    app.unmount();
    await mount(overridden, {
      slots: {
        previewTargets: (slot) =>
          h(
            'button',
            {
              'data-add-target': '',
              onClick: () =>
                slot.setValue([
                  ...(slot.value as unknown[]),
                  {label: '', urlFormat: '', refresh: true},
                ]),
            },
            'Add a target'
          ),
      },
    });

    container.querySelector<HTMLButtonElement>('[data-add-target]')!.click();
    await nextTick();

    expect(renderer.currentValues()).toEqual({
      previewTargets: [
        {label: 'Primary', urlFormat: '{url}', refresh: true},
        {label: '', urlFormat: '', refresh: true},
      ],
    });
  });

  it('does not report native control events as Form changes', async () => {
    const onChange = vi.fn();
    app.unmount();
    await mount(structuredClone(payload) as FormPayload, {onChange});

    container
      .querySelector('craft-input')!
      .dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(onChange).not.toHaveBeenCalled();
  });

  it('includes hidden controls but not unowned values in complete form values', async () => {
    const hidden: FormPayload = {
      scope: [],
      refreshable: false,
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\HiddenField',
          component: 'craft:hidden-field',
          props: {},
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\Hidden',
            component: 'craft:hidden',
            props: {},
            path: ['siteId'],
            mode: 'editable',
            deltaGroup: ['siteId'],
          },
        },
      ],
      values: {siteId: 42, stale: 'not owned by a control'},
      errors: [],
      globalErrors: [],
    };
    app.unmount();
    await mount(hidden);

    expect(new FormData(form).get('siteId')).toBe('42');
    expect(renderer.currentValues()).toEqual({siteId: 42});
  });

  it('renders collapsible groups with the shared disclosure component', async () => {
    const collapsible = structuredClone(payload) as Mutable<FormPayload>;
    collapsible.nodes[2]!.props.collapsible = true;
    app.unmount();
    await mount(collapsible);

    const disclosure = container.querySelector<HTMLElement & {label: string}>(
      'craft-disclosure[data-form-node="plain-text-field-limit"]'
    );

    expect(disclosure?.label).toBe('Field Limit');
    expect(
      disclosure?.querySelector('craft-field-group[slot="content"]')
    ).not.toBeNull();
  });

  it('initializes and reads server-rendered condition builder updates', async () => {
    const condition = structuredClone(payload) as Mutable<FormPayload>;
    condition.nodes = [condition.nodes[0]!];
    condition.nodes[0]!.control = {
      type: 'CraftCms\\Cms\\Form\\Controls\\ConditionBuilder',
      component: 'craft:condition-builder',
      props: {
        conditionClass: 'CraftCms\\Cms\\Entry\\Conditions\\EntryCondition',
        queryParams: ['site'],
        forProjectConfig: true,
      },
      path: ['settings', 'selectionCondition'],
      mode: 'editable',
      deltaGroup: ['settings', 'selectionCondition'],
    };
    condition.values = {
      settings: {selectionCondition: {conditionRules: []}},
    };
    let finishFirstRead!: () => void;
    const firstRead = new Promise<void>((resolve) => {
      finishFirstRead = resolve;
    });
    let reads = 0;
    const request = vi
      .spyOn(actionClient, 'post')
      .mockImplementation(async (url) => {
        if (url === 'fields/render-condition-builder') {
          return {
            data: {
              html: '<div class="condition-container"><span class="legacy-vue-template">{{ suggestion.item.name }}</span><input name="settings[selectionCondition][conditionRules][1][class]" value="Title"></div>',
              headHtml: '<style data-condition-builder></style>',
              bodyHtml: '<script data-condition-builder></script>',
            },
          };
        }

        reads++;
        if (reads === 1) {
          await firstRead;
        }

        return {
          data: {
            value:
              reads === 1
                ? {conditionRules: []}
                : {conditionRules: [{class: 'Title'}]},
          },
        };
      });
    app.unmount();
    await mount(condition);

    await vi.waitFor(() =>
      expect(
        document.head.querySelector('[data-condition-builder]')
      ).not.toBeNull()
    );
    expect(
      document.body.querySelector('script[data-condition-builder]')
    ).not.toBeNull();

    const builder = container.querySelector('.condition-container')!;
    builder.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await new Promise(requestAnimationFrame);
    expect(reads).toBe(0);
    expect(
      builder.querySelector('.legacy-vue-template')?.textContent
    ).toContain('{{ suggestion.item.name }}');
    builder.dispatchEvent(new InputEvent('input', {bubbles: true}));
    await vi.waitFor(() => expect(reads).toBe(1));
    builder.dispatchEvent(new CustomEvent('htmx:afterSwap', {bubbles: true}));
    await new Promise(requestAnimationFrame);
    finishFirstRead();
    await vi.waitFor(() =>
      expect(renderer.currentValues()).toMatchObject({
        settings: {
          selectionCondition: {conditionRules: [{class: 'Title'}]},
        },
      })
    );
    expect(request).toHaveBeenCalledWith(
      'fields/normalize-condition-builder',
      expect.any(Object)
    );
    request.mockRestore();
    document
      .querySelectorAll('[data-condition-builder]')
      .forEach((element) => element.remove());
  });

  it('renders a reactive payload', async () => {
    app.unmount();
    await mount(reactive(structuredClone(payload)) as FormPayload);

    expect(
      container.querySelector('input[name="settings[placeholder]"]')
    ).not.toBeNull();
  });

  it('renders FieldLayout tabs and semantic content', async () => {
    app.unmount();
    await mount({
      scope: [],
      refreshable: false,
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Tab',
          component: 'craft:tab',
          props: {label: 'Content'},
          uid: 'tab-content',
          children: [
            {
              type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
              component: 'craft:field',
              props: {
                label: 'Headline',
                instructions: 'Keep it short.',
                instructionsPosition: 'after',
                tip: 'Use *sentence* case.',
                tipHtml: 'Use <em>sentence</em> case.',
                warning: 'This appears **publicly**.',
                warningHtml: 'This appears <strong>publicly</strong>.',
                required: true,
                layoutUid: 'field-title',
                width: 50,
                status: 'modified',
                statusLabel: 'This field has been modified.',
              },
              control: {
                type: 'CraftCms\\Cms\\Form\\Controls\\Text',
                component: 'craft:text',
                props: {},
                path: ['title'],
                mode: 'readOnly',
                deltaGroup: ['title'],
              },
            },
            {
              type: 'CraftCms\\Cms\\Form\\Nodes\\MarkdownContent',
              component: 'craft:markdown-content',
              props: {
                html: '<p><strong>Editorial note</strong></p>',
                displayInPane: true,
                width: 50,
              },
              uid: 'content-note',
              children: [],
            },
            {
              type: 'CraftCms\\Cms\\Form\\Nodes\\TemplateContent',
              component: 'craft:template-content',
              props: {
                html: '<p><strong>Template note</strong></p>',
                width: 50,
              },
              uid: 'template-content',
              children: [],
            },
            {
              type: 'CraftCms\\Cms\\Form\\Nodes\\Heading',
              component: 'craft:heading',
              props: {
                content: 'Details',
                description: 'Supporting copy.',
                level: 3,
                width: 100,
              },
              uid: 'heading',
              children: [],
            },
            {
              type: 'CraftCms\\Cms\\Form\\Nodes\\Separator',
              component: 'craft:separator',
              props: {},
              uid: 'separator',
              children: [],
            },
            {
              type: 'CraftCms\\Cms\\Form\\Nodes\\LineBreak',
              component: 'craft:line-break',
              props: {},
              uid: 'line-break',
              children: [],
            },
            {
              type: 'CraftCms\\Cms\\Form\\Nodes\\Callout',
              component: 'craft:callout',
              props: {
                html: '<p><strong>Careful</strong></p>',
                variant: 'warning',
                appearance: 'plain',
                icon: 'circle-info',
                dismissible: false,
                width: 50,
              },
              uid: 'callout',
              children: [],
            },
            {
              type: 'CraftCms\\Cms\\Form\\Nodes\\Callout',
              component: 'craft:callout',
              props: {
                html: '<p>Default appearance</p>',
                variant: 'success',
                dismissible: false,
                width: 100,
              },
              uid: 'callout-default-appearance',
              children: [],
            },
          ],
        },
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Tab',
          component: 'craft:tab',
          props: {label: 'SEO'},
          uid: 'tab-seo',
          children: [
            {
              type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
              component: 'craft:field',
              props: {label: 'Slug'},
              control: {
                type: 'CraftCms\\Cms\\Form\\Controls\\Text',
                component: 'craft:text',
                props: {},
                path: ['slug'],
                mode: 'editable',
                deltaGroup: ['slug'],
              },
            },
          ],
        },
      ],
      values: {title: 'Persisted title', slug: 'persisted-title'},
      errors: [{path: ['slug'], messages: ['Slug is already taken.']}],
      globalErrors: [],
    });

    const tab = container.querySelector<HTMLElement>(
      'section[data-form-tab="tab-content"]'
    );
    const seoTab = container.querySelector<HTMLElement>(
      'section[data-form-tab="tab-seo"]'
    );
    const tabButtons = [
      ...container.querySelectorAll<HTMLElement>('[role="tab"]'),
    ];
    const content = tab?.querySelector<HTMLElement>(
      '[data-form-node="content-note"]'
    );
    const templateContent = tab?.querySelector<HTMLElement>(
      '[data-form-node="template-content"]'
    );

    expect(tabButtons).toHaveLength(2);
    expect(tabButtons[0]?.getAttribute('aria-selected')).toBe('true');
    expect(tabButtons[1]?.getAttribute('aria-selected')).toBe('false');
    expect(tabButtons[1]?.querySelector('craft-icon')).not.toBeNull();
    expect(tab?.getAttribute('aria-label')).toBe('Content');
    // `craft-tabs` pairs the two in external-panel mode: the tab points at the
    // panel id this component assigned, and the panel back at the tab's own id
    // — which the strip generates, so it's matched rather than spelled out.
    expect(tabButtons[0]?.getAttribute('aria-controls')).toBe(
      'form-tab-tab-content-tab'
    );
    expect(tab?.getAttribute('aria-labelledby')).toBe(tabButtons[0]?.id);
    expect(tabButtons[0]?.id).toBeTruthy();
    expect(tab?.classList).not.toContain('hidden');
    expect(seoTab?.classList).toContain('hidden');

    tabButtons[1]!.click();
    await nextTick();

    expect(tab?.classList).toContain('hidden');
    expect(seoTab?.classList).not.toContain('hidden');
    expect(tabButtons[1]?.getAttribute('aria-selected')).toBe('true');

    // `craft-tabs` claims the navigation keys on keydown but moves the
    // selection on keyup, so a realistic press is both.
    tabButtons[1]!.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Home', bubbles: true})
    );
    tabButtons[1]!.dispatchEvent(
      new KeyboardEvent('keyup', {key: 'Home', bubbles: true})
    );
    await nextTick();

    expect(tab?.classList).not.toContain('hidden');
    expect(seoTab?.classList).toContain('hidden');
    expect(document.activeElement).toBe(tabButtons[0]);
    expect(
      tab?.querySelector('craft-field')?.classList.contains('width-50')
    ).toBe(true);
    expect(tab?.querySelector('craft-field')?.dataset.layoutElement).toBe(
      'field-title'
    );
    const statusField = tab?.querySelector('craft-field') as
      | (HTMLElement & {status?: string; statusLabel?: string})
      | null;
    await (statusField as unknown as {updateComplete?: Promise<unknown>})
      ?.updateComplete;
    expect(statusField?.status).toBe('modified');
    expect(statusField?.statusLabel).toBe('This field has been modified.');
    // The status name rides on the host (reflected, and on the wrapper's
    // `form-field--*` class); the indicator itself just marks the spot.
    expect(
      statusField?.shadowRoot?.querySelector('.form-field__status-indicator')
    ).not.toBeNull();
    expect(tab?.querySelector('[slot="tip"] em')?.textContent).toBe('sentence');
    expect(tab?.querySelector('[slot="warning"] strong')?.textContent).toBe(
      'publicly'
    );
    expect(content?.classList.contains('pane')).toBe(true);
    expect(content?.classList.contains('width-50')).toBe(true);
    expect(content?.querySelector('strong')?.textContent).toBe(
      'Editorial note'
    );
    expect(templateContent?.inert).toBe(true);
    expect(templateContent?.classList.contains('width-50')).toBe(true);
    expect(templateContent?.querySelector('strong')?.textContent).toBe(
      'Template note'
    );
    const heading = tab?.querySelector('[data-form-node="heading"]');
    expect(heading?.querySelector('h3')?.textContent).toBe('Details');
    expect(heading?.querySelector('h3')?.classList).toContain('my-0');
    expect(heading?.querySelector('p')?.textContent).toBe('Supporting copy.');
    expect(heading?.querySelector('p')?.classList).toContain('my-0');
    expect(heading?.classList).toContain('gap-1');
    expect(tab?.querySelector('hr[data-form-node="separator"]')).not.toBeNull();
    expect(
      tab?.querySelector('[data-form-node="line-break"]')?.classList
    ).toContain('line-break');
    const callout = tab?.querySelector(
      'craft-callout[data-form-node="callout"]'
    ) as
      | (HTMLElement & {
          appearance: string;
          icon: string;
          updateComplete: Promise<unknown>;
        })
      | undefined;
    await callout?.updateComplete;

    expect(callout?.textContent).toContain('Careful');
    expect(callout?.appearance).toBe('plain');
    expect(callout?.icon).toBe('circle-info');
    expect(
      callout?.shadowRoot?.querySelector('craft-icon')?.getAttribute('name')
    ).toBe('circle-info');
    const defaultAppearanceCallout = tab?.querySelector(
      'craft-callout[data-form-node="callout-default-appearance"]'
    ) as (HTMLElement & {appearance: string}) | undefined;

    expect(defaultAppearanceCallout?.appearance).toBe('outline-fill');
  });

  it('renders server-localized copy unchanged', async () => {
    const localized = structuredClone(payload) as Mutable<FormPayload>;
    localized.nodes[0]!.props.label = 'UI-Modus';
    app.unmount();
    await mount(localized);

    expect(
      container.querySelector('craft-field > [slot="label"]')?.textContent
    ).toBe('UI-Modus');
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

  it('renders missing persisted providers without submitting their values', async () => {
    const missing = structuredClone(payload) as Mutable<FormPayload>;
    missing.nodes = [
      {
        type: 'CraftCms\\Cms\\Form\\Nodes\\Missing',
        component: 'craft:missing-node',
        props: {
          provider: 'Acme\\Forms\\MissingLayoutElement',
          error:
            'Form Node provider [Acme\\Forms\\MissingLayoutElement] is unavailable.',
          pluginName: 'Missing Node Plugin',
          action: {
            label: 'Enable',
            url: '/settings/plugins/missing-node-plugin/enable',
            method: 'post',
          },
        },
        uid: 'missing-node',
        children: [],
      },
      {
        type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Unavailable field'},
        control: {
          type: 'CraftCms\\Cms\\Form\\Controls\\Missing',
          component: 'craft:missing-control',
          props: {
            provider: 'Acme\\Forms\\MissingField',
            error:
              'Form Control provider [Acme\\Forms\\MissingField] is unavailable.',
            pluginName: 'Missing Control Plugin',
            action: {
              label: 'Install',
              url: '/settings/plugins/missing-control-plugin/install',
              method: 'post',
            },
          },
          path: ['settings', 'missing'],
          mode: 'disabled',
          deltaGroup: ['settings', 'missing'],
        },
      },
    ];
    missing.values = {settings: {missing: 'Original content'}};
    app.unmount();
    await mount(missing);

    const placeholders = container.querySelectorAll('craft-missing-component');

    expect(placeholders[0]?.error).toContain(
      'Acme\\Forms\\MissingLayoutElement'
    );
    expect(placeholders[1]?.error).toContain('Acme\\Forms\\MissingField');
    expect(placeholders[0]?.pluginName).toBe('Missing Node Plugin');
    expect(placeholders[1]?.pluginName).toBe('Missing Control Plugin');
    expect(placeholders[1]?.slot).toBe('input');
    expect(
      placeholders[0]?.querySelector<HTMLButtonElement>('[slot="action"]')
        ?.formAction
    ).toContain('/settings/plugins/missing-node-plugin/enable');
    expect(
      placeholders[1]?.querySelector<HTMLButtonElement>('[slot="action"]')
        ?.formAction
    ).toContain('/settings/plugins/missing-control-plugin/install');
    expect(
      placeholders[0]?.querySelector<HTMLButtonElement>('[slot="action"]')?.form
    ).toBe(form);
    expect(container.querySelector('input, select, textarea')).toBeNull();
    expect(Object.fromEntries(new FormData(form))).not.toHaveProperty(
      'settings[missing]'
    );
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
      values: {settings: values},
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
    expect(refresh).toHaveBeenCalledWith(
      expect.objectContaining({placeholder: 'Changed in Vue'}),
      ['settings']
    );

    placeholder.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();
    await vi.advanceTimersByTimeAsync(1000);
    expect(refresh).toHaveBeenCalledOnce();
    vi.useRealTimers();
  });

  it('keeps the active text expander suggestion across a form refresh', async () => {
    vi.spyOn(HTMLElement.prototype, 'offsetWidth', 'get').mockReturnValue(1);
    vi.useFakeTimers();
    const refreshable = structuredClone(payload) as Mutable<FormPayload>;
    refreshable.refreshable = true;
    refreshable.nodes[1]!.control!.props.textExpanderTriggers = [
      {
        trigger: '@',
        boundary: 'anywhere',
        options: [
          {label: 'Brad', value: '@brad'},
          {label: 'Brandon', value: '@brandon'},
        ],
      },
    ];
    const refresh = vi.fn(async (values: FormPayload['values']) => ({
      ...structuredClone(refreshable),
      values: {settings: values},
    })) as unknown as (values: FormPayload['values']) => Promise<FormPayload>;
    app.unmount();
    await mount(refreshable, {refresh});

    const target = container.querySelector<HTMLInputElement>(
      'input[name="settings[placeholder]"]'
    )!;
    const expander = container.querySelector<
      HTMLElement & {
        updateComplete: Promise<unknown>;
      }
    >('craft-text-expander')!;
    await expander.updateComplete;
    target.focus();
    target.value = '@b';
    target.setSelectionRange(2, 2);
    target.dispatchEvent(new InputEvent('input', {bubbles: true}));
    await nextTick();

    const initialOptions = expander.querySelectorAll('craft-option');
    expect(initialOptions).toHaveLength(2);
    await vi.waitFor(() =>
      expect(initialOptions[0]!.getAttribute('aria-selected')).toBe('true')
    );
    target.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'ArrowDown', bubbles: true})
    );
    expect(initialOptions[1]!.getAttribute('aria-selected')).toBe('true');

    await vi.advanceTimersByTimeAsync(1000);
    await nextTick();
    await expander.updateComplete;

    expect(refresh).toHaveBeenCalledOnce();
    const refreshedOptions = expander.querySelectorAll('craft-option');
    await vi.waitFor(() =>
      expect(
        Array.from(refreshedOptions).some(
          (option) => option.getAttribute('aria-selected') === 'true'
        )
      ).toBe(true)
    );
    expect(refreshedOptions[1]!.getAttribute('aria-selected')).toBe('true');
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

  it('retains removed values without including them in submissions', async () => {
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
    expect(renderer.currentValues()).not.toHaveProperty('settings.placeholder');

    currentPayload.value = structuredClone(payload) as FormPayload;
    await nextTick();
    expect(
      container.querySelector<HTMLInputElement>(
        'input[name="settings[placeholder]"]'
      )?.value
    ).toBe('Unsaved');
    expect(renderer.currentValues()).toHaveProperty(
      'settings.placeholder',
      'Unsaved'
    );
    expect(
      container
        .querySelector('input[name="settings[placeholder]"]')
        ?.closest('[data-form-touched]')
        ?.getAttribute('data-form-touched')
    ).toBe('true');

    renderer.advanceBaseline();
    expect(mutation).toEqual({});
  });

  /**
   * A refresh keeps unsaved values because the client owns them. Discarding is
   * the one case where it doesn't — the user has thrown them away — so the host
   * says so explicitly rather than a payload arriving meaning it implicitly.
   */
  it('drops unsaved values when the host resets the Form', async () => {
    let mutation: FormPayload['values'] = {};
    app.unmount();
    await mount(structuredClone(payload) as FormPayload, {
      onMutation: (value) => (mutation = value),
    });

    const placeholder = () =>
      container.querySelector<HTMLInputElement>(
        'input[name="settings[placeholder]"]'
      )!;
    placeholder().value = 'Unsaved';
    placeholder().dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(mutation).toHaveProperty('settings.placeholder', 'Unsaved');

    // The canonical payload arriving on its own leaves the edit alone.
    currentPayload.value = structuredClone(payload) as FormPayload;
    await nextTick();

    expect(placeholder().value).toBe('Unsaved');

    renderer.resetValues();
    await nextTick();

    expect(placeholder().value).toBe('Submitted placeholder');
    expect(renderer.currentValues()).toHaveProperty(
      'settings.placeholder',
      'Submitted placeholder'
    );
    // Nothing left to submit, and nothing left touched.
    expect(mutation).toEqual({});
    expect(
      placeholder()
        .closest('[data-form-touched]')
        ?.getAttribute('data-form-touched')
    ).not.toBe('true');
  });

  it('drops unsaved values inside nested Forms when the host resets', async () => {
    let mutation: FormPayload['values'] = {};
    const nested = structuredClone(payload) as Mutable<FormPayload>;
    const blockScope = ['settings', 'matrix', 'entries', 'block-a'];
    nested.refreshable = false;
    nested.values = {
      settings: {
        matrix: {
          entries: {'block-a': {type: 'text', heading: 'Canonical heading'}},
          sortOrder: ['block-a'],
        },
      },
    };
    nested.nodes = [
      {
        type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Content', instructions: null, required: false},
        control: {
          type: 'CraftCms\\Cms\\Form\\Controls\\Matrix',
          component: 'craft:matrix',
          props: {
            entryTypes: [{value: 'text', label: 'Text'}],
            addLabel: 'Add an entry',
            minEntries: null,
            maxEntries: null,
          },
          path: ['settings', 'matrix'],
          mode: 'editable',
          deltaGroup: ['settings', 'matrix'],
          forms: [
            {
              scope: blockScope,
              refreshable: false,
              nodes: [
                {
                  type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
                  component: 'craft:field',
                  props: {
                    label: 'Heading',
                    instructions: null,
                    required: false,
                  },
                  control: {
                    type: 'CraftCms\\Cms\\Form\\Controls\\Text',
                    component: 'craft:text',
                    props: {inputType: 'text'},
                    path: [...blockScope, 'heading'],
                    mode: 'editable',
                    deltaGroup: ['settings', 'matrix'],
                    forms: [],
                  },
                },
              ],
            },
          ],
        },
      },
    ] as Mutable<FormPayload>['nodes'];
    nested.errors = [];
    app.unmount();
    await mount(nested as FormPayload, {
      onMutation: (value) => (mutation = value),
    });

    const heading = () =>
      container.querySelector<HTMLInputElement>(
        'input[name="settings[matrix][entries][block-a][heading]"]'
      )!;
    heading().value = 'Unsaved heading';
    heading().dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(mutation).not.toEqual({});

    renderer.resetValues();
    await nextTick();

    expect(heading().value).toBe('Canonical heading');
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

  it('selects and removes ordered element relationships as changed-only values', async () => {
    let mutation: FormPayload['values'] = {};
    let selectElements: (elements: Array<Record<string, unknown>>) => void;
    const createElementSelectorModal = vi.fn(
      (_elementType: string, settings: Record<string, unknown>) => {
        selectElements = settings.onSelect as typeof selectElements;

        return {};
      }
    );
    vi.stubGlobal('Craft', {
      createElementSelectorModal,
      initUiElements: vi.fn(),
    });
    const relational = structuredClone(payload) as Mutable<FormPayload>;
    relational.nodes = [relational.nodes[0]!];
    relational.values = {settings: {related: [2, 1]}};
    relational.errors = [
      {path: ['settings', 'related'], messages: ['Choose valid entries.']},
    ];
    relational.nodes[0]!.control = {
      type: 'CraftCms\\Cms\\Form\\Controls\\ElementSelect',
      component: 'craft:element-select',
      props: {
        elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
        customElement: 'craft-entry-select-input',
        elements: [
          {id: 2, label: 'Second entry'},
          {id: 1, label: 'First entry'},
        ],
        sources: null,
        criteria: {},
        selectionLabel: 'Add an entry',
        limit: null,
        showSiteMenu: true,
      },
      path: ['settings', 'related'],
      mode: 'editable',
      deltaGroup: ['settings', 'related'],
    };
    app.unmount();
    await mount(relational, {
      onMutation: (value) => (mutation = value),
    });

    // The chip's own text nodes only — its `[slot="suffix"]` action menu
    // contributes the items' labels to `textContent`.
    expect(
      [...container.querySelectorAll('craft-chip')].map((chip) =>
        [...chip.childNodes]
          .filter((node) => node.nodeType === Node.TEXT_NODE)
          .map((node) => node.textContent)
          .join('')
          .trim()
      )
    ).toEqual(['Second entry', 'First entry']);
    expect(container.textContent).toContain('Choose valid entries.');
    container.querySelector<HTMLElement>('[data-element-select-add]')!.click();
    selectElements!([{id: 3, label: 'Third entry', siteId: 1}]);
    await nextTick();

    expect(createElementSelectorModal).toHaveBeenCalledWith(
      'CraftCms\\Cms\\Entry\\Elements\\Entry',
      expect.objectContaining({disabledElementIds: [2, 1]})
    );
    expect(elementSelectMocks.entry).toHaveBeenCalled();
    expect(mutation).toEqual({settings: {related: [2, 1, 3]}});

    elementSelectMocks.removeElement(2);
    await nextTick();

    expect(renderer.currentValues()).toEqual({settings: {related: [1, 3]}});
    expect(mutation).toEqual({settings: {related: [1, 3]}});
    expect([...new FormData(form).getAll('settings[related][]')]).toEqual([
      '1',
      '3',
    ]);
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
        'craft:text',
        'Text',
        'retryDuration',
        {maxLength: 4, inputMode: 'numeric'},
        '60',
      ],
      [
        'craft:textarea',
        'Textarea',
        'summary',
        {rows: 4, maxLength: 120, placeholder: '<write>'},
        '<summary>',
      ],
      [
        'craft:combobox',
        'Combobox',
        'timezone',
        {
          options: [{label: 'UTC', value: 'UTC'}],
          limit: 10,
          clearable: true,
          requireOptionMatch: true,
          showAllOnEmpty: true,
          dir: 'rtl',
        },
        'UTC',
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
      [
        'craft:date-time',
        'DateTime',
        'datetime',
        {
          showDate: true,
          showTime: true,
          showTimeZone: true,
          locale: 'en-US',
          minuteIncrement: 15,
        },
        {
          date: '2026-08-07',
          time: '14:30',
          timezone: 'Europe/Brussels',
        },
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
    const retryDuration = container.querySelector<HTMLInputElement>(
      'input[name="settings[retryDuration]"]'
    )!;
    expect(retryDuration.inputMode).toBe('numeric');
    expect(retryDuration.maxLength).toBe(4);
    const combobox = container.querySelector(
      'craft-combobox'
    ) as HTMLElement & {
      limit: number;
      clearable: boolean;
      requireOptionMatch: boolean;
      showAllOnEmpty: boolean;
    };
    expect(combobox.limit).toBe(10);
    expect(combobox.clearable).toBe(true);
    expect(combobox.requireOptionMatch).toBe(true);
    expect(combobox.showAllOnEmpty).toBe(true);
    expect(combobox.dir).toBe('rtl');
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
    const clearDateTime = container.querySelector<HTMLButtonElement>(
      'craft-input-date-time > .clear-btn'
    );
    expect(clearDateTime).not.toBeNull();
    clearDateTime!.click();
    await nextTick();
    expect(renderer.currentValues()).toMatchObject({
      settings: {datetime: {date: '', time: '', timezone: ''}},
    });
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

  it('renders and submits composite and rich Controls', async () => {
    const controlsPayload = structuredClone(payload) as Mutable<FormPayload>;
    const controls = [
      [
        'craft:markdown',
        'Markdown',
        'body',
        {
          rows: 6,
          placeholder: 'Write <Markdown>',
          toolbarButtons: ['bold', 'link'],
          showToolbar: true,
        },
        '<script>alert(1)</script> **Safe**',
      ],
      [
        'craft:table',
        'Table',
        'rows',
        {
          columns: {
            name: {heading: 'Name', type: 'singleline'},
            enabled: {heading: 'Enabled', type: 'checkbox'},
          },
          allowAdd: true,
          allowDelete: true,
          allowReorder: true,
          minRows: 2,
        },
        [{name: '<Row>', enabled: true}],
      ],
      [
        'craft:link',
        'Link',
        'link',
        {
          types: [{id: 'url', label: 'URL', kind: 'text'}],
          showLabelField: true,
          advancedFields: ['urlSuffix', 'title'],
        },
        {type: 'url', value: 'https://craftcms.com', label: '<Craft>'},
      ],
      [
        'craft:choice',
        'Choice',
        'advancedFields',
        {
          options: [
            {
              label: 'Relation (rel)',
              labelHtml: 'Relation (<code>rel</code>)',
              value: 'rel',
            },
          ],
          multiple: true,
          presentation: 'checkboxes',
        },
        ['rel'],
      ],
      [
        'craft:address',
        'Address',
        'address',
        {
          countryCode: 'BE',
          fields: [
            {
              name: 'addressLine1',
              label: 'Address Line 1',
              type: 'text',
              visible: true,
              required: true,
            },
            {
              name: 'administrativeArea',
              label: 'Province',
              type: 'select',
              visible: true,
              required: false,
              spinner: true,
              options: {'BE-VAN': 'Antwerp', 'BE-WBR': 'Walloon Brabant'},
            },
            {
              name: 'locality',
              label: 'City',
              type: 'text',
              visible: true,
              required: true,
            },
          ],
        },
        {
          addressLine1: 'Museumstraat 1',
          administrativeArea: 'BE-VAN',
          locality: 'Antwerp',
        },
      ],
      ['craft:icon-picker', 'Icon', 'icon', {freeOnly: true}, 'star'],
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
      {
        path: ['settings', 'link'],
        messages: ['Enter a valid link.'],
      },
    ];
    controlsPayload.globalErrors = [];
    app.unmount();
    await mount(controlsPayload);
    await vi.waitFor(() => {
      expect(
        container.querySelector<HTMLTextAreaElement>(
          'craft-markdown-field textarea'
        )?.value
      ).toBe('<script>alert(1)</script> **Safe**');
    });
    expect(
      container.querySelector<HTMLTextAreaElement>(
        'craft-markdown-field textarea'
      )?.name
    ).toBe('settings[body]');
    expect(
      container
        .querySelector('craft-markdown-field')
        ?.hasAttribute('sanitize-html')
    ).toBe(true);
    expect(container.innerHTML).not.toContain('<script>alert(1)</script>');
    expect(
      container.querySelector<HTMLInputElement>(
        'textarea[name="settings[rows][0][name]"]'
      )?.value
    ).toBe('<Row>');
    expect(
      container.querySelector<HTMLInputElement>(
        'input[name="settings[rows][0][enabled]"][type="checkbox"]'
      )?.checked
    ).toBe(true);
    await vi.waitFor(() =>
      expect(
        container.querySelector<HTMLInputElement>(
          'input[name="settings[link][value]"]'
        )?.value
      ).toBe('https://craftcms.com')
    );
    expect(
      container.querySelectorAll('craft-link-field craft-field-group')
    ).toHaveLength(2);
    expect(
      container.querySelector(
        'craft-link-field craft-disclosure craft-field-group[slot="content"]'
      )
    ).not.toBeNull();
    expect(
      container.querySelector<HTMLInputElement>(
        'input[name="settings[address][addressLine1]"]'
      )?.value
    ).toBe('Museumstraat 1');
    expect(
      container.querySelector<HTMLInputElement>('input[name="settings[icon]"]')
        ?.value
    ).toBe('star');
    expect(container.textContent).toContain('Enter a valid link.');
    expect(
      container.querySelector('craft-checkbox label code')?.textContent
    ).toBe('rel');
    await vi.waitFor(() =>
      expect(
        (renderer.currentValues().settings as Record<string, unknown>).rows
      ).toEqual([
        {name: '<Row>', enabled: true},
        {name: '', enabled: false},
      ])
    );

    const tableInput = container.querySelector<HTMLTextAreaElement>(
      'textarea[name="settings[rows][0][name]"]'
    )!;
    tableInput.value = '<Changed row>';
    tableInput.dispatchEvent(new Event('input', {bubbles: true}));

    container.querySelector('craft-link-field')!.dispatchEvent(
      new CustomEvent('apply', {
        bubbles: true,
        detail: {
          defaultLabel: 'example.com',
          href: 'https://example.com',
          label: 'Example',
          title: '',
          type: 'url',
          urlSuffix: '',
          value: 'https://example.com',
        },
      })
    );
    await nextTick();

    expect(renderer.currentValues()).toMatchObject({
      settings: {
        rows: [
          {name: '<Changed row>', enabled: true},
          {name: '', enabled: false},
        ],
      },
    });
    const addressLine = container.querySelector<
      HTMLElement & {modelValue: string}
    >('craft-input[name="settings[address][addressLine1]"]')!;
    addressLine.modelValue = 'Changed address';
    addressLine.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();
    expect(renderer.currentValues()).toMatchObject({
      settings: {address: {addressLine1: 'Changed address'}},
    });

    const originalAddressFields = (
      controlsPayload.nodes.find(
        (node) => node.control?.component === 'craft:address'
      )!.control!.props as {fields: Array<Record<string, unknown>>}
    ).fields;
    const selectLocalityFields = structuredClone(originalAddressFields);
    Object.assign(
      selectLocalityFields.find((field) => field.name === 'locality')!,
      {type: 'select', options: {Brussels: 'Brussels'}, spinner: true}
    );
    const request = vi
      .spyOn(actionClient, 'post')
      .mockResolvedValueOnce({
        data: {fieldDefinitions: originalAddressFields},
      })
      .mockResolvedValueOnce({
        data: {fieldDefinitions: selectLocalityFields},
      });
    const administrativeArea = container.querySelector<
      HTMLElement & {modelValue: string}
    >('craft-select[name="settings[address][administrativeArea]"]')!;
    administrativeArea.modelValue = 'BE-WBR';
    administrativeArea.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await vi.waitFor(() => expect(request).toHaveBeenCalledOnce());
    expect(request).toHaveBeenCalledWith('addresses/fields', {
      namespace: 'settings[address]',
      countryCode: 'BE',
      administrativeArea: 'BE-WBR',
    });
    expect(renderer.currentValues()).toMatchObject({
      settings: {address: {locality: 'Antwerp'}},
    });

    administrativeArea.modelValue = 'BE-VAN';
    administrativeArea.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await vi.waitFor(() => expect(request).toHaveBeenCalledTimes(2));
    await vi.waitFor(() =>
      expect(renderer.currentValues()).toMatchObject({
        settings: {
          address: {administrativeArea: 'BE-VAN', locality: null},
        },
      })
    );
    expect(container.querySelectorAll('tbody tr')).toHaveLength(2);
    expect(Object.fromEntries(new FormData(form))).toMatchObject({
      'settings[link][value]': 'https://example.com',
      'settings[address][addressLine1]': 'Changed address',
      'settings[icon]': 'star',
    });

    for (const mode of ['readOnly', 'disabled'] as const) {
      const nonEditable = structuredClone(controlsPayload);
      visitControls(nonEditable.nodes, (control) => (control.mode = mode));
      currentPayload.value = nonEditable;
      await vi.waitFor(() =>
        expect(Array.from(new FormData(form))).toEqual([])
      );
      const linkField = container.querySelector<
        HTMLElement & {disabled: boolean}
      >('craft-link-field')!;
      expect(linkField.disabled).toBe(true);
      await vi.waitFor(() =>
        expect(
          linkField.querySelector<HTMLElement & {disabled: boolean}>(
            'craft-input'
          )?.disabled
        ).toBe(true)
      );
      expect(
        container.querySelector<HTMLElement & {modelValue: unknown}>(
          '.address-fields craft-input'
        )?.modelValue
      ).toBe('Museumstraat 1');

      const refreshed = structuredClone(nonEditable);
      (refreshed.values.settings as Record<string, unknown>).body =
        'Refreshed **Markdown**';
      (refreshed.values.settings as Record<string, unknown>).rows = [
        {name: 'Refreshed row', enabled: false},
      ];
      currentPayload.value = refreshed;
      await vi.waitFor(() => {
        expect(
          container.querySelector<HTMLTextAreaElement>(
            'craft-markdown-field textarea'
          )?.value
        ).toBe('Refreshed **Markdown**');
        expect(
          container.querySelector<HTMLTextAreaElement>('tbody textarea')?.value
        ).toBe('Refreshed row');
      });
    }
  });

  it('edits nested Controls as one ordered atomic mutation', async () => {
    vi.useFakeTimers();
    let mutation: FormPayload['values'] = {};
    const nested = structuredClone(payload) as Mutable<FormPayload>;
    const textControl = (uid: string, path: string) => ({
      type: 'CraftCms\\Cms\\Form\\Controls\\Text',
      component: 'craft:text',
      props: {inputType: 'text'},
      path: ['settings', 'matrix', 'entries', uid, path],
      mode: 'editable' as const,
      deltaGroup: ['settings', 'matrix'],
      forms: [],
    });
    const fieldNode = (
      label: string,
      control: ReturnType<typeof textControl> | Record<string, unknown>
    ) => ({
      type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
      component: 'craft:field',
      props: {label, instructions: null, required: false},
      control,
    });
    const firstScope = ['settings', 'matrix', 'entries', 'block-a'];
    const secondScope = ['settings', 'matrix', 'entries', 'block-b'];
    const contentScope = [...firstScope, 'content'];
    nested.refreshable = false;
    nested.values = {
      settings: {
        matrix: {
          entries: {
            'block-a': {
              type: 'text',
              heading: 'First',
              content: {body: 'Nested body'},
            },
            'block-b': {type: 'text', heading: 'Second'},
          },
          sortOrder: ['block-a', 'block-b'],
        },
      },
    };
    nested.nodes = [
      fieldNode('Content', {
        type: 'CraftCms\\Cms\\Form\\Controls\\Matrix',
        component: 'craft:matrix',
        props: {
          entryTypes: [{value: 'text', label: 'Text'}],
          addLabel: 'Add an entry',
          minEntries: null,
          maxEntries: null,
        },
        path: ['settings', 'matrix'],
        mode: 'editable',
        deltaGroup: ['settings', 'matrix'],
        forms: [
          {
            scope: firstScope,
            refreshable: true,
            nodes: [
              fieldNode('Heading', textControl('block-a', 'heading')),
              fieldNode('Content block', {
                type: 'CraftCms\\Cms\\Form\\Controls\\ContentBlock',
                component: 'craft:content-block',
                props: {
                  addLabel: 'Add content',
                  clearLabel: 'Clear content',
                  emptyLabel: 'No content.',
                },
                path: [...firstScope, 'content'],
                mode: 'editable',
                deltaGroup: ['settings', 'matrix'],
                forms: [
                  {
                    scope: contentScope,
                    refreshable: true,
                    nodes: [
                      fieldNode('Body', {
                        ...textControl('block-a', 'body'),
                        path: [...contentScope, 'body'],
                      }),
                    ],
                  },
                ],
              }),
            ],
          },
          {
            scope: secondScope,
            refreshable: true,
            nodes: [fieldNode('Heading', textControl('block-b', 'heading'))],
          },
        ],
      }),
    ] as Mutable<FormPayload>['nodes'];
    nested.errors = [
      {path: [...contentScope, 'body'], messages: ['Body is invalid.']},
    ];
    const firstForm = nested.nodes[0]!.control!.forms![0]!;
    const refresh = vi.fn(
      async (_values: FormPayload['values'], scope?: string[]) => ({
        ...nested,
        scope: scope!,
        refreshable: firstForm.refreshable,
        nodes: firstForm.nodes,
      })
    );
    app.unmount();
    await mount(nested, {
      refresh,
      onMutation: (value) => (mutation = value),
    });

    expect(container.querySelectorAll('.matrixblock')).toHaveLength(2);
    expect(container.querySelectorAll('[data-content-block]')).toHaveLength(1);
    expect(container.textContent).toContain('Body is invalid.');
    const heading = container.querySelector<HTMLInputElement>(
      'input[name="settings[matrix][entries][block-a][heading]"]'
    )!;
    heading.value = 'Changed';
    heading.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(mutation).toEqual({
      settings: {
        matrix: {
          entries: {
            'block-a': {
              type: 'text',
              heading: 'Changed',
              content: {body: 'Nested body'},
            },
            'block-b': {type: 'text', heading: 'Second'},
          },
          sortOrder: ['block-a', 'block-b'],
        },
      },
    });
    await vi.advanceTimersByTimeAsync(1000);
    expect(refresh).toHaveBeenCalledWith(expect.any(Object), firstScope);

    const reorder = container.querySelectorAll('craft-reorder-button')[1]!;
    reorder.dispatchEvent(
      new CustomEvent('reorder', {
        bubbles: true,
        composed: true,
        detail: {direction: 'up'},
      })
    );
    await nextTick();
    expect(
      (renderer.currentValues().settings as Record<string, any>).matrix
        .sortOrder
    ).toEqual(['block-b', 'block-a']);

    container
      .querySelector<HTMLElement>('[data-form-matrix-add="text"]')!
      .click();
    await nextTick();
    const addedMatrix = (
      renderer.currentValues().settings as Record<string, any>
    ).matrix;
    const addedUid = addedMatrix.sortOrder.at(-1);
    expect(addedMatrix.entries[addedUid]).toEqual({type: 'text'});
    expect(
      (mutation.settings as Record<string, any>).matrix.sortOrder
    ).toContain(addedUid);

    const clearContent = [...container.querySelectorAll('craft-button')].find(
      (button) => button.textContent?.includes('Clear content')
    )!;
    clearContent.click();
    await nextTick();
    expect(renderer.currentValues()).toMatchObject({
      settings: {
        matrix: {entries: {'block-a': {content: null}}},
      },
    });

    while (
      container.querySelector<HTMLElement>(
        '.matrixblock craft-button[data-form-matrix-remove]'
      )
    ) {
      container
        .querySelector<HTMLElement>(
          '.matrixblock craft-button[data-form-matrix-remove]'
        )!
        .click();
      await nextTick();
    }

    expect(mutation).toEqual({
      settings: {matrix: {entries: {}, sortOrder: []}},
    });
    expect(new FormData(form).get('settings[matrix]')).toBe('');
    vi.useRealTimers();
  });

  it('renders a just-added entry against the Form the server scoped to its UUID', async () => {
    const uid = '369f71c3-873f-4842-8fe9-90641773b62b';
    app.unmount();
    await mount({
      scope: ['settings'],
      refreshable: false,
      // The block is still keyed the way it was added — with the `uid:` prefix
      // the server strips before saving — so its Form comes back scoped to the
      // bare UUID. Failing to match the two leaves the block on its spinner.
      values: {
        settings: {
          matrix: {
            entries: {[`uid:${uid}`]: {type: 'text'}},
            sortOrder: [`uid:${uid}`],
          },
        },
      },
      errors: [],
      globalErrors: [],
      nodes: [
        {
          type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
          component: 'craft:field',
          props: {label: 'Content'},
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\Matrix',
            component: 'craft:matrix',
            props: {
              entryTypes: [{value: 'text', label: 'Text'}],
              addLabel: 'Add an entry',
              minEntries: null,
              maxEntries: null,
            },
            path: ['settings', 'matrix'],
            mode: 'editable',
            deltaGroup: ['settings', 'matrix'],
            forms: [
              {
                scope: ['settings', 'matrix', 'entries', uid],
                refreshable: false,
                nodes: [
                  {
                    type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
                    component: 'craft:field',
                    props: {label: 'Heading'},
                    control: {
                      type: 'CraftCms\\Cms\\Form\\Controls\\Text',
                      component: 'craft:text',
                      props: {inputType: 'text'},
                      path: ['settings', 'matrix', 'entries', uid, 'heading'],
                      mode: 'editable',
                      deltaGroup: ['settings', 'matrix'],
                      forms: [],
                    },
                  },
                ],
              },
            ],
          },
        },
      ],
    } as unknown as FormPayload);

    expect(container.querySelector('.matrixblock craft-spinner')).toBeNull();
    expect(
      container.querySelector(
        `input[name="settings[matrix][entries][${uid}][heading]"]`
      )
    ).not.toBeNull();
  });

  it('reports no change when a Money field is populated with the server’s empty value', async () => {
    let mutation: FormPayload['values'] | undefined;
    const emptyMoney = structuredClone(payload) as Mutable<FormPayload>;
    emptyMoney.values = {settings: {price: {value: null, locale: 'en-US'}}};
    emptyMoney.errors = [];
    emptyMoney.globalErrors = [];
    emptyMoney.nodes = [
      {
        type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Price', instructions: null, required: false},
        control: {
          type: 'CraftCms\\Cms\\Form\\Controls\\Money',
          component: 'craft:money',
          props: {currency: 'USD', locale: 'en-US', showCurrency: true},
          path: ['settings', 'price'],
          mode: 'editable',
          deltaGroup: ['settings', 'price'],
        },
      },
    ] as Mutable<FormPayload>['nodes'];
    app.unmount();
    await mount(emptyMoney, {onMutation: (value) => (mutation = value)});
    await nextTick();

    // The control announces itself once as it's populated. happy-dom won't
    // bootstrap the underlying form control far enough to fire that on its own,
    // so raise it exactly as the browser does — the field is still empty, and
    // the announcement is not marked as coming from a person.
    const control = container.querySelector('craft-input-money')!;
    control.dispatchEvent(
      new CustomEvent('model-value-changed', {
        bubbles: true,
        detail: {isTriggeredByUser: false},
      })
    );
    await nextTick();

    // Binding the server's own value back onto the control is not an edit. If
    // the control reshapes empty into `''`, this is `{settings: {price: …}}` —
    // a difference the editor would autosave before anyone touched the page.
    expect(mutation ?? {}).toEqual({});

    const input = container.querySelector<HTMLInputElement>(
      'input[name="settings[price][value]"]'
    )!;
    input.value = '12.50';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    // A real edit still comes through.
    expect(mutation).toEqual({
      settings: {price: {value: '12.50', locale: 'en-US'}},
    });
  });

  it('treats an absent value and an empty one as the same, for any control', async () => {
    let mutation: FormPayload['values'] | undefined;
    const emptyText = structuredClone(payload) as Mutable<FormPayload>;
    emptyText.values = {settings: {summary: null}};
    emptyText.errors = [];
    emptyText.globalErrors = [];
    emptyText.nodes = [
      {
        type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Summary', instructions: null, required: false},
        control: {
          type: 'CraftCms\\Cms\\Form\\Controls\\Text',
          component: 'craft:text',
          props: {inputType: 'text'},
          path: ['settings', 'summary'],
          mode: 'editable',
          deltaGroup: ['settings', 'summary'],
        },
      },
    ] as Mutable<FormPayload>['nodes'];
    app.unmount();
    await mount(emptyText, {onMutation: (value) => (mutation = value)});

    // Report an empty string where the server sent nothing. Driving the value
    // directly rather than through the DOM is deliberate: a text input already
    // showing "" won't re-announce itself, but controls layered on a form
    // library do exactly this as they're populated on load.
    renderer.setValue(['settings', 'summary'], '');
    await nextTick();

    // The same value said two ways, not an edit — so no control has to know how
    // the server happens to spell "empty".
    expect(mutation ?? {}).toEqual({});

    renderer.setValue(['settings', 'summary'], 'real');
    await nextTick();

    expect(mutation).toEqual({settings: {summary: 'real'}});
  });

  it('renders and updates permission trees', async () => {
    let mutation: FormPayload['values'] = {};
    app.unmount();
    await mount(
      {
        scope: ['settings'],
        refreshable: false,
        nodes: [
          {
            type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
            component: 'craft:field',
            props: {label: 'Permissions'},
            control: {
              type: 'CraftCms\\Cms\\Form\\Controls\\PermissionTree',
              component: 'craft:permission-tree',
              props: {
                groups: [
                  {
                    handle: 'content',
                    heading: 'Content',
                    keys: ['viewEntries', 'editEntries', 'deleteEntries'],
                    permissions: {
                      viewEntries: {
                        key: 'viewEntries',
                        label: 'View entries',
                        info: null,
                        warning: null,
                        nested: {
                          editEntries: {
                            key: 'editEntries',
                            label: 'Edit entries',
                            info: null,
                            warning: null,
                            nested: {},
                          },
                          deleteEntries: {
                            key: 'deleteEntries',
                            label: 'Delete entries',
                            info: null,
                            warning: null,
                            nested: {},
                          },
                        },
                      },
                    },
                  },
                ],
                lockedPermissions: ['deleteEntries'],
              },
              path: ['settings', 'permissions'],
              mode: 'editable',
              deltaGroup: ['settings', 'permissions'],
            },
          },
        ],
        values: {settings: {permissions: ['viewEntries']}},
        errors: [],
        globalErrors: [],
      },
      {onMutation: (value) => (mutation = value)}
    );

    const permissionTree = container.querySelector(
      'craft-permission-tree'
    ) as CraftPermissionTree;
    await permissionTree.updateComplete;
    const checkboxes = [
      ...permissionTree.shadowRoot!.querySelectorAll<
        HTMLElement & {
          checked: boolean;
          choiceValue: string;
          disabled: boolean;
        }
      >('craft-checkbox'),
    ];
    expect(checkboxes.map((checkbox) => checkbox.choiceValue)).toEqual([
      'viewEntries',
      'editEntries',
      'deleteEntries',
    ]);
    const edit = checkboxes.find(
      (checkbox) => checkbox.choiceValue === 'editEntries'
    )!;
    const inherited = checkboxes.find(
      (checkbox) => checkbox.choiceValue === 'deleteEntries'
    )!;

    expect(inherited.checked).toBe(true);
    expect(inherited.disabled).toBe(true);
    expect(new FormData(form).getAll('settings[permissions][]')).toEqual([
      'viewEntries',
    ]);

    edit.checked = true;
    edit.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, composed: true})
    );
    await nextTick();

    expect(renderer.currentValues()).toEqual({
      settings: {permissions: ['viewEntries', 'editEntries']},
    });
    expect(mutation).toEqual({
      settings: {permissions: ['viewEntries', 'editEntries']},
    });
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
      refresh?: (
        values: FormPayload['values'],
        scope?: string[]
      ) => Promise<FormPayload>;
      onMutation?: (mutation: FormPayload['values']) => void;
      onChange?: (change: FormChange, values: FormPayload['values']) => void;
      modified?: string[];
      components?: Record<string, CpComponentRegistration>;
      registerComponents?: (
        components: Pick<
          ReturnType<typeof createCpComponentRegistry>,
          'register'
        >
      ) => void;
      slots?: Record<
        string,
        (props: FormControlOverrideProps) => ReturnType<typeof h>
      >;
    } = {}
  ): Promise<void> {
    currentPayload = shallowRef(formPayload);
    const rendererRef = ref();
    app = createApp({
      setup: () => () =>
        h(
          FormRenderer,
          {
            ref: rendererRef,
            payload: currentPayload.value,
            modified: options.modified,
            refresh: options.refresh,
            'onUpdate:mutation': options.onMutation,
            onChange: options.onChange,
          },
          options.slots
        ),
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
      node.control.forms?.forEach((form) =>
        visitControls(form.nodes as Mutable<FormPayload>['nodes'], visit)
      );
    }

    if (node.children) {
      visitControls(node.children, visit);
    }
  }
}
