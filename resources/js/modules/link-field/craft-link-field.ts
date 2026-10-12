import {t, type ElementInfo} from '@craftcms/ui';
import {createElementSelectorModal} from '@/modules/element-selector-modal/create-element-selector-modal';
import '@craftcms/ui/components/disclosure/disclosure';
import '@craftcms/ui/components/field/field';
import '@craftcms/ui/components/field-group/field-group';
import '@craftcms/ui/components/pane/pane';
import {
  html,
  LitElement,
  nothing,
  type PropertyValues,
  type TemplateResult,
} from 'lit';
import {customElement, property, state} from 'lit/decorators.js';
import './craft-link-field.css';

export type LinkTypeConfig = {
  id: string;
  label: string;
  kind: 'custom' | 'element' | 'text';
  prefixes?: string[];
  pattern?: string;
  inputAttributes?: Record<string, string>;
  elementType?: string;
  refHandle?: string;
  elementSelectConfig?: import('@/modules/ui/types').UiValues;
};

/** The link attributes a field can let authors set, under “Advanced”. */
export type LinkAdvancedField =
  | 'urlSuffix'
  | 'target'
  | 'title'
  | 'class'
  | 'id'
  | 'rel'
  | 'ariaLabel'
  | 'download';

type LinkAttributes = {
  urlSuffix: string;
  target: string;
  title: string;
  class: string;
  id: string;
  rel: string;
  ariaLabel: string;
  download: boolean;
  filename: string;
};

export type LinkFieldValue = LinkAttributes & {
  defaultLabel: string;
  href: string;
  label: string;
  type: string;
  value: string;
};

/** Whether a change came from typing, or from a discrete choice. */
export type LinkChangeKind = 'typing' | 'discrete';

export type LinkChangeDetail = {
  kind: LinkChangeKind;
  value: LinkFieldValue;
};

type ElementSelectStartDetail = {
  restoreFocusTo?: HTMLElement;
  waitUntil: (promise: Promise<unknown>) => void;
};

const emptyAttributes: LinkAttributes = {
  urlSuffix: '',
  target: '',
  title: '',
  class: '',
  id: '',
  rel: '',
  ariaLabel: '',
  download: false,
  filename: '',
};

const formValueKeys = [
  'type',
  'value',
  'label',
  ...(Object.keys(emptyAttributes) as Array<keyof LinkAttributes>),
] as const;

let advancedPanelIndex = 0;

/**
 * Edits a link: its type, its destination, and optionally its label and
 * advanced attributes.
 *
 * By default it's a form of its own, with Apply and Cancel buttons that fire
 * `apply` and `cancel`. With `live`, it's a field like any other: each edit
 * fires `link-change` straight away, and it sits in a pane when it has a label
 * or advanced attributes.
 *
 * @fires apply - The link was applied. `detail` is the `LinkFieldValue`.
 * @fires cancel - Editing was canceled.
 * @fires link-change - A `live` field was edited. `detail` is a `LinkChangeDetail`.
 */
@customElement('craft-link-field')
class CraftLinkField extends LitElement {
  @property({attribute: 'advanced-fields', type: Array})
  advancedFields: string[] = [];

  @property({attribute: 'show-label-field', type: Boolean})
  showLabelField = false;

  @property({type: Array})
  types: LinkTypeConfig[] = [];

  @property({attribute: 'model-value', type: Object})
  modelValue: Partial<LinkFieldValue> | null = null;

  @property()
  name = '';

  @property({reflect: true, type: Boolean})
  disabled = false;

  /** Applies each edit as it's made, rather than on Apply. */
  @property({reflect: true, type: Boolean})
  live = false;

  @state()
  private defaultLabel = '';

  @state()
  private label = '';

  @state()
  private linkAttributes: LinkAttributes = {...emptyAttributes};

  @state()
  private typeId = '';

  @state()
  private value = '';

  @state()
  private valueError = '';

  private readonly advancedPanelId = `craft-link-field-advanced-${++advancedPanelIndex}`;

  private readonly valueErrorId = `${this.advancedPanelId}-value-errors`;

  /** The last value a `live` edit sent out, so its echo doesn't reset what's being typed. */
  private emittedValue: Partial<LinkFieldValue> | null = null;

  override connectedCallback(): void {
    super.connectedCallback();

    if (!this.typeId) {
      this.typeId = this.types[0]?.id ?? '';
    }
  }

