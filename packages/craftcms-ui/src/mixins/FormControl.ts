import {
  css,
  html,
  nothing,
  render,
  type LitElement,
  type PropertyValues,
  type TemplateResult,
} from 'lit';
import {property} from 'lit/decorators.js';
// Registers <craft-callout> and <craft-visually-hidden> for the notices.
import '../components/callout/callout.js';
import '../components/visually-hidden/visually-hidden.js';
import {HasSlotController} from '@src/utilities/slot';
import {t} from '@src/utilities/translate';
import {uniqueId} from '@src/utilities/unique-id.js';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/**
 * What a `slots` entry may create when the consumer hasn't supplied that slot:
 * a node appended once, or a template re-rendered into the light DOM on every
 * update. A template must put `slot="…"` on its own root element.
 */
export type SlotFactory = () => Node | TemplateResult | undefined;

/** References are kept in the order they were added. */
export type AriaReferenceConfig = {idPrefix?: string};

type NoticeKind = 'tip' | 'warning';

/** The mixin's surface, as a type: Lit's pattern for typing protected members. */
export declare class FormControlInterface {
  label: string;
  helpText: string;
  labelSrOnly: boolean;
  readOnly: boolean;
  disabled: boolean;
  labelledBy: string | null;
  describedBy: string | null;
  readonly _inputId: string;
  get _inputNode(): HTMLElement | undefined;
  get _labelNode(): HTMLElement | undefined;
  get _helpTextNode(): HTMLElement | undefined;
  get _feedbackNode(): HTMLElement | undefined;
  get slots(): Record<string, SlotFactory>;
  addToAriaLabelledBy(element: HTMLElement, config?: AriaReferenceConfig): void;
  removeFromAriaLabelledBy(element: HTMLElement): void;
  addToAriaDescribedBy(
    element: HTMLElement,
    config?: AriaReferenceConfig
  ): void;
  removeFromAriaDescribedBy(element: HTMLElement): void;
  protected _onLabelClick(): void;
  protected _enhanceLightDomA11y(): void;
  protected _reflectAriaReferences(): void;
  protected _groupOneTemplate(): unknown;
  protected _groupTwoTemplate(): unknown;
  protected _labelTemplate(): unknown;
  protected _helpTextTemplate(): unknown;
  protected _inputGroupTemplate(): unknown;
  protected _inputGroupBeforeTemplate(): unknown;
  protected _inputGroupPrefixTemplate(): unknown;
  protected _inputGroupInputTemplate(): unknown;
  protected _inputGroupSuffixTemplate(): unknown;
  protected _inputGroupAfterTemplate(): unknown;
  protected _feedbackTemplate(): unknown;
  protected _noticeTemplate(kind: NoticeKind): unknown;
}

/** The chrome's own layout, for subclasses that compose their styles. */
export const formControlStyles = css`
  :host {
    display: block;
  }

  :host([hidden]) {
    display: none;
  }

  :host([disabled]),
  :host(:disabled) {
    pointer-events: none;
  }

  :host([disabled]) .form-field__label ::slotted(*),
  :host([disabled]) .form-field__help-text ::slotted(*),
  :host([disabled]) .input-group ::slotted([slot='input']),
  :host(:disabled) .form-field__label ::slotted(*),
  :host(:disabled) .form-field__help-text ::slotted(*),
  :host(:disabled) .input-group ::slotted([slot='input']) {
    color: var(--disabled-text-color, #767676);
  }

  :host([label-sr-only]) .form-field__label {
    position: absolute;
    inset-block-start: 0;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip-path: inset(100%);
    clip: rect(1px, 1px, 1px, 1px);
    white-space: nowrap;
    border: 0;
    margin: 0;
    padding: 0;
  }

  .input-group__container {
    display: flex;
  }

  .input-group__input {
    flex: 1;
    display: flex;
  }

  .input-group__container > .input-group__input ::slotted(.form-control) {
    flex: 1 1 auto;
    margin: 0;
    font-size: 100%;
  }
`;

/** Light-DOM slot children that can join the control's name or description. */
const ADDITIONAL_SLOTS = ['prefix', 'suffix', 'before', 'after'];

const NOTICES = ['tip', 'warning'] as const;

const HOST_REFERENCES = {
  labelledBy: ['addToAriaLabelledBy', 'removeFromAriaLabelledBy'],
  describedBy: ['addToAriaDescribedBy', 'removeFromAriaDescribedBy'],
} as const;

