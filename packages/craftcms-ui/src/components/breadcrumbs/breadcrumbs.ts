import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import type CraftActionMenu from '../action-menu/action-menu.js';
import type {ActionMenuItem} from '../action-menu/action-menu.types.js';
import type CraftBreadcrumbItem from '../breadcrumb-item/breadcrumb-item.js';
import styles from './breadcrumbs.styles.js';
import {t} from '../../utilities/translate';

import '../action-menu/action-menu.js';
import '../breadcrumb-item/breadcrumb-item.js';
import '../button/button.js';
import '../icon/icon.js';

/**
 * Slack, in pixels, when deciding whether a crumb fits. Sub-pixel rounding can
 * leave a trail a hair wider than a container that does fit it.
 */
const FIT_TOLERANCE = 1;

/** The parts of a crumb around its label. */
const AFFIXES = '[slot="separator"], [slot="prefix"], [slot="suffix"]';

/**
 * @summary A trail of `craft-breadcrumb-item`s showing where the current page
 * sits, and giving a route back up.
 *
 * The trail collapses when it runs out of room rather than wrapping or
 * overflowing, so a deep path stays on one line in a narrow window. Crumbs
 * give way from the middle, into a menu that takes their place: the first and
 * last crumbs are kept for as long as there's room for them. With room for
 * only one, the last crumb stays and truncates its label.
 *
 * @slot - The `craft-breadcrumb-item`s making up the trail.
 * @slot separator - Drawn between crumbs. Defaults to a chevron.
 *
 * @csspart overflow-menu - The `craft-action-menu` holding the collapsed
 *   crumbs. Present but `hidden` while everything fits.
 */
export default class CraftBreadcrumbs extends LitElement {
  static override styles = [styles];

  /**
   * Accessible name for the navigation landmark. A page with more than one
   * set of breadcrumbs should name each, so they are distinguishable in a
   * list of landmarks.
   */
  @property() label = t('Breadcrumbs');

  /** Indexes of the crumbs currently collapsed into the menu. */
  #collapsed: number[] = [];

  /** What the menu's actions were last built from. */
  #menuSignature: string | null = null;

  #resizeObserver?: ResizeObserver;
  #measureQueued = false;

  get #items(): CraftBreadcrumbItem[] {
    const slot =
      this.shadowRoot?.querySelector<HTMLSlotElement>('slot:not([name])');

