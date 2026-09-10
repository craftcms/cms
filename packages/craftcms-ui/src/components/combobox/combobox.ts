import {LionCombobox} from '@lion/ui/combobox.js';
import {html, nothing, render} from 'lit';
import {property} from 'lit/decorators.js';
import {keyed} from 'lit/directives/keyed.js';
import {repeat} from 'lit/directives/repeat.js';
import styles from './combobox.styles.js';
import type CraftOption from '../option/option.js';
import {t} from '@src/utilities/translate';
import '../option/option.js';
import '../icon/icon.js';
import '../indicator/indicator.js';
import '../button/button.js';

export interface ComboboxOptionData {
  /** Extra text matched against the query, in addition to label/value. */
  keywords?: string;
  /** Secondary text rendered after the label. */
  hint?: string;
  /** Renders a `craft-indicator` before the label. */
  indicator?: {variant?: string} & Record<string, unknown>;
  /** Name of a `craft-icon` rendered before the label, and in the textbox while this option is selected. */
  icon?: string;
  [key: string]: unknown;
}

export interface ComboboxOption {
  label: string;
  value: string;
  disabled?: boolean;
  type?: 'option';
  data?: ComboboxOptionData | null;
}

export interface ComboboxOptGroup {
  type: 'optgroup';
  label: string;
  options: ComboboxOption[];
}

export type ComboboxItem = ComboboxOption | ComboboxOptGroup;

interface VisibleEntry {
  groupLabel?: string;
  option: ComboboxOption;
}

/**
 * @summary A combobox with type-ahead filtering and optional multiple selection.
 * @since 1.0
 *
 * Unlike Lion's combobox (which expects every option authored as a slotted
 * child), `craft-combobox` takes an `options` array property and renders only
 * the matching subset — capped at {@link limit} — as `craft-option` children.
 * This keeps the DOM node count bounded even when the source list has hundreds
 * of entries. Lion still drives selection, keyboard navigation, and a11y over
 * the rendered set.
 *
 * @dependency craft-option
 * @dependency craft-indicator
 *
 * @slot input - The text input (provided by Lion).
 * @slot label - Field label.
 * @slot after - Supplementary content rendered below the field.
 * @slot feedback - Validation feedback.
 */
export default class CraftCombobox extends LionCombobox {
  static formAssociated = true;

  static override get properties() {
    return {modelValue: {attribute: 'model-value'}};
  }

  @property({type: Boolean, attribute: 'multiple-choice', reflect: true})
  override multipleChoice = false;

  @property({type: Boolean}) required = false;

  private internals?: ElementInternals;
  private inputs?: HTMLSpanElement;
  private initialValues: string[] = [];
  private fieldsetDisabled = false;
  private changingValues = false;

  override attributeChangedCallback(
    name: string,
    old: string | null,
    value: string | null
  ) {
    if (name === 'model-value') {
      this.modelValue = this.hasAttribute('multiple-choice')
        ? JSON.parse(value ?? '[]')
        : (value ?? '');
      return;
    }
    super.attributeChangedCallback(name, old, value);
  }

  static override get styles() {
    return [...super.styles, styles];
  }

  /** Options to render. Groups are supported via `type: 'optgroup'`. */
  @property({type: Array}) options: ComboboxItem[] = [];

  /** Maximum number of matching options rendered at once (performance guard). */
  @property({type: Number}) limit = 150;

  /** Shows a clear button when a value is present. */
  @property({type: Boolean, reflect: true}) clearable = false;

  /** Placeholder shown when the textbox is empty. */
  @property({type: String, reflect: true}) placeholder = '';

  /** Includes the selected option's hint in the textbox. */
  @property({type: Boolean, reflect: true, attribute: 'show-selected-hint'})
  showSelectedHint = false;

  declare private pendingModelValue: string | string[];

  override get modelValue(): string | string[] {
    return super.modelValue;
  }

  override set modelValue(value: string | string[]) {
    this.pendingModelValue = value;
    super.modelValue = value;
  }

