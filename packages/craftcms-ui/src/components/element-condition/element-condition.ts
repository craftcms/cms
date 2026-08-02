import {css, html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';

type RuntimeWindow = Window & {
  Craft?: {initUiElements?(container: HTMLElement): void};
  htmx?: {process(container: HTMLElement): void};
};

/**
 * @summary Hosts a server-rendered element condition builder.
 *
 * @slot - The condition builder controls.
 */
export default class CraftElementCondition extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }
  `;

  /** Prevents condition changes. */
  @property({attribute: 'readonly', reflect: true, type: Boolean})
  readOnly = false;

  private lightDomObserver = new MutationObserver(() => this.applyReadOnly());

  private disabledControls = new WeakSet<HTMLElement>();

  override connectedCallback(): void {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'group');
    }

    this.lightDomObserver.observe(this, {childList: true, subtree: true});
    this.addEventListener('click', this.preventReadOnly, true);
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.lightDomObserver.disconnect();
    this.removeEventListener('click', this.preventReadOnly, true);
  }

  protected override firstUpdated(): void {
    this.initialize();
  }

  protected override updated(changedProperties: PropertyValues<this>): void {
    super.updated(changedProperties);

    if (!changedProperties.has('readOnly')) {
      return;
    }

    if (this.readOnly) {
      this.setAttribute('aria-disabled', 'true');
    } else {
      this.removeAttribute('aria-disabled');
    }
    this.applyReadOnly();
  }

  initialize(): void {
    const runtimeWindow = window as RuntimeWindow;

    runtimeWindow.htmx?.process(this);
    runtimeWindow.Craft?.initUiElements?.(this);
    this.applyReadOnly();
  }

  private applyReadOnly(): void {
    for (const control of this.querySelectorAll<
      HTMLElement & {disabled: boolean}
    >('button, input, select, textarea, craft-action-menu, craft-button')) {
      if (this.readOnly) {
        if (!control.disabled) {
          this.disabledControls.add(control);
          control.disabled = true;
          control.setAttribute('disabled', '');
        }

        continue;
      }

      if (this.disabledControls.delete(control)) {
        control.disabled = false;
        control.removeAttribute('disabled');
      }
    }
  }

  private preventReadOnly = (event: Event): void => {
    if (!this.readOnly) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
  };

  protected override render() {
    return html`<slot></slot>`;
  }
}

if (!customElements.get('craft-element-condition')) {
  customElements.define('craft-element-condition', CraftElementCondition);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-element-condition': CraftElementCondition;
  }
}
