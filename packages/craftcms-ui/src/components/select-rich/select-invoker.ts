import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './select-invoker.styles.js';
import '../icon/icon.js';

/**
 * @summary The field of a {@link CraftSelectRich}: it shows the selected
 * option and opens the listbox. The select renders its `_selectedTemplate()`
 * into it and wires up its semantics. It's in the select's light DOM so it
 * can point at the options by id, and has a shadow root of its own to lay
 * out the selection and its chevron, which the select's styles can't reach.
 *
 * @slot - What the select shows for the selected option.
 *
 * @since 1.0
 */
export default class CraftSelectInvoker extends LitElement {
  static override styles = styles;

  /** Whether there is only one option, so there is nothing to open. */
  @property({type: Boolean, attribute: 'single-option', reflect: true})
  singleOption = false;

  /** Whether the select is disabled. */
  @property({type: Boolean, reflect: true}) disabled = false;

  override render() {
    return html`<div id="content-wrapper"><slot></slot></div>
      ${this.singleOption
        ? ''
        : html`<craft-icon
            class="indicator"
            name="chevron-down"
          ></craft-icon>`}`;
  }
}

if (!customElements.get('craft-select-invoker')) {
  customElements.define('craft-select-invoker', CraftSelectInvoker);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-select-invoker': CraftSelectInvoker;
  }
}