  override willUpdate(changedProperties: PropertyValues<this>): void {
    if (changedProperties.has('modelValue') && !this.isEcho(this.modelValue)) {
      const value = this.modelValue ?? {};
      this.defaultLabel = value.defaultLabel ?? '';
      this.label = value.label ?? '';
      this.linkAttributes = {
        urlSuffix: value.urlSuffix ?? '',
        target: value.target ?? '',
        title: value.title ?? '',
        class: value.class ?? '',
        id: value.id ?? '',
        rel: value.rel ?? '',
        ariaLabel: value.ariaLabel ?? '',
        download: Boolean(value.download),
        filename: value.filename ?? '',
      };
      this.typeId = value.type ?? '';
      this.value = value.value ?? '';
    }

    if (!this.typeId || !this.types.some((type) => type.id === this.typeId)) {
      this.typeId = this.types[0]?.id ?? '';
    }
  }

  private isEcho(value: Partial<LinkFieldValue> | null): boolean {
    const emitted = this.emittedValue;

    return Boolean(
      emitted &&
      value &&
      (Object.keys(emitted) as Array<keyof LinkFieldValue>).every(
        (key) => key === 'href' || value[key] === emitted[key]
      )
    );
  }

  private get selectedType(): LinkTypeConfig | undefined {
    return this.types.find((type) => type.id === this.typeId);
  }

  private hasAdvancedField(field: LinkAdvancedField): boolean {
    return this.advancedFields.includes(field);
  }

  private get showAdvancedFields(): boolean {
    return this.showLabelField || this.advancedFields.length > 0;
  }

  private handleTypeChange(event: Event): void {
    if (!(event.target instanceof HTMLSelectElement)) {
      return;
    }
    this.typeId = event.target.value;
    this.value = '';
    this.defaultLabel = '';
    this.valueError = '';
    this.change('discrete');
  }

  private handleValueChange(event: Event): void {
    if (
      !(event.target instanceof HTMLElement) ||
      this.isInitialization(event)
    ) {
      return;
    }
    // SAFETY: This handler is registered only on the craft-input value control.
    const input = event.target as HTMLElementTagNameMap['craft-input'];
    this.value = this.inputValue(input);
    this.valueError = '';
    this.defaultLabel = this.defaultLabelFor(
      this.normalizeTextValue(this.value)
    );
    this.change('typing');
  }

  private inputValue(input: HTMLElementTagNameMap['craft-input']): string {
    return String(input.modelValue ?? '');
  }

  private handleLabelChange(event: Event): void {
    if (
      !(event.target instanceof HTMLElement) ||
      this.isInitialization(event)
    ) {
      return;
    }
    // SAFETY: this handler is registered only on the craft-input label control.
    this.label = this.inputValue(
      event.target as HTMLElementTagNameMap['craft-input']
    );
    this.change('typing');
  }

  private handleAttributeInput(
    attribute: Exclude<keyof LinkAttributes, 'download'>
  ): (event: Event) => void {
    return (event) => {
      if (
        !(event.target instanceof HTMLElement) ||
        this.isInitialization(event)
      ) {
        return;
      }
      // SAFETY: this handler is registered only on craft-input controls.
      this.linkAttributes = {
        ...this.linkAttributes,
        [attribute]: this.inputValue(
          event.target as HTMLElementTagNameMap['craft-input']
        ),
      };
      this.change('typing');
    };
  }

  private handleSwitch(
    update: (checked: boolean) => Partial<LinkAttributes>
  ): (event: Event) => void {
    return (event) => {
      if (
        !(event.target instanceof HTMLElement) ||
        this.isInitialization(event)
      ) {
        return;
      }
      // SAFETY: this handler is registered only on craft-switch controls.
      const {checked} = event.target as HTMLElementTagNameMap['craft-switch'];
      this.linkAttributes = {
        ...this.linkAttributes,
        ...update(Boolean(checked)),
      };
      this.change('discrete');
    };
  }

  /** Lion fires `model-value-changed` once as a control sets up; that isn't an edit. */
  private isInitialization(event: Event): boolean {
    return event instanceof CustomEvent && Boolean(event.detail?.initialize);
  }

