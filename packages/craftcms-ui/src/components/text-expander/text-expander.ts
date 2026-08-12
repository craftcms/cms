import Combobox from '@github/combobox-nav';
import {OverlayController} from '@lion/ui/overlays.js';
import {InputRange} from 'dom-input-range';
import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {property, query, state} from 'lit/decorators.js';
import {actionClient} from '@src/utilities/api/actionClient';
import {t} from '@src/utilities/translate';
import styles from './text-expander.styles.js';
import '../option/option.js';

export interface TextExpanderOption<Data = unknown> {
  label: string;
  value: string;
  keywords?: readonly string[];
  data?: Data;
}

interface TextExpanderTriggerBase<Data = unknown> {
  limit?: number;
  renderOption?: (option: Readonly<TextExpanderOption<Data>>) => Node;
}

export type TextExpanderTrigger<Data = unknown> =
  TextExpanderTriggerBase<Data> &
    (
      | {
          options: readonly TextExpanderOption<Data>[];
          source?: never;
        }
      | {
          source: string;
          options?: never;
        }
    );

export type TextExpanderTriggers = Readonly<
  Record<string, TextExpanderTrigger>
>;

export interface TextExpanderSelectDetail {
  character: string;
  query: string;
  option: Readonly<TextExpanderOption>;
}

export interface TextExpanderErrorDetail {
  character: string;
  query: string;
  error: unknown;
}

type TextExpanderTarget = HTMLInputElement | HTMLTextAreaElement;

interface TextExpanderMatch {
  character: string;
  query: string;
  start: number;
  end: number;
  trigger: TextExpanderTrigger;
}

// Queries may contain Unicode letters or numbers, underscores, and hyphens;
// any other character terminates the active query.
const queryPattern = /^[\p{L}\p{N}_-]*$/u;
const targetAttributes = [
  'role',
  'aria-autocomplete',
  'aria-haspopup',
  'aria-expanded',
  'aria-controls',
  'aria-activedescendant',
  'aria-busy',
] as const;

let nextId = 0;

/**
 * Adds trigger-driven suggestions to an existing text input or textarea.
 *
 * @slot loading - Content shown while an asynchronous source is loading.
 * @csspart popup - The floating suggestion popup.
 * @csspart listbox - The suggestion listbox.
 * @csspart option - A generated suggestion option.
 * @csspart loading - The loading row.
 * @fires craft-text-expander-select - Fired after a suggestion is inserted.
 * @fires craft-text-expander-error - Fired when a suggestion source fails.
 */
export default class CraftTextExpander extends LitElement {
  static override styles = [styles];

  /** ID of a native text input or textarea in the same DOM root. */
  @property({reflect: true}) for = '';

  /** Trigger-specific static options or asynchronous sources. */
  @property({type: Object}) triggers: TextExpanderTriggers = {};

  @state() private loading = false;
  @state() private announcement = '';

  @query('.text-expander__popup')
  private popup!: HTMLElement;

  @query('#overlay-content-node-wrapper')
  private popupWrapper!: HTMLElement;

  #listbox!: HTMLDivElement;
  #boundTarget: TextExpanderTarget | null = null;
  #combobox: Combobox | null = null;
  #originalAttributes = new Map<string, string | null>();
  #overlay: OverlayController | null = null;
  #match: TextExpanderMatch | null = null;
  #visibleOptions: readonly TextExpanderOption[] = [];
  #request = 0;
  #requestController: AbortController | null = null;
  #debounceTimer: ReturnType<typeof setTimeout> | null = null;
  #composing = false;
  #inputRange: InputRange | null = null;
  #caretRect = new DOMRect();

  override connectedCallback(): void {
    super.connectedCallback();
    this.#ensureListbox();
  }

  override disconnectedCallback(): void {
    this.#unbindTarget();
    this.#listbox?.remove();
    super.disconnectedCallback();
  }

  protected override firstUpdated(changedProperties: PropertyValues): void {
    super.firstUpdated(changedProperties);
    this.#bindTarget();
  }

  protected override updated(changedProperties: PropertyValues): void {
    super.updated(changedProperties);

    if (
      changedProperties.has('for') &&
      this.#boundTarget !== this.#resolveTarget()
    ) {
      this.#bindTarget();
    }

    if (
      changedProperties.has('triggers') &&
      changedProperties.get('triggers')
    ) {
      this.#close();
    }
  }

