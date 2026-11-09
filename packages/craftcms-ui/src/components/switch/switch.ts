import type CraftSwitchButton from '../switch-button/switch-button.js';
import '../switch-button/switch-button.js';
import styles from './switch.styles.js';
import {baseFieldStyles} from '@src/styles/form.styles';
import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {t} from '@src/utilities/translate';
import {emitChange} from '@src/utilities/form-events';
import {HasLabel} from '@src/mixins/HasLabel';
import {ChoiceInput, choiceInputStyles} from '@src/mixins/ChoiceInput';

function splitIds(value: string | null): string[] {
  return (value ?? '').split(/\s+/).filter(Boolean);
}

function escapeCssId(id: string): string {
  return typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
    ? CSS.escape(id)
    : id;
}

/**
 * @summary A switch, for a setting that takes effect as soon as it is
 * toggled — the modern replacement for the legacy lightswitch.
 *
 * Use a switch when the change is immediate and needs no confirmation, and a
 * `craft-checkbox` when the value is part of a form that has to be submitted.
 * The control tells a person which they are dealing with.
 *
 * A hidden input carries the value, so a switch posts like any other field.
 *
 * @slot label - The switch's label, as an alternative to the `label`
 *   attribute.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages.
 *
 * @fires {Event} input - When the user toggles it.
 * @fires {Event} change - When the user toggles it, or it's turned on, off
 *   or indeterminate without muting.
 */
export default class CraftSwitch extends HasLabel(ChoiceInput(LitElement)) {
  static override styles = [...choiceInputStyles, baseFieldStyles, styles];

  /** Size of the control. */
  @property({type: String, reflect: true}) size: 'small' | 'medium' = 'medium';

  /**
   * Optional text rendered after the control that describes the "on" state,
   * matching the legacy lightswitch's `onLabel`. When an on/off label is
   * present, a visually hidden description ("Check for …. Uncheck for ….")
   * is linked to the switch via `aria-describedby`, and the visible labels
   * are hidden from assistive technology. Clicking a state label sets the
   * corresponding state, like the legacy lightswitch.
   */
  @property({type: String, attribute: 'on-label'}) onLabel = '';

  /**
   * Optional text rendered before the control that describes the "off"
   * state, matching the legacy lightswitch's `offLabel`.
   */
  @property({type: String, attribute: 'off-label'}) offLabel = '';

  /** The value posted by the hidden input when the switch is indeterminate. */
  @property({attribute: 'indeterminate-value'}) indeterminateValue = '-';

  /**
   * Mixed ("indeterminate") state, matching the legacy lightswitch. Only
   * meaningful while the switch is off; it is cleared as soon as the switch
   * is turned on.
   */
  @property({type: Boolean, reflect: true}) indeterminate = false;

  private __clickableExternalLabels = new Set<HTMLElement>();

  // While true, programmatic state changes don't dispatch a `change` event,
  // backing the `muteEvent` argument of turnOn()/turnOff()/turnIndeterminate().
  #suppressNativeChange = false;

  /** The value posted when the switch is on, like the legacy lightswitch's. */
  protected override get _defaultValue(): string {
    return '1';
  }

  override get _inputNode(): CraftSwitchButton | undefined {
    return super._inputNode as CraftSwitchButton | undefined;
  }

  /** The switch button, and the hidden input that posts. */
  override get slots() {
    return {
      ...super.slots,
      input: () => {
        const btnEl = document.createElement('craft-switch-button');
        btnEl.setAttribute('size', this.size);
        btnEl.setAttribute('data-tag-name', 'craft-switch-button');
        return btnEl;
      },
      'state-description': () => {
        const el = document.createElement('div');
        el.textContent = this._stateDescriptionText;
        return el;
      },
      // Posts the switch state like the legacy lightswitch's hidden input.
      // Server-rendered markup (lightswitch.twig) provides its own slotted
      // hidden input, in which case this one is skipped.
      'hidden-input': () => {
        if (!this.name) {
          // SlotMixin factories accept `undefined` for "no slot content" —
          // lit's `nothing` is a Symbol and breaks SlotMixin's slot init.
          return undefined;
        }
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = this.name;
        input.value = this._postedValue;
        input.disabled = this.disabled;
        return input;
      },
    };
  }