  constructor() {
    super();
    // Configure validators on construction.
    this.defaultValidators = [];
    // We own filtering (see `matchCondition`), so keep Lion in list mode and
    // avoid its inline-autofill, which would fight our pre-filtered set.
    this.autocomplete = 'list';

    // Lion announces while it still holds the previous value: a requested value
    // is only adopted once the option naming it registers, so until then
    // `pendingModelValue` runs ahead of `modelValue`. A bound v-model writes
    // every announcement straight back, so letting that one out puts the old
    // value into the consumer's state and marks it changed. Registered in the
    // constructor so it precedes any listener the consumer attaches.
    this.addEventListener('model-value-changed', (event: Event) => {
      if (this.changingValues) {
        event.stopImmediatePropagation();
        return;
      }
      if (
        event.target === this &&
        !(event as CustomEvent).detail?.isTriggeredByUser &&
        this.pendingModelValue !== undefined &&
        JSON.stringify(this.modelValue) !==
          JSON.stringify(this.pendingModelValue)
      ) {
        event.stopImmediatePropagation();
        return;
      }
      if (
        this.multipleChoice &&
        event.target === this &&
        (event as CustomEvent).detail?.isTriggeredByUser
      ) {
        this.pendingModelValue = [...this.selectedValues];
        this.syncInputs();
        this.dispatchEvent(
          new Event('change', {bubbles: true, composed: true})
        );
      }
    });
    this.addEventListener('focusout', (event) => {
      if (
        this.multipleChoice &&
        !this.requireOptionMatch &&
        !(
          event.relatedTarget instanceof Node &&
          this.contains(event.relatedTarget)
        )
      ) {
        this.commitQuery();
      }
    });
  }

  /** Last model value we've announced via `model-value-changed`. */
  #lastNotifiedValue: unknown = undefined;

  #filtering = false;

  override firstUpdated(changed: Map<PropertyKey, unknown>) {
    super.firstUpdated(changed);
    this._inputNode?.addEventListener('input', this.#onInput);
    // Keep our notion of the last-announced value in sync with every
    // model-value-changed the component emits — Lion's (on selection) and ours
    // (on free-text, below) — so we never double-announce the same value.
    this.addEventListener('model-value-changed', () => {
      this.#lastNotifiedValue = this.modelValue;
    });
    this.#lastNotifiedValue = this.modelValue;
    this.#renderOptions();
    if (this.multipleChoice) {
      this.initialValues = [
        ...(Array.isArray(this.pendingModelValue)
          ? this.pendingModelValue
          : []),
      ];
    }
  }

  override updated(changed: Map<PropertyKey, unknown>) {
    super.updated(changed);
    if (this.multipleChoice) {
      this.syncInputs();
      this._inputNode.removeAttribute('name');
      this._comboboxNode.setAttribute(
        'aria-labelledby',
        this._inputNode.getAttribute('aria-labelledby') ?? ''
      );
      this._inputNode.disabled = this.disabled || this.fieldsetDisabled;
      const missing =
        this.required && !this.inactive && this.selectedValues.length === 0;
      this.internals ??= this.attachInternals();
      this.internals.setValidity(
        missing ? {valueMissing: true} : {},
        missing ? t('This field is required.') : '',
        this._inputNode
      );
    }
    if (changed.has('opened') && !this.opened) {
      this.#filtering = false;
    }
    if (changed.has('placeholder')) {
      this._inputNode.placeholder = this.placeholder;
    }
    if (
      changed.has('options') ||
      changed.has('limit') ||
      changed.has('opened') ||
      changed.has('modelValue') ||
      changed.has('showSelectedHint') ||
      changed.has('multipleChoice')
    ) {
      this.#renderOptions();
    }
    if (changed.has('options') || changed.has('modelValue')) {
      // The prefix icon is absolutely positioned over the slotted input, so
      // the input only gets its leading padding while an icon is showing.
      this.toggleAttribute(
        'has-prefix-icon',
        this.#selectedOption()?.data?.icon != null
      );
    }
  }

  override addFormElement(child: unknown, indexToInsertAt?: number) {
    super.addFormElement(
      child as Parameters<LionCombobox['addFormElement']>[0],
      indexToInsertAt
    );
    const option = child as CraftOption;
    option.updateComplete.then(() => {
      if (this.multipleChoice) {
        option.checked =
          Array.isArray(this.pendingModelValue) &&
          this.pendingModelValue.includes(String(option.choiceValue));
        return;
      }
      if (String(option.choiceValue) !== String(this.pendingModelValue)) {
        return;
      }

      super.modelValue = this.pendingModelValue;
      this._setTextboxValue(this._getTextboxValueFromOption(option));
    });
  }

  #onInput = () => {
    this.#filtering = true;
    this.#renderOptions();
    if (!this.multipleChoice) {
      this.#syncModelFromInput();
    }
  };

