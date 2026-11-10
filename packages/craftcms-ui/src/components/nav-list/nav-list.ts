import {css, html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {
  NavAppearance,
  type NavAppearanceValue,
} from '@src/constants/nav-appearances';

/**
 * @summary A list wrapper for `craft-nav-item`s. Renders a real `<ul>` around
 * them, so the group is announced as a list with a count rather than as a run
 * of loose links.
 *
 * It supplies the list semantics and the spacing between items, and nothing
 * else — the items carry their own appearance.
 *
 * `appearance` is the exception, and only because the look it names belongs to
 * the list rather than to any one item: `tabs` lays the items out as a strip
 * ruled off from the content below it, the way `craft-tabs` draws its tab row.
 * It changes nothing about what the list is — these are still links to other
 * pages, announced as a list — so a strip switching between views of one page
 * is `craft-tabs`, not this.
 *
 * @slot - The `craft-nav-item`s making up the list.
 *
 * @cssproperty [--c-nav-list-gap=--c-spacing-xs] - Space between items. A tab
 *   strip spaces them further apart, by `--c-spacing-lg`.
 * @cssproperty [--c-nav-list-border=--c-color-neutral-border-quiet] - Color of
 *   the rule along a tab strip.
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
      gap: var(--c-nav-list-gap, var(--c-spacing-xs));
    }

    :host([appearance='tabs']) .nav-list {
      display: flex;
      flex-wrap: wrap;
      gap: var(--c-nav-list-gap, var(--c-spacing-lg));
      border: 0 solid
        var(--c-nav-list-border, var(--c-color-neutral-border-quiet));
      border-block-end-width: 1px;
    }
  `;

  /**
   * How the list is laid out: `list` (the default), a column of rows, or
   * `tabs`, a horizontal strip ruled off from what follows it.
   *
   * Set on the list rather than on each item, and passed down to them, so the
   * two halves of the strip can't disagree — the rule along the bottom is the
   * list's, and the indicator marking the current page is each item's.
   */
  @property({reflect: true}) appearance: NavAppearanceValue =
    NavAppearance.List;

  /** The slotted items, which are normally `craft-nav-item`s. */
  get items(): HTMLElement[] {
    return Array.from(this.children).filter(
      (child): child is HTMLElement =>
        child instanceof HTMLElement && child.slot === ''
    );
  }

  override firstUpdated(changedProperties: PropertyValues) {
    super.firstUpdated(changedProperties);

    this.shadowRoot
      ?.querySelector('slot')
      ?.addEventListener('slotchange', this.#applyAppearance);
    this.#applyAppearance();
  }

  protected override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (changedProperties.has('appearance')) {
      this.#applyAppearance();
    }
  }

  #applyAppearance = () => {
    this.items.forEach((item) => {
      if (this.appearance === NavAppearance.List) {
        item.removeAttribute('appearance');
      } else {
        item.setAttribute('appearance', this.appearance);
      }
    });
  };

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