  private async chooseElement(event: Event): Promise<void> {
    const type = this.selectedType;

    if (!type?.elementType || !type.refHandle) {
      return;
    }

    const config = {...type.elementSelectConfig};
    delete config.elementType;
    delete config.limit;
    delete config.single;

    await this.dispatchElementSelectStartEvent(
      event.currentTarget instanceof HTMLElement ? event.currentTarget : null
    );

    const modal = await createElementSelectorModal(type.elementType, {
      ...config,
      hideOnSelect: true,
      modalTitle: t('Choose {type}', {type: type.label}),
      multiSelect: false,
      onSelect: (elements: ElementInfo[]) => {
        const [element] = elements;

        if (!element) {
          return;
        }

        this.value = `{${type.refHandle}:${element.id}@${element.siteId}:url}`;
        this.defaultLabel = String(element.label || '');
        this.valueError = '';
        this.change('discrete');
      },
    });

    // The modal lives on <body>, outside this element's shadow root, so its DOM
    // events never reach here — the controller is the subscription point.
    modal.on('close', () => {
      this.dispatchElementSelectEvent('element-select-end');
      modal.destroy();
    });
  }

  private async dispatchElementSelectStartEvent(
    restoreFocusTo: HTMLElement | null
  ): Promise<void> {
    const promises: Array<Promise<unknown>> = [];

    this.dispatchEvent(
      new CustomEvent<ElementSelectStartDetail>('element-select-start', {
        bubbles: true,
        detail: {
          restoreFocusTo: restoreFocusTo ?? undefined,
          waitUntil: (promise) => promises.push(promise),
        },
      })
    );

    await Promise.all(promises);
  }

  private dispatchElementSelectEvent(name: string): void {
    this.dispatchEvent(
      new CustomEvent(name, {
        bubbles: true,
      })
    );
  }

  private normalizeTextValue(value: string): string {
    const type = this.selectedType;
    value = value.trim();

    if (!value || type?.kind !== 'text') {
      return value;
    }

    if (this.validateTextValue(value)) {
      return value;
    }

    const prefix = type.prefixes?.[0] ?? '';
    const prefixed = `${prefix}${value}`;

    return this.validateTextValue(prefixed) ? prefixed : value;
  }

  private normalizeUrlSuffix(value: string): string {
    value = value.trim();

    if (!value || value.startsWith('#') || value.startsWith('?')) {
      return value;
    }

    return `?${value}`;
  }

  private renderTextDestination(typeId: string, value: string): string {
    if (typeId === 'url' || typeId === 'email') {
      return value.replaceAll(' ', '+');
    }

    if (typeId === 'sms') {
      return this.renderSmsDestination(value);
    }

    if (typeId === 'tel') {
      return value.replaceAll(' ', '-');
    }

    return value;
  }

  private renderSmsDestination(value: string): string {
    const [, root = '', queryString = ''] =
      value.match(/^([^?&]*)(?:[?&]+(.*))?$/) ?? [];
    const destination = queryString
      ? `${root}&${queryString.replaceAll(' ', '%20')}`
      : root;

    return destination.replaceAll(' ', '-');
  }

  private validateTextValue(value: string): boolean {
    const pattern = this.selectedType?.pattern;

    if (!pattern) {
      return true;
    }

    try {
      return new RegExp(pattern, 'i').test(value);
    } catch {
      return true;
    }
  }

  private defaultLabelFor(value: string): string {
    const type = this.selectedType;

    if (!value || type?.kind !== 'text') {
      return this.defaultLabel;
    }

    let label = value;
    for (const prefix of type.prefixes ?? []) {
      if (label.toLowerCase().startsWith(prefix.toLowerCase())) {
        label = label.slice(prefix.length);
      }
    }

    return /^[^/]+\/$/.test(label) ? label.slice(0, -1) : label;
  }

  /** The link as it stands, or `null` when no link type is selected. */
  private currentValue(): LinkFieldValue | null {
    const type = this.selectedType;

    if (!type) {
      return null;
    }

    const value =
      type.kind === 'text' ? this.normalizeTextValue(this.value) : this.value;
    const urlSuffix = this.normalizeUrlSuffix(this.linkAttributes.urlSuffix);
    const destination =
      type.kind === 'text' ? this.renderTextDestination(type.id, value) : value;

    return {
      ...this.linkAttributes,
      defaultLabel: this.defaultLabelFor(value),
      href: value ? `${destination}${urlSuffix}` : '',
      label: this.label.trim(),
      title: this.linkAttributes.title.trim(),
      type: type.id,
      urlSuffix,
      value,
    };
  }

  /** Commits an edit straight away when `live`. */
  private change(kind: LinkChangeKind): void {
    if (!this.live) {
      return;
    }

    const value = this.currentValue();

    if (!value) {
      return;
    }

    this.emittedValue = value;
    this.modelValue = value;
    this.dispatchEvent(
      new CustomEvent<LinkChangeDetail>('link-change', {
        bubbles: true,
        detail: {kind, value},
      })
    );
  }