  protected override render() {
    return html`
      <div id="overlay-content-node-wrapper">
        <div class="text-expander__popup" part="popup">
          ${this.loading
            ? html`<div class="text-expander__loading" part="loading">
                <slot name="loading">${t('Loading')}</slot>
              </div>`
            : nothing}
          <slot name="listbox"></slot>
        </div>
      </div>
      <div class="cp-visually-hidden" aria-live="polite" aria-atomic="true">
        ${this.announcement}
      </div>
    `;
  }

  #ensureListbox(): void {
    this.#listbox = document.createElement('div');
    this.#listbox.id = `craft-text-expander-listbox-${++nextId}`;
    this.#listbox.slot = 'listbox';
    this.#listbox.setAttribute('part', 'listbox');
    this.#listbox.role = 'listbox';
    this.#listbox.setAttribute('aria-label', t('Suggestions'));
    this.#listbox.addEventListener('pointerdown', this.#onOptionPointerDown);
    this.#listbox.addEventListener('pointerup', this.#onOptionPointerUp);
    this.#listbox.addEventListener('combobox-commit', this.#onComboboxCommit);
    this.append(this.#listbox);
  }

  #resolveTarget(): TextExpanderTarget | null {
    const root = this.getRootNode() as Document | ShadowRoot;
    return root.getElementById(this.for) as TextExpanderTarget | null;
  }

  #bindTarget(): void {
    const target = this.#resolveTarget();
    this.#unbindTarget();

    if (!isTextTarget(target)) {
      throw new TypeError(
        'craft-text-expander requires an input[type="text"] or textarea target.'
      );
    }

    this.#boundTarget = target;
    this.#storeTargetAttributes(target);
    this.#combobox = new Combobox(target, this.#listbox, {
      tabInsertsSuggestions: false,
      firstOptionSelectionMode: 'selected',
      scrollIntoViewOptions: {block: 'nearest'},
    });
    this.#applyTargetAttributes(target);
    target.addEventListener('input', this.#onInput);
    target.addEventListener('keydown', this.#onKeyDown);
    target.addEventListener('blur', this.#onBlur);
    target.addEventListener('compositionstart', this.#onCompositionStart);
    target.addEventListener('compositionend', this.#onCompositionEnd);
    target.ownerDocument.addEventListener(
      'selectionchange',
      this.#onSelectionChange
    );
    target.ownerDocument.addEventListener(
      'pointerdown',
      this.#onDocumentPointerDown,
      true
    );
    this.#overlay = new OverlayController({
      contentNode: this.popup,
      contentWrapperNode: this.popupWrapper,
      invokerNode: target,
      referenceNode: {
        contextElement: target,
        getBoundingClientRect: () => this.#caretRect,
      } as unknown as HTMLElement,
      placementMode: 'local',
      hidesOnOutsideClick: false,
      hidesOnEsc: false,
      handlesAccessibility: false,
      popperConfig: {
        strategy: 'fixed',
        placement: 'bottom-start',
        modifiers: [
          {name: 'offset', options: {offset: [0, 4]}},
          {name: 'computeStyles', options: {gpuAcceleration: false}},
        ],
      },
    });
  }

  #unbindTarget(): void {
    this.#close();

    const target = this.#boundTarget;
    if (!target) {
      return;
    }

    target.removeEventListener('input', this.#onInput);
    target.removeEventListener('keydown', this.#onKeyDown);
    target.removeEventListener('blur', this.#onBlur);
    target.removeEventListener('compositionstart', this.#onCompositionStart);
    target.removeEventListener('compositionend', this.#onCompositionEnd);
    target.ownerDocument.removeEventListener(
      'selectionchange',
      this.#onSelectionChange
    );
    target.ownerDocument.removeEventListener(
      'pointerdown',
      this.#onDocumentPointerDown,
      true
    );
    this.#inputRange?.getStyleClone().disconnect();
    this.#inputRange = null;
    this.#overlay?.teardown();
    this.#overlay = null;
    this.#combobox?.destroy();
    this.#combobox = null;
    this.#restoreTargetAttributes(target);
    this.#boundTarget = null;
  }

  #storeTargetAttributes(target: TextExpanderTarget): void {
    this.#originalAttributes.clear();
    for (const attribute of targetAttributes) {
      this.#originalAttributes.set(attribute, target.getAttribute(attribute));
    }
  }

  #applyTargetAttributes(target: TextExpanderTarget): void {
    const controls = [
      this.#originalAttributes.get('aria-controls'),
      this.#listbox.id,
    ]
      .filter(Boolean)
      .join(' ');

    target.setAttribute('aria-autocomplete', 'list');
    target.setAttribute('aria-controls', controls);

    if (target instanceof HTMLInputElement) {
      target.setAttribute('role', 'combobox');
      target.setAttribute('aria-haspopup', 'listbox');
      target.setAttribute('aria-expanded', 'false');
    } else {
      this.#restoreTargetAttribute(target, 'role');
      this.#restoreTargetAttribute(target, 'aria-haspopup');
      this.#restoreTargetAttribute(target, 'aria-expanded');
    }
  }

  #restoreTargetAttributes(target: TextExpanderTarget): void {
    for (const attribute of targetAttributes) {
      this.#restoreTargetAttribute(target, attribute);
    }
    this.#originalAttributes.clear();
  }

  #restoreTargetAttribute(
    target: TextExpanderTarget,
    attribute: (typeof targetAttributes)[number]
  ): void {
    const value = this.#originalAttributes.get(attribute);
    if (value === null || value === undefined) {
      target.removeAttribute(attribute);
    } else {
      target.setAttribute(attribute, value);
    }
  }

  #onInput = (): void => {
    if (!this.#composing) {
      this.#evaluate();
    }
  };

  #onCompositionStart = (): void => {
    this.#composing = true;
    this.#close();
  };

  #onCompositionEnd = (): void => {
    this.#composing = false;
    this.#evaluate();
  };

  #onBlur = (): void => {
    queueMicrotask(() => this.#close());
  };

  #onSelectionChange = (): void => {
    const target = this.#boundTarget;
    if (!target || !this.#match || !isFocused(target)) {
      return;
    }

    queueMicrotask(() => {
      if (this.#match) {
        this.#evaluate();
      }
    });
  };

  #onInputRangeUpdate = (): void => {
    if (!this.#match) {
      return;
    }

    this.#positionPopup();
  };

  #onDocumentPointerDown = (event: Event): void => {
    const path = event.composedPath();
    if (
      this.#match &&
      !path.includes(this.#boundTarget as EventTarget) &&
      !path.includes(this) &&
      !path.includes(this.#listbox)
    ) {
      this.#close();
    }
  };

  #onKeyDown: EventListener = (rawEvent): void => {
    const event = rawEvent as KeyboardEvent;
    if (!this.#match) {
      return;
    }

    if (event.key === 'Escape') {
      event.preventDefault();
      event.stopImmediatePropagation();
      this.#close();
      return;
    }

    if (event.key === 'Tab') {
      this.#close();
      return;
    }

    const selected = this.#listbox.querySelector('[aria-selected="true"]');
    if (selected && ['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) {
      event.stopPropagation();
    }
  };

  #onOptionPointerDown = (event: Event): void => {
    if (
      event.target instanceof Element &&
      event.target.closest('[role="option"]')
    ) {
      event.preventDefault();
    }
  };

  #onOptionPointerUp = (event: PointerEvent): void => {
    if (event.pointerType === 'touch' && event.target instanceof Element) {
      (event.target.closest('[role="option"]') as HTMLElement | null)?.click();
    }
  };

  #onComboboxCommit = (event: Event): void => {
    this.#select(Number((event.target as HTMLElement).dataset.index));
  };

  #evaluate(): void {
    this.#cancelPending();
    const match = this.#findMatch();

    if (!match) {
      this.#match = null;
      this.#closePopup();
      return;
    }

    this.#match = match;
    const limit = match.trigger.limit ?? 8;

    if (match.trigger.options) {
      const query = match.query.toLowerCase();
      const matches = match.trigger.options
        .filter((option) =>
          [option.label, ...(option.keywords ?? [])].some((term) =>
            term.toLowerCase().includes(query)
          )
        )
        .slice(0, limit);
      this.#showOptions(matches);
      return;
    }

    this.loading = true;
    this.#showOptions([]);
    this.#announce(t('Loading'));
    const request = this.#request;
    this.#debounceTimer = setTimeout(() => {
      void this.#loadOptions(match, limit, request);
    }, 150);
  }

  async #loadOptions(
    match: TextExpanderMatch,
    limit: number,
    request: number
  ): Promise<void> {
    this.#requestController = new AbortController();
    let options: readonly TextExpanderOption[];

    try {
      const response = await actionClient.get<readonly TextExpanderOption[]>(
        match.trigger.source!,
        {
          params: {query: `${match.character}${match.query}`, limit},
          signal: this.#requestController.signal,
        }
      );
      options = response.data;
    } catch (error) {
      if (request !== this.#request || isAbortError(error)) {
        return;
      }

      this.#close();
      this.dispatchEvent(
        new CustomEvent<TextExpanderErrorDetail>('craft-text-expander-error', {
          bubbles: true,
          composed: true,
          detail: {character: match.character, query: match.query, error},
        })
      );
      return;
    }

    if (request !== this.#request) {
      return;
    }

    this.loading = false;
    this.#showOptions(options.slice(0, limit));
  }

  #findMatch(): TextExpanderMatch | null {
    const target = this.#boundTarget;
    if (
      !target ||
      !target.isConnected ||
      target.disabled ||
      target.readOnly ||
      !isFocused(target) ||
      target.selectionStart === null ||
      target.selectionEnd === null ||
      target.selectionStart !== target.selectionEnd
    ) {
      return null;
    }

    const end = target.selectionStart;
    const prefix = target.value.slice(0, end);
    let result: TextExpanderMatch | null = null;

    for (const [character, trigger] of Object.entries(this.triggers)) {
      const start = prefix.lastIndexOf(character);
      if (start === -1 || (result && start <= result.start)) {
        continue;
      }

      const before = Array.from(prefix.slice(0, start)).at(-1);
      const query = prefix.slice(start + character.length);
      if ((!before || /\s/u.test(before)) && queryPattern.test(query)) {
        result = {character, query, start, end, trigger};
      }
    }

    return result;
  }

  #showOptions(options: readonly TextExpanderOption[]): void {
    this.#stopCombobox();
    this.#visibleOptions = options;
    this.#listbox.replaceChildren();
    this.#listbox.hidden = this.loading;

    options.forEach((option, index) => {
      const element = document.createElement('craft-option');
      element.id = `${this.#listbox.id}-option-${index}`;
      element.setAttribute('part', 'option');
      element.dataset.index = String(index);
      element.setAttribute('aria-label', option.label);
      element.append(
        this.#match?.trigger.renderOption?.(option) ??
          document.createTextNode(option.label)
      );
      this.#listbox.append(element);
    });

    if (!this.loading && !options.length) {
      this.#match = null;
      this.#closePopup();
      this.#announce(t('No suggestions'));
      return;
    }

    void this.updateComplete.then(() => {
      if (this.#match) {
        this.#openPopup();
      }
    });
  }

  #select(index: number): void {
    const target = this.#boundTarget;
    const option = this.#visibleOptions[index];
    const currentMatch = this.#findMatch();
    const match = this.#match;

    if (
      !target ||
      !option ||
      !match ||
      !currentMatch ||
      currentMatch.start !== match.start ||
      currentMatch.end !== match.end ||
      currentMatch.character !== match.character ||
      currentMatch.query !== match.query
    ) {
      this.#close();
      return;
    }

    insertReplacement(target, match.start, match.end, option.value);
    const detail: TextExpanderSelectDetail = {
      character: match.character,
      query: match.query,
      option,
    };
    this.#close();
    this.dispatchEvent(
      new CustomEvent<TextExpanderSelectDetail>('craft-text-expander-select', {
        bubbles: true,
        composed: true,
        detail,
      })
    );
  }

  #openPopup(): void {
    const target = this.#boundTarget;
    if (!target || !this.#positionPopup()) {
      this.#close();
      return;
    }

    target.setAttribute('aria-busy', String(this.loading));
    if (target instanceof HTMLInputElement) {
      target.setAttribute('aria-expanded', 'true');
    }
    void this.#overlay?.show(target);
    if (this.#visibleOptions.length) {
      this.#combobox?.start();

      // Let the combobox clear its previous selection before options are rebuilt.
      target.removeEventListener('input', this.#onInput);
      target.addEventListener('input', this.#onInput);

      if (target instanceof HTMLTextAreaElement) {
        this.#restoreTargetAttribute(target, 'aria-expanded');
      }
    }
  }

  #stopCombobox(): void {
    this.#combobox?.stop();

    if (this.#boundTarget instanceof HTMLTextAreaElement) {
      this.#restoreTargetAttribute(this.#boundTarget, 'aria-expanded');
    }
  }

  #positionPopup(): boolean {
    const target = this.#boundTarget;
    const position = target?.selectionStart;
    if (!target?.isConnected || position === null || position === undefined) {
      return false;
    }

    if (!this.#inputRange) {
      this.#inputRange = new InputRange(target);
      const styleClone = this.#inputRange.getStyleClone();
      // Keep the caret clone in the same web component slot as its input.
      styleClone.element.parentElement!.slot = target.slot;
      styleClone.forceUpdate();
      styleClone.addEventListener('update', this.#onInputRangeUpdate);
    }
    this.#inputRange.setStartOffset(position);
    this.#inputRange.setEndOffset(position);
    this.#caretRect = this.#inputRange.getBoundingClientRect();
    this.#overlay?._popper?.update();
    return true;
  }

  #close(): void {
    this.#cancelPending();
    this.#match = null;
    this.#closePopup();
  }

  #closePopup(): void {
    this.loading = false;
    this.#stopCombobox();
    this.#visibleOptions = [];
    this.#listbox?.replaceChildren();
    if (this.#listbox) {
      this.#listbox.hidden = false;
    }
    void this.#overlay?.hide();

    const target = this.#boundTarget;
    if (target) {
      this.#restoreTargetAttribute(target, 'aria-activedescendant');
      this.#restoreTargetAttribute(target, 'aria-busy');
      if (target instanceof HTMLInputElement) {
        target.setAttribute('aria-expanded', 'false');
      }
    }
  }

  #cancelPending(): void {
    this.#request++;
    if (this.#debounceTimer) {
      clearTimeout(this.#debounceTimer);
      this.#debounceTimer = null;
    }
    this.#requestController?.abort();
    this.#requestController = null;
  }

  #announce(message: string): void {
    this.announcement = '';
    queueMicrotask(() => {
      if (this.isConnected) {
        this.announcement = message;
      }
    });
  }
}

