import {css, html, LitElement, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {repeat} from 'lit/directives/repeat.js';
import {t} from '@src/utilities/translate';
import type {
  ReorderDirection,
  ReorderPosition,
} from '../reorder-button/reorder-button.js';
import '../button/button.js';
import '../input/input.js';
import '../reorder-button/reorder-button.js';

export type FieldLayoutElement = Record<string, unknown> & {uid?: string};

export type FieldLayoutTab = Record<string, unknown> & {
  uid?: string;
  name?: string;
  elements?: FieldLayoutElement[];
};

export type GeneratedField = Record<string, unknown> & {
  uid?: string;
  name?: string;
  handle?: string;
  template?: string;
};

export type FieldLayoutValue = Record<string, unknown> & {
  tabs?: FieldLayoutTab[];
  generatedFields?: GeneratedField[];
};

export type AvailableFieldLayoutElement = {
  key: string;
  label: string;
  value: Record<string, unknown>;
  multiple: boolean;
};

const objectConverter = {
  fromAttribute(value: string | null): FieldLayoutValue {
    return value ? JSON.parse(value) : {};
  },
};

const arrayConverter = {
  fromAttribute(value: string | null): AvailableFieldLayoutElement[] {
    return value ? JSON.parse(value) : [];
  },
};

/**
 * @summary Edits a host-owned field layout configuration.
 *
 * @event model-value-changed - Emitted when the field layout changes.
 *
 * @since 1.0
 */
export default class CraftFieldLayout extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }

    [data-field-layout-tab] {
      border: 1px solid var(--c-border, currentColor);
      border-radius: var(--c-radius-md, 0.375rem);
      padding: var(--c-spacing-md, 1rem);
    }

    [data-field-layout-tab] + [data-field-layout-tab],
    [data-generated-fields] {
      margin-block-start: var(--c-spacing-md, 1rem);
    }

    [data-field-layout-tab-heading],
    [data-field-layout-element],
    [data-generated-field] {
      display: grid;
      align-items: center;
      gap: var(--c-spacing-sm, 0.5rem);
    }

    [data-field-layout-tab-heading],
    [data-field-layout-element] {
      grid-template-columns: auto 1fr auto;
    }

    [data-field-layout-element],
    [data-field-layout-controls],
    [data-field-layout-add-tab],
    [data-generated-field],
    [data-generated-field-add] {
      margin-block-start: var(--c-spacing-sm, 0.5rem);
    }

    [data-field-layout-controls] {
      display: flex;
      gap: var(--c-spacing-sm, 0.5rem);
    }

    [data-generated-field] {
      grid-template-columns: auto 1fr 1fr 2fr auto;
    }
  `;

  /** Final input name supplied by the form host. */
  @property({reflect: true}) name: string | null = null;

  /** Current host-owned field layout configuration. */
  @property({attribute: 'value', converter: objectConverter})
  modelValue: FieldLayoutValue = {};

  /** Elements that may be added to the layout. */
  @property({attribute: 'available-elements', converter: arrayConverter})
  availableElements: AvailableFieldLayoutElement[] = [];

  /** Whether generated-field configuration is available. */
  @property({attribute: 'with-generated-fields', reflect: true, type: Boolean})
  withGeneratedFields = false;

  /** Prevents field layout changes. */
  @property({attribute: 'readonly', reflect: true, type: Boolean})
  readOnly = false;

  override connectedCallback() {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'group');
    }
  }

  protected override updated(changedProperties: PropertyValues<this>) {
    super.updated(changedProperties);

    if (changedProperties.has('readOnly')) {
      if (this.readOnly) {
        this.setAttribute('aria-readonly', 'true');
      } else {
        this.removeAttribute('aria-readonly');
      }
    }

    if (changedProperties.has('name') || changedProperties.has('modelValue')) {
      this.syncFormInputs();
    }
  }

  private get tabs(): FieldLayoutTab[] {
    return Array.isArray(this.modelValue.tabs) ? this.modelValue.tabs : [];
  }

  private get generatedFields(): GeneratedField[] {
    return Array.isArray(this.modelValue.generatedFields)
      ? this.modelValue.generatedFields
      : [];
  }

  private get layoutElements(): FieldLayoutElement[] {
    return this.tabs.flatMap((tab) =>
      Array.isArray(tab.elements) ? tab.elements : []
    );
  }

  private availableElementsForSelection(): AvailableFieldLayoutElement[] {
    return this.availableElements.filter(
      (option) =>
        option.multiple ||
        !this.layoutElements.some(
          (element) =>
            this.elementIdentity(element) === this.optionIdentity(option)
        )
    );
  }

  private optionIdentity(option: AvailableFieldLayoutElement): string {
    return String(option.value.fieldUid ?? option.value.type ?? option.key);
  }

  private elementIdentity(element: FieldLayoutElement): string {
    return String(element.fieldUid ?? element.type ?? element.uid ?? '');
  }

  private elementLabel(element: FieldLayoutElement): string {
    const authoredLabel = element.label ?? element.heading;

    if (typeof authoredLabel === 'string' && authoredLabel !== '') {
      return authoredLabel;
    }

    return (
      this.availableElements.find(
        (option) =>
          this.optionIdentity(option) === this.elementIdentity(element)
      )?.label ?? this.elementIdentity(element)
    );
  }

  private updateValue(value: FieldLayoutValue) {
    if (this.readOnly) {
      return;
    }

    this.modelValue = value;
    this.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, composed: true})
    );
  }

  private updateTabs(tabs: FieldLayoutTab[]) {
    this.updateValue({...this.modelValue, tabs});
  }

  private updateTab(index: number, tab: FieldLayoutTab) {
    this.updateTabs(
      this.tabs.map((existingTab, tabIndex) =>
        tabIndex === index ? tab : existingTab
      )
    );
  }

  private addTab() {
    this.updateTabs([
      ...this.tabs,
      {uid: crypto.randomUUID(), name: t('New Tab'), elements: []},
    ]);
  }

  private removeTab(index: number) {
    this.updateTabs(this.tabs.filter((_, tabIndex) => tabIndex !== index));
  }

  private reorderTab(index: number, direction: ReorderDirection) {
    const reordered = this.reorder(this.tabs, index, direction);

    if (reordered) {
      this.updateTabs(reordered);
    }
  }

  private renameTab(index: number, event: Event) {
    this.updateTab(index, {
      ...this.tabs[index],
      name: (event.target as HTMLInputElement).value,
    });
  }

  private addElement(index: number, button: HTMLElement) {
    const select = button
      .closest('[data-field-layout-tab]')
      ?.querySelector<HTMLSelectElement>('[data-field-layout-available]');
    const option = this.availableElementsForSelection().find(
      ({key}) => key === select?.value
    );

    if (!option) {
      return;
    }

    const tab = this.tabs[index];
    const elements = Array.isArray(tab?.elements) ? tab.elements : [];

    this.updateTab(index, {
      ...tab,
      elements: [...elements, {...option.value, uid: crypto.randomUUID()}],
    });
  }

  private removeElement(tabIndex: number, elementIndex: number) {
    const tab = this.tabs[tabIndex];
    const elements = Array.isArray(tab?.elements) ? tab.elements : [];

    this.updateTab(tabIndex, {
      ...tab,
      elements: elements.filter((_, index) => index !== elementIndex),
    });
  }

  private reorderElement(
    tabIndex: number,
    elementIndex: number,
    direction: ReorderDirection
  ) {
    const tab = this.tabs[tabIndex];
    const elements = Array.isArray(tab?.elements) ? tab.elements : [];
    const reordered = this.reorder(elements, elementIndex, direction);

    if (reordered) {
      this.updateTab(tabIndex, {...tab, elements: reordered});
    }
  }

  private addGeneratedField() {
    this.updateValue({
      ...this.modelValue,
      generatedFields: [
        ...this.generatedFields,
        {uid: crypto.randomUUID(), name: '', handle: '', template: ''},
      ],
    });
  }

  private updateGeneratedField(index: number, property: string, event: Event) {
    const value = (event.target as HTMLInputElement | HTMLTextAreaElement)
      .value;

    this.updateValue({
      ...this.modelValue,
      generatedFields: this.generatedFields.map((field, fieldIndex) =>
        fieldIndex === index ? {...field, [property]: value} : field
      ),
    });
  }

  private removeGeneratedField(index: number) {
    this.updateValue({
      ...this.modelValue,
      generatedFields: this.generatedFields.filter(
        (_, fieldIndex) => fieldIndex !== index
      ),
    });
  }

  private reorderGeneratedField(index: number, direction: ReorderDirection) {
    const reordered = this.reorder(this.generatedFields, index, direction);

    if (reordered) {
      this.updateValue({...this.modelValue, generatedFields: reordered});
    }
  }

  private reorder<T>(items: T[], index: number, direction: ReorderDirection) {
    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    if (this.readOnly || targetIndex < 0 || targetIndex >= items.length) {
      return null;
    }

    const reordered = [...items];
    const item = reordered[index]!;

    reordered[index] = reordered[targetIndex]!;
    reordered[targetIndex] = item;

    return reordered;
  }

  private reorderPosition(index: number, length: number): ReorderPosition {
    if (index === 0) {
      return 'first';
    }

    if (index === length - 1) {
      return 'last';
    }

    return 'middle';
  }

  private syncFormInputs() {
    this.querySelectorAll('[data-field-layout-value]').forEach((input) =>
      input.remove()
    );

    if (!this.name) {
      return;
    }

    const fragment = document.createDocumentFragment();
    this.appendFormValue(fragment, this.name, this.modelValue);
    this.append(fragment);
  }

  private appendFormValue(
    fragment: DocumentFragment,
    name: string,
    value: unknown
  ) {
    if (Array.isArray(value)) {
      value.forEach((item, index) => {
        this.appendFormValue(fragment, `${name}[${index}]`, item);
      });

      return;
    }

    if (value && typeof value === 'object') {
      Object.entries(value).forEach(([key, item]) => {
        this.appendFormValue(fragment, `${name}[${key}]`, item);
      });

      return;
    }

    const input = document.createElement('input');

    input.type = 'hidden';
    input.name = name;
    input.value =
      typeof value === 'boolean' ? (value ? '1' : '0') : String(value ?? '');
    input.dataset.fieldLayoutValue = '';
    fragment.append(input);
  }

  protected override render() {
    const availableElements = this.availableElementsForSelection();

    return html`
      ${repeat(
        this.tabs,
        (tab, index) => String(tab.uid ?? index),
        (tab, tabIndex) => {
          const elements = Array.isArray(tab.elements) ? tab.elements : [];

          return html`
            <section data-field-layout-tab="${String(tab.uid ?? tabIndex)}">
              <div data-field-layout-tab-heading>
                <craft-reorder-button
                  ?disabled="${this.readOnly || this.tabs.length < 2}"
                  position="${this.reorderPosition(tabIndex, this.tabs.length)}"
                  @reorder="${(
                    event: CustomEvent<{direction: ReorderDirection}>
                  ) => this.reorderTab(tabIndex, event.detail.direction)}"
                ></craft-reorder-button>
                <craft-input
                  .value="${String(tab.name ?? '')}"
                  label="${t('Tab name')}"
                  label-sr-only
                  ?disabled="${this.readOnly}"
                  @input="${(event: Event) => this.renameTab(tabIndex, event)}"
                ></craft-input>
                <craft-button
                  type="button"
                  size="small"
                  variant="plain"
                  aria-label="${t('Remove tab {name}', {
                    name: String(tab.name ?? ''),
                  })}"
                  ?disabled="${this.readOnly}"
                  data-field-layout-remove-tab
                  @activate="${() => this.removeTab(tabIndex)}"
                >
                  ${t('Remove')}
                </craft-button>
              </div>

              ${repeat(
                elements,
                (layoutElement, index) => String(layoutElement.uid ?? index),
                (layoutElement, elementIndex) => html`
                  <div
                    data-field-layout-element="${String(
                      layoutElement.uid ?? elementIndex
                    )}"
                  >
                    <craft-reorder-button
                      ?disabled="${this.readOnly || elements.length < 2}"
                      position="${this.reorderPosition(
                        elementIndex,
                        elements.length
                      )}"
                      @reorder="${(
                        event: CustomEvent<{direction: ReorderDirection}>
                      ) =>
                        this.reorderElement(
                          tabIndex,
                          elementIndex,
                          event.detail.direction
                        )}"
                    ></craft-reorder-button>
                    <span>${this.elementLabel(layoutElement)}</span>
                    <craft-button
                      type="button"
                      size="small"
                      variant="plain"
                      aria-label="${t('Remove {label}', {
                        label: this.elementLabel(layoutElement),
                      })}"
                      ?disabled="${this.readOnly}"
                      data-field-layout-remove
                      @activate="${() =>
                        this.removeElement(tabIndex, elementIndex)}"
                    >
                      ${t('Remove')}
                    </craft-button>
                  </div>
                `
              )}
              ${availableElements.length
                ? html`
                    <div data-field-layout-controls>
                      <select
                        aria-label="${t('Available layout elements')}"
                        ?disabled="${this.readOnly}"
                        data-field-layout-available
                      >
                        ${availableElements.map(
                          (option) => html`
                            <option value="${option.key}">
                              ${option.label}
                            </option>
                          `
                        )}
                      </select>
                      <craft-button
                        type="button"
                        variant="dashed"
                        ?disabled="${this.readOnly}"
                        data-field-layout-add
                        @activate="${(event: Event) =>
                          this.addElement(
                            tabIndex,
                            event.currentTarget as HTMLElement
                          )}"
                      >
                        ${t('Add')}
                      </craft-button>
                    </div>
                  `
                : nothing}
            </section>
          `;
        }
      )}

      <craft-button
        type="button"
        variant="dashed"
        ?disabled="${this.readOnly}"
        data-field-layout-add-tab
        @activate="${this.addTab}"
      >
        ${t('New Tab')}
      </craft-button>

      ${this.withGeneratedFields
        ? html`
            <section data-generated-fields>
              <h3>${t('Generated Fields')}</h3>
              ${repeat(
                this.generatedFields,
                (field, index) => String(field.uid ?? index),
                (field, index) => html`
                  <div data-generated-field="${String(field.uid ?? index)}">
                    <craft-reorder-button
                      ?disabled="${this.readOnly ||
                      this.generatedFields.length < 2}"
                      position="${this.reorderPosition(
                        index,
                        this.generatedFields.length
                      )}"
                      @reorder="${(
                        event: CustomEvent<{direction: ReorderDirection}>
                      ) =>
                        this.reorderGeneratedField(
                          index,
                          event.detail.direction
                        )}"
                    ></craft-reorder-button>
                    <craft-input
                      .value="${String(field.name ?? '')}"
                      placeholder="${t('Name')}"
                      label="${t('Name')}"
                      label-sr-only
                      ?disabled="${this.readOnly}"
                      @input="${(event: Event) =>
                        this.updateGeneratedField(index, 'name', event)}"
                    ></craft-input>
                    <craft-input
                      .value="${String(field.handle ?? '')}"
                      placeholder="${t('Handle')}"
                      label="${t('Handle')}"
                      label-sr-only
                      class="code"
                      ?disabled="${this.readOnly}"
                      @input="${(event: Event) =>
                        this.updateGeneratedField(index, 'handle', event)}"
                    ></craft-input>
                    <textarea
                      .value="${String(field.template ?? '')}"
                      placeholder="${t('Template')}"
                      aria-label="${t('Template')}"
                      class="code"
                      rows="2"
                      ?disabled="${this.readOnly}"
                      @input="${(event: Event) =>
                        this.updateGeneratedField(index, 'template', event)}"
                    ></textarea>
                    <craft-button
                      type="button"
                      size="small"
                      variant="plain"
                      aria-label="${t('Remove generated field {name}', {
                        name: String(field.name ?? ''),
                      })}"
                      ?disabled="${this.readOnly}"
                      data-generated-field-remove
                      @activate="${() => this.removeGeneratedField(index)}"
                    >
                      ${t('Remove')}
                    </craft-button>
                  </div>
                `
              )}
              <craft-button
                type="button"
                variant="dashed"
                ?disabled="${this.readOnly}"
                data-generated-field-add
                @activate="${this.addGeneratedField}"
              >
                ${t('Add a field')}
              </craft-button>
            </section>
          `
        : nothing}
    `;
  }
}

if (!customElements.get('craft-field-layout')) {
  customElements.define('craft-field-layout', CraftFieldLayout);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-field-layout': CraftFieldLayout;
  }
}
