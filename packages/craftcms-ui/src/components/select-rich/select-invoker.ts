import {LionSelectInvoker} from '@lion/ui/select-rich.js';
import {html, type PropertyValues} from 'lit';
import styles from './select-invoker.styles.js';
import '../icon/icon.js';

/**
 * @summary The invoker (trigger button) for {@link CraftSelectRich}. Extends
 * Lion's select invoker to append a `chevron-down` indicator, which is hidden
 * when the select has only a single option (and therefore can't be changed).
 *
 * The selected option's `badge` content is appended to the value, and stays
 * whole while a long value is cut short.
 *
 * Not globally registered — it is wired in as the `lion-select-invoker` scoped
 * element of `CraftSelectRich` rather than used directly.
 *
 * @since 1.0
 */
export default class CraftSelectInvoker extends LionSelectInvoker {
  static override get styles() {
    return [...super.styles, styles];
  }

  /**
   * Copies of the selected option's `badge` content. They're slotted from the
   * light DOM rather than rendered in the shadow root, so the page's styles —
   * a badge's colors among them — reach them as they do the option's.
   */
  #badgeNodes: Node[] = [];

  /** Picks up badge content that changes while its option stays selected. */
  #selectedObserver = new MutationObserver(() => this.#syncBadge());

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.#selectedObserver.disconnect();
  }

  override updated(changed: PropertyValues) {
    super.updated(changed);

    if (changed.has('selectedElement')) {
      this.#selectedObserver.disconnect();

      if (this.selectedElement) {
        this.#selectedObserver.observe(this.selectedElement, {childList: true});
      }

      this.#syncBadge();
    }
  }

  override _contentTemplate() {
    const value = this.selectedElement?.childNodes.length
      ? Array.from(this.selectedElement.childNodes)
          .filter((node) => !isBadge(node))
          .map((node) => node.cloneNode(true))
      : super._contentTemplate();

    return html`<span class="value">${value}</span><slot name="badge"></slot>`;
  }

  override _afterTemplate() {
    return html`${!this.singleOption
      ? html`<craft-icon class="indicator" name="chevron-down"></craft-icon>`
      : ''}`;
  }

  #syncBadge(): void {
    for (const node of this.#badgeNodes) {
      node.parentNode?.removeChild(node);
    }

    this.#badgeNodes = this.selectedElement
      ? Array.from(this.selectedElement.childNodes)
          .filter(isBadge)
          .map((node) => node.cloneNode(true))
      : [];

    this.append(...this.#badgeNodes);
  }
}

function isBadge(node: Node): boolean {
  return node instanceof Element && node.getAttribute('slot') === 'badge';
}
