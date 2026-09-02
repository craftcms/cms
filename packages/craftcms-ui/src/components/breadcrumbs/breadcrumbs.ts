import {html, LitElement} from 'lit';
import {property, query, queryAssignedElements, state} from 'lit/decorators.js';
import '../icon/icon.js';
import type CraftBreadcrumbItem from '../breadcrumb-item/breadcrumb-item.js';
import styles from './breadcrumbs.styles.js';
import {t} from '../../utilities/translate';

type BreadcrumbItem = {
  label?: string;
  href?: string;
  value: string;
  offsetWidth: number;
  isVisible: boolean; // false if displayed in menu overlay
};

/**
 * @summary A trail of `craft-breadcrumb-item`s showing where the current page
 * sits, and giving a route back up.
 *
 * The trail collapses when it runs out of room rather than wrapping or
 * overflowing, so a deep path stays on one line in a narrow window.
 *
 * @slot - The `craft-breadcrumb-item`s making up the trail.
 */
export default class CraftBreadcrumbs extends LitElement {
  static override styles = [styles];
  @query('slot') protected defaultSlot: HTMLSlotElement;
  @query('slot[name="separator"]') protected separatorSlot: HTMLSlotElement;

  @queryAssignedElements({selector: 'craft-breadcrumb-item'})
  private breadcrumbsElements!: CraftBreadcrumbItem[];

  /**
   * The label to use for the breadcrumb control. This will not be shown on the screen, but it will be announced by
   * screen readers and other assistive devices to provide more context for users.
   */
  /**
   * Accessible name for the navigation landmark. A page with more than one
   * set of breadcrumbs should name each, so they are distinguishable in a
   * list of landmarks.
   */
  @property() label = t('Breadcrumbs');

  @state()
  private items: BreadcrumbItem[] = [];

  @state()
  private visibleItems = 0;

  private resizeObserver: ResizeObserver | undefined;
  private firstRender = true;

  private getSeparator() {
    const separator = this.separatorSlot.assignedElements({
      flatten: true,
    })[0] as HTMLElement;

    // Clone it, remove ids, and slot it
    const clone = separator.cloneNode(true) as HTMLElement;
    [clone, ...clone.querySelectorAll('[id]')].forEach((el) =>
      el.removeAttribute('id')
    );
    clone.setAttribute('data-default', '');
    clone.slot = 'separator';

    return clone;
  }

  /**
   * We need to understand how much space (px) each breadcrumb item occupies,
   * in order to know if it fits the available horizontal space.
   */
  private calculateBreadcrumbItemsWidth(): void {
    this.items = this.breadcrumbsElements.map((el, index) => {
      let width = el.offsetWidth;

      /**
       * For breadcrumbs which are hidden,
       * we need to temporarily remove the hidden attribute to calculate the width.
       */
      if (el.hasAttribute('hidden')) {
        el.removeAttribute('hidden');
        width = el.offsetWidth;
        el.setAttribute('hidden', '');
      }

      return {
        label: el.innerText,
        href: el.href,
        value: /*el.value || */ index.toString(),
        offsetWidth: width,
        isVisible: true,
      };
    });
  }

  private async handleSlotChange() {
    const items = [
      ...this.defaultSlot.assignedElements({flatten: true}),
    ].filter(
      (item) => item.tagName.toLowerCase() === 'craft-breadcrumb-item'
    ) as CraftBreadcrumbItem[];

    items.forEach((item, index) => {
      const isLast = index === items.length - 1;
      const separator = item.querySelector('[slot="separator"]');

      if (isLast) {
        // Nothing follows the last crumb, so it gets no separator. Clear any
        // default one we added while this item still had a sibling after it —
        // slot changes can promote an item to last. A custom separator is the
        // author's call, so leave it be.
        if (separator?.hasAttribute('data-default')) {
          separator.remove();
        }
      } else if (separator === null) {
        // No separator exists, add one
        item.append(this.getSeparator());
      } else if (separator.hasAttribute('data-default')) {
        // A default separator exists, replace it
        separator.replaceWith(this.getSeparator());
      } else {
        // The user provided a custom separator, leave it alone
      }

      // The last breadcrumb item is the "current page"
      if (isLast) {
        item.setAttribute('aria-current', 'page');
      } else {
        item.removeAttribute('aria-current');
      }
    });

    if (this.breadcrumbsElements.length === 0) {
      this.items = [];
      this.visibleItems = 0;
      return;
    }

    // Wait for all breadcrumb items to complete their updates
    await Promise.all(this.breadcrumbsElements.map((el) => el.updateComplete));

    // Force a recalculation of widths and overflow
    this.calculateBreadcrumbItemsWidth();

    // Reset visibleItems to 0 to force a full recalculation
    this.visibleItems = 0;
    this.adjustOverflow();
  }

  override connectedCallback() {
    super.connectedCallback();

    this.resizeObserver = new ResizeObserver(() => {
      if (this.firstRender) {
        // Don't adjust overflow on first render, it is adjusted in slotChangeHandler
        this.firstRender = false;
        return;
      }
      this.adjustOverflow();
    });

    this.resizeObserver.observe(this);
  }

  private adjustOverflow() {
    const availableSpace = this.getBoundingClientRect().width;
    console.log({availableSpace});
  }

  override disconnectedCallback() {
    this.resizeObserver?.unobserve(this);
    super.disconnectedCallback();
  }

  override render() {
    return html`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
      </nav>

      <span hidden aria-hidden="true">
        <slot name="separator"><span class="separator">/</span></slot>
      </span>
    `;
  }
}

if (!customElements.get('craft-breadcrumbs')) {
  customElements.define('craft-breadcrumbs', CraftBreadcrumbs);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-breadcrumbs': CraftBreadcrumbs;
  }
}
