import {uuid} from '@lion/ui/core.js';
import {LionTabs} from '@lion/ui/tabs.js';
import {html, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import hostStyles from '@src/styles/host.styles.js';
import type CraftTab from '../tab/tab.js';
import styles from './tabs.styles.js';

export const TabsLayout = {
  Horizontal: 'horizontal',
  Vertical: 'vertical',
} as const;

export const tabsLayouts = Object.values(TabsLayout);

export type TabsLayoutValue = (typeof TabsLayout)[keyof typeof TabsLayout];

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
 * Lion; this component adds the Craft styling, the `layout` axis, and the
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
 * @slot tab - The tab triggers, one per panel. Normally `<craft-tab>`.
 * @slot panel - The panels, one per tab, in the same order. Omitted entirely
 *   in external-panel mode.
 *
 * @event selected-changed - Fired when the selected tab changes, by click or
 *   keyboard. Read `selectedIndex` off the target for the new index.
 *
 * @csspart base - The wrapper around both regions, which owns the layout axis.
 * @csspart tab-group - The `role="tablist"` strip holding the tabs.
 * @csspart panels - The container holding the panels.
 *
 * @cssproperty --c-tabs-gap - Space between the tab strip and the panels.
 *   Defaults to `--c-spacing-lg`.
 * @cssproperty --c-tabs-tab-gap - Space between adjacent tabs. Defaults to
 *   `--c-spacing-md`.
 * @cssproperty --c-tabs-border - Color of the rule along the tab strip.
 *   Defaults to `--c-color-neutral-border-quiet`.
 */
export default class CraftTabs extends LionTabs {
  static override get styles() {
    return [...super.styles, hostStyles, styles];
  }

  /**
   * Which axis the tab strip runs along: `horizontal` puts the tabs above the
   * panels, `vertical` beside them. Only affects presentation and the
   * `aria-orientation` hint — Lion's arrow-key handling accepts both axes
   * either way.
   */
  @property({reflect: true}) layout: TabsLayoutValue = TabsLayout.Horizontal;

  /** Cleanup for the listeners bound to each tab in external-panel mode. */
  #externalCleanup: Array<() => void> = [];

  /** Whether this strip drives panels elsewhere in the document. */
  #external = false;

  /** The tabs, narrowed — external mode only ever holds `<craft-tab>`s. */
  get #tabs(): CraftTab[] {
    return this.tabs as unknown as CraftTab[];
  }

  /**
   * Re-resolves the external panels and reapplies their wiring. Needed after
   * the markup holding them is replaced (an Inertia fragment re-render, say):
   * the ids come back identical but the elements are new, so the `role`,
   * `aria-labelledby`, and visibility this strip put on them are gone.
   *
   * A no-op outside external-panel mode.
   */
  refresh() {
    if (this.#external) {
      this.#applyExternal();
    }
  }

  override firstUpdated(changedProperties: PropertyValues) {
    // Decided from an explicit author signal rather than an absent panel
    // count, so a strip that simply hasn't been filled in yet doesn't quietly
    // land in the wrong mode.
    this.#external = this.#tabs.some((tab) => tab.controls);

    if (!this.#external) {
      super.firstUpdated(changedProperties);
      return;
    }

    const tabSlot = this.shadowRoot?.querySelector('slot[name="tab"]');
    tabSlot?.addEventListener('slotchange', this.#setupExternal);

    this.#setupExternal();
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.#teardownExternal();
  }

  protected override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (this.#external && changedProperties.has('selectedIndex')) {
      this.#applyExternal();
    }
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
      tab.setAttribute('tabindex', selected ? '0' : '-1');

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
  }

  #panelFor(tab: CraftTab): HTMLElement | null {
    return tab.controls ? document.getElementById(tab.controls) : null;
  }

  #select(index: number, withFocus: boolean) {
    const tab = this.#tabs[index];

    if (!tab || tab.disabled) {
      return;
    }

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

  /** The next selectable tab for a navigation key, skipping disabled ones. */
  #nextIndex(key: string): number {
    const tabs = this.#tabs;
    const selectable = (index: number) => tabs[index] && !tabs[index].disabled;

    if (key === 'Home') {
      return tabs.findIndex((tab) => !tab.disabled);
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
        <div
          class="tabs__tab-group"
          part="tab-group"
          role="tablist"
          aria-orientation="${this.layout}"
        >
          <slot name="tab"></slot>
        </div>
        <div class="tabs__panels" part="panels">
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
