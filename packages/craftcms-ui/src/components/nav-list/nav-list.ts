import {css, html, LitElement} from 'lit';

/**
 * @summary A list wrapper for `craft-nav-item`s. Renders a real `<ul>` around
 * them, so the group is announced as a list with a count rather than as a run
 * of loose links.
 *
 * It supplies the list semantics and the spacing between items, and nothing
 * else — the items carry their own appearance.
 *
 * @slot - The `craft-nav-item`s making up the list.
 */
export default class CraftNavList extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }

    .nav-list {
      display: grid;
      margin: 0;
      padding: 0;
      list-style: none;
      gap: var(--c-spacing-xs);
    }
  `;

  override render() {
    return html`
      <ul class="nav-list">
        <slot></slot>
      </ul>
    `;
  }
}

if (!customElements.get('craft-nav-list')) {
  customElements.define('craft-nav-list', CraftNavList);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-nav-list': CraftNavList;
  }
}
