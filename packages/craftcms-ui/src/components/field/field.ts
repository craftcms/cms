import {LitElement, html, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import {FormControlMixin} from '@lion/ui/form-core.js';
// Registers <craft-callout> globally for the tip/warning notices.
import '../callout/callout.js';
import {baseFieldStyles} from '@src/styles/form.styles';
import visuallyHiddenStyles from '@src/styles/visually-hidden.styles.js';
import styles from './field.styles.js';
import {t} from '@src/utilities/translate';

type FormControlTarget = HTMLElement & {
  addToAriaLabelledBy(
    element: HTMLElement,
    config?: {idPrefix?: string; reorder?: boolean}
  ): void;
  addToAriaDescribedBy(
    element: HTMLElement,
    config?: {idPrefix?: string; reorder?: boolean}
  ): void;
};

/**
 * @summary A generic form-field shell that renders the standard CP field chrome
 * (label, instructions, tip/warning notices, errors and status badge) around
 * any slotted control, mirroring the server-side field wrapper
 * (`FormFields::fieldHtml()` / `forms/field.blade.php`).
 *
 * The shell never touches the slotted input's value, validation or events —
 * it only provides the surrounding chrome and the label/description aria
 * wiring supplied by Lion's `FormControlMixin`.
 *
 * @slot input - The wrapped control. Required.
 * @slot label - The field label. Alternative to the `label` attribute.
 * @slot help-text - The field instructions. Alternative to the `help-text`
 *   attribute.
 * @slot feedback - Validation errors (e.g. an error list).
 * @slot tip - Tip notice content, rendered inside an info callout.
 * @slot warning - Warning notice content, rendered inside a warning callout.
 * @slot label-extra - Extra heading content, rendered after a flex-grow spacer.
 *   @deprecated Use `actions` instead.
 * @slot actions - Field-level actions (hide-label toggles, copy-value buttons,
 *   field settings menus), rendered as a group at the end of the heading.
 */
export default class CraftField extends FormControlMixin(LitElement) {
  static override get styles() {
    return [visuallyHiddenStyles, baseFieldStyles, styles];
  }

  /** Renders the required indicator inside the label. */
  @property({type: Boolean, reflect: true}) required = false;

  /** Renders the translation icon inside the label. */
  @property({type: Boolean, reflect: true}) translatable = false;

  /** Accessible description for the translation icon. */
  @property({attribute: 'translation-description'})
  translationDescription: string = t('This field is translatable.');

  /**
   * Group semantics: sets `role="group"` + `aria-labelledby` on the host
   * instead of a `label[for]` association, like the server-side `fieldset`
   * config option.
   */
  @property({type: Boolean, reflect: true}) fieldset = false;

  /** Status badge modifier (e.g. `modified`, `outdated`). */
  @property({reflect: true}) status?: string;

  /** Human-facing label for the status badge. */
  @property({attribute: 'status-label'}) statusLabel?: string;

  /**
   * Writing direction of the input container. Defaults to the closest `dir`
   * attribute, falling back to the document direction.
   */
  @property({reflect: true}) orientation?: 'ltr' | 'rtl';

  /**
   * Style hook mirroring the server-side `.field.has-errors` class. Kept in
   * sync automatically with the presence of `feedback` slot content; can
   * also be set manually when no feedback node is slotted.
   */
  @property({type: Boolean, reflect: true, attribute: 'has-errors'})
  hasErrors = false;

  /** Where the instructions render relative to the input. */
  @property({attribute: 'instructions-position'})
  instructionsPosition: 'before' | 'after' = 'before';

  /**
   * Overrides the inferred width behavior. By default, the field spans its
   * column, unless the slotted control declares a `maxlength` (reflected
   * onto the host as `has-maxlength`) — which shrinks the field to the
   * control's width. `full` spans the column despite a `maxlength`; `auto`
   * shrinks without one.
   */
  @property({type: String, reflect: true}) width?: 'full' | 'auto';

  private __lightDomObserver = new MutationObserver(() =>
    this.__onLightDomChanged()
  );

  override connectedCallback(): void {
    super.connectedCallback();
    this.__lightDomObserver.observe(this, {childList: true});
    this.__syncHasErrors();
    this.__syncHasMaxlength();
    this.__syncControlWidth();
  }

  /**
   * Reflects whether the slotted control declares a `maxlength` onto the
   * host, where the stylesheet can react to it — a shadow stylesheet can't
   * observe light-DOM children via `:host(:has(…))`.
   */
  private __syncHasMaxlength(): void {
    this.toggleAttribute(
      'has-maxlength',
      this.querySelector(':scope > [slot="input"]')?.hasAttribute(
        'maxlength'
      ) ?? false
    );
  }

  /**
   * Mirrors the slotted control's `width` override onto the host when the
   * field doesn't declare one of its own, so the `has-maxlength` shrink
   * styles honor the control's opt-out (`width="full"` on a maxlength'd
   * control must keep the whole field spanning its column).
   */
  private __syncControlWidth(): void {
    if (this.width !== undefined) {
      return;
    }

    const width = this.querySelector(':scope > [slot="input"]')?.getAttribute(
      'width'
    );

    if (width === 'full' || width === 'auto') {
      this.width = width;
    }
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.__lightDomObserver.disconnect();
  }

  override updated(changedProperties: PropertyValues): void {
    super.updated(changedProperties);

    if (changedProperties.has('disabled')) {
      // FormControlMixin reflects `aria-disabled` onto the slotted input, but
      // the shell doesn't own the input (its disabled state is the input's
      // own business), so undo that.
      this._inputNode?.removeAttribute('aria-disabled');
    }

    if (changedProperties.has('fieldset')) {
      this.__syncFieldsetSemantics();
    }

    if (
      changedProperties.has('label') ||
      changedProperties.has('required') ||
      changedProperties.has('translatable') ||
      changedProperties.has('translationDescription')
    ) {
      // FormControlMixin rewrites the label node's textContent when the label
      // property changes, wiping any decorations, so re-sync afterwards.
      this.__syncLabelDecorations();
    }
  }

  /**
   * The wrapped control. Exposed for consumers; the shell never mutates its
   * value or listens to its events.
   */
  get control(): HTMLElement | undefined {
    return this._inputNode ?? undefined;
  }

  protected get _resolvedOrientation(): 'ltr' | 'rtl' {
    if (this.orientation) {
      return this.orientation;
    }
    const dir =
      (this.closest('[dir]') as HTMLElement | null)?.getAttribute('dir') ??
      document.documentElement.getAttribute('dir');
    return dir?.toLowerCase() === 'rtl' ? 'rtl' : 'ltr';
  }

  /**
   * Links tip/warning notices (and any late-registered instructions or
   * feedback nodes) into the input's `aria-describedby`, mirroring the
   * server-side `describedBy` computation.
   */
  protected override _enhanceLightDomA11y(): void {
    super._enhanceLightDomA11y();
    this.__wireDescribedBy();
    this.__syncFieldsetSemantics();
  }

  protected _onLabelClick(): void {
    this.__formControlTarget()?.focus();
  }

  /**
   * Registers aria references in insertion order instead of Lion's
   * DOM-order reordering. The reorder implementation depends on
   * `Element.assignedSlot`, which happy-dom doesn't populate, and insertion
   * order already matches the template order here (label; help-text,
   * feedback, tip, warning).
   */
  override addToAriaLabelledBy(
    element: HTMLElement,
    customConfig: {idPrefix?: string; reorder?: boolean} = {}
  ): void {
    const control = this.__formControlTarget();
    if (control) {
      control.addToAriaLabelledBy(element, {
        ...customConfig,
        idPrefix: `field-${customConfig.idPrefix ?? 'label'}`,
      });
      return;
    }

    if (this.fieldset || !this.__hasNativeLabelTarget()) {
      this.__addGroupAriaReference('aria-labelledby', element, 'label');
      return;
    }

    super.addToAriaLabelledBy(element, {...customConfig, reorder: false});
  }

  /**
   * Adds an element to the field's `aria-describedby`, so a control outside
   * the field can still be described by it.
   */
  override addToAriaDescribedBy(
    element: HTMLElement,
    customConfig: {idPrefix?: string; reorder?: boolean} = {}
  ): void {
    const control = this.__formControlTarget();
    if (control) {
      control.addToAriaDescribedBy(element, {
        ...customConfig,
        idPrefix: `field-${customConfig.idPrefix ?? 'description'}`,
      });
      return;
    }

    if (this.fieldset || !this.__hasNativeLabelTarget()) {
      this.__addGroupAriaReference(
        'aria-describedby',
        element,
        customConfig.idPrefix ?? 'description'
      );
      return;
    }

    super.addToAriaDescribedBy(element, {...customConfig, reorder: false});
  }

  // `readOnly` (attribute: `readonly`) is inherited from FormControlMixin
  // and renders the read-only badge in the heading.

  override render() {
    return html`
      <div
        class="${classMap({
          'form-field': true,
          [`form-field--${this.status}`]: !!this.status,
        })}"
      >
        ${this._statusBadgeTemplate()}
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      </div>
    `;
  }

  protected override _groupOneTemplate() {
    return html`
      ${this._labelTemplate()}
      ${this.instructionsPosition === 'before'
        ? this._helpTextTemplate()
        : nothing}
    `;
  }

  protected override _groupTwoTemplate() {
    return html`
      ${this._inputGroupTemplate()}
      ${this.instructionsPosition === 'after'
        ? this._helpTextTemplate()
        : nothing}
      ${this._noticeTemplate('tip')} ${this._noticeTemplate('warning')}
      ${this._feedbackTemplate()}
    `;
  }

  /**
   * The field heading: label, read-only badge, flex-grow spacer, label extras
   * and actions, mirroring `.field > .heading` in the Blade wrapper.
   */
  protected override _labelTemplate() {
    const hasActions = this.__hasLightChild('actions');

    return html`
      <div class="form-field__label">
        <slot name="heading-prefix"></slot>
        <slot name="label"></slot>
        ${this.readOnly
          ? html`<span class="read-only-badge">${t('Read Only')}</span>`
          : nothing}
        ${this.__hasLightChild('label-extra') || hasActions
          ? html`<div class="flex-grow"></div>`
          : nothing}
        <slot name="label-extra"></slot>
        ${hasActions
          ? html`
              <div
                class="field-actions"
                part="actions"
                role="group"
                aria-label=${t('Field actions')}
              >
                <slot name="actions"></slot>
              </div>
            `
          : html`<slot name="actions"></slot>`}
        <slot name="heading-suffix"></slot>
      </div>
    `;
  }

  /**
   * The input container, mirroring the server-side
   * `.input.{orientation}.errors.disabled` classes while keeping Lion's
   * `input-group` structure.
   */
  protected override _inputGroupTemplate() {
    const classes = {
      'input-group': true,
      input: true,
      [this._resolvedOrientation]: true,
      errors: this.hasErrors,
      disabled: this.disabled,
    };
    return html`
      <div class=${classMap(classes)}>
        ${this._inputGroupBeforeTemplate()}
        <div class="input-group__container">
          ${this._inputGroupPrefixTemplate()} ${this._inputGroupInputTemplate()}
          ${this._inputGroupSuffixTemplate()}
        </div>
        ${this._inputGroupAfterTemplate()}
      </div>
    `;
  }

  protected _statusBadgeTemplate() {
    if (!this.status) {
      return nothing;
    }
    return html`
      <div
        class="form-field__status-indicator"
        title=${ifDefined(this.statusLabel)}
        aria-hidden="true"
      >
        <span class="cp-visually-hidden">${this.statusLabel}</span>
      </div>
    `;
  }

  /**
   * Tip/warning notices, mirroring `forms/field-notice.blade.php`: an info
   * callout with a lightbulb icon for tips, a warning callout (default icon)
   * for warnings, each with a visually hidden prefix.
   */
  protected _noticeTemplate(kind: 'tip' | 'warning') {
    if (!this.__hasLightChild(kind)) {
      return nothing;
    }

    const isTip = kind === 'tip';

    return html`
      <craft-callout
        class="field-notice"
        variant=${isTip ? 'info' : 'warning'}
        appearance="plain"
      >
        <craft-visually-hidden>
          ${isTip ? t('Tip:') : t('Warning:')}
        </craft-visually-hidden>
        <slot name=${kind}></slot>
      </craft-callout>
    `;
  }

  private __hasLightChild(slotName: string): boolean {
    return this.__lightChild(slotName) !== undefined;
  }

  private __lightChild(slotName: string): HTMLElement | undefined {
    return (Array.from(this.children) as HTMLElement[]).find(
      (el) => el.slot === slotName
    );
  }

  private __onLightDomChanged(): void {
    this.__wireDescribedBy();
    this.__syncFieldsetSemantics();
    this.__syncHasErrors();
    this.__syncLabelDecorations();
    this.__syncHasMaxlength();
    this.__syncControlWidth();
    // Conditional templates (tip/warning callouts, heading spacer, action
    // group) depend on light DOM children.
    this.requestUpdate();
  }

  private __wireDescribedBy(): void {
    for (const slotName of ['help-text', 'feedback', 'tip', 'warning']) {
      const node = this.__lightChild(slotName);
      if (node) {
        // Already-registered nodes are ignored by addToAriaDescribedBy.
        this.addToAriaDescribedBy(node, {idPrefix: slotName});
      }
    }
  }

  private __formControlTarget(): FormControlTarget | undefined {
    const input = this._inputNode;

    return input &&
      'addToAriaLabelledBy' in input &&
      'addToAriaDescribedBy' in input
      ? (input as FormControlTarget)
      : undefined;
  }

  private __hasNativeLabelTarget(): boolean {
    return Boolean(
      this._inputNode?.matches(
        'button, input, meter, output, progress, select, textarea'
      )
    );
  }

  private __addGroupAriaReference(
    attribute: 'aria-labelledby' | 'aria-describedby',
    element: HTMLElement,
    idPrefix: string
  ): void {
    element.id ||= `${idPrefix}-${this._inputId}`;
    const ids = new Set(
      (this.getAttribute(attribute) ?? '').split(/\s+/).filter(Boolean)
    );
    ids.add(element.id);
    this.setAttribute(attribute, [...ids].join(' '));
  }

  private __syncHasErrors(): void {
    const feedback = this.__lightChild('feedback');
    if (!feedback) {
      // No feedback node: leave manual control of `has-errors` alone.
      return;
    }
    this.hasErrors = Boolean(
      feedback.childElementCount || feedback.textContent?.trim()
    );
  }

  private __syncFieldsetSemantics(): void {
    const labelNode = this._labelNode;
    const control = this.__formControlTarget();
    if (this.fieldset || (!control && !this.__hasNativeLabelTarget())) {
      this.setAttribute('role', 'group');
      if (labelNode) {
        if (!labelNode.id) {
          labelNode.id = `label-${this._inputId}`;
        }
        this.setAttribute('aria-labelledby', labelNode.id);
        labelNode.removeAttribute('for');
      }
    } else {
      if (this.getAttribute('role') === 'group') {
        this.removeAttribute('role');
      }
      this.removeAttribute('aria-labelledby');
      if (labelNode && control) {
        labelNode.removeAttribute('for');
      } else if (labelNode && this._inputNode) {
        labelNode.setAttribute('for', this._inputNode.id || this._inputId);
      }
    }
  }

  /**
   * Renders the required indicator and translation icon inside the label
   * node, matching `FormFields::fieldHtml()`'s label internals. These live in
   * the light DOM (like the server-rendered markup), so they pick up the CP's
   * global `.visually-hidden`, `.required` and `.t9n-indicator` styles.
   */
  private __syncLabelDecorations(): void {
    const labelNode = this._labelNode;
    if (!labelNode) {
      return;
    }

    for (const el of labelNode.querySelectorAll(
      '[data-craft-field-decoration]'
    )) {
      el.remove();
    }

    if (!this.label) {
      return;
    }

    if (this.required) {
      const srLabel = document.createElement('span');
      srLabel.className = 'visually-hidden';
      srLabel.textContent = t('Required');
      srLabel.setAttribute('data-craft-field-decoration', '');

      // The asterisk is purely decorative — the visually hidden "Required"
      // above it is what assistive tech announces. craft-icon hides itself
      // when it has no `label`, but it only does so once it upgrades, so set
      // the attribute here too: the decoration is rendered into the light DOM
      // and may be read before then.
      const indicator = document.createElement('craft-icon');
      indicator.setAttribute('name', 'asterisk');
      indicator.setAttribute('data-craft-field-decoration', '');
      indicator.setAttribute('data-color', 'danger');
      indicator.setAttribute('aria-hidden', 'true');

      labelNode.append(srLabel, indicator);
    }

    if (this.translatable) {
      const tooltip = document.createElement('craft-tooltip');
      tooltip.setAttribute('placement', 'bottom');
      tooltip.setAttribute('max-width', '200px');
      tooltip.setAttribute('text', this.translationDescription);
      tooltip.setAttribute('delay', '1000');
      tooltip.setAttribute('data-craft-field-decoration', '');

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 't9n-indicator prevent-autofocus';
      button.setAttribute('data-icon', 'language');
      button.setAttribute('aria-label', this.translationDescription);

      tooltip.append(button);
      labelNode.append(tooltip);
    }
  }
}

if (!customElements.get('craft-field')) {
  customElements.define('craft-field', CraftField);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-field': CraftField;
  }
}
