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
import {HasSlotController} from '@src/utilities/slot';
import {uniqueId} from '@src/utilities/unique-id.js';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/**
 * What a `slots` entry may create when the consumer hasn't supplied that slot:
 * a node appended once, or a template re-rendered into the light DOM on every
 * update. A template must put `slot="…"` on its own root element.
 */
export type SlotFactory = () => Node | TemplateResult | undefined;

/**
 * `reorder` is accepted for callers that also target older controls; references
 * are always kept in the order they were added.
 */
export type AriaReferenceConfig = {idPrefix?: string; reorder?: boolean};

/** The mixin's surface, as a type: Lit's pattern for typing protected members. */
export declare class FormControlInterface {
  label: string;
  helpText: string;
  labelSrOnly: boolean;
  readOnly: boolean;
  disabled: boolean;
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
}

/** The chrome's own layout, for subclasses that compose their styles. */
export const formControlStyles = css`
  :host {
    display: block;
  }

  :host([hidden]) {
    display: none;
  }

  :host([disabled]) {
    pointer-events: none;
  }

  :host([disabled]) .form-field__label ::slotted(*),
  :host([disabled]) .form-field__help-text ::slotted(*),
  :host([disabled]) .input-group ::slotted([slot='input']) {
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

/**
 * The field chrome shared by the CP's form controls: a label, help text and
 * feedback around a slotted control (`slot="input"`), with the label and help
 * text generated in the light DOM from `label` and `help-text` so they can be
 * referenced by id, and `aria-labelledby`/`aria-describedby` kept on the
 * control.
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
    }

    override disconnectedCallback() {
      super.disconnectedCallback();
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

    protected override updated(changed: PropertyValues) {
      super.updated(changed);
      this.#renderTemplateSlots();

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

    /** Called when the label is clicked. */
    protected _onLabelClick(): void {}

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

    protected _groupOneTemplate(): unknown {
      return html`${this._labelTemplate()} ${this._helpTextTemplate()}`;
    }

    protected _groupTwoTemplate(): unknown {
      return html`${this._inputGroupTemplate()} ${this._feedbackTemplate()}`;
    }

    protected _labelTemplate(): unknown {
      return html`
        <div class="form-field__label">
          <slot name="label"></slot>
        </div>
      `;
    }

    protected _helpTextTemplate(): unknown {
      return html`
        <small class="form-field__help-text">
          <slot name="help-text"></slot>
        </small>
      `;
    }

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

    protected _inputGroupBeforeTemplate(): unknown {
      return html`
        <div class="input-group__before">
          <slot name="before"></slot>
        </div>
      `;
    }

    protected _inputGroupPrefixTemplate(): unknown {
      return this.#hasAffix.test('prefix')
        ? html`
            <div class="input-group__prefix">
              <slot name="prefix"></slot>
            </div>
          `
        : nothing;
    }

    protected _inputGroupInputTemplate(): unknown {
      return html`
        <div class="input-group__input">
          <slot name="input"></slot>
        </div>
      `;
    }

    protected _inputGroupSuffixTemplate(): unknown {
      return this.#hasAffix.test('suffix')
        ? html`
            <div class="input-group__suffix">
              <slot name="suffix"></slot>
            </div>
          `
        : nothing;
    }

    protected _inputGroupAfterTemplate(): unknown {
      return html`
        <div class="input-group__after">
          <slot name="after"></slot>
        </div>
      `;
    }

    protected _feedbackTemplate(): unknown {
      return html`
        <div class="form-field__feedback">
          <slot name="feedback"></slot>
        </div>
      `;
    }
  }

  return FormControlElement as unknown as Constructor<FormControlInterface> & T;
};
