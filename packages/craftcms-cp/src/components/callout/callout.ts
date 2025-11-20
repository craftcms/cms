import {type CSSResultGroup, html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './callout.styles.js';

export default class CraftCallout extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Variant style of the callout */
  @property({reflect: true}) variant:
    | 'default'
    | 'success'
    | 'warning'
    | 'danger' = 'default';

  /** Appearance style of the callout */
  @property() appearance: 'filled' | 'outline-filled' | 'outline' | 'plain' =
    'outline-filled';

  protected override render(): unknown {
    return html`<slot></slot>`;
  }
}

if (!customElements.get('craft-callout')) {
  customElements.define('craft-callout', CraftCallout);
}
