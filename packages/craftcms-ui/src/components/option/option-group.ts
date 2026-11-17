import styles from './option-group.styles.js';
import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';

/**
 * @summary A named group of `craft-option`s inside a listbox, such as the
 * options a `craft-combobox` lists under a heading.
 *
 * The group is exposed as a `group` named by its `label`, which is also shown
 * above its options, so a screen reader announces the group on entering it.
 *
 * @slot - The group's `craft-option`s.
 */
export default class CraftOptionGroup extends LitElement {
  static override styles = styles;

  /** The group's visible heading, which also names it. */
  @property()
  label = '';

  override connectedCallback() {
    super.connectedCallback();
    this.setAttribute('role', 'group');
  }

  override willUpdate() {
    // A heading in the shadow root can't be referenced by `aria-labelledby`.
    this.setAttribute('aria-label', this.label);
  }

  override render() {
    return html`
      <div class="label" aria-hidden="true">${this.label}</div>
      <slot></slot>
    `;
  }
}

if (!customElements.get('craft-option-group')) {
  customElements.define('craft-option-group', CraftOptionGroup);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-option-group': CraftOptionGroup;
  }
}
