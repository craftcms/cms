import {css, html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import visuallyHiddenStyles from '@src/styles/visually-hidden.styles.js';
import {linkTarget, newTabCue} from '@src/utilities/external-links';

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

  /**
   * Browsing context for the link, as on a native anchor. Unset, a link to
   * another host opens in a new tab.
   */
  @property() target?: '_blank' | '_parent' | '_self' | '_top';

  /**
   * Link relationship. Defaults to `noreferrer noopener`, which is what a
   * crumb opening in a new tab should carry.
   */
  @property() rel = 'noreferrer noopener';

  static override styles = [
    visuallyHiddenStyles,
    css`
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
        font-weight: var(--font-weight-medium);
        color: inherit;
        text-decoration: none;
      }

      /* The current page, which <craft-breadcrumbs> marks on the last crumb. */
      :host([aria-current='page']) .label {
        font-weight: var(--font-weight-light);
      }

      a.label:hover {
        text-decoration: underline;
      }

      slot[name='suffix']::slotted(*) {
        margin-inline-start: var(--c-spacing-sm);
      }

      /* With the crumb's gap, its margin puts as much space before it as the
         list's gap puts after it (see craft-breadcrumbs), centering it. */
      slot[name='separator']::slotted(*) {
        color: var(--c-text-quiet);
        margin-block: 0;
        margin-inline: var(--c-spacing-sm) 0;
      }
    `,
  ];

  override render() {
    const target = linkTarget(this.href, this.target);
    const label = this.href
      ? html`<a
          part="label"
          class="label"
          href=${this.href}
          target=${target ?? nothing}
          rel=${target ? this.rel : nothing}
          ><slot></slot>${!this.target && target
            ? html`<span class="cp-visually-hidden">${newTabCue()}</span>`
            : nothing}</a
        >`
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
