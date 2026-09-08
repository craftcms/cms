import {LionCombobox} from '@lion/ui/combobox.js';
import {html, nothing, render} from 'lit';
import {property} from 'lit/decorators.js';
import {keyed} from 'lit/directives/keyed.js';
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
 * @summary A single-select combobox with type-ahead filtering.
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

  declare private pendingModelValue: string;

  override get modelValue(): string {
    return super.modelValue;
  }

  override set modelValue(value: string) {
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
      if (
        event.target === this &&
        !(event as CustomEvent).detail?.isTriggeredByUser &&
        this.pendingModelValue !== undefined &&
        this.modelValue !== this.pendingModelValue
      ) {
        event.stopImmediatePropagation();
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
  }

  override updated(changed: Map<PropertyKey, unknown>) {
    super.updated(changed);
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
      changed.has('showSelectedHint')
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

  override addFormElement(option: CraftOption, indexToInsertAt: number) {
    super.addFormElement(option, indexToInsertAt);
    option.updateComplete.then(() => {
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
    this.#syncModelFromInput();
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
    if (this.modelValue !== this.#lastNotifiedValue) {
      this.#lastNotifiedValue = this.modelValue;
      this.dispatchEvent(
        new CustomEvent('model-value-changed', {
          bubbles: true,
          detail: {
            formPath: [this],
            isTriggeredByUser: true,
            changeSource: 'input',
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
  override matchCondition() {
    return true;
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
    const matched = this.#matchedOptions(query);
    const visible = matched.slice(0, this.limit);

    let lastGroup: string | undefined;
    const rows = visible.map((entry) => {
      const header =
        entry.groupLabel && entry.groupLabel !== lastGroup
          ? html`<div class="combobox__optgroup" aria-hidden="true">
              ${entry.groupLabel}
            </div>`
          : nothing;
      lastGroup = entry.groupLabel;

      return html`${header}${this.#optionTemplate(entry.option)}`;
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

    // Keyed on the option *set*, not on each row. A changed set has to yield
    // new `<craft-option>` elements: Lit would otherwise patch the existing
    // ones in place, and because they never disconnect, Lion's form registry
    // keeps the previous options — `addFormElement` never runs for the new
    // values, so a `modelValue` naming one of them can never be adopted and the
    // combobox keeps announcing the value it already had. Filtering leaves the
    // set alone, so it still patches in place rather than rebuilding the list
    // on every keystroke.
    render(keyed(this.#optionSetKey(), html`${rows}${footer}`), node);
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

  #optionTemplate(option: ComboboxOption) {
    const data = option.data ?? {};
    const label = option.label;
    const isEnv = label.startsWith('$') || label.startsWith('@');

    return html`
      <craft-option
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
    return this.modelValue !== '' && this.modelValue != null;
  }

  #clear = () => {
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
        ${icon
          ? html`<craft-icon class="prefix" name=${icon}></craft-icon>`
          : nothing}
        <slot name="input"></slot>
        ${this.clearable && this.#hasValue()
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
    `;
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
    if (!this.#hasValue()) {
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

    // @ts-expect-error Lion handles `null` but the types don't account for it
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
