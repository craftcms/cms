import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import type CraftActionItem from '../action-item/action-item.js';
import type {
  ActionMenuItem,
  ActionMenuItemButton,
  ActionMenuItemLink,
} from '../action-menu/action-menu.types.js';
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

  #resizeObserver?: ResizeObserver;
  #contentObserver?: MutationObserver;
  #measureQueued = false;

  /**
   * The crumbs, in trail order, across the `first` slot and the default. A
   * crumb hidden by its author, rather than collapsed here, isn't part of the
   * trail.
   */
  get #items(): CraftBreadcrumbItem[] {
    const slots = this.shadowRoot?.querySelectorAll<HTMLSlotElement>(
      'slot[name="first"], slot:not([name])'
    );

    return [...(slots ?? [])]
      .flatMap((slot) => slot.assignedElements({flatten: true}))
      .filter(
        (el): el is CraftBreadcrumbItem =>
          el.tagName.toLowerCase() === 'craft-breadcrumb-item' &&
          (!(el as HTMLElement).hidden || el.hasAttribute('data-collapsed'))
      )
      .sort((a, b) =>
        a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1
      );
  }

  get #nav(): HTMLElement | null {
    return this.shadowRoot?.querySelector('.breadcrumbs') ?? null;
  }

  get #overflowItem(): CraftBreadcrumbItem | null {
    return this.shadowRoot?.querySelector('.breadcrumbs__overflow') ?? null;
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

      // The first crumb goes in a slot of its own, ahead of the menu, so the
      // menu follows it in focus and reading order, not only on screen.
      // Crumbs forwarded through another slot stay where they are.
      if (item.parentElement === this) {
        if (index === 0) {
          item.slot = 'first';
        } else if (item.slot === 'first') {
          item.removeAttribute('slot');
        }
      }
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

    // A crumb's label or link can change in place, as when a framework patches
    // the trail for the next page, without resizing the trail or changing
    // which crumbs are slotted.
    this.#contentObserver = new MutationObserver((records) => {
      if (!records.every(this.#isOwnSeparatorChange)) {
        this.#queueMeasure();
      }
    });
    this.#contentObserver.observe(this, {
      subtree: true,
      childList: true,
      characterData: true,
      attributeFilter: ['href'],
    });
  }

  override disconnectedCallback() {
    this.#resizeObserver?.disconnect();
    this.#resizeObserver = undefined;
    this.#contentObserver?.disconnect();
    this.#contentObserver = undefined;
    super.disconnectedCallback();
  }

  /** Whether a mutation only adds or removes the separators drawn here. */
  #isOwnSeparatorChange = (record: MutationRecord) =>
    record.type === 'childList' &&
    [...record.addedNodes, ...record.removedNodes].every(
      (node) => node instanceof Element && node.hasAttribute('data-default')
    );

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
      // Marked as collapsed here, so it's told apart from a crumb its author
      // hid, which stays hidden.
      item.toggleAttribute('data-collapsed', hidden.has(index));
      item.toggleAttribute('hidden', hidden.has(index));
    });

    this.#collapsed = collapsed;

    const overflow = this.#overflowItem;

    if (overflow) {
      overflow.hidden = collapsed.length === 0;
    }
  }

  /**
   * What the menu lists, read when it opens: the collapsed crumbs in trail
   * order, as links. A crumb with a menu of its own beside it, such as a
   * section or site switcher, brings that menu's items along under its label,
   * so collapsing it doesn't take them away.
   */
  #menuActions = (): ActionMenuItem[] => {
    const items = this.#items;

    return this.#collapsed.flatMap((index): ActionMenuItem[] => {
      const item = items[index];

      if (!item) {
        return [];
      }

      const label = this.#textOf(item, AFFIXES);
      const link = this.#linkOf(item);
      const crumb: ActionMenuItemButton | ActionMenuItemLink = link
        ? {type: 'link', label, href: link.href, onClick: this.#follow(link)}
        : {label};
      const suffixItems = this.#suffixItemsOf(item);

      if (suffixItems.length === 0) {
        return [crumb];
      }

      // A switcher usually lists the crumb's own page among its choices, so the
      // crumb's link would only repeat it.
      const listed =
        !link || suffixItems.some((choice) => choice.href === link.href);

      return [
        {
          type: 'group',
          heading: label,
          items: [...(listed ? [] : [crumb]), ...suffixItems],
        },
      ];
    });
  };

  /** The items of a menu slotted beside a crumb's label, standing in for it. */
  #suffixItemsOf(
    item: CraftBreadcrumbItem
  ): Array<ActionMenuItemButton | ActionMenuItemLink> {
    return [...item.children]
      .filter((el) => el.slot === 'suffix')
      .map((el) =>
        el.matches('craft-action-menu')
          ? el
          : el.querySelector('craft-action-menu')
      )
      .flatMap((menu) =>
        menu
          ? [...menu.querySelectorAll<CraftActionItem>('craft-action-item')]
              .filter(
                (source) =>
                  source.closest('craft-action-menu') === menu && !source.hidden
              )
              .map((source) => {
                const common = {
                  label: this.#textOf(source, '[slot]'),
                  icon: source.icon ?? undefined,
                  variant: source.variant,
                  // The switcher's selected choice, such as the current
                  // site, stays marked as the current one.
                  current: source.current || source.checked || undefined,
                  disabled: source.disabled || undefined,
                  onClick: this.#follow(source),
                };

                return source.href
                  ? {
                      ...common,
                      type: 'link' as const,
                      href: new URL(source.href, document.baseURI).href,
                    }
                  : common;
              })
          : []
      );
  }

  /**
   * Handles choosing a menu item by pressing the control it stands in for, so
   * whatever handles that control handles this too. A modified click on a
   * link is left to open the menu's copy wherever it asked to.
   */
  #follow(target: HTMLElement) {
    return (event: Event) => {
      const mouse = event as MouseEvent;
      const isLink = target.matches('a[href], [href]');

      if (
        isLink &&
        (mouse.metaKey || mouse.ctrlKey || mouse.shiftKey || mouse.altKey)
      ) {
        return;
      }

      event.preventDefault();

      // A menu item's link or button is in its shadow root, and an item in a
      // closed menu may not have rendered it yet. Pressing that, rather than
      // the item, follows a plain link; the press still reaches the item.
      const item = target as Partial<CraftActionItem>;
      item.renderNow?.();
      const control =
        target.shadowRoot?.querySelector<HTMLElement>('[part~="control"]') ??
        target;

      // A whole press, not just a click: some links go on `mouseup`, such as
      // Inertia's when they prefetch on click, and only cancel the click.
      const init = {bubbles: true, cancelable: true, composed: true, button: 0};
      control.dispatchEvent(new MouseEvent('mousedown', init));
      control.dispatchEvent(new MouseEvent('mouseup', init));
      control.click();
    };
  }

  /** An element's text, without the parts matching `skip`. */
  #textOf(element: Element, skip: string): string {
    const clone = element.cloneNode(true) as HTMLElement;
    clone.querySelectorAll(skip).forEach((el) => el.remove());

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
        <slot name="first" @slotchange="${this.handleSlotChange}"></slot>
        <!--
          A crumb of its own so it lays out, and is separated, like the ones it
          stands in for. Its \`hidden\` state is driven from the measurement, not
          bound here, so the two don't fight. The menu reads its items when it
          opens.
        -->
        <craft-breadcrumb-item class="breadcrumbs__overflow" hidden>
          <craft-action-menu
            class="breadcrumbs__menu"
            part="overflow-menu"
            placement="bottom-start"
            .actions=${this.#menuActions}
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