  private apply(): void {
    const type = this.selectedType;
    const linkValue = this.currentValue();

    if (!type || !linkValue) {
      return;
    }

    if (!linkValue.value) {
      this.valueError = t('{attribute} cannot be blank.', {
        attribute: type.label,
      });

      return;
    }

    if (type.kind === 'text' && !this.validateTextValue(linkValue.value)) {
      this.valueError = t('{attribute} is invalid.', {
        attribute: type.label,
      });

      return;
    }

    this.valueError = '';
    this.modelValue = linkValue;
    this.dispatchEvent(
      new CustomEvent<LinkFieldValue>('apply', {
        bubbles: true,
        detail: linkValue,
      })
    );
  }

  private cancel(): void {
    this.dispatchEvent(new CustomEvent('cancel', {bubbles: true}));
  }

  /** A label for one of the controls in the type + destination row, marked up like a grouped UI field's. */
  private renderLabel(label: string): TemplateResult {
    return html`<span slot="label">${label}</span>`;
  }

  private renderTypeSelect(): TemplateResult | typeof nothing {
    if (this.types.length < 2) {
      return nothing;
    }

    return html`
      <craft-field>
        ${this.renderLabel(t('Link Type'))}
        <craft-select
          slot="input"
          .modelValue=${this.typeId}
          .disabled=${this.disabled}
        >
          <select
            slot="input"
            .value=${this.typeId}
            @change=${this.handleTypeChange}
          >
            ${this.types.map(
              (type) => html`<option value=${type.id}>${type.label}</option>`
            )}
          </select>
        </craft-select>
      </craft-field>
    `;
  }

  private renderTypeInput(): TemplateResult | typeof nothing {
    const type = this.selectedType;

    if (!type) {
      return nothing;
    }

    if (type.kind === 'element') {
      return html`
        <craft-field>
          ${this.renderLabel(type.label)}
          <div slot="input" class="craft-link-field__element">
            <craft-button
              type="button"
              variant="dashed"
              icon=${this.value ? nothing : 'plus'}
              ?disabled=${this.disabled}
              aria-describedby=${this.valueError ? this.valueErrorId : nothing}
              aria-invalid=${this.valueError ? 'true' : nothing}
              @click=${this.chooseElement}
            >
              ${this.value ? t('Change') : t('Choose')}
            </craft-button>
            ${this.value && this.defaultLabel
              ? html`<span class="craft-link-selected-label"
                  >${this.defaultLabel}</span
                >`
              : nothing}
          </div>
          ${this.renderValueError('feedback')}
        </craft-field>
      `;
    }

    const inputType = type.inputAttributes?.type ?? 'text';
    const inputMode = type.inputAttributes?.inputmode ?? undefined;

    return html`
      <craft-field>
        ${this.renderLabel(type.label)}
        <craft-input
          slot="input"
          data-link-value
          type=${inputType}
          inputmode=${inputMode ?? nothing}
          aria-invalid=${this.valueError ? 'true' : nothing}
          .modelValue=${this.value}
          .disabled=${this.disabled}
          @model-value-changed=${this.handleValueChange}
        ></craft-input>
        ${this.renderValueError('feedback')}
      </craft-field>
    `;
  }

  private renderValueError(slot?: string): TemplateResult | typeof nothing {
    if (!this.valueError) {
      return nothing;
    }

    return html`
      <ul id=${this.valueErrorId} class="errors" slot=${slot ?? nothing}>
        <li>
          <span class="sr-only">${t('Error:')}</span>
          ${this.valueError}
        </li>
      </ul>
    `;
  }

  private renderTextAttribute(
    attribute: Exclude<keyof LinkAttributes, 'download' | 'target'>,
    label: string,
    options: {helpText?: string; code?: boolean; placeholder?: string} = {}
  ): TemplateResult {
    return html`
      <craft-input
        label=${label}
        help-text=${options.helpText ?? nothing}
        placeholder=${options.placeholder ?? nothing}
        type="text"
        ?monospace=${options.code}
        .modelValue=${this.linkAttributes[attribute]}
        .disabled=${this.disabled}
        @model-value-changed=${this.handleAttributeInput(attribute)}
      ></craft-input>
    `;
  }