type HostReference = keyof typeof HOST_REFERENCES;

/**
 * The field chrome shared by the CP's form controls: a label, help text and
 * feedback around a slotted control (`slot="input"`), with the label and help
 * text generated in the light DOM from `label` and `help-text` so they can be
 * referenced by id, and `aria-labelledby`/`aria-describedby` kept on the
 * control.
 *
 * The label and help text render only when there's something to show, so
 * `baseFieldStyles` can space them unconditionally. `tip` and `warning` slot
 * content renders as field notices just ahead of the feedback.
 *
 * Subclasses pick their control by adding an `input` entry to `slots`, or by
 * expecting the consumer to slot one, and arrange the chrome by overriding the
 * `_…Template()` methods.
 */
export const FormControl = <T extends Constructor<LitElement>>(Base: T) => {
  class FormControlElement extends Base {
    static styles = [formControlStyles];

    /** The field's label. Slot `label` instead for markup. */
    @property()
    get label(): string {
      return this.#label ?? this._labelNode?.textContent ?? '';
    }

    set label(value: string | null) {
      const old = this.label;
      // A removed attribute arrives as `null`, which clears it; `undefined`
      // still falls back to the slotted node's text.
      this.#label = value === null ? '' : value;
      this.requestUpdate('label', old);
    }

    /** Guidance shown with the label. Slot `help-text` instead for markup. */
    @property({attribute: 'help-text'})
    get helpText(): string {
      return this.#helpText ?? this._helpTextNode?.textContent ?? '';
    }

    set helpText(value: string | null) {
      const old = this.helpText;
      // A removed attribute arrives as `null`, which clears it; `undefined`
      // still falls back to the slotted node's text.
      this.#helpText = value === null ? '' : value;
      this.requestUpdate('helpText', old);
    }

    /** Visually hides the label, keeping it available to screen readers. */
    @property({type: Boolean, attribute: 'label-sr-only', reflect: true})
    labelSrOnly = false;

    /** Whether the value can be read but not changed. */
    @property({type: Boolean, attribute: 'readonly', reflect: true})
    readOnly = false;

    /** Whether the control is disabled. */
    @property({type: Boolean, reflect: true}) disabled = false;

    /**
     * Ids of elements elsewhere that label the control, such as a table
     * column heading. They aren't `aria-labelledby` because the host isn't the
     * control; the ids are resolved and written onto the control along with
     * its own label. The elements need to be in the tree when the control
     * connects or the property changes.
     */
    @property({attribute: 'labelled-by'}) labelledBy: string | null = null;

    /** Ids of elements elsewhere that describe the control. See `labelledBy`. */
    @property({attribute: 'described-by'}) describedBy: string | null = null;

    #label?: string;

    #helpText?: string;

    /** The id given to the control when it has none. */
    readonly _inputId = uniqueId(this.localName);

    #labelledNodes: HTMLElement[] = [];

    #describedNodes: HTMLElement[] = [];

    /** The feedback in the description, swapped when it's replaced. */
    #describedFeedback?: HTMLElement;

    /** Feedback can come and go after connection, such as errors shown later. */
    #feedbackObserver = new MutationObserver(() => this.#describeFeedback());

    /** The ids this control last wrote to each reference attribute. */
    #writtenReferences = new Map<string, string[]>();

    /** Slots the consumer supplied, which are never generated. */
    #suppliedSlots = new Set<string>();

    /** Template slots, re-rendered before their marker on every update. */
    #templateSlots = new Map<string, Comment>();

    #slotsCreated = false;

    /** Re-renders the chrome when a prefix or suffix comes or goes. */
    #hasAffix = new HasSlotController(this, 'prefix', 'suffix');

    #hasLabel = false;

    #hasHelpText = false;

    #hasNoticeSlot = new HasSlotController(this, ...NOTICES);

    // Slotted label and help text can change outside of Lit.
    #observer = new MutationObserver(() => this.#syncChrome());

    #hostReferences: Record<HostReference, HTMLElement[]> = {
      labelledBy: [],
      describedBy: [],
    };

    /**
     * Light-DOM content created for slots the consumer didn't fill. Subclasses
     * extend it: `{...super.slots, input: () => document.createElement('input')}`.
     */
    get slots(): Record<string, SlotFactory> {
      return {
        label: () => {
          const label = document.createElement('label');
          label.textContent = this.label;
          return label;
        },
        'help-text': () => {
          const helpText = document.createElement('div');
          helpText.textContent = this.helpText;
          return helpText;
        },
      };
    }

    get _inputNode(): HTMLElement | undefined {
      return this.#slotted('input');
    }

    get _labelNode(): HTMLElement | undefined {
      return this.#slotted('label');
    }

    get _helpTextNode(): HTMLElement | undefined {
      return this.#slotted('help-text');
    }

    get _feedbackNode(): HTMLElement | undefined {
      return this.#slotted('feedback');
    }

    #slotted(name: string): HTMLElement | undefined {
      for (const child of this.children) {
        if (child instanceof HTMLElement && child.slot === name) {
          return child;
        }
      }

      return undefined;
    }

    override connectedCallback() {
      super.connectedCallback();
      this.#createSlots();
      this._enhanceLightDomA11y();
      this.#feedbackObserver.observe(this, {childList: true});
      this._labelNode?.addEventListener('click', this.#onLabelClick);
      this.addEventListener('focusin', this.#onFocusChange);
      this.addEventListener('focusout', this.#onFocusChange);
      this.#observer.observe(this, {
        childList: true,
        subtree: true,
        characterData: true,
      });
      this.#syncChrome();
      this.#adoptHostReference('labelledBy');
      this.#adoptHostReference('describedBy');
    }

    override disconnectedCallback() {
      super.disconnectedCallback();
      this.#observer.disconnect();
      this.#feedbackObserver.disconnect();
      this._labelNode?.removeEventListener('click', this.#onLabelClick);
      this.removeEventListener('focusin', this.#onFocusChange);
      this.removeEventListener('focusout', this.#onFocusChange);
    }

    /** Feedback is announced politely while the user is in the field. */
    #onFocusChange = (event: FocusEvent): void => {
      this._feedbackNode?.setAttribute(
        'aria-live',
        event.type === 'focusin' ? 'polite' : 'assertive'
      );
    };

    protected override willUpdate(changed: PropertyValues) {
      super.willUpdate(changed);
      this.#hasLabel = this.#hasContent(this.label, 'label');
      this.#hasHelpText = this.#hasContent(this.helpText, 'help-text');
    }

    protected override updated(changed: PropertyValues) {
      super.updated(changed);
      this.#renderTemplateSlots();
      this.#syncChrome();

      for (const kind of NOTICES) {
        const node = this.#slotted(kind);
        // Only a notice that renders describes the control.
        if (node && this.renderRoot.querySelector(`slot[name="${kind}"]`)) {
          this.addToAriaDescribedBy(node, {idPrefix: kind});
        }
      }

      for (const reference of ['labelledBy', 'describedBy'] as const) {
        if (changed.has(reference)) {
          this.#adoptHostReference(reference);
        }
      }

      if (
        changed.has('label') &&
        this.#label !== undefined &&
        this._labelNode
      ) {
        this._labelNode.textContent = this.label;
      }

      if (
        changed.has('helpText') &&
        this.#helpText !== undefined &&
        this._helpTextNode
      ) {
        this._helpTextNode.textContent = this.helpText;
      }

      this._reflectAriaReferences();
    }

    #hasContent(text: string, slot: string): boolean {
      return (
        text.trim() !== '' || (this.#slotted(slot)?.childElementCount ?? 0) > 0
      );
    }

    #syncChrome(): void {
      if (
        this.#hasContent(this.label, 'label') !== this.#hasLabel ||
        this.#hasContent(this.helpText, 'help-text') !== this.#hasHelpText
      ) {
        this.requestUpdate();
      }
    }

    #adoptHostReference(reference: HostReference): void {
      const [add, remove] = HOST_REFERENCES[reference];
      // A disconnected element's root may lack getElementById;
      // connectedCallback() adopts again.
      const root = this.getRootNode() as Document | ShadowRoot;
      if (typeof root.getElementById !== 'function') {
        return;
      }
      const elements = (this[reference] ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .map((id) => root.getElementById(id))
        .filter((element): element is HTMLElement => element !== null);

      for (const element of this.#hostReferences[reference]) {
        if (!elements.includes(element)) {
          this[remove](element);
        }
      }
      for (const element of elements) {
        this[add](element);
      }
      this.#hostReferences[reference] = elements;
    }

    #createSlots(): void {
      if (this.#slotsCreated) {
        return;
      }
      this.#slotsCreated = true;

      const factories = this.slots;

      for (const name of Object.keys(factories)) {
        if (this.#slotted(name)) {
          this.#suppliedSlots.add(name);
        }
      }

      for (const [name, factory] of Object.entries(factories)) {
        if (this.#suppliedSlots.has(name)) {
          continue;
        }

        const result = factory();

        if (result instanceof Node) {
          if (result instanceof Element) {
            result.slot = name;
          }
          this.append(result);
        } else if (result) {
          const marker = document.createComment(`slot:${name}`);
          this.append(marker);
          this.#templateSlots.set(name, marker);
        }
      }

      this.#renderTemplateSlots();
    }

    #renderTemplateSlots(): void {
      const factories = this.slots;

      for (const [name, marker] of this.#templateSlots) {
        const template = factories[name]?.();

        if (template && !(template instanceof Node)) {
          render(template, this, {renderBefore: marker, host: this});
        }
      }
    }

    #onLabelClick = (): void => {
      this._onLabelClick();
    };

    /**
     * Called when the label is clicked. Focuses the control, which a label
     * can't do for one that isn't a native form control.
     */
    protected _onLabelClick(): void {
      if (!this.disabled) {
        this._inputNode?.focus();
      }
    }

    /**
     * Gives the control an id, points the label at it, and adds the label,
     * help text and feedback to its name and description.
     */
    protected _enhanceLightDomA11y(): void {
      const {_inputNode, _labelNode, _helpTextNode} = this;

      _inputNode?.classList.add('form-control');

      if (_inputNode) {
        _inputNode.id ||= this._inputId;
      }

      if (_labelNode) {
        _labelNode.setAttribute('for', _inputNode?.id ?? this._inputId);
        this.addToAriaLabelledBy(_labelNode, {idPrefix: 'label'});
      }

      if (_helpTextNode) {
        this.addToAriaDescribedBy(_helpTextNode, {idPrefix: 'help-text'});
      }

      this.#describeFeedback();

      for (const name of ADDITIONAL_SLOTS) {
        const node = this.#slotted(name);

        if (node?.hasAttribute('data-label')) {
          this.addToAriaLabelledBy(node, {idPrefix: name});
        }

        if (node?.hasAttribute('data-description')) {
          this.addToAriaDescribedBy(node, {idPrefix: name});
        }
      }
    }

    #describeFeedback(): void {
      const feedback = this._feedbackNode;

      if (feedback === this.#describedFeedback) {
        return;
      }

      if (this.#describedFeedback) {
        this.removeFromAriaDescribedBy(this.#describedFeedback);
      }

      if (feedback) {
        this.addToAriaDescribedBy(feedback, {idPrefix: 'feedback'});
      }

      this.#describedFeedback = feedback;
    }

    addToAriaLabelledBy(
      element: HTMLElement,
      {idPrefix = ''}: AriaReferenceConfig = {}
    ): void {
      element.id ||= uniqueId(`${idPrefix}-${this._inputId}`);

      if (!this.#labelledNodes.includes(element)) {
        this.#labelledNodes = [...this.#labelledNodes, element];
        this._reflectAriaReferences();
      }
    }

    removeFromAriaLabelledBy(element: HTMLElement): void {
      if (this.#labelledNodes.includes(element)) {
        this.#labelledNodes = this.#labelledNodes.filter((n) => n !== element);
        this._reflectAriaReferences();
      }
    }

    addToAriaDescribedBy(
      element: HTMLElement,
      {idPrefix = ''}: AriaReferenceConfig = {}
    ): void {
      element.id ||= uniqueId(`${idPrefix}-${this._inputId}`);

      if (!this.#describedNodes.includes(element)) {
        this.#describedNodes = [...this.#describedNodes, element];
        this._reflectAriaReferences();
      }
    }

    removeFromAriaDescribedBy(element: HTMLElement): void {
      if (this.#describedNodes.includes(element)) {
        this.#describedNodes = this.#describedNodes.filter(
          (n) => n !== element
        );
        this._reflectAriaReferences();
      }
    }

    /**
     * Writes the label and description references onto the control, in the
     * order they were added. Subclasses whose name and description belong on
     * another node can redirect them.
     */
    protected _reflectAriaReferences(): void {
      const control = this._inputNode;

      if (!control) {
        return;
      }

      // Only the ids this control wrote are replaced, so ones the markup or
      // anything else put there stay, ahead of the control's own.
      const write = (attribute: string, nodes: HTMLElement[]) => {
        const own = nodes.map((n) => n.id);
        const written = this.#writtenReferences.get(attribute) ?? [];
        const ids = new Set([
          ...(control.getAttribute(attribute) ?? '')
            .split(/\s+/)
            .filter((id) => id && !written.includes(id)),
          ...own,
        ]);
        this.#writtenReferences.set(attribute, own);

        if (ids.size) {
          control.setAttribute(attribute, [...ids].join(' '));
        } else if (written.length) {
          control.removeAttribute(attribute);
        }
      };

      // The control's own label and help text only count while they have
      // something to say, so an empty one doesn't stand in for another name.
      const counts = (node: HTMLElement) =>
        (node !== this._labelNode && node !== this._helpTextNode) ||
        node.textContent!.trim() !== '' ||
        node.childElementCount > 0;

      write('aria-labelledby', this.#labelledNodes.filter(counts));
      write('aria-describedby', this.#describedNodes.filter(counts));
    }

    protected override render(): unknown {
      return html`
        <div class="form-field__group-one">${this._groupOneTemplate()}</div>
        <div class="form-field__group-two">${this._groupTwoTemplate()}</div>
      `;
    }

    /** The label and help text, above the control. */
    protected _groupOneTemplate(): unknown {
      return html`${this._labelTemplate()} ${this._helpTextTemplate()}`;
    }

    /** The control and everything below it. */
    protected _groupTwoTemplate(): unknown {
      return html`${this._inputGroupTemplate()} ${this._feedbackTemplate()}`;
    }

    /** The `label` slot, when there is a label. */
    protected _labelTemplate(): unknown {
      if (!this.#hasLabel) {
        return nothing;
      }

      return html`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `;
    }

    /** The `help-text` slot, when there is help text. */
    protected _helpTextTemplate(): unknown {
      if (!this.#hasHelpText) {
        return nothing;
      }

      return html`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `;
    }

    /** The `before` content, the control row and the `after` content. */
    protected _inputGroupTemplate(): unknown {
      return html`
        <div class="input-group">
          ${this._inputGroupBeforeTemplate()}
          <div class="input-group__container">
            ${this._inputGroupPrefixTemplate()}
            ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
          ${this._inputGroupAfterTemplate()}
        </div>
      `;
    }

    /** The `before` slot, above the control row. */
    protected _inputGroupBeforeTemplate(): unknown {
      return html`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `;
    }

    /** The `prefix` slot, at the start of the control row. */
    protected _inputGroupPrefixTemplate(): unknown {
      return this.#hasAffix.test('prefix')
        ? html`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `
        : nothing;
    }

    /** The `input` slot, holding the control. */
    protected _inputGroupInputTemplate(): unknown {
      return html`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `;
    }

    /** The `suffix` slot, at the end of the control row. */
    protected _inputGroupSuffixTemplate(): unknown {
      return this.#hasAffix.test('suffix')
        ? html`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `
        : nothing;
    }

    /** The `after` slot, below the control row. */
    protected _inputGroupAfterTemplate(): unknown {
      return html`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `;
    }

    /** Field notices, then the feedback. */
    protected _feedbackTemplate(): unknown {
      return html`
        ${this._noticeTemplate('tip')} ${this._noticeTemplate('warning')}
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `;
    }

    /**
     * A tip or warning notice, mirroring `forms/field-notice.blade.php`, with
     * a visually hidden prefix. Skipped when nothing is slotted.
     */
    protected _noticeTemplate(kind: NoticeKind): unknown {
      if (!this.#hasNoticeSlot.test(kind)) {
        return nothing;
      }

      const isTip = kind === 'tip';

      return html`
        <craft-callout
          class="field-notice"
          variant=${isTip ? 'info' : 'warning'}
          appearance="plain"
          padding="none"
        >
          <craft-visually-hidden>
            ${isTip ? t('Tip:') : t('Warning:')}
          </craft-visually-hidden>
          <slot name=${kind}></slot>
        </craft-callout>
      `;
    }
  }

  return FormControlElement as unknown as Constructor<FormControlInterface> & T;
};
