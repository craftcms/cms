import {css, html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';

/**
 * A single crumb inside `craft-breadcrumbs`. Renders its default-slot content
 * as a link when `href` is set. `craft-breadcrumbs` appends separator content
 * into the `separator` slot.
 */
/**
 * @summary One step in a `craft-breadcrumbs` trail. Renders as a link when
 * `href` is set, and as plain text otherwise — which is what the current page
 * should be.
 *
 * @slot - The step's label.
 */
export default class CraftBreadcrumbItem extends LitElement {
  /** When set, the label is rendered as a link. */
  /**
   * Where the step links to. Leave it off for the current page: the last
   * crumb is where the person already is, so linking it goes nowhere.
   */
  @property({reflect: true}) href?: string;

  /** The link target (only used when `href` is set). */
  /** Browsing context for the link, as on a native anchor. */
  @property() target?: '_blank' | '_parent' | '_self' | '_top';

  /** The link rel (only used when `href` and `target` are set). */
  /**
   * Link relationship. Defaults to `noreferrer noopener`, which is what a
   * crumb opening in a new tab should carry.
   */
  @property() rel = 'noreferrer noopener';

  static override styles = css`
    :host {
      white-space: nowrap;
      display: inline-flex;
      gap: var(--c-spacing-sm);
      align-items: center;
      color: inherit;
    }

    .label {
      display: inline-flex;
      align-items: center;
      font-weight: 400;
      color: inherit;
      text-decoration: none;
    }

    a.label:hover {
      text-decoration: underline;
    }

    slot[name='prefix']::slotted(*) {
      margin-inline-end: var(--c-spacing-sm);
    }

    slot[name='suffix']::slotted(*) {
      margin-inline-start: var(--c-spacing-sm);
    }

    slot[name='separator']::slotted(*) {
      color: var(--c-text-quiet);
      margin: 0 var(--c-spacing-md);
    }
  `;

  override render() {
    const label = this.href
      ? html`<a
          part="label"
          class="label"
          href=${this.href}
          target=${this.target ?? nothing}
          rel=${this.target ? this.rel : nothing}
          ><slot></slot
        ></a>`
      : html`<span part="label" class="label"><slot></slot></span>`;

    return html`
      <slot name="prefix" part="prefix"></slot>
      ${label}
      <slot name="suffix" part="suffix"></slot>
      <slot name="separator" part="separator" aria-hidden="true"></slot>
    `;
  }
}

if (!customElements.get('craft-breadcrumb-item')) {
  customElements.define('craft-breadcrumb-item', CraftBreadcrumbItem);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-breadcrumb-item': CraftBreadcrumbItem;
  }
}
