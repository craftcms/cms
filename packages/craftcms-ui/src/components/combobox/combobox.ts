import {html, LitElement, nothing, render, type PropertyValues} from 'lit';
import {property, state} from 'lit/decorators.js';
import {repeat} from 'lit/directives/repeat.js';
import styles from './combobox.styles.js';
import type CraftOption from '../option/option.js';
import {emitChange, emitInput} from '@src/utilities/form-events';
import {t} from '@src/utilities/translate';
import '../option/option.js';
import '../option/option-group.js';
import './combobox-token.js';
import '../icon/icon.js';
import '../indicator/indicator.js';
import '../button/button.js';
import {formControlStyles, type SlotFactory} from '@src/mixins/FormControl';
import {FormControl} from '@src/mixins/FormControl';
import {FormAssociated, type FormValue} from '@src/mixins/FormAssociated';
import visuallyHiddenStyles from '@src/styles/visually-hidden.styles.js';
import {colors} from '@src/constants/colors';
import {variants} from '@src/constants/variants';
import {claimEscape} from '@src/utilities/overlay-escape';
import {layerStyles} from '@src/utilities/overlay-layer';
import {Dropdown} from '@src/utilities/overlay-dropdown';
import {
  activateOption,
  listboxOptions,
  stepOption,
} from '@src/utilities/listbox';
import {uniqueId} from '@src/utilities/unique-id';