  override connectedCallback(): void {
    if (!this.name) {
      this.name = this._hiddenInputNode?.name ?? '';
    }
    super.connectedCallback();
    this.__setupExternalLabelClicks();
    this.addEventListener('keydown', this.__onKeydown);
    // Capturing, so the hidden input posts the new state by the time the
    // event bubbles out of the switch.
    this.addEventListener('input', this.#onToggle, true);
    this.addEventListener('change', this.#onToggle, true);
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.removeEventListener('keydown', this.__onKeydown);
    this.removeEventListener('input', this.#onToggle, true);
    this.removeEventListener('change', this.#onToggle, true);
    this.__teardownExternalLabelClicks();
  }

  /** The button isn't labelable, so its label toggles it here. */
  protected override _onLabelClick(): void {
    if (!this.disabled) {
      this._inputNode?.click();
    }
  }

  /**
   * The hidden input that posts the switch state, either server-rendered by
   * lightswitch.twig or created via the `hidden-input` slot.
   */
  protected get _hiddenInputNode(): HTMLInputElement | undefined {
    return (Array.from(this.children) as HTMLElement[]).find(
      (el): el is HTMLInputElement =>
        el instanceof HTMLInputElement && el.slot === 'hidden-input'
    );
  }

  /** The value the hidden input should post for the current state. */
  protected get _postedValue(): string {
    if (this.checked) {
      return this.value;
    }
    if (this.indeterminate) {
      return this.indeterminateValue;
    }
    return '';
  }

  /**
   * The visually hidden light DOM node holding the on/off state description.
   */
  protected get _stateDescriptionNode(): HTMLElement | undefined {
    return (Array.from(this.children) as HTMLElement[]).find(
      (el) => el.slot === 'state-description'
    );
  }

  protected get _stateDescriptionText(): string {
    return [
      this.onLabel ? t('Check for {onLabel}.', {onLabel: this.onLabel}) : '',
      this.offLabel
        ? t('Uncheck for {offLabel}.', {offLabel: this.offLabel})
        : '',
    ]
      .filter(Boolean)
      .join(' ');
  }

  override willUpdate(changedProperties: PropertyValues): void {
    super.willUpdate(changedProperties);
    if (
      changedProperties.has('checked') &&
      this.checked &&
      this.indeterminate
    ) {
      this.indeterminate = false;
    }
  }

  override updated(changedProperties: PropertyValues): void {
    super.updated(changedProperties);
    if (changedProperties.has('onLabel') || changedProperties.has('offLabel')) {
      this.__syncStateDescription();
    }
    if (
      (
        [
          'checked',
          'indeterminate',
          'value',
          'indeterminateValue',
          'disabled',
          'size',
        ] as const
      ).some((prop) => changedProperties.has(prop))
    ) {
      this.__syncSwitchButton();
      this.__syncHiddenInput();
    }
    if (
      changedProperties.has('checked') ||
      changedProperties.has('indeterminate')
    ) {
      this.__syncToggleTargets();
    }
  }

  /**
   * Shows/hides the `data-target`/`data-reverse-target` containers, like the
   * legacy lightswitch's Craft.FieldToggle behavior. In the control panel a
   * FieldToggle is bound to the switch button and owns the reveal, so this only
   * runs where none is (e.g. outside the control panel).
   */
  private __syncToggleTargets(): void {
    const btn = this._inputNode;
    if (!btn) {
      return;
    }
    const targetSelector = btn.getAttribute('data-target');
    const reverseTargetSelector = btn.getAttribute('data-reverse-target');
    if (!targetSelector && !reverseTargetSelector) {
      return;
    }
    const jq = (
      window as unknown as {
        jQuery?: (el: Element) => {data?: (key: string) => unknown};
      }
    ).jQuery;
    if (jq && jq(btn).data?.('fieldtoggle')) {
      // Legacy Craft.FieldToggle owns the reveal.
      return;
    }
    this.__toggleTargets(targetSelector, this.checked);
    this.__toggleTargets(reverseTargetSelector, !this.checked);
  }

  private __toggleTargets(selector: string | null, show: boolean): void {
    if (!selector) {
      return;
    }
    // Craft.FieldToggle treats selectors without a leading #/. as ids.
    const normalized = /^[#.]/.test(selector) ? selector : `#${selector}`;
    const rootNode = this.getRootNode() as Document | ShadowRoot;
    let changed = false;
    for (const target of rootNode.querySelectorAll(normalized)) {
      if (target.classList.contains('hidden') === show) {
        target.classList.toggle('hidden', !show);
        changed = true;
      }
    }
    if (changed && show) {
      // Give grids etc. inside the revealed container a chance to lay out,
      // like Craft.FieldToggle's resize trigger.
      window.dispatchEvent(new Event('resize'));
    }
  }

  /**
   * Sets the switch to an explicit state, clearing the indeterminate state.
   * Used by the clickable on/off state labels and the arrow keys, mirroring
   * the legacy lightswitch's `turnOn()`/`turnOff()`.
   */
  protected _setCheckedState(checked: boolean): void {
    if (this.disabled) {
      return;
    }
    const wasIndeterminate = this.indeterminate;
    if (this.checked === checked && !wasIndeterminate) {
      return;
    }
    this.indeterminate = false;
    // Leaving the indeterminate state counts as a change, like the legacy
    // lightswitch's turnOff().
    this.checked = checked;
    this.__dispatchNativeChange();
  }

  // ---------------------------------------------------------------------------
  // Imperative API mirroring the legacy Craft.LightSwitch, so code migrated off
  // `$el.data('lightswitch')` can drive the element directly, e.g.
  // `switchEl.turnOn(true)`. Pass `true` to suppress the change event.
  // ---------------------------------------------------------------------------

  /** Whether the switch is on. Mirrors the legacy lightswitch's `on`. */
  get on(): boolean {
    return this.checked;
  }

  /** The value the switch's hidden input posts for its current state. */
  get postedValue(): string {
    return this._postedValue;
  }

  /**
   * Turns the switch on. Pass `true` to suppress the `change` event, mirroring
   * the legacy `Craft.LightSwitch` API that code migrating off
   * `$el.data('lightswitch')` still calls.
   */
  turnOn(muteEvent = false): void {
    this.#applyState(() => this._setCheckedState(true), muteEvent);
  }

  /**
   * Turns the switch off. Pass `true` to suppress the `change` event.
   */
  turnOff(muteEvent = false): void {
    this.#applyState(() => this._setCheckedState(false), muteEvent);
  }

  /**
   * Puts the switch in the indeterminate state — neither on nor off. Pass
   * `true` to suppress the `change` event.
   */
  turnIndeterminate(muteEvent = false): void {
    this.#applyState(() => {
      if (this.indeterminate && !this.checked) {
        return;
      }
      this.checked = false;
      this.indeterminate = true;
      this.__dispatchNativeChange();
    }, muteEvent);
  }

  #applyState(mutate: () => void, muteEvent: boolean): void {
    if (this.disabled) {
      return;
    }
    if (!muteEvent) {
      mutate();
      return;
    }
    this.#suppressNativeChange = true;
    mutate();
    this.#suppressNativeChange = false;
  }

  private __onKeydown = (event: KeyboardEvent): void => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
      return;
    }
    if (this.disabled || event.target !== this._inputNode) {
      return;
    }
    event.preventDefault();
    const rtl = getComputedStyle(this).direction === 'rtl';
    this._setCheckedState(event.key === (rtl ? 'ArrowLeft' : 'ArrowRight'));
  };