  private renderAdvancedField(field: LinkAdvancedField): TemplateResult {
    const separateWithSpaces = t('Separate multiple values with spaces.');

    switch (field) {
      case 'urlSuffix':
        return this.renderTextAttribute('urlSuffix', t('URL Suffix'), {
          helpText: t(
            'Query params (e.g. {ex1}) or a URI fragment (e.g. {ex2}) that should be appended to the URL.',
            {ex1: '?p1=foo&p2=bar', ex2: '#anchor'}
          ),
        });
      case 'target':
        return html`
          <craft-switch
            label=${t('Open in a new tab')}
            .checked=${this.linkAttributes.target === '_blank'}
            .disabled=${this.disabled}
            @model-value-changed=${this.handleSwitch((checked) => ({
              target: checked ? '_blank' : '',
            }))}
          ></craft-switch>
        `;
      case 'title':
        return this.renderTextAttribute('title', t('Title Text'));
      case 'class':
        return this.renderTextAttribute('class', t('Class Name'), {
          helpText: separateWithSpaces,
          code: true,
        });
      case 'id':
        return this.renderTextAttribute('id', t('ID'), {code: true});
      case 'rel':
        return this.renderTextAttribute(
          'rel',
          t('Relation ({ex})', {ex: 'rel'}),
          {
            helpText: separateWithSpaces,
            code: true,
          }
        );
      case 'ariaLabel':
        return this.renderTextAttribute('ariaLabel', t('ARIA Label'));
      case 'download':
        return html`
          <craft-switch
            label=${t('Download')}
            .checked=${this.linkAttributes.download}
            .disabled=${this.disabled}
            @model-value-changed=${this.handleSwitch((download) => ({
              download,
            }))}
          ></craft-switch>
          ${this.linkAttributes.download
            ? this.renderTextAttribute('filename', t('Filename'))
            : nothing}
        `;
    }
  }

  private renderAdvancedFields(): TemplateResult | typeof nothing {
    if (!this.showAdvancedFields) {
      return nothing;
    }

    const fields = (
      [
        'urlSuffix',
        'target',
        'title',
        'class',
        'id',
        'rel',
        'ariaLabel',
        'download',
      ] as const
    ).filter((field) => this.hasAdvancedField(field));

    return html`
      <craft-disclosure id=${this.advancedPanelId}>
        <craft-button
          slot="invoker"
          type="button"
          variant="plain"
          size="small"
          flush
          icon="chevron-down"
        >
          ${t('Advanced')}
        </craft-button>
        <craft-field-group slot="content">
          ${this.showLabelField
            ? html`
                <craft-input
                  label=${t('Label')}
                  type="text"
                  placeholder=${this.defaultLabel || nothing}
                  .modelValue=${this.label}
                  .disabled=${this.disabled}
                  @model-value-changed=${this.handleLabelChange}
                ></craft-input>
              `
            : nothing}
          ${fields.map((field) => this.renderAdvancedField(field))}
        </craft-field-group>
      </craft-disclosure>
    `;
  }

  private renderButtons(): TemplateResult | typeof nothing {
    if (this.live) {
      return nothing;
    }

    return html`
      <div class="buttons right">
        <craft-button
          type="button"
          variant="plain"
          size="small"
          ?disabled=${this.disabled}
          @click=${this.cancel}
        >
          ${t('Cancel')}
        </craft-button>
        <craft-button
          type="button"
          size="small"
          ?disabled=${this.disabled}
          @click=${this.apply}
        >
          ${t('Apply')}
        </craft-button>
      </div>
    `;
  }

  private renderHiddenInputs(): TemplateResult[] | typeof nothing {
    if (!this.name) {
      return nothing;
    }

    return formValueKeys.map((key) => {
      const value = this.modelValue?.[key];

      return html`
        <input
          type="hidden"
          name=${`${this.name}[${key}]`}
          .value=${typeof value === 'boolean'
            ? value
              ? '1'
              : ''
            : (value ?? '')}
          ?disabled=${this.disabled}
        />
      `;
    });
  }

  override render(): TemplateResult {
    const content = html`
      <div class="craft-link-field">
        <div class="craft-link-field__destination">
          ${this.renderTypeSelect()} ${this.renderTypeInput()}
        </div>
        ${this.renderAdvancedFields()} ${this.renderButtons()}
      </div>
    `;

    return html`
      ${this.live && this.showAdvancedFields
        ? html`<craft-pane
            class="craft-link-field__pane"
            appearance="outline"
            padding="lg"
            >${content}</craft-pane
          >`
        : content}
      ${this.renderHiddenInputs()}
    `;
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }
}

export default CraftLinkField;

declare global {
  interface HTMLElementTagNameMap {
    'craft-link-field': CraftLinkField;
  }
}
