import {t, type ElementInfo} from '@craftcms/ui';
import {createElementSelectorModal} from '@/modules/element-selector-modal/create-element-selector-modal';
import '@craftcms/ui/components/disclosure/disclosure';
import '@craftcms/ui/components/field-group/field-group';
import {
  html,
  LitElement,
  nothing,
  type PropertyValues,
  type TemplateResult,
} from 'lit';
import {customElement, property, state} from 'lit/decorators.js';

export type LinkTypeConfig = {
  id: string;
  label: string;
  kind: 'custom' | 'element' | 'text';
  prefixes?: string[];
  pattern?: string;
  inputAttributes?: Record<string, string>;
  elementType?: string;
  refHandle?: string;
  elementSelectConfig?: Record<string, unknown>;
};

export type LinkFieldValue = {
  defaultLabel: string;
  href: string;
  label: string;
  title: string;
  type: string;
  urlSuffix: string;
  value: string;
};

type ElementSelectStartDetail = {
  restoreFocusTo?: HTMLElement;
  waitUntil: (promise: Promise<unknown>) => void;
};

const formValueKeys = ['type', 'value', 'label', 'urlSuffix', 'title'] as const;

let advancedPanelIndex = 0;

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

  @state()
  private defaultLabel = '';

  @state()
  private label = '';

  @state()
  private linkTitle = '';

  @state()
  private typeId = '';

  @state()
  private urlSuffix = '';

  @state()
  private value = '';

  @state()
  private valueError = '';

  private readonly advancedPanelId = `craft-link-field-advanced-${++advancedPanelIndex}`;

  private readonly valueErrorId = `${this.advancedPanelId}-value-errors`;

  override connectedCallback(): void {
    super.connectedCallback();

    if (!this.typeId) {
      this.typeId = this.types[0]?.id ?? '';
    }
  }

  override willUpdate(changedProperties: PropertyValues<this>): void {
    if (changedProperties.has('modelValue')) {
      const value = this.modelValue ?? {};
      this.defaultLabel = value.defaultLabel ?? '';
      this.label = value.label ?? '';
      this.linkTitle = value.title ?? '';
      this.typeId = value.type ?? '';
      this.urlSuffix = value.urlSuffix ?? '';
      this.value = value.value ?? '';
    }

    if (!this.typeId || !this.types.some((type) => type.id === this.typeId)) {
      this.typeId = this.types[0]?.id ?? '';
    }
  }

  private get selectedType(): LinkTypeConfig | undefined {
    return this.types.find((type) => type.id === this.typeId);
  }

  private get showTitleField(): boolean {
    return this.advancedFields.includes('title');
  }

  private get showUrlSuffixField(): boolean {
    return this.advancedFields.includes('urlSuffix');
  }

  private get showAdvancedFields(): boolean {
    return this.showUrlSuffixField || this.showTitleField;
  }

  private handleTypeChange(event: Event): void {
    this.typeId = (event.target as HTMLSelectElement).value;
    this.value = '';
    this.defaultLabel = '';
    this.valueError = '';
  }

  private handleValueChange(event: Event): void {
    const input = event.target as HTMLElementTagNameMap['craft-input'];
    this.value = this.inputValue(input);
    this.valueError = '';
    this.defaultLabel = this.defaultLabelFor(
      this.normalizeTextValue(this.value)
    );
  }

  private inputValue(input: HTMLElementTagNameMap['craft-input']): string {
    return String(input.modelValue ?? '');
  }

  private textInputValue(event: Event): string {
    return (event.target as HTMLInputElement).value;
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

  private apply(): void {
    const type = this.selectedType;
    const value =
      type?.kind === 'text' ? this.normalizeTextValue(this.value) : this.value;

    if (!type) {
      return;
    }

    if (!value) {
      this.valueError = t('{attribute} cannot be blank.', {
        attribute: type.label,
      });

      return;
    }

    if (type.kind === 'text' && !this.validateTextValue(value)) {
      this.valueError = t('{attribute} is invalid.', {
        attribute: type.label,
      });

      return;
    }

    this.valueError = '';

    const urlSuffix = this.normalizeUrlSuffix(this.urlSuffix);
    const destination =
      type.kind === 'text' ? this.renderTextDestination(type.id, value) : value;

    const linkValue: LinkFieldValue = {
      defaultLabel: this.defaultLabelFor(value),
      href: `${destination}${urlSuffix}`,
      label: this.label.trim(),
      title: this.linkTitle.trim(),
      type: type.id,
      urlSuffix,
      value,
    };
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

  private renderTypeInput(): TemplateResult | typeof nothing {
    const type = this.selectedType;

    if (!type) {
      return nothing;
    }

    if (type.kind === 'element') {
      return html`
        <div class=${this.valueError ? 'field has-errors' : 'field'}>
          <div class="heading"><label>${type.label}</label></div>
          <div class="input ltr">
            <craft-button
              type="button"
              variant="fill"
              ?disabled=${this.disabled}
              aria-describedby=${this.valueError ? this.valueErrorId : nothing}
              aria-invalid=${this.valueError ? 'true' : nothing}
              @click=${this.chooseElement}
            >
              ${this.value ? t('Change') : t('Choose')}
            </craft-button>
            ${this.defaultLabel
              ? html`<span class="craft-link-selected-label"
                  >${this.defaultLabel}</span
                >`
              : nothing}
          </div>
          ${this.renderValueError()}
        </div>
      `;
    }

    const inputType = type.inputAttributes?.type ?? 'text';
    const inputMode = type.inputAttributes?.inputmode ?? undefined;

    return html`
      <craft-input
        data-link-value
        label=${type.label}
        type=${inputType}
        inputmode=${inputMode ?? nothing}
        aria-describedby=${this.valueError ? this.valueErrorId : nothing}
        aria-invalid=${this.valueError ? 'true' : nothing}
        .modelValue=${this.value}
        .disabled=${this.disabled}
        @model-value-changed=${this.handleValueChange}
      >
        ${this.renderValueError('feedback')}
      </craft-input>
    `;
  }

  private renderValueError(slot?: string): TemplateResult | typeof nothing {
    if (!this.valueError) {
      return nothing;
    }

    return html`
      <ul id=${this.valueErrorId} class="errors" slot=${slot ?? nothing}>
        <li>
          <span class="visually-hidden">${t('Error:')}</span>
          ${this.valueError}
        </li>
      </ul>
    `;
  }

  private renderUrlSuffixField(): TemplateResult {
    const inputId = `${this.advancedPanelId}-url-suffix`;

    return html`
      <div class="field">
        <div class="heading">
          <label for=${inputId}>${t('URL Suffix')}</label>
          <craft-info-icon>
            ${t(
              'Query params (e.g. {ex1}) or a URI fragment (e.g. {ex2}) that should be appended to the URL.',
              {
                ex1: '?p1=foo&p2=bar',
                ex2: '#anchor',
              }
            )}
          </craft-info-icon>
        </div>
        <div class="input ltr">
          <input
            id=${inputId}
            class="text fullwidth"
            type="text"
            ?disabled=${this.disabled}
            .value=${this.urlSuffix}
            @input=${(event: Event) =>
              (this.urlSuffix = this.textInputValue(event))}
          />
        </div>
      </div>
    `;
  }

  private renderTitleField(): TemplateResult {
    const inputId = `${this.advancedPanelId}-title`;

    return html`
      <div class="field">
        <div class="heading">
          <label for=${inputId}>${t('Title Text')}</label>
        </div>
        <div class="input ltr">
          <input
            id=${inputId}
            class="text fullwidth"
            type="text"
            ?disabled=${this.disabled}
            .value=${this.linkTitle}
            @input=${(event: Event) =>
              (this.linkTitle = this.textInputValue(event))}
          />
        </div>
      </div>
    `;
  }

  private renderAdvancedFields(): TemplateResult | typeof nothing {
    if (!this.showAdvancedFields) {
      return nothing;
    }

    return html`
      <craft-disclosure id=${this.advancedPanelId}>
        <craft-button
          slot="invoker"
          type="button"
          appearance="plain"
          icon="chevron-down"
          ?disabled=${this.disabled}
        >
          ${t('Advanced')}
        </craft-button>
        <craft-field-group slot="content" class="meta pane hairline">
          ${this.showUrlSuffixField ? this.renderUrlSuffixField() : nothing}
          ${this.showTitleField ? this.renderTitleField() : nothing}
        </craft-field-group>
      </craft-disclosure>
    `;
  }

  override render(): TemplateResult {
    return html`
      <craft-field-group class="craft-link-field">
        ${this.types.length > 1
          ? html`
              <craft-select
                label=${t('Link Type')}
                .modelValue=${this.typeId}
                .disabled=${this.disabled}
              >
                <select
                  slot="input"
                  .value=${this.typeId}
                  @change=${this.handleTypeChange}
                >
                  ${this.types.map(
                    (type) =>
                      html`<option value=${type.id}>${type.label}</option>`
                  )}
                </select>
              </craft-select>
            `
          : nothing}
        ${this.renderTypeInput()}
        ${this.showLabelField
          ? html`
              <craft-input
                label=${t('Label')}
                type="text"
                .modelValue=${this.label}
                .disabled=${this.disabled}
                @model-value-changed=${(event: Event) =>
                  (this.label = this.inputValue(
                    event.target as HTMLElementTagNameMap['craft-input']
                  ))}
              ></craft-input>
            `
          : nothing}
        ${this.renderAdvancedFields()}

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
        ${this.name
          ? formValueKeys.map(
              (key) => html`
                <input
                  type="hidden"
                  name=${`${this.name}[${key}]`}
                  .value=${this.modelValue?.[key] ?? ''}
                  ?disabled=${this.disabled}
                />
              `
            )
          : nothing}
      </craft-field-group>
    `;
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }
}

export default CraftLinkField;