  private __dispatchNativeChange(): void {
    if (this.#suppressNativeChange) {
      return;
    }
    // Legacy CP code (e.g. Craft.FieldToggle, which powers toggle/
    // reverseToggle reveals) listens for native change events on the switch
    // button.
    if (this._inputNode) {
      emitChange(this._inputNode);
    }
  }

  #onToggle = (event: Event): void => {
    if (event.target === this._inputNode) {
      this.__syncHiddenInput();
    }
  };

  private __setupExternalLabelClicks(): void {
    const btn = this._inputNode;
    if (!btn) {
      return;
    }
    const rootNode = this.getRootNode() as Document | ShadowRoot;
    const labels = new Set<HTMLElement>(
      splitIds(btn.getAttribute('aria-labelledby'))
        .map((id) => rootNode.getElementById?.(id))
        .filter((el): el is HTMLLabelElement => el instanceof HTMLLabelElement)
        .filter((el) => el !== this._labelNode)
    );
    if (btn.id) {
      for (const el of rootNode.querySelectorAll(
        `label[for="${escapeCssId(btn.id)}"]`
      )) {
        labels.add(el as HTMLElement);
      }
    }
    for (const el of labels) {
      if (!this.__clickableExternalLabels.has(el)) {
        el.addEventListener('click', this.__onExternalLabelClick);
        this.__clickableExternalLabels.add(el);
      }
    }
  }

  private __teardownExternalLabelClicks(): void {
    for (const el of this.__clickableExternalLabels) {
      el.removeEventListener('click', this.__onExternalLabelClick);
    }
    this.__clickableExternalLabels.clear();
  }

  private __onExternalLabelClick = (event: Event): void => {
    if (this.disabled) {
      return;
    }
    // Don't double-toggle if the label contains the switch itself.
    if (event.composedPath().includes(this)) {
      return;
    }
    this._inputNode?.click();
  };

  private __syncSwitchButton(): void {
    const btn = this._inputNode as CraftSwitchButton | undefined;
    if (!btn) {
      return;
    }
    if (btn.getAttribute('size') !== this.size) {
      btn.setAttribute('size', this.size);
    }
    const indeterminate = this.indeterminate && !this.checked;
    if (btn.indeterminate !== indeterminate) {
      btn.indeterminate = indeterminate;
    }
  }

  private __syncHiddenInput(): void {
    const input = this._hiddenInputNode;
    if (!input) {
      return;
    }
    input.value = this._postedValue;
    input.disabled = this.disabled;
    if (this.name && input.name !== this.name) {
      input.name = this.name;
    }
  }

  private __syncStateDescription(): void {
    const node = this._stateDescriptionNode;
    if (!node) {
      return;
    }
    const text = this._stateDescriptionText;
    node.textContent = text;
    if (text) {
      this.addToAriaDescribedBy(node, {idPrefix: 'state-description'});
    } else {
      this.removeFromAriaDescribedBy(node);
    }
  }

  override render() {
    return html`
      <div class="form-field__group-one">${this._groupOneTemplate()}</div>
      <div class="form-field__group-two form-field__group-two--switch">
        ${this._groupTwoTemplate()}
      </div>
    `;
  }

  protected override _groupOneTemplate() {
    return html`${super._groupOneTemplate()} ${this._feedbackTemplate()}
    ${this._stateDescriptionTemplate()}`;
  }

  protected override _groupTwoTemplate() {
    return this._inputGroupTemplate();
  }

  protected _stateDescriptionTemplate() {
    return html`<slot name="state-description"></slot>`;
  }

  /**
   * The visible on/off labels are presentational; screen readers get the
   * state description instead (same approach as the legacy lightswitch).
   * Clicking a label sets the corresponding state, like the legacy
   * lightswitch.
   */
  protected _stateLabelTemplate(state: 'on' | 'off') {
    const label = state === 'on' ? this.onLabel : this.offLabel;
    if (!label) {
      return nothing;
    }
    return html`<span
      class="state-label"
      data-state=${state}
      aria-hidden="true"
      @click=${() => this._setCheckedState(state === 'on')}
      >${label}</span
    >`;
  }

  protected override _inputGroupBeforeTemplate() {
    return html`
      <div class="input-group__before">
        <slot name="before"></slot>
        ${this._stateLabelTemplate('off')}
      </div>
    `;
  }

  protected override _inputGroupAfterTemplate() {
    return html`
      <div class="input-group__after">
        ${this._stateLabelTemplate('on')}
        <slot name="after"></slot>
      </div>
    `;
  }
}

if (!customElements.get('craft-switch')) {
  customElements.define('craft-switch', CraftSwitch);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-switch': CraftSwitch;
  }
}
