import {uuid} from '@lion/ui/core.js';
import {LionTabs} from '@lion/ui/tabs.js';
import {html, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import hostStyles from '@src/styles/host.styles.js';
import {t} from '@src/utilities/translate.js';
import type CraftActionMenu from '../action-menu/action-menu.js';
import type {ActionMenuItem} from '../action-menu/action-menu.types.js';
import type {SizeValue} from '@src/constants/size';
import type CraftTab from '../tab/tab.js';
import styles from './tabs.styles.js';

import '../action-menu/action-menu.js';
import '../button/button.js';
import '../icon/icon.js';

/**
 * Where the tab strip sits relative to the panels, in logical terms — so a
 * strip placed at the inline start lands on the left in LTR and on the right
 * in RTL without anything special-casing it.
 */
export const TabsPlacement = {
  BlockStart: 'block-start',
  BlockEnd: 'block-end',
  InlineStart: 'inline-start',
  InlineEnd: 'inline-end',
} as const;

export const tabsPlacements = Object.values(TabsPlacement);

export type TabsPlacementValue =
  (typeof TabsPlacement)[keyof typeof TabsPlacement];

/** @deprecated Use {@link TabsPlacement}. */
export const TabsLayout = {
  Horizontal: 'horizontal',
  Vertical: 'vertical',
} as const;

/** @deprecated Use {@link tabsPlacements}. */
export const tabsLayouts = Object.values(TabsLayout);

/** @deprecated Use {@link TabsPlacementValue}. */
export type TabsLayoutValue = (typeof TabsLayout)[keyof typeof TabsLayout];

/** The placements whose strip runs down the block axis, beside the panels. */
const INLINE_PLACEMENTS: readonly TabsPlacementValue[] = [
  TabsPlacement.InlineStart,
  TabsPlacement.InlineEnd,
];

/** Keys that move the selection along the strip (external-panel mode). */
const NAVIGATION_KEYS = new Set([
  'ArrowLeft',
  'ArrowRight',
  'ArrowUp',
  'ArrowDown',
  'Home',
  'End',
]);

/**
 * Slack, in pixels, when deciding whether a tab fits. Sub-pixel layout rounding
 * routinely leaves a row a hair wider than its container, and without this the
 * last tab flickers into the overflow menu on a container that does fit it.
 */
const FIT_TOLERANCE = 1;

/**
 * @summary A tabbed interface: a strip of `<craft-tab>` triggers over a set of
 * panels, only one of which is visible at a time.
 *
 * Tabs and panels are slotted as siblings and paired **by document order** —
 * the first `slot="tab"` child owns the first `slot="panel"` child, and so on.
 * This is Lion's convention, inherited from {@link LionTabs}, and it's what
 * lets a panel be any element (a `<div>`, a `<craft-pane>`, a form section)
 * rather than a wrapper this component defines:
 *
 *     <craft-tabs>
 *       <craft-tab slot="tab">Content</craft-tab>
 *       <div slot="panel">…</div>
 *       <craft-tab slot="tab">Settings</craft-tab>
 *       <div slot="panel">…</div>
 *     </craft-tabs>
 *
 * A mismatched count logs a warning and leaves the extras inert. Everything
 * else — the `role`/`aria-controls`/`aria-labelledby` wiring, roving tabindex,
 * arrow/Home/End keyboard navigation, and skipping `disabled` tabs — comes from
 * Lion; this component adds the Craft styling, the `placement`, and the
 * `part`s needed to restyle the strip.
 *
 * Selection is a *property*, not slotted state: read or set `selectedIndex`
 * (`selected-index`) rather than toggling `selected` on a tab, which Lion owns
 * and will overwrite.
 *
 * ## External-panel mode
 *
 * Some strips can't slot their panels: a server-rendered field layout puts the
 * tab bar in the page header and the sections inside a pane, so the two halves
 * are never siblings. Give every tab a `controls` naming its panel's `id` and
 * the strip drives those panels in place instead — toggling Craft's `hidden`
 * class and owning the same `role`/`aria`/roving-tabindex contract:
 *
 *     <craft-tabs>
 *       <craft-tab slot="tab" controls="form-tab-1">Content</craft-tab>
 *       <craft-tab slot="tab" controls="form-tab-2">Settings</craft-tab>
 *     </craft-tabs>
 *     …
 *     <section id="form-tab-1">…</section>
 *     <section id="form-tab-2" class="hidden">…</section>
 *
 * The mode is chosen from that explicit signal, not inferred from a missing
 * panel count, and it is fixed at first render — a strip is all-`controls` or
 * all-slotted, never a mix. Lion's own pairing is bypassed entirely here (it
 * indexes into a panel list that doesn't exist), so this component owns the
 * keyboard navigation in this mode; `selectedIndex` and `selected-changed`
 * behave identically either way.
 *
 * Replacing the external panels' markup (re-rendering the fragment they live
 * in) leaves the new elements unwired — call {@link CraftTabs.refresh} after.
 *
 * ## Overflow
 *
 * A horizontal strip too narrow for its tabs collapses the ones that don't fit
 * into a `<craft-action-menu>` at the end, rather than scrolling or wrapping.
 * Tabs collapse from the end in document order, and the selected tab is always
 * kept in the strip — picking a tab from the menu swaps it back into view.
 *
 * Collapsed tabs get the `hidden` attribute, so they leave the accessibility
 * tree along with the layout and are reachable only through the menu. Strips
 * placed on the inline axis run down the block axis and are left alone.
 *
 * ## Collapsible strips
 *
 * With `collapsible`, clicking the selected tab deselects it: `selectedIndex`
 * becomes `-1`, the panel region collapses to nothing, and the component is
 * just the strip. Combined with `placement="inline-start"` and icon tabs,
 * that's an icon toolbar — a rail that opens and closes a panel beside it:
 *
 *     <craft-tabs placement="inline-start" collapsible selected-index="-1">
 *       <craft-tab slot="tab"><craft-icon name="gear" label="Settings"></craft-icon></craft-tab>
 *       <div slot="panel">…</div>
 *     </craft-tabs>
 *
 * `-1` is also a valid *initial* state, with or without `collapsible`, so a
 * toolbar can start closed. Escape closes a collapsible strip from a tab, and
 * arrowing out of the closed state selects the first (or, backwards, the last)
 * tab. Without `collapsible` nothing deselects a tab but another selection.
 *

 * @slot tab - The tab triggers, one per panel. Normally `<craft-tab>`.
 * @slot panel - The panels, one per tab, in the same order. Omitted entirely
 *   in external-panel mode.
 *
 * @event selected-changed - Fired when the selected tab changes, by click or
 *   keyboard, including when a collapsible strip closes. Read `selectedIndex`
 *   off the target for the new index — `-1` when nothing is selected. Note it
 *   does not bubble, so listen on the element itself.
 *
 * @attr collapsed - Present while nothing is selected and the panel region is
 *   taking no space. Reflected and read-only — set `selectedIndex` (or let a
 *   `collapsible` strip be toggled) to change it. Exists so a surrounding
 *   layout can respond in CSS alone, without listening for `selected-changed`:
 *
 *       .body:has(craft-tabs[collapsed]) { grid-template-columns: 1fr auto; }
 *
 * @csspart base - The wrapper around both regions, which owns the placement.
 * @csspart strip - The tab row: the tablist plus the overflow menu, and what
 *   carries the rule along the strip.
 * @csspart tab-group - The `role="tablist"` element holding the tabs. Kept
 *   separate from the strip so the overflow menu isn't a child of the tablist.
 * @csspart overflow-menu - The `<craft-action-menu>` holding the collapsed
 *   tabs. Present but `hidden` while everything fits.
 * @csspart panels - The container holding the panels. `hidden` while nothing
 *   is selected, so it takes no space at all.
 *
 * @attr size - The scale of the strip: `small`, `medium` (the default), or
 *   `large`. Sets the strip's font size, which the tabs and the overflow
 *   invoker size themselves from.
 *
 * @cssproperty --c-tabs-gap - Space between the tab strip and the panels.
 *   Defaults to `--c-spacing-lg`.
 * @cssproperty --c-tabs-tab-gap - Space between adjacent tabs. Defaults to
 *   `--c-spacing-md`.
 * @cssproperty --c-tabs-border - Color of the rule along the tab strip.
 *   Defaults to `--c-color-neutral-border-quiet`.
 * @cssproperty --c-tabs-font-size - Font size of the tab strip, and so the
 *   scale of everything in it. Set by `size`; override for a scale between the
 *   named ones.
 */
export default class CraftTabs extends LionTabs {
  static override get styles() {
    return [...super.styles, hostStyles, styles];
  }

  /**
   * Where the strip sits relative to the panels: `block-start` (the default)
   * puts it above them, `block-end` below, `inline-start` before them (left in
   * LTR, right in RTL) and `inline-end` after.
   *
   * Everything follows from this: the flex direction, which edge carries the
   * rule, where each tab's selected indicator sits, the `aria-orientation`
   * hint, and whether the strip collapses its overflow (only the block
   * placements do — see the overflow notes above). The CSS is written in
   * logical properties throughout, so the inline placements swap sides in RTL
   * on their own.
   */
  @property({reflect: true}) placement: TabsPlacementValue =
    TabsPlacement.BlockStart;

  /**
   * Whether clicking the selected tab deselects it, leaving `selectedIndex` at
   * `-1` and the panel region collapsed to nothing.
   */
  @property({type: Boolean, reflect: true}) collapsible = false;

  /**
   * Which axis the tab strip runs along: `horizontal` or `vertical`.
   *
   * @deprecated Use {@link CraftTabs.placement}, which says which *side* the
   *   strip is on as well as which axis. This is an alias over it —
   *   `horizontal` maps to `block-start` and `vertical` to `inline-start`, and
   *   reading it back reports the axis of whatever placement is set.
   */
  @property({reflect: true})
  get layout(): TabsLayoutValue {
    return this.#inline ? TabsLayout.Vertical : TabsLayout.Horizontal;
  }

  set layout(value: TabsLayoutValue) {
    this.placement =
      value === TabsLayout.Vertical
        ? TabsPlacement.InlineStart
        : TabsPlacement.BlockStart;
  }

  /** Whether the strip runs down the block axis, beside the panels. */
  get #inline(): boolean {
    return INLINE_PLACEMENTS.includes(this.placement);
  }

  /** Whether nothing is selected, so there is no panel to show. */
  get #collapsed(): boolean {
    return this.selectedIndex < 0;
  }

  /**
   * How large the strip is. Expressed as a font size on the strip and nothing
   * else: `<craft-tab>`'s padding is `em`-based and the overflow invoker's
   * icon scales with its own text, so both follow from ordinary inheritance
   * rather than a second set of per-size rules. See tabs.styles.ts.
   */
  @property({reflect: true}) size: SizeValue = 'medium';

  /** Cleanup for the listeners bound to each tab in external-panel mode. */
  #externalCleanup: Array<() => void> = [];

  /** Whether this strip drives panels elsewhere in the document. */
  #external = false;

  /** Indexes of the tabs currently collapsed into the overflow menu. */
  #overflowed: number[] = [];

  /** Which tab holds the tab order while nothing is selected. */
  #entryIndex = 0;

  #resizeObserver?: ResizeObserver;

  #measureQueued = false;

  /** The tabs, narrowed — external mode only ever holds `<craft-tab>`s. */
  get #tabs(): CraftTab[] {
    return this.tabs as unknown as CraftTab[];
  }

  get #tabGroup(): HTMLElement | null {
    return this.shadowRoot?.querySelector('.tabs__tab-group') ?? null;
  }

  get #overflowMenu(): CraftActionMenu | null {
    return this.shadowRoot?.querySelector('.tabs__overflow') ?? null;
  }

  /**
   * Re-resolves the external panels and reapplies their wiring. Needed after
   * the markup holding them is replaced (an Inertia fragment re-render, say):
   * the ids come back identical but the elements are new, so the `role`,
   * `aria-labelledby`, and visibility this strip put on them are gone.
   *
   * Also re-measures the overflow. A no-op for the panel half outside
   * external-panel mode.
   */
  refresh() {
    if (this.#external) {
      this.#applyExternal();
    }

    this.#measureOverflow();
  }

  override firstUpdated(changedProperties: PropertyValues) {
    // Decided from an explicit author signal rather than an absent panel
    // count, so a strip that simply hasn't been filled in yet doesn't quietly
    // land in the wrong mode.
    this.#external = this.#tabs.some((tab) => tab.controls);

    const tabSlot = this.shadowRoot?.querySelector('slot[name="tab"]');

    if (this.#external) {
      tabSlot?.addEventListener('slotchange', this.#setupExternal);
      this.#setupExternal();
    } else {
      // Lion moves the initial selection onto the first enabled tab when the
      // first one is disabled, which would open a strip asked to start closed.
      const startsCollapsed = this.#collapsed;

      super.firstUpdated(changedProperties);

      if (startsCollapsed) {
        this.selectedIndex = -1;
      }

      // Registered after Lion's own handler above, and so runs after it: its
      // slot setup deselects every tab before restoring the selected one,
      // which leaves a collapsed strip with nothing in the tab order.
      tabSlot?.addEventListener('slotchange', this.#syncSlotted);
    }

    // A capturing listener, so a click on the selected tab is intercepted
    // before it reaches the per-tab handler that would select it again.
    this.addEventListener('click', this.#handleClick, true);
    this.addEventListener('keydown', this.#handleEscape);

    // Overflow is independent of the mode: the strip is the same either way.
    tabSlot?.addEventListener('slotchange', this.#queueMeasure);
    this.#resizeObserver = new ResizeObserver(this.#queueMeasure);
    this.#resizeObserver.observe(this);
    this.#measureOverflow();
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.#teardownExternal();
    this.#resizeObserver?.disconnect();
    this.#resizeObserver = undefined;
  }

  protected override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    // Reflected so a consumer's stylesheet can react to the strip closing —
    // e.g. a layout giving the panel's grid track its space back. Written here
    // rather than declared as a `@property` because it is derived from
    // `selectedIndex`: it's readable state, not a knob to set.
    this.toggleAttribute('collapsed', this.#collapsed);

    if (changedProperties.has('selectedIndex')) {
      if (this.#external) {
        this.#applyExternal();
      } else {
        this.#syncSlotted();
      }
    }

    if (
      changedProperties.has('selectedIndex') ||
      changedProperties.has('placement') ||
      changedProperties.has('size')
    ) {
      // The selected tab is never left in the menu, so a selection landing on
      // a collapsed tab has to redraw the strip. A size change resizes the tabs
      // without resizing the host, so the ResizeObserver never sees it.
      this.#measureOverflow();
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Collapsing
  |--------------------------------------------------------------------------
  */

  /**
   * Toggling the selected tab off. Runs in the capture phase so it can claim
   * the click before the per-tab handler — Lion's in slotted mode, ours in
   * external-panel mode — selects the tab that is already selected. One
   * handler covers both modes, and neither one has to know about collapsing.
   *
   * The click it claims is stopped where it is, so a listener bound to the
   * host (or above it) won't see the one that closes the strip. Listen for
   * `selected-changed` instead, which fires either way.
   */
  #handleClick = (event: Event) => {
    if (!this.collapsible || this.#collapsed) {
      return;
    }

    const tab = this.#tabFrom(event.target);

    if (
      !tab ||
      tab.disabled ||
      this.#tabs.indexOf(tab) !== this.selectedIndex
    ) {
      return;
    }

    event.stopPropagation();
    this.#collapse(true);
  };

  /**
   * Escape closes a collapsible strip, giving the keyboard the same way out
   * that clicking the selected tab gives the pointer. Scoped to the tabs: a
   * panel is arbitrary content, often with its own Escape handling, and this
   * has no business taking that key away from it.
   */
  #handleEscape = (event: KeyboardEvent) => {
    if (event.key !== 'Escape' || !this.collapsible || this.#collapsed) {
      return;
    }

    if (!this.#tabFrom(event.target)) {
      return;
    }

    event.stopPropagation();
    this.#collapse(true);
  };

  /** The tab an event came from, if it came from one of ours. */
  #tabFrom(target: EventTarget | null): CraftTab | null {
    if (!(target instanceof Node)) {
      return null;
    }

    return (
      this.#tabs.find((tab) => tab === target || tab.contains(target)) ?? null
    );
  }

  /**
   * Deselects everything. Lion's setter fires `selected-changed` and requests
   * an update; the tabs and panels are reconciled from `updated()`, because
   * Lion's own pass indexes its store by `selectedIndex` and bails on a miss,
   * leaving the outgoing tab looking selected.
   */
  #collapse(withFocus: boolean) {
    const tab = this.#tabs[this.selectedIndex];

    // Keep the tab order where the user left it rather than snapping it back
    // to the front of the strip.
    this.#entryIndex = this.selectedIndex;
    this.selectedIndex = -1;

    if (withFocus) {
      tab?.focus();
    }
  }

  /**
   * Applies a collapsed selection to the slotted tabs and panels, and keeps
   * the roving tabindex honest in the states Lion doesn't cover: `-1`, which
   * it skips, and the move back out of it, after which the tab that held the
   * tab order while collapsed would otherwise still hold it.
   */
  #syncSlotted = () => {
    if (this.#collapsed) {
      this.#tabs.forEach((tab) => {
        tab.removeAttribute('selected');
        tab.setAttribute('aria-selected', 'false');
      });

      this.panels.forEach((panel) => panel.removeAttribute('selected'));
    }

    this.#syncTabindex();
  };

  /**
   * Exactly one tab is in the tab order: the selected one, or — with nothing
   * selected — the tab the selection was last on, so a collapsed strip is
   * still reachable by keyboard.
   */
  #syncTabindex() {
    const tabs = this.#tabs;
    const focusable = (index: number) =>
      !!tabs[index] && !tabs[index].disabled && !tabs[index].hidden;

    let entry = this.selectedIndex;

    if (entry < 0) {
      entry = focusable(this.#entryIndex)
        ? this.#entryIndex
        : tabs.findIndex((_, index) => focusable(index));
    }

    tabs.forEach((tab, index) => {
      tab.setAttribute('tabindex', index === entry ? '0' : '-1');
    });
  }

  /**
   * Binds the interaction listeners to each tab, then wires the panels. Re-run
   * on every slot change, so it tears down first rather than stacking
   * listeners on the tabs that survived.
   */
  #setupExternal = () => {
    this.#teardownExternal();

    this.#tabs.forEach((tab, index) => {
      const onClick = () => this.#select(index, true);
      const onKeydown = (event: KeyboardEvent) => {
        // Claim the keys we navigate with before they scroll the page.
        if (NAVIGATION_KEYS.has(event.key)) {
          event.preventDefault();
        }
      };
      const onKeyup = (event: KeyboardEvent) => this.#handleKeyup(event);

      tab.addEventListener('click', onClick);
      tab.addEventListener('keydown', onKeydown);
      tab.addEventListener('keyup', onKeyup);

      this.#externalCleanup.push(() => {
        tab.removeEventListener('click', onClick);
        tab.removeEventListener('keydown', onKeydown);
        tab.removeEventListener('keyup', onKeyup);
      });
    });

    // Lion moves the initial selection off a disabled first tab; match that.
    if (this.#tabs[this.selectedIndex]?.disabled) {
      const enabled = this.#tabs.findIndex((tab) => !tab.disabled);
      if (enabled !== -1) {
        this.selectedIndex = enabled;
      }
    }

    this.#applyExternal();
  };

  #teardownExternal() {
    while (this.#externalCleanup.length) {
      this.#externalCleanup.pop()?.();
    }
  }

  /**
   * Puts the full tab/tabpanel contract on both halves: the accessibility
   * wiring, the roving tabindex, and the visibility of the external panels.
   * Idempotent, and re-run rather than diffed — panels can be replaced
   * wholesale underneath us.
   */
  #applyExternal() {
    const panels = this.#tabs.map((tab) => this.#panelFor(tab));

    // A strip whose panels are all missing is the normal state of a page whose
    // panel markup is still being injected, so it stays quiet. Some resolving
    // and some not is an author error worth reporting.
    const reportMissing = panels.some(Boolean);

    this.#tabs.forEach((tab, index) => {
      const selected = index === this.selectedIndex;
      const panel = panels[index];

      tab.id ||= `tab-${uuid()}`;
      tab.setAttribute('role', 'tab');
      tab.toggleAttribute('selected', selected);
      tab.setAttribute('aria-selected', String(selected));

      if (!panel) {
        if (reportMissing) {
          console.error(
            `<craft-tabs> found no panel with id "${tab.controls}".`,
            tab
          );
        }
        return;
      }

      tab.setAttribute('aria-controls', panel.id);
      panel.setAttribute('role', 'tabpanel');
      panel.setAttribute('aria-labelledby', tab.id);

      // Panels whose content doesn't start with something focusable need to be
      // reachable themselves (WAI-ARIA APG tabs pattern).
      if (!panel.hasAttribute('tabindex')) {
        panel.setAttribute('tabindex', '0');
      }

      // Craft's `hidden` class, which is how the server renders these panels
      // collapsed in the first place.
      panel.classList.toggle('hidden', !selected);
      panel.toggleAttribute('selected', selected);
    });

    this.#syncTabindex();
  }

  #panelFor(tab: CraftTab): HTMLElement | null {
    return tab.controls ? document.getElementById(tab.controls) : null;
  }

  /*
  |--------------------------------------------------------------------------
  | Overflow
  |--------------------------------------------------------------------------
  */

  #queueMeasure = () => {
    if (this.#measureQueued) {
      return;
    }

    // Coalesce the burst of callbacks a resize produces, and get off the
    // ResizeObserver callback before writing layout back.
    this.#measureQueued = true;
    requestAnimationFrame(() => {
      this.#measureQueued = false;
      this.#measureOverflow();
    });
  };

  /**
   * Decides which tabs fit and collapses the rest into the menu.
   *
   * Reads layout directly rather than going through a render: this runs from a
   * `ResizeObserver`, and a measurement that waited on Lit's update queue would
   * show the user a frame of the wrong strip.
   */
  #measureOverflow() {
    const group = this.#tabGroup;
    const menu = this.#overflowMenu;
    const tabs = this.#tabs;

    if (!group || !menu) {
      return;
    }

    // A strip placed on the inline axis runs down the block axis, which the
    // inline measurement below doesn't describe, so it never collapses.
    if (this.#inline || tabs.length === 0) {
      this.#applyOverflow([]);
      return;
    }

    // Measure from a clean slate — every tab laid out, the menu taking no room
    // — so a tab that has since been given space comes back.
    tabs.forEach((tab) => tab.removeAttribute('hidden'));
    menu.hidden = true;

    const gap = this.#tabGap(group);
    const widths = tabs.map((tab) => tab.offsetWidth);
    const natural = widths.reduce(
      (total, width, index) => total + width + (index ? gap : 0),
      0
    );

    if (natural <= group.clientWidth + FIT_TOLERANCE) {
      this.#applyOverflow([]);
      return;
    }

    // Showing the menu narrows the tablist, so the budget has to be read back
    // with it in place rather than estimated.
    menu.hidden = false;
    const available = group.clientWidth;

    const visible: number[] = [];
    let used = 0;

    for (let index = 0; index < tabs.length; index++) {
      const width = widths[index]! + (visible.length ? gap : 0);

      // Collapse from the first tab that doesn't fit onward: a later, narrower
      // tab jumping the queue would reorder the strip.
      if (used + width > available + FIT_TOLERANCE) {
        break;
      }

      used += width;
      visible.push(index);
    }

    // The selected tab always stays in the strip, so evict from the end until
    // it has room. `visible` is contiguous from 0, so popping walks backwards.
    const selected = this.selectedIndex;

    if (
      selected >= 0 &&
      selected < tabs.length &&
      !visible.includes(selected)
    ) {
      const selectedWidth = widths[selected]!;

      while (visible.length && used + selectedWidth + gap > available) {
        const evicted = visible.pop()!;
        used -= widths[evicted]! + (visible.length ? gap : 0);
      }

      visible.push(selected);
    }

    const kept = new Set(visible);
    this.#applyOverflow(
      tabs.map((_, index) => index).filter((index) => !kept.has(index))
    );
  }

  /** The gap between tabs, as laid out. */
  #tabGap(group: HTMLElement): number {
    const gap = parseFloat(getComputedStyle(group).columnGap);

    return Number.isFinite(gap) ? gap : 0;
  }

  #applyOverflow(overflowed: number[]) {
    const menu = this.#overflowMenu;
    const collapsed = new Set(overflowed);

    this.#tabs.forEach((tab, index) => {
      tab.toggleAttribute('hidden', collapsed.has(index));
    });

    this.#overflowed = overflowed;

    if (menu) {
      menu.hidden = overflowed.length === 0;
      menu.actions = this.#overflowActions();
    }
  }

  #overflowActions(): ActionMenuItem[] {
    return this.#overflowed.map((index) => {
      const tab = this.#tabs[index]!;

      return {
        label: tab.textContent?.trim() ?? '',
        disabled: tab.disabled,
        onClick: () => this.#selectFromMenu(index),
      };
    });
  }

  /**
   * Selecting a collapsed tab. The measurement that follows pulls it back into
   * the strip, which has to happen before the focus lands — a `hidden` element
   * can't take it.
   */
  #selectFromMenu(index: number) {
    const tab = this.#tabs[index];

    if (!tab || tab.disabled) {
      return;
    }

    // craft-action-menu closes itself when an item is clicked, but only for
    // items it can still find assigned to its content slot — which isn't the
    // case once Lion has moved that content into the overlay container. Close
    // it here rather than leave the menu hanging open over the strip.
    const menu = this.#overflowMenu;

    if (menu) {
      menu.opened = false;
    }

    this.selectedIndex = index;
    this.#measureOverflow();
    tab.focus();
  }

  #select(index: number, withFocus: boolean) {
    const tab = this.#tabs[index];

    if (!tab || tab.disabled) {
      return;
    }

    // A click on the selected tab never reaches here on a collapsible strip:
    // `#handleClick` claims it in the capture phase and collapses instead.
    // Lion's setter dispatches `selected-changed` and requests an update; the
    // panel work happens in `updated()`.
    this.selectedIndex = index;

    if (withFocus) {
      tab.focus();
    }
  }

  #handleKeyup(event: KeyboardEvent) {
    if (!NAVIGATION_KEYS.has(event.key)) {
      return;
    }

    const next = this.#nextIndex(event.key);

    if (next !== -1) {
      this.#select(next, true);
    }
  }

  /**
   * The next selectable tab for a navigation key. Skips disabled tabs, and
   * collapsed ones — those are out of the strip entirely, so arrowing into one
   * would move focus to something the user can't see.
   */
  #nextIndex(key: string): number {
    const tabs = this.#tabs;
    const selectable = (index: number) =>
      tabs[index] && !tabs[index].disabled && !tabs[index].hidden;

    if (key === 'Home') {
      return tabs.findIndex((_, index) => selectable(index));
    }

    if (key === 'End') {
      for (let index = tabs.length - 1; index >= 0; index--) {
        if (selectable(index)) {
          return index;
        }
      }

      return -1;
    }

    const step = key === 'ArrowRight' || key === 'ArrowDown' ? 1 : -1;

    // Arrowing out of the collapsed state opens the strip at the end you came
    // from — forwards lands on the first tab, backwards on the last. (Lion's
    // slotted handling arrives at the same two answers from `-1`.)
    if (this.#collapsed) {
      return this.#nextIndex(step === 1 ? 'Home' : 'End');
    }

    // Walk at most a full lap so a strip of entirely disabled tabs terminates.
    for (let hop = 1; hop <= tabs.length; hop++) {
      const offset = (this.selectedIndex + step * hop) % tabs.length;
      const index = (offset + tabs.length) % tabs.length;

      if (selectable(index)) {
        return index;
      }
    }

    return -1;
  }

  /**
   * Adds the Craft wrapper and `part`s around Lion's two regions. The
   * `tabs__*` class names and the `slot[name=tab]`/`slot[name=panel]`
   * structure are load-bearing: `LionTabs` styles against those classes and
   * queries its own shadow root for the tab slot to observe `slotchange`.
   */
  override render() {
    return html`
      <div class="tabs" part="base">
        <div class="tabs__strip" part="strip">
          <div
            class="tabs__tab-group"
            part="tab-group"
            role="tablist"
            aria-orientation="${this.#inline ? 'vertical' : 'horizontal'}"
          >
            <slot name="tab"></slot>
          </div>
          <!--
            The menu sits beside the tablist rather than inside it, so the
            tablist holds nothing but tabs. Its \`hidden\` state and \`actions\`
            are driven imperatively from the overflow measurement, not bound
            here — the measurement runs between renders and would otherwise
            fight Lit over the same attribute.
          -->
          <craft-action-menu
            class="tabs__overflow"
            part="overflow-menu"
            placement="bottom-end"
            hidden
          >
            <!--
              Slotted rather than left to the menu's generated default, which
              asks craft-button for a \`variant="inherit"\` that isn't one of its
              variants — so it falls back to the solid fill and lands a filled
              button in the middle of the tab strip.
            -->
            <craft-button
              slot="invoker"
              part="overflow-invoker"
              type="button"
              variant="plain"
              size="small"
            >
              <!--
                The name has to come from the icon: craft-button does not
                name itself, so an icon-only button with nothing else to read
                is nameless. A label on the icon (or aria-label on the host)
                is what supplies one.
              -->
              <craft-icon
                name="ellipsis"
                label="${t('More tabs')}"
              ></craft-icon>
            </craft-button>
          </craft-action-menu>
        </div>
        <!--
          Hidden rather than emptied when nothing is selected: the region has
          to take no space at all — a collapsible strip is meant to be just the
          strip — but the slot has to survive, since removing it would unassign
          the panels this strip was given.
        -->
        <div class="tabs__panels" part="panels" ?hidden="${this.#collapsed}">
          <slot name="panel"></slot>
        </div>
      </div>
    `;
  }
}

if (!customElements.get('craft-tabs')) {
  customElements.define('craft-tabs', CraftTabs);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-tabs': CraftTabs;
  }
}
