import type {CSSResultGroup} from 'lit';
import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import hostStyles from '@src/styles/host.styles.js';
import styles from './tab.styles.js';

/**
 * @summary A single trigger within a `<craft-tabs>` strip. Goes in the `tab`
 * slot, paired by document order with the panel at the same position.
 *
 *     <craft-tab slot="tab">Content</craft-tab>
 *
 * Deliberately thin: `<craft-tabs>` (via Lion) owns the accessibility
 * contract, assigning this element its `id`, `role="tab"`, `aria-controls`,
 * `aria-selected`, and `tabindex`, and toggling the `selected` attribute. The
 * only state this component owns is `disabled`.
 *
 * @slot - The tab's label. Any inline content; keep it short enough to sit in
 *   a strip.
 *
 * @cssproperty --c-tab-spacing-inline - Inline padding. Defaults to `1em`, so
 *   the tab scales with whatever font size it inherits — which is how
 *   `<craft-tabs size>` resizes its tabs without touching them.
 * @cssproperty --c-tab-spacing-block - Block padding. Defaults to `0.5em`; see
 *   above.
 * @cssproperty --c-tab-border-active - Color of the selected indicator.
 *   Defaults to `--c-color-accent-border-loud`.
 * @cssproperty --c-tab-text-disabled - Label color while disabled. Defaults to
 *   `--c-status-disabled-text`.
 * @cssproperty --c-tab-indicator-inset-block-start - Indicator geometry. Set
 *   by `<craft-tabs>` per `placement`; override to reposition the indicator.
 * @cssproperty --c-tab-indicator-inset-block-end - See above.
 * @cssproperty --c-tab-indicator-inset-inline-start - See above.
 * @cssproperty --c-tab-indicator-inset-inline-end - See above.
 * @cssproperty --c-tab-indicator-block-size - See above.
 * @cssproperty --c-tab-indicator-inline-size - See above.
 */
export default class CraftTab extends LitElement {
  static override styles: CSSResultGroup = [hostStyles, styles];

  /**
   * Whether the tab can be selected. Disabled tabs are skipped by the strip's
   * arrow-key navigation, and the styles below drop them out of hit-testing so
   * a click can't reach the handler `<craft-tabs>` binds.
   */
  @property({type: Boolean, reflect: true}) disabled = false;

  /**
   * The `id` of a panel living elsewhere in the document, for strips whose
   * panels can't be slotted alongside their tabs — a server-rendered field
   * layout, say, where the tab bar sits in the page header and the sections
   * are inside a pane. Setting this on every tab in a strip switches
   * `<craft-tabs>` into external-panel mode; see its docs.
   */
  @property({reflect: true}) controls: string | null = null;

  /**
   * Whether this is the strip's selected tab.
   *
   * Read-only by design, and intentionally *not* a reactive property: the
   * `selected` attribute is written directly by `<craft-tabs>`, so declaring
   * it reactive here would let a stale property value reflect back over what
   * the strip set. To change the selection, set `selectedIndex` on the strip.
   */
  get selected(): boolean {
    return this.hasAttribute('selected');
  }

  override render() {
    return html`<slot></slot>`;
  }
}

if (!customElements.get('craft-tab')) {
  customElements.define('craft-tab', CraftTab);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-tab': CraftTab;
  }
}