export interface ComboboxOptionData {
  /** Extra text matched against the query, in addition to label/value. */
  keywords?: string;
  /** Secondary text rendered after the label. */
  hint?: string;
  /**
   * Renders a `craft-indicator` before the label, and on the option's
   * `multiple` token. `fill` takes what the component's own does: a
   * status variant, a palette swatch, or any color.
   */
  indicator?: {fill?: string; appearance?: string} & Record<string, unknown>;
  /**
   * Name of a `craft-icon` rendered before the label, and while this option is
   * selected, in the textbox or on its `multiple` token.
   */
  icon?: string;
  /** The icon's color: a status variant, a palette swatch, or any color. */
  color?: string;
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

export type ComboboxValue = string | string[];

/**
 * Resolves a palette or variant name to its loud fill token, as
 * `craft-indicator` does, and passes any other color through.
 */
function iconColor(color: string): string {
  return (colors as string[]).includes(color) ||
    (variants as string[]).includes(color)
    ? `var(--c-color-${color}-fill-loud)`
    : color;
}

/** A `value` attribute or property, read as a list of values. */
function toList(value: unknown): string[] {
  if (Array.isArray(value)) {
    return value.map(String);
  }

  if (typeof value !== 'string' || value === '') {
    return [];
  }

  try {
    const parsed: unknown = JSON.parse(value);

    return Array.isArray(parsed) ? parsed.map(String) : [value];
  } catch {
    return [value];
  }
}

function sameValue(a: ComboboxValue, b: ComboboxValue): boolean {
  return JSON.stringify(a) === JSON.stringify(b);
}

const announcementTimeout = 5000;

/** The `inputType` of the `input` a selected option causes. */
const PICKED = 'insertReplacementText';

interface VisibleEntry {
  groupLabel?: string;
  option: ComboboxOption;
}

interface EntryRun {
  key: string;
  groupLabel?: string;
  entries: VisibleEntry[];
}

/**
 * @summary A combobox with type-ahead filtering and optional multiple selection.
 * @since 1.0
 *
 * Takes an `options` array and renders only the matching subset — capped at
 * {@link limit} — as `craft-option` children, which keeps the DOM bounded
 * even when the source list has hundreds of entries.
 *
 * Focus stays in the textbox, which points at the active option with
 * `aria-activedescendant`.
 *
 * @dependency craft-option
 * @dependency craft-indicator
 *
 * @slot input - The textbox (provided by default).
 * @slot label - Field label.
 * @slot after - Supplementary content rendered below the field.
 * @slot feedback - Validation feedback.
 *
 * @attr {string} value - The initial value. A JSON array of values when
 *   `multiple` is set, otherwise the value itself.
 *
 * @fires input - Emitted on every alteration to the value, typed or selected.
 *   Selecting an option makes it an `InputEvent` whose `inputType` is
 *   `insertReplacementText`, as picking a suggestion does on a native input.
 * @fires change - Emitted when the value is committed: an option is selected,
 *   or the field is left after typing.
 */
export default class CraftCombobox extends FormAssociated(
  FormControl(LitElement)
) {
  static override styles = [
    formControlStyles,
    visuallyHiddenStyles,
    layerStyles,
    styles,
  ];

  /** Options to render. Groups are supported via `type: 'optgroup'`. */
  @property({type: Array}) options: ComboboxItem[] = [];

  /** Maximum number of matching options rendered at once (performance guard). */
  @property({type: Number}) limit = 150;

  /**
   * Whether more than one option may be selected. Each selected value posts
   * as `name[]`.
   */
  @property({type: Boolean, attribute: 'multiple', reflect: true})
  multiple = false;

  /** Whether the value has to be one of the options, rather than free text. */
  @property({type: Boolean, attribute: 'require-option-match'})
  requireOptionMatch = false;

  /**
   * Lists every option while the textbox is empty. Otherwise the list closes
   * when the textbox is emptied, and opens from the keyboard or the chevron.
   */
  @property({type: Boolean, attribute: 'show-all-on-empty'})
  showAllOnEmpty = false;

  /** Whether the form refuses to submit without a value. */
  @property({type: Boolean}) required = false;

  /** Marks the value as invalid: sets `aria-invalid` on the textbox. */
  @property({type: Boolean, reflect: true}) invalid = false;

  /** Whether the listbox is showing. */
  @property({type: Boolean, reflect: true}) open = false;

  /** Shows a clear button when a value is present. */
  @property({type: Boolean, reflect: true}) clearable = false;

  /** Placeholder shown when the textbox is empty. */
  @property({type: String, reflect: true}) placeholder = '';

  /** Includes the selected option's hint in the textbox. */
  @property({type: Boolean, reflect: true, attribute: 'show-selected-hint'})
  showSelectedHint = false;

  #value: unknown = '';

  /**
   * The selected value: an option's value or free text, or with
   * `multiple`, a list of them.
   */
  @property({attribute: 'value'})
  get value(): ComboboxValue {
    return this.multiple
      ? toList(this.#value)
      : Array.isArray(this.#value)
        ? String(this.#value[0] ?? '')
        : String(this.#value ?? '');
  }

  set value(value: ComboboxValue) {
    const old = this.value;
    this.#value = value ?? '';

    if (sameValue(old, this.value)) {
      return;
    }

    // Typed text that stays in the textbox goes on filtering the options.
    const text = this._control?.value;
    this.#syncTextbox();
    if (this._control?.value !== text) {
      this.#filtering = false;
    }
    this.requestUpdate('value', old);

    // A value set from script isn't an edit to commit on blur.
    if (!this.#editing) {
      this.#committedValue = this.value;
    }
  }

  /** Whether the user has changed the value since it was last committed. */
  #editing = false;

  /** The `aria-labelledby` ids the textbox got from the host and selection. */
  #addedLabelledBy: string[] = [];

  #initialValue: ComboboxValue = '';
  #fieldsetDisabled = false;
  #filtering = false;
  #committedValue: ComboboxValue = '';
  #activeValue: string | null = null;
  #lastQuery = '';
  #dropdown = new Dropdown(() => {
    const layer =
      this.renderRoot.querySelector<HTMLElement>('.overlay-layer') ?? null;
    const pane = this.#popup;
    const reference =
      this.renderRoot.querySelector('.input-group__input') ?? null;

    return layer && pane && reference ? {layer, pane, reference} : undefined;
  });

  constructor() {
    super();

    this.addEventListener('focusout', this.#onFocusOut);
  }

  /** Creates the textbox and the listbox when none is slotted. */
  override get slots(): Record<string, SlotFactory> {
    return {
      ...super.slots,
      input: () => {
        const input = document.createElement('input');
        input.autocomplete = 'off';
        return input;
      },
      listbox: () => document.createElement('div'),
    };
  }

  override get _control(): HTMLInputElement | undefined {
    const node = super._control;

    return node instanceof HTMLInputElement
      ? node
      : (node?.querySelector('input') ?? undefined);
  }

  /** The listbox, in the light DOM so the textbox can reference its options. */
  get #listbox(): HTMLElement | undefined {
    return (
      this.querySelector<HTMLElement>(':scope > [slot=listbox]') ?? undefined
    );
  }

  get #popup(): HTMLElement | null {
    return this.renderRoot.querySelector<HTMLElement>(
      '#overlay-content-node-wrapper'
    );
  }

  override connectedCallback() {
    super.connectedCallback();

    const input = this._control;
    const listbox = this.#listbox;

    if (input && listbox) {
      this.#setupTextbox(input, listbox);
    }

    this.#renderOptions();
    this.#syncTextbox();
  }

  override disconnectedCallback() {
    if (this.#announceTimeout !== null) {
      clearTimeout(this.#announceTimeout);
      this.#announceTimeout = null;
    }
    this.#announcingRemoval = false;
    this.#announcedMatches = '';
    this.announcement = '';
    this.#dropdown.hide();
    document.removeEventListener('pointerdown', this.#onDocumentPointerDown);
    super.disconnectedCallback();
  }

  #setupTextbox(input: HTMLInputElement, listbox: HTMLElement): void {
    if (input.dataset.comboboxTextbox !== undefined) {
      return;
    }
    input.dataset.comboboxTextbox = '';

    listbox.id ||= uniqueId('listbox');
    listbox.setAttribute('role', 'listbox');
    listbox.addEventListener('mousedown', (event) => event.preventDefault());
    listbox.addEventListener('click', this.#onListboxClick);

    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-haspopup', 'listbox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', listbox.id);
    input.setAttribute('aria-expanded', 'false');
    input.addEventListener('input', this.#onInput);
    input.addEventListener('keydown', this.#onKeydown);
    input.addEventListener('click', this.#onTextboxClick);
    // The textbox's own `change` on blur would reach consumers with the
    // textbox as `target`; the host announces its own commits.
    input.addEventListener('change', (event) => event.stopPropagation());
  }

  protected override firstUpdated(changed: PropertyValues) {
    super.firstUpdated(changed);
    this.#initialValue = this.value;
    this.#committedValue = this.value;
  }

  protected override willUpdate(changed: PropertyValues) {
    super.willUpdate(changed);

    if (changed.has('multiple')) {
      this.#value = this.value;
    }
  }

  protected override updated(changed: PropertyValues) {
    super.updated(changed);

    const input = this._control;
    const listbox = this.#listbox;

    if (changed.has('open')) {
      this.#onOpenChanged(changed.get('open') as boolean | undefined);
    }

    if (
      changed.has('options') ||
      changed.has('limit') ||
      changed.has('open') ||
      changed.has('value') ||
      changed.has('showSelectedHint') ||
      changed.has('multiple')
    ) {
      this.#renderOptions();
    }

    if (
      (changed.has('options') || changed.has('showSelectedHint')) &&
      !this.#filtering
    ) {
      this.#syncTextbox();
    }

    if (this.multiple) {
      this.#syncSelection();
    } else {
      this.#selection?.remove();
      this.#selection = undefined;
    }

    this._reflectAriaReferences();

    if (input) {
      input.placeholder = this.placeholder;
      input.disabled = this.disabled || this.#fieldsetDisabled;
      input.readOnly = this.readOnly;
      if (this.invalid) {
        input.setAttribute('aria-invalid', 'true');
      } else {
        input.removeAttribute('aria-invalid');
      }
      if (this.required) {
        input.setAttribute('aria-required', 'true');
      } else {
        input.removeAttribute('aria-required');
      }
    }

    if (this.multiple) {
      listbox?.setAttribute('aria-multiselectable', 'true');
    } else {
      listbox?.removeAttribute('aria-multiselectable');
    }

    this.#syncValidity();

    this.toggleAttribute(
      'has-prefix-icon',
      this.#selectedOption()?.data?.icon != null ||
        this.#selectedOption()?.data?.indicator != null
    );

    if (this.open) {
      this.#dropdown.update();
    }
  }

  /**
   * Names the textbox and listbox. An `aria-labelledby` on the host names a
   * label the consumer renders itself — a table column heading, say — and
   * takes the place of the label slot. In multiple mode the textbox
   * is named by its label and then the selected values, so focusing it says
   * what's already chosen.
   */
  protected override _reflectAriaReferences(): void {
    const input = this._control;

    // The base keeps ids it didn't write, so the ones added here last time
    // come off first; otherwise a host label that's gone would stay.
    if (input && this.#addedLabelledBy.length) {
      const kept = (input.getAttribute('aria-labelledby') ?? '')
        .split(/\s+/)
        .filter((id) => id && !this.#addedLabelledBy.includes(id));
      if (kept.length) {
        input.setAttribute('aria-labelledby', kept.join(' '));
      } else {
        input.removeAttribute('aria-labelledby');
      }
    }

    super._reflectAriaReferences();

    if (!input) {
      return;
    }

    const selectionId = this.#selection?.id;
    this.#addedLabelledBy = [
      ...(this.getAttribute('aria-labelledby') ?? '').split(/\s+/),
      selectionId ?? '',
    ].filter(Boolean);
    const labelledBy = (
      this.getAttribute('aria-labelledby') ||
      input.getAttribute('aria-labelledby') ||
      ''
    )
      .split(' ')
      .filter((id) => id && id !== selectionId)
      .join(' ');

    input.setAttribute(
      'aria-labelledby',
      selectionId ? `${labelledBy} ${selectionId}`.trim() : labelledBy
    );
    this.#listbox?.setAttribute('aria-labelledby', labelledBy);

    const label = this.getAttribute('aria-label');
    if (label) {
      input.setAttribute('aria-label', label);
    }
  }

  // ─── Opening and closing ──────────────────────────────────────────────

  #onOpenChanged(was: boolean | undefined): void {
    this._control?.setAttribute('aria-expanded', String(this.open));

    if (this.open) {
      this.#dropdown.show();
      document.addEventListener('pointerdown', this.#onDocumentPointerDown);
      return;
    }

    this.#dropdown.hide();
    document.removeEventListener('pointerdown', this.#onDocumentPointerDown);

    if (was) {
      this.#setActive(undefined);
      if (this.#filtering) {
        this.#filtering = false;
        this.#syncTextbox();
      }
    }
  }

  #onDocumentPointerDown = (event: PointerEvent): void => {
    if (!event.composedPath().includes(this)) {
      this.open = false;
    }
  };

  get #inactive(): boolean {
    return this.disabled || this.readOnly || this.#fieldsetDisabled;
  }

  /** Whether the list opens for what the textbox now holds. */
  #shouldOpen(): boolean {
    return (
      !this.#inactive &&
      ((this._control?.value ?? '') !== '' || this.showAllOnEmpty)
    );
  }

  #onTextboxClick = (): void => {
    if (!this.open && this.#shouldOpen()) {
      this.open = true;
    }
  };

  #onFocusOut = (event: FocusEvent): void => {
    if (
      event.relatedTarget instanceof Node &&
      (this.contains(event.relatedTarget) ||
        this.renderRoot.contains(event.relatedTarget))
    ) {
      return;
    }

    if (this.multiple && !this.requireOptionMatch) {
      this.#commitQuery();
    }

    this.open = false;
    this.#commit();
  };

  // ─── Typing ───────────────────────────────────────────────────────────

  #onInput = (event: Event): void => {
    // The textbox's own event would reach consumers with the textbox as
    // `target`; the host re-emits it as its own.
    event.stopPropagation();

    const query = this._control?.value ?? '';
    const previous = this.#lastQuery;
    this.#lastQuery = query;
    this.#filtering = true;
    this.#editing = true;

    if (!this.multiple && !this.requireOptionMatch) {
      this.#setValue(this.#parse(query));
    }

    this.open = this.#shouldOpen();
    this.#renderOptions();

    // Adding to the query makes its best match active, so Enter picks it.
    // Deleting doesn't, so Backspace never selects something by accident.
    const adding =
      previous.length < query.length ||
      (previous !== '' &&
        query !== '' &&
        previous[0]!.toLowerCase() !== query[0]!.toLowerCase());

    this.#setActive(
      !this.multiple && adding && query !== ''
        ? this.#options().find((option) => !option.disabled)
        : undefined
    );

    emitInput(this);
  };

  /**
   * Maps typed text to a value: an option's value when the text is that
   * option's label as the textbox would show it, and the text itself
   * otherwise, so free text and environment variables pass through.
   */
  #parse(text: string): string {
    const target = text.trim();

    if (target !== '') {
      for (const option of this.#allOptions()) {
        if (this.#displayLabel(option) === target) {
          return option.value;
        }
      }
    }

    return text;
  }