function isTextTarget(value: unknown): value is TextExpanderTarget {
  return (
    value instanceof HTMLTextAreaElement ||
    (value instanceof HTMLInputElement && value.type === 'text')
  );
}

function isFocused(target: TextExpanderTarget): boolean {
  return (
    (target.getRootNode() as Document | ShadowRoot).activeElement === target
  );
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError';
}

function insertReplacement(
  target: TextExpanderTarget,
  start: number,
  end: number,
  value: string
): void {
  const expected = `${target.value.slice(0, start)}${value}${target.value.slice(end)}`;
  let emitted = false;
  const markEmitted = () => {
    emitted = true;
  };

  target.focus({preventScroll: true});
  target.setSelectionRange(start, end);
  target.addEventListener('input', markEmitted, {once: true});
  try {
    target.ownerDocument.execCommand?.('insertText', false, value);
  } catch {
    // setRangeText() below is the non-undo-preserving fallback.
  }
  target.removeEventListener('input', markEmitted);

  if (target.value !== expected) {
    target.setRangeText(value, start, end, 'end');
  }

  if (!emitted) {
    target.dispatchEvent(
      new InputEvent('input', {
        bubbles: true,
        composed: true,
        data: value,
        inputType: 'insertReplacementText',
      })
    );
  }
}

if (!customElements.get('craft-text-expander')) {
  customElements.define('craft-text-expander', CraftTextExpander);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-text-expander': CraftTextExpander;
  }

  interface HTMLElementEventMap {
    'craft-text-expander-select': CustomEvent<TextExpanderSelectDetail>;
    'craft-text-expander-error': CustomEvent<TextExpanderErrorDetail>;
  }
}
