import {LionTabs} from '@lion/ui/tabs.js';
import {html} from 'lit';
import {property} from 'lit/decorators.js';
import hostStyles from '@src/styles/host.styles.js';
import styles from './tabs.styles.js';

export const TabsLayout = {
  Horizontal: 'horizontal',
  Vertical: 'vertical',
} as const;

export const tabsLayouts = Object.values(TabsLayout);

export type TabsLayoutValue = (typeof TabsLayout)[keyof typeof TabsLayout];

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
 * @slot tab - The tab triggers, one per panel. Normally `<craft-tab>`.
 * @slot panel - The panels, one per tab, in the same order.
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