  /** Sets the value from the user, without rewriting the textbox. */
  #setValue(value: ComboboxValue): void {
    const old = this.value;
    this.#value = value;
    if (!sameValue(old, this.value)) {
      this.requestUpdate('value', old);
    }
  }

  /** Shows the value in the textbox: the selected option's label, or the text. */
  #syncTextbox(): void {
    const input = this._control;

    if (!input || this.multiple) {
      return;
    }

    const value = this.value as string;
    const option = this.#option(value);
    const text = option ? this.#displayLabel(option) : value;

    if (input.value !== text) {
      input.value = text;
    }
    this.#lastQuery = text;
  }

  // ─── Keyboard ─────────────────────────────────────────────────────────

  #onKeydown = (event: KeyboardEvent): void => {
    if (event.isComposing) {
      return;
    }

    switch (event.key) {
      case 'ArrowDown':
      case 'ArrowUp':
        this.#onArrow(event);
        break;
      case 'Enter':
        this.#onEnter(event);
        break;
      case 'Escape':
        if (this.open) {
          event.preventDefault();
          claimEscape(event);
          this.open = false;
        }
        break;
      case 'Tab':
        this.open = false;
        break;
      case 'Backspace':
        if (
          this.multiple &&
          !this.#inactive &&
          (this._control?.value ?? '') === ''
        ) {
          event.preventDefault();
          this.#changeValues((this.value as string[]).slice(0, -1));
        }
        break;
    }
  };

  #onArrow(event: KeyboardEvent): void {
    if (this.#inactive) {
      return;
    }

    event.preventDefault();

    if (event.altKey) {
      this.open = event.key === 'ArrowDown';
      return;
    }

    if (!this.open) {
      this.open = true;
      return;
    }

    this.#setActive(
      stepOption(
        this.#options(),
        this.#activeOption(),
        event.key === 'ArrowDown' ? 1 : -1
      )
    );
  }

  #onEnter(event: KeyboardEvent): void {
    const option = this.open ? this.#activeOption() : undefined;

    if (this.open) {
      event.preventDefault();
    }

    if (this.#inactive) {
      return;
    }

    if (this.multiple) {
      if (option) {
        if (!option.disabled) {
          this.#pick(option);
        }
        return;
      }

      event.preventDefault();
      this.#commitQuery();
      return;
    }

    if (option && !option.disabled) {
      this.#pick(option);
    }

    this.open = false;
  }

  // ─── Selecting ────────────────────────────────────────────────────────

  #onListboxClick = (event: MouseEvent): void => {
    const option = (event.target as Element | null)?.closest<CraftOption>(
      'craft-option'
    );

    if (!option || option.disabled || this.#inactive) {
      return;
    }

    this.#pick(option);
    this._control?.focus();

    if (!this.multiple) {
      this.open = false;
    }
  };

  /** Selects an option on the user's behalf. */
  #pick(option: CraftOption): void {
    const value = option.value;

    if (this.multiple) {
      const values = this.value as string[];
      if (!values.includes(value)) {
        this.#changeValues([...values, value], {
          close: false,
          inputType: PICKED,
        });
      }
      return;
    }

    this.#editing = true;
    this.value = value;
    this.#filtering = false;
    this.#syncTextbox();
    emitInput(this, PICKED);
    this.#commit();
  }

  /** Emits `change` when the value has moved since the last commit. */
  #commit(): void {
    this.#editing = false;

    if (this.multiple || sameValue(this.value, this.#committedValue)) {
      return;
    }

    this.#committedValue = this.value;
    emitChange(this);
  }

  #changeValues(
    values: string[],
    {close = true, inputType}: {close?: boolean; inputType?: string} = {}
  ): void {
    if (this.#inactive) {
      return;
    }

    const removed = (this.value as string[]).filter(
      (value) => !values.includes(value)
    );

    this.value = values;
    if (this._control) {
      this._control.value = '';
    }
    this.#lastQuery = '';
    this.#filtering = false;
    if (close) {
      this.open = false;
    }
    this.#renderOptions();
    this._syncFormValue();
    emitInput(this, inputType);
    this.#committedValue = this.value;
    // An `input` listener may have put the value back; that's no commit.
    if (sameValue(this.value, values)) {
      emitChange(this);
    }
    this._control?.focus();

    // The list of values announces additions only; a removed value leaves no
    // node behind to announce, so say what went.
    if (removed.length) {
      this.#announce(
        t('Removed {name}', {
          name: new Intl.ListFormat(
            document.documentElement.lang || undefined,
            {type: 'conjunction'}
          ).format(removed.map((value) => this.#valueLabel(value))),
        }),
        true
      );
    }
  }

  /** Adds the typed text as a value, when it's allowed to be one. */
  #commitQuery(): void {
    const query = this._control?.value ?? '';

    if (this.#inactive || query === '') {
      return;
    }

    const option = this.#option(query);
    if (option?.disabled || (this.requireOptionMatch && !option)) {
      return;
    }

    this.#changeValues([...new Set([...(this.value as string[]), query])]);
  }

  #clear = (): void => {
    if (this.multiple) {
      this.#changeValues([]);
      return;
    }

    this.#editing = true;
    this.value = '';
    this.#syncTextbox();
    this.#renderOptions();
    this._control?.focus();
    emitInput(this);
    this.#commit();
  };

  // ─── The active option ────────────────────────────────────────────────

  #options(): CraftOption[] {
    return listboxOptions(this.#listbox);
  }

  #activeOption(): CraftOption | undefined {
    return this.#activeValue === null
      ? undefined
      : this.#options().find((option) => option.value === this.#activeValue);
  }

  #setActive(option: CraftOption | undefined): void {
    this.#activeValue = option?.value ?? null;
    activateOption(this.#options(), option, this._control, {
      scroll: this.open,
    });
  }

  // ─── Rendering the options ────────────────────────────────────────────

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

    return q === '' ? entries : this.#exactMatchesFirst(q, entries);
  }

  /**
   * Moves options whose label or value is exactly the query to the front of
   * their group, so the first match, which becomes active, is the one typed.
   * Other options keep their order, and groups stay together.
   */
  #exactMatchesFirst(
    loweredQuery: string,
    entries: VisibleEntry[]
  ): VisibleEntry[] {
    const isExact = ({option}: VisibleEntry) =>
      option.label.toLowerCase() === loweredQuery ||
      String(option.value).toLowerCase() === loweredQuery;
    const sorted: VisibleEntry[] = [];

    for (let start = 0; start < entries.length;) {
      let end = start;
      while (
        end < entries.length &&
        entries[end]!.groupLabel === entries[start]!.groupLabel
      ) {
        end++;
      }
      const run = entries.slice(start, end);
      sorted.push(...run.filter(isExact), ...run.filter((e) => !isExact(e)));
      start = end;
    }

    return sorted;
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

  #renderOptions(): void {
    const node = this.#listbox;
    if (!node) {
      return;
    }

    const query = this.#filtering ? (this._control?.value ?? '') : '';
    const selected = this.multiple ? (this.value as string[]) : [];
    const matched = this.#matchedOptions(query).filter(
      (entry) => !selected.includes(entry.option.value)
    );
    const visible = matched.slice(0, this.limit);
    const runs: EntryRun[] = [];
    const occurrences = new Map<string, number>();

    for (const entry of visible) {
      const last = runs.at(-1);
      if (last && last.groupLabel === entry.groupLabel) {
        last.entries.push(entry);
        continue;
      }
      const name = entry.groupLabel ?? '';
      const occurrence = occurrences.get(name) ?? 0;
      occurrences.set(name, occurrence + 1);
      runs.push({
        key: `${entry.groupLabel === undefined ? 'options' : 'group'}:${name}:${occurrence}`,
        groupLabel: entry.groupLabel,
        entries: [entry],
      });
    }

    const current = this.multiple ? null : (this.value as string);
    const optionsTemplate = (run: EntryRun) =>
      repeat(
        run.entries,
        (entry) => entry.option.value,
        (entry) =>
          this.#optionTemplate(
            entry.option,
            current !== null && String(entry.option.value) === current
          )
      );

    this.overflowTotal = matched.length > this.limit ? matched.length : 0;
    this.#announceMatches(matched.length);

    render(
      html`${repeat(
        runs,
        (run) => run.key,
        (run) =>
          run.groupLabel === undefined
            ? optionsTemplate(run)
            : html`<craft-option-group .label=${run.groupLabel}>
                ${optionsTemplate(run)}
              </craft-option-group>`
      )}`,
      node
    );

    const active = this.#activeOption();
    if (this.#activeValue !== null && !active) {
      this.#activeValue = null;
    }
    activateOption(this.#options(), active, this._control, {scroll: false});
  }

  #optionTemplate(option: ComboboxOption, selected: boolean) {
    const data = option.data ?? {};
    const label = option.label;
    const isEnv = label.startsWith('$') || label.startsWith('@');

    return html`
      <craft-option
        .value=${String(option.value)}
        .selected=${selected}
        .hint=${data.hint ?? null}
        ?disabled=${option.disabled ?? false}
      >
        ${this.#decorationTemplate(data)}${isEnv
          ? html`<code>${label}</code>`
          : label}
      </craft-option>
    `;
  }

  /** The option's dot and icon, which only repeat what its label says. */
  #decorationTemplate(data: ComboboxOptionData) {
    return html`${data.indicator
      ? html`<craft-indicator
          fill=${data.indicator.fill ?? nothing}
          appearance=${data.indicator.appearance ?? nothing}
        ></craft-indicator>`
      : nothing}${data.icon
      ? html`<craft-icon
          name="${data.icon}"
          style=${data.color ? `color: ${iconColor(data.color)}` : nothing}
        ></craft-icon>`
      : nothing}`;
  }

  #hasValue(): boolean {
    const value = this.value;

    return Array.isArray(value) ? value.length > 0 : value !== '';
  }

  // ─── Announcements ────────────────────────────────────────────────────

  @state()
  private announcement = '';

  #announceTimeout: ReturnType<typeof setTimeout> | null = null;

  /** The match count last announced while the list was open, if any. */
  #announcedMatches = '';

  /** Whether the live region is showing which values were just removed. */
  #announcingRemoval = false;

  #announceMatches(count: number): void {
    if (!this.open && !this.#filtering) {
      this.#announcedMatches = '';
      return;
    }

    const message =
      count === 0
        ? t('No results')
        : t('{num, number} {num, plural, =1{result} other{results}}', {
            num: count,
          });

    if (message === this.#announcedMatches) {
      return;
    }
    // A removal announces itself; the count waits until typing starts.
    if (this.#announcingRemoval && !this.#filtering) {
      return;
    }
    this.#announcedMatches = message;
    this.#announce(message);
  }

  #announce(message: string, removal = false): void {
    this.#announcingRemoval = removal;
    if (this.#announceTimeout !== null) {
      clearTimeout(this.#announceTimeout);
      this.#announceTimeout = null;
    }

    this.announcement = '';
    queueMicrotask(() => {
      if (this.isConnected) {
        this.announcement = message;
      }
    });

    this.#announceTimeout = setTimeout(() => {
      this.#announceTimeout = null;
      this.#announcingRemoval = false;
      this.announcement = '';
    }, announcementTimeout);
  }

  /** How many options match when that's more than `limit`, or 0. */
  @state()
  private overflowTotal = 0;

  // ─── The chevron ──────────────────────────────────────────────────────

  /** Whether the listbox was open when the chevron was pressed. */
  #openedOnIndicatorMousedown = false;

  /**
   * The chevron sits beside the textbox rather than over it, so a press on it
   * focuses the textbox and a click toggles the listbox, as a select's arrow
   * would.
   */
  #onIndicatorMousedown = (event: MouseEvent) => {
    if (event.button !== 0 || this.#inactive) {
      return;
    }

    event.preventDefault();
    this.#openedOnIndicatorMousedown = this.open;
    this._control?.focus();
  };

  #onIndicatorClick = () => {
    if (this.#inactive) {
      return;
    }

    this.open = !this.#openedOnIndicatorMousedown;
  };

  // ─── Templates ────────────────────────────────────────────────────────

  protected override _inputGroupInputTemplate() {
    const selectedData = this.#selectedOption()?.data;
    const icon = selectedData?.icon;
    const color = selectedData?.color;
    const indicator = selectedData?.indicator;

    return html`
      <div class="input-group__input">
        <slot name="selection"></slot>
        <div class="combobox__textbox">
          ${icon
            ? html`<craft-icon
                class="prefix"
                name=${icon}
                style=${color ? `color: ${iconColor(color)}` : nothing}
              ></craft-icon>`
            : indicator
              ? html`<span class="prefix prefix--indicator">
                  <craft-indicator
                    fill=${indicator.fill ?? nothing}
                    appearance=${indicator.appearance ?? nothing}
                  ></craft-indicator>
                </span>`
              : nothing}
          <slot name="input"></slot>
          ${this.clearable && this.#hasValue() && !this.#inactive
            ? html`<craft-button
                class="clear"
                type="button"
                variant="plain"
                size="small"
                icon
                aria-label=${t('Clear')}
                @mousedown=${(e: Event) => e.preventDefault()}
                @click=${this.#clear}
              >
                <craft-icon
                  name="xmark-large"
                  style="font-size: 0.8em"
                ></craft-icon>
              </craft-button>`
            : nothing}
          <craft-icon
            class="indicator"
            name="chevron-down"
            style="font-size: 0.8em"
            @mousedown=${this.#onIndicatorMousedown}
            @click=${this.#onIndicatorClick}
          ></craft-icon>
        </div>
      </div>
      <div class="overlay-layer" role="none" popover="manual">
        <div id="overlay-content-node-wrapper">
          <slot name="listbox"></slot>
          ${this.overflowTotal
            ? html`<div class="combobox__footer" aria-hidden="true">
                ${t(
                  'Showing {shown} of {total} — keep typing to narrow results.',
                  {shown: this.limit, total: this.overflowTotal}
                )}
              </div>`
            : nothing}
        </div>
      </div>
      <div class="cp-visually-hidden" aria-live="polite" aria-atomic="true">
        ${this.announcement}
      </div>
    `;
  }

  // ─── Values and labels ────────────────────────────────────────────────

  #valueLabel(value: string): string {
    return this.#option(value)?.label ?? value;
  }

  #option(value: string): ComboboxOption | undefined {
    for (const option of this.#allOptions()) {
      if (String(option.value) === value) {
        return option;
      }
    }

    return undefined;
  }

  /**
   * The option the value holds, if any. Free text matches nothing, so a
   * half-typed query never shows a stale option's icon.
   */
  #selectedOption(): ComboboxOption | undefined {
    return this.multiple || !this.#hasValue()
      ? undefined
      : this.#option(this.value as string);
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

  #displayLabel(option: ComboboxOption): string {
    return this.showSelectedHint && option.data?.hint
      ? `${option.label} – ${option.data.hint}`
      : option.label;
  }

  // ─── Multiple choice ──────────────────────────────────────────────────

  /**
   * The selected values, as a list in the light DOM so the textbox's
   * `aria-labelledby` can reach it. It's a polite live region, so a value is
   * announced as it's added.
   */
  #selection?: HTMLUListElement;

  #syncSelection(): void {
    if (!this.#selection) {
      this.#selection = document.createElement('ul');
      this.#selection.slot = 'selection';
      this.#selection.id = `selection-${this._inputId}`;
      // Safari drops the list role from a list without bullets.
      this.#selection.setAttribute('role', 'list');
      this.#selection.setAttribute('aria-live', 'polite');
      this.#selection.setAttribute('aria-atomic', 'false');
      this.append(this.#selection);
    }

    const values = this.value as string[];
    render(
      repeat(
        values,
        (value) => value,
        (value) => html`
          <li>
            <craft-combobox-token
              ?removable=${!this.#inactive}
              @craft-remove=${() =>
                this.#changeValues(
                  (this.value as string[]).filter((item) => item !== value)
                )}
            >
              ${this.#decorationTemplate(this.#option(value)?.data ?? {})}
              ${this.#valueLabel(value)}
            </craft-combobox-token>
          </li>
        `
      ),
      this.#selection
    );
  }

  /** Focuses the textbox, as a `craft-field` does when its label is clicked. */
  override focus(options?: FocusOptions): void {
    this._inputNode?.focus(options);
  }

  // ─── Forms ────────────────────────────────────────────────────────────

  #syncValidity(): void {
    const missing =
      this.multiple &&
      this.required &&
      !this.disabled &&
      !this.readOnly &&
      !this.#fieldsetDisabled &&
      (this.value as string[]).length === 0;

    this._setValidity(
      missing ? {valueMissing: true} : {},
      missing ? t('This field is required.') : '',
      this._control
    );
  }

  override _formValue(): FormValue {
    if (this.#fieldsetDisabled) {
      return null;
    }

    return this.multiple
      ? [...(this.value as string[])]
      : (this.value as string);
  }

  /** Restores the initial value when the owning form is reset. */
  formResetCallback(): void {
    this.value = this.#initialValue;
    this.#committedValue = this.value;
    this.#syncTextbox();
    this._syncFormValue();
  }

  /** Tracks whether an ancestor `<fieldset>` has disabled the control. */
  formDisabledCallback(disabled: boolean): void {
    this.#fieldsetDisabled = disabled;
    this.requestUpdate();
    this._syncFormValue();
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
