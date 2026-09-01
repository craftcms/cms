import {css, html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';

/**
 * A single crumb inside `craft-breadcrumbs`. Renders its default-slot content
 * as a link when `href` is set. `craft-breadcrumbs` appends separator content
 * into the `separator` slot.
 */
export default class CraftBreadcrumbItem extends LitElement {
  /** When set, the label is rendered as a link. */
  @property({reflect: true}) href?: string;

  /** The link target (only used when `href` is set). */
  @property() target?: '_blank' | '_parent' | '_self' | '_top';

  /** The link rel (only used when `href` and `target` are set). */
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