  /**
   * Keep the model value in sync with typed (free-text) input.
   *
   * With `autocomplete='list'` Lion only re-derives a custom value from the
   * textbox inside its autoselect branch (which list mode disables), so a
   * typed value stops updating the model after the first keystroke. And even
   * when the model does change for free text, Lion never emits
   * `model-value-changed` for it — that only happens by repropagating a
   * *selected option's* event. So we own both here: derive the model from the
   * textbox each keystroke, then announce it (matching Lion's dispatch shape;
   * `target === this` makes Lion's child-repropagation handler ignore it, so it
   * reaches application listeners — the Vue v-model — without looping).
   *
   * Driving the model from the raw input each keystroke also means an external
   * `.modelValue` write-back (from the bound v-model) can't wedge editing:
   * the next keystroke recomputes from the actual textbox value.
   */
  #syncModelFromInput() {
    const parsed = this.parser(this._inputNode?.value ?? '');
    if (parsed !== this.modelValue) {
      this.modelValue = parsed;
    }

    this._notifyModelValueChanged();
  }

  protected _notifyModelValueChanged(): void {
    if (
      JSON.stringify(this.modelValue) !==
      JSON.stringify(this.#lastNotifiedValue)
    ) {
      this.#lastNotifiedValue = this.modelValue;
      this.dispatchEvent(
        new CustomEvent('model-value-changed', {
          bubbles: true,
          composed: true,
          detail: {
            formPath: [this],
            isTriggeredByUser: true,
            changeSource: this.multipleChoice ? 'selection' : 'input',
          },
        })
      );
    }
  }

  /**
   * We filter the source array ourselves and only render matches, so every
   * rendered option is already a match — never let Lion hide one (its default
   * label-substring check would drop keyword-only matches).
   */
  override matchCondition(option: CraftOption) {
    return !this.multipleChoice || !option.hidden;
  }

  /**
   * Lion highlights the matched substring by mutating each option's DOM
   * (bolding text and moving children into an a11y span). We render option
   * content ourselves with lit-html and reconcile on every keystroke, so those
   * mutations detach lit-html's tracked nodes and corrupt the output — stale
   * text is left behind and new text is appended (e.g. "Option 000Option 300").
   * Disable the highlight entirely; we own the option content. The visibility
   * toggling in `_onFilterMatch`/`_onFilterUnmatch` is untouched (and moot,
   * since we only render matches).
   */
  override _highlightMatchedOption() {}

  override _unhighlightMatchedOption() {}

  /**
   * Open the listbox on Down/Up arrow even when the textbox is empty, so
   * keyboard users can browse the options (the standard combobox affordance).
   * Lion otherwise only opens an empty field when `showAllOnEmpty` is set.
   */
  override _showOverlayCondition(options: {
    currentValue?: string;
    lastKey?: string;
  }) {
    if (
      !this.disabled &&
      !this.readOnly &&
      (options.lastKey === 'ArrowDown' || options.lastKey === 'ArrowUp')
    ) {
      return true;
    }

    return super._showOverlayCondition(options);
  }

  #matchedOptions(query: string): VisibleEntry[] {
    const q = query.trim().toLowerCase();
    const entries: VisibleEntry[] = [];

    for (const item of this.options) {
      if (this.#isGroup(item)) {
        for (const option of item.options) {
          if (q === '' || this.#matches(q, option)) {
            entries.push({groupLabel: item.label, option});
          }
        }
      } else if (q === '' || this.#matches(q, item)) {
        entries.push({option: item});
      }
    }

    return entries;
  }

  #isGroup(item: ComboboxItem): item is ComboboxOptGroup {
    return item.type === 'optgroup';
  }

  #matches(loweredQuery: string, option: ComboboxOption): boolean {
    return (
      option.label.toLowerCase().includes(loweredQuery) ||
      String(option.value).toLowerCase().includes(loweredQuery) ||
      (option.data?.keywords?.toLowerCase().includes(loweredQuery) ?? false)
    );
  }

  #renderOptions() {
    const node = this._listboxNode as HTMLElement | undefined;
    if (!node) {
      return;
    }

    const query = this.#filtering ? (this._inputNode?.value ?? '') : '';
    const selected = this.multipleChoice
      ? Array.isArray(this.pendingModelValue)
        ? this.pendingModelValue
        : this.selectedValues
      : [];
    const matched = this.#matchedOptions(query).filter(
      (entry) => !selected.includes(entry.option.value)
    );
    const visible = matched.slice(0, this.limit);

    const options = [...this.#allOptions()];
    const entries: VisibleEntry[] = [
      ...selected.map((value) => ({
        option: options.find((option) => option.value === value) ?? {
          value,
          label: value,
        },
      })),
      ...visible,
    ];
    let lastGroup: string | undefined;
    const rows = entries.map((entry) => {
      const header =
        entry.groupLabel && entry.groupLabel !== lastGroup
          ? html`<div class="combobox__optgroup" aria-hidden="true">
              ${entry.groupLabel}
            </div>`
          : nothing;
      lastGroup = entry.groupLabel;

      return {
        value: entry.option.value,
        template: html`${header}${this.#optionTemplate(
          entry.option,
          selected.includes(entry.option.value)
        )}`,
      };
    });

    const footer =
      matched.length > this.limit
        ? html`<div class="combobox__footer" aria-hidden="true">
            ${t('Showing {shown} of {total} — keep typing to narrow results.', {
              shown: this.limit,
              total: matched.length,
            })}
          </div>`
        : nothing;

    // Retain selected option elements while filtering: Lion derives checked
    // values from registered options, including selections outside the limit.
    const content = html`${this.multipleChoice
      ? repeat(
          rows,
          (row) => row.value,
          (row) => row.template
        )
      : rows.map((row) => row.template)}${footer}`;
    // Single selection keys the whole option set so replaced options disconnect
    // and register with Lion again. Otherwise Lion keeps the old choice values
    // and can reject the new model value. Filtering keeps the same key.
    render(
      this.multipleChoice ? content : keyed(this.#optionSetKey(), content),
      node
    );
  }

  /** Identifies the current option set, so a changed one rebuilds the list. */
  #optionSetKey(): string {
    const values: string[] = [];

    for (const item of this.options) {
      if (this.#isGroup(item)) {
        for (const option of item.options) {
          values.push(String(option.value));
        }
      } else {
        values.push(String(item.value));
      }
    }

    return values.join('\u0000');
  }

  #optionTemplate(option: ComboboxOption, selected = false) {
    const data = option.data ?? {};
    const label = option.label;
    const isEnv = label.startsWith('$') || label.startsWith('@');

    return html`
      <craft-option
        ?hidden=${selected}
        aria-hidden=${selected ? 'true' : nothing}
        .choiceValue=${String(option.value)}
        .hint=${data.hint ?? null}
        ?disabled=${option.disabled ?? false}
      >
        <span class="combobox__option">
          ${data.indicator
            ? html`<craft-indicator
                variant=${data.indicator.variant ?? 'neutral'}
              ></craft-indicator>`
            : nothing}
          ${data.icon
            ? html`<craft-icon name="${data.icon}"></craft-icon>`
            : nothing}
          ${isEnv ? html`<code>${label}</code>` : label}
        </span>
      </craft-option>
    `;
  }

  #hasValue(): boolean {
    return this.multipleChoice
      ? this.selectedValues.length > 0
      : this.modelValue !== '' && this.modelValue != null;
  }

  #clear = () => {
    if (this.multipleChoice) {
      this.changeValues([]);
      return;
    }
    this.modelValue = '';
    if (this._inputNode) {
      this._inputNode.value = '';
    }
    this.#renderOptions();
    this._inputNode?.focus();
  };

  override _inputGroupInputTemplate() {
    const icon = this.#selectedOption()?.data?.icon;

    return html`
      <div class="input-group__input">
        ${this.multipleChoice
          ? repeat(
              this.selectedValues,
              (value) => value,
              (value) => html`
                <span class="token">
                  ${this.valueLabel(value)}
                  ${!this.inactive
                    ? html`<button
                        type="button"
                        aria-label=${t('Remove {label}', {
                          label: this.valueLabel(value),
                        })}
                        @click=${() =>
                          this.changeValues(
                            this.selectedValues.filter((item) => item !== value)
                          )}
                      >
                        ×
                      </button>`
                    : nothing}
                </span>
              `
            )
          : nothing}
        <div class="combobox__textbox">
          ${icon
            ? html`<craft-icon class="prefix" name=${icon}></craft-icon>`
            : nothing}
          <slot name="input"></slot>
          ${this.clearable && this.#hasValue() && !this.inactive
            ? html`<craft-button
                class="clear"
                type="button"
                appearance="plain"
                size="small"
                icon
                aria-label=${t('Clear')}
                @mousedown=${(e: Event) => e.preventDefault()}
                @click=${this.#clear}
              >
                <craft-icon name="xmark" style="font-size: 0.8em"></craft-icon>
              </craft-button>`
            : nothing}
          <craft-icon
            class="indicator"
            name="chevron-down"
            style="font-size: 0.8em"
          ></craft-icon>
        </div>
      </div>
    `;
  }

  private get selectedValues(): string[] {
    return Array.isArray(this.modelValue) ? this.modelValue : [];
  }

  private get inactive(): boolean {
    return this.disabled || this.readOnly || this.fieldsetDisabled;
  }

  private valueLabel(value: string): string {
    return (
      [...this.#allOptions()].find((option) => option.value === value)?.label ??
      value
    );
  }

  private syncInputs() {
    if (!this.inputs) {
      this.inputs = document.createElement('span');
      this.inputs.hidden = true;
      this.inputs.slot = 'native-inputs';
      this.inputs.setAttribute('data-combobox-inputs', '');
      this.append(this.inputs);
    }
    // HTMX also serializes condition builders that are not inside a form.
    render(
      !this.name || this.disabled || this.fieldsetDisabled
        ? nothing
        : html`
            <input type="hidden" name=${this.name} value="" />
            ${this.selectedValues.map(
              (value) =>
                html`<input
                  type="hidden"
                  name=${`${this.name}[]`}
                  .value=${value}
                />`
            )}
          `,
      this.inputs
    );
  }

  formResetCallback() {
    if (this.multipleChoice) {
      this.modelValue = [...this.initialValues];
      this.value = '';
      this.syncInputs();
    }
  }

  formDisabledCallback(disabled: boolean) {
    this.fieldsetDisabled = disabled;
    this.requestUpdate();
    if (this.multipleChoice) {
      this.syncInputs();
    }
  }

  private changeValues(values: string[]) {
    if (this.inactive) {
      return;
    }
    this.changingValues = true;
    try {
      this.modelValue = values;
    } finally {
      this.changingValues = false;
    }
    this.value = '';
    this.opened = false;
    this.#renderOptions();
    this.syncInputs();
    this._notifyModelValueChanged();
    this._inputNode.focus();
  }

  private commitQuery() {
    const value = this.value;
    if (this.inactive || value === '') {
      return;
    }
    const option = [...this.#allOptions()].find(
      (option) => option.value === value
    );
    if (option?.disabled || (this.requireOptionMatch && !option)) {
      return;
    }
    this.changeValues([...new Set([...this.selectedValues, value])]);
  }

  override _listboxOnKeyDown(event: KeyboardEvent) {
    if (!this.multipleChoice) {
      super._listboxOnKeyDown(event);
      return;
    }
    if (this.inactive || event.isComposing) {
      return;
    }
    if (event.key === 'Backspace' && this.value === '') {
      event.preventDefault();
      this.changeValues(this.selectedValues.slice(0, -1));
      return;
    }
    const option = this.formElements[this.activeIndex];
    if (
      event.key === 'Enter' &&
      (!this.opened ||
        !option ||
        option.hidden ||
        option.hasAttribute('aria-hidden'))
    ) {
      event.preventDefault();
      this.commitQuery();
      return;
    }
    super._listboxOnKeyDown(event);
  }

  override _syncToTextboxMultiple() {}

  override _resetListboxOptions() {
    super._resetListboxOptions();
    this.hideSelectedOptions();
  }

  override _handleAutocompletion() {
    super._handleAutocompletion();
    this.hideSelectedOptions();
  }

  private hideSelectedOptions() {
    if (!this.multipleChoice) {
      return;
    }
    // Lion shows every option for an empty query, regardless of matchCondition.
    for (const option of this.formElements) {
      if (option.hidden) {
        option.setAttribute('aria-hidden', 'true');
        option.removeAttribute('aria-posinset');
        option.removeAttribute('aria-setsize');
      }
    }
    const visible = this.formElements.filter(
      (option) => !option.hasAttribute('aria-hidden')
    );
    visible.forEach((option, index) => {
      option.setAttribute('aria-posinset', String(index + 1));
      option.setAttribute('aria-setsize', String(visible.length));
    });
  }

  /**
   * Lion drives a single-select combobox's model value off the textbox string.
   * Because we display each option's label (not its value), map a displayed
   * label back to its option value here so `modelValue` is the option's value —
   * e.g. selecting "Online" yields `'1'`, not `'Online'`. Text that matches no
   * option label is passed through as a custom value (`requireOptionMatch`
   * false), preserving free-text/env-var entry.
   */
  override parser(value: string | Array<string>) {
    if (typeof value === 'string' && value !== '') {
      const match = this.#optionByLabel(value);
      return match ? match.value : value;
    }

    return super.parser(value);
  }

  #optionByLabel(label: string): ComboboxOption | undefined {
    const target = label.trim();

    for (const option of this.#allOptions()) {
      if (this.#displayLabel(option) === target) {
        return option;
      }
    }

    return undefined;
  }

  /**
   * The option the model value currently holds, if any. Free text (which the
   * model also carries, `requireOptionMatch` being false) matches nothing, so
   * a half-typed query never shows a stale option's icon.
   */
  #selectedOption(): ComboboxOption | undefined {
    if (this.multipleChoice || !this.#hasValue()) {
      return undefined;
    }

    const target = String(this.modelValue);

    for (const option of this.#allOptions()) {
      if (String(option.value) === target) {
        return option;
      }
    }

    return undefined;
  }

  /** Every option in source order, with groups flattened. */
  *#allOptions(): Generator<ComboboxOption> {
    for (const item of this.options) {
      if (this.#isGroup(item)) {
        yield* item.options;
      } else {
        yield item;
      }
    }
  }

  /**
   * With `autocomplete='list'`, Lion only reflects the model value into the
   * textbox while the field is blurred. Selecting an option refocuses the
   * input, so on overlay close the condition was false and the chosen label
   * never appeared. Force the sync when a selection commits (overlay close);
   * typing is unaffected — that path never runs with the `overlay-close`
   * phase, and the overlay-close sync is already gated on a real selection
   * (`checkedIndex !== -1`), so custom/free-text entry is preserved.
   */
  override _syncToTextboxCondition(
    modelValue: string | string[],
    oldModelValue: string | string[],
    config: {phase?: string} = {}
  ) {
    if (config.phase === 'overlay-close') {
      return true;
    }

    return super._syncToTextboxCondition(modelValue, oldModelValue, config);
  }

  /**
   * Override to use the option's text content instead of choiceValue.
   */
  override _getTextboxValueFromOption(option: CraftOption) {
    if (option) {
      const label = option.textContent?.trim() || '';

      return this.showSelectedHint && option.hint
        ? `${label} – ${option.hint}`
        : label;
    }

    return super._getTextboxValueFromOption(option);
  }

  #displayLabel(option: ComboboxOption): string {
    return this.showSelectedHint && option.data?.hint
      ? `${option.label} – ${option.data.hint}`
      : option.label;
  }
}

if (!customElements.get('craft-combobox')) {
  customElements.define('craft-combobox', CraftCombobox);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-combobox': CraftCombobox;
  }
}