    return (slot?.assignedElements({flatten: true}) ?? []).filter(
      (el): el is CraftBreadcrumbItem =>
        el.tagName.toLowerCase() === 'craft-breadcrumb-item'
    );
  }

  get #nav(): HTMLElement | null {
    return this.shadowRoot?.querySelector('.breadcrumbs') ?? null;
  }

  get #overflowItem(): CraftBreadcrumbItem | null {
    return this.shadowRoot?.querySelector('.breadcrumbs__overflow') ?? null;
  }

  get #menu(): CraftActionMenu | null {
    return this.shadowRoot?.querySelector('.breadcrumbs__menu') ?? null;
  }

  private getSeparator() {
    const separator = this.shadowRoot!.querySelector<HTMLSlotElement>(
      'slot[name="separator"]'
    )!.assignedElements({flatten: true})[0] as HTMLElement;

    // Clone it, remove ids, and slot it
    const clone = separator.cloneNode(true) as HTMLElement;
    [clone, ...clone.querySelectorAll('[id]')].forEach((el) =>
      el.removeAttribute('id')
    );
    clone.setAttribute('data-default', '');
    clone.slot = 'separator';

    return clone;
  }

  private async handleSlotChange() {
    const items = this.#items;

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

    // The menu stands in for crumbs, so it's followed by a separator too.
    const overflow = this.#overflowItem;

    if (overflow) {
      overflow.querySelector('[slot="separator"]')?.remove();
      overflow.append(this.getSeparator());
    }

    // Widths aren't final until every crumb has rendered.
    await Promise.all(items.map((el) => el.updateComplete));
    this.#measure();
  }

  override connectedCallback() {
    super.connectedCallback();
    this.#resizeObserver = new ResizeObserver(this.#queueMeasure);
    this.#resizeObserver.observe(this);
  }

  override disconnectedCallback() {
    this.#resizeObserver?.disconnect();
    this.#resizeObserver = undefined;
    super.disconnectedCallback();
  }

  #queueMeasure = () => {
    if (this.#measureQueued) {
      return;
    }

    // Coalesce the burst of callbacks a resize produces, and get off the
    // ResizeObserver callback before writing layout back.
    this.#measureQueued = true;
    requestAnimationFrame(() => {
      this.#measureQueued = false;
      this.#measure();
    });
  };

  /**
   * Decides which crumbs fit and collapses the rest into the menu.
   *
   * Reads layout directly rather than going through a render, so a resize
   * never shows a frame of the wrong trail.
   */
  #measure() {
    const nav = this.#nav;
    const overflow = this.#overflowItem;
    const items = this.#items;

    if (!nav || !overflow) {
      return;
    }

    if (items.length < 2) {
      this.#apply([]);
      return;
    }

    // Measure from a clean slate — every crumb at its natural width — so a
    // crumb that has since been given room comes back.
    items.forEach((item) => item.removeAttribute('hidden'));
    overflow.hidden = false;
    nav.classList.add('breadcrumbs--measuring');

    const gap = parseFloat(getComputedStyle(nav).columnGap) || 0;
    const widths = items.map((item) => item.offsetWidth);
    const menuWidth = overflow.offsetWidth;
    overflow.hidden = true;
    const available = nav.clientWidth;
    nav.classList.remove('breadcrumbs--measuring');

    const natural =
      widths.reduce((total, width) => total + width, 0) +
      gap * (items.length - 1);

    if (natural <= available + FIT_TOLERANCE) {
      this.#apply([]);
      return;
    }

    const last = items.length - 1;
    const kept = new Set([last]);
    let used = widths[last]! + gap + menuWidth;

    // The root is the next most useful crumb after the current page, so it
    // takes precedence over the ones between them.
    if (used + gap + widths[0]! <= available + FIT_TOLERANCE) {
      kept.add(0);
      used += gap + widths[0]!;
    }

    // Then the nearest ancestors, walking back up the trail. Stop at the first
    // that doesn't fit: a shorter crumb further up jumping the queue would
    // leave a gap in the path.
    for (let index = last - 1; index > 0; index--) {
      if (used + gap + widths[index]! > available + FIT_TOLERANCE) {
        break;
      }

      kept.add(index);
      used += gap + widths[index]!;
    }

    this.#apply(items.map((_, index) => index).filter((i) => !kept.has(i)));
  }

  #apply(collapsed: number[]) {
    const items = this.#items;
    const hidden = new Set(collapsed);

    items.forEach((item, index) => {
      item.toggleAttribute('hidden', hidden.has(index));
    });

    this.#collapsed = collapsed;

    const overflow = this.#overflowItem;
    const menu = this.#menu;

    if (overflow) {
      overflow.hidden = collapsed.length === 0;
    }

    if (!menu) {
      return;
    }

    // New actions rebuild the menu's items, which drops focus out of an open
    // menu, so a measurement that collapsed the same crumbs leaves them alone.
    const signature = JSON.stringify(
      collapsed.map((index) => [index, this.#labelOf(items[index]!)])
    );

    if (signature !== this.#menuSignature) {
      this.#menuSignature = signature;
      menu.actions = this.#menuActions();
    }
  }

  #menuActions(): ActionMenuItem[] {
    const items = this.#items;

    return this.#collapsed.map((index) => {
      const item = items[index]!;
      const link = this.#linkOf(item);

      if (!link) {
        return {label: this.#labelOf(item)};
      }

      return {
        label: this.#labelOf(item),
        href: link.href,
        // Follow the crumb's own link rather than the menu's copy of it, so
        // whatever handles clicks on that link handles this one too. A
        // modified click is left to open the copy wherever it asked to.
        onClick: (event: Event) => {
          const mouse = event as MouseEvent;

          if (
            mouse.metaKey ||
            mouse.ctrlKey ||
            mouse.shiftKey ||
            mouse.altKey
          ) {
            return;
          }

          event.preventDefault();
          link.click();
        },
      };
    });
  }

  /** A crumb's label, without what's slotted around it. */
  #labelOf(item: CraftBreadcrumbItem): string {
    const clone = item.cloneNode(true) as HTMLElement;
    clone.querySelectorAll(AFFIXES).forEach((el) => el.remove());

    return (clone.textContent ?? '').replace(/\s+/g, ' ').trim();
  }

  /** The link a crumb leads to: its own, or one slotted into its label. */
  #linkOf(item: CraftBreadcrumbItem): HTMLAnchorElement | null {
    return (
      item.shadowRoot?.querySelector<HTMLAnchorElement>('a[href]') ??
      [...item.querySelectorAll<HTMLAnchorElement>('a[href]')].find(
        (link) => !link.closest(AFFIXES)
      ) ??
      null
    );
  }

  /**
   * The menu's own events stay inside the trail: leaving the shadow root,
   * they'd read as the trail's.
   */
  #stopMenuEvent = (event: Event) => {
    if (event.target === event.currentTarget) {
      event.stopPropagation();
    }
  };

  override render() {
    return html`
      <nav class="breadcrumbs" aria-label="${this.label}">
        <slot @slotchange="${this.handleSlotChange}"></slot>
        <!--
          A crumb of its own so it lays out, and is separated, like the ones it
          stands in for. Its \`hidden\` state and the menu's \`actions\` are driven
          from the measurement, not bound here, so the two don't fight.
        -->
        <craft-breadcrumb-item class="breadcrumbs__overflow" hidden>
          <craft-action-menu
            class="breadcrumbs__menu"
            part="overflow-menu"
            placement="bottom-start"
            @craft-show=${this.#stopMenuEvent}
            @craft-hide=${this.#stopMenuEvent}
            @craft-after-show=${this.#stopMenuEvent}
            @craft-after-hide=${this.#stopMenuEvent}
            @craft-select=${this.#stopMenuEvent}
          >
            <craft-button
              slot="invoker"
              type="button"
              variant="plain"
              size="small"
              inherit
            >
              <craft-icon
                name="ellipsis"
                label="${t('More items')}"
              ></craft-icon>
            </craft-button>
          </craft-action-menu>
        </craft-breadcrumb-item>
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
