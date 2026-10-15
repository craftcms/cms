import {html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './badge-indicator.styles.js';
import {classMap} from 'lit/directives/class-map.js';
import '@shoelace-style/shoelace/dist/components/visually-hidden/visually-hidden.js';

/**
 * @summary A small marker showing that something is new or has changed, with
 * an optional count.
 *
 * It attaches to whatever it sits beside — a nav item, a tab, a button — and
 * says only "there is something here". What that something is has to come from
 * the text around it or from `alt-text`.
 */

export default class CraftBadgeIndicator extends LitElement {
  static override styles = [styles];

  /** Alternative text if the badge is not decorative */
  @property({attribute: 'alt-text'}) altText: string | null = null;

  /** Displays a number on the badge. If using `badgeCount`, the `badgeCountSuffix` should also be used to describe what the number represents. */
  @property({attribute: 'badge-count'}) badgeCount: number | null = null;

  /** Visually hidden text that comes after the count to provide additional context. */
  @property({attribute: 'badge-count-suffix'}) badgeCountSuffix: string | null =
    null;

  /**
   * The largest count shown in full. Anything above it shows as `{max}+`, as
   * an unread count past 99 stops being useful and would not fit. Set it to
   * `Infinity` for a count that's a tally rather than a nudge, such as how many
   * elements a source has, so the badge grows to fit the whole number.
   */
  @property({type: Number}) max: number = 99;

  /** Theme variant of the badge indicator. Defaults to "primary" */
  @property() variant: 'primary' | 'secondary' | 'inverse' = 'primary';

  /**
   * Id of the badge, so the element it marks can reference it. Generated when
   * not supplied.
   */
  @property()
  override id: string;

  constructor() {
    super();
    this.id =
      this.id || `badge-${Math.floor(Math.random() * 1000000000).toString()}`;
  }

  private showCount() {
    return this.badgeCount !== null && this.badgeCount > 0;
  }

  private truncatedNumber(): string {
    const count = this.badgeCount ?? 0;
    const format = (value: number) =>
      new Intl.NumberFormat(document.documentElement.lang || undefined).format(
        value
      );

    return count > this.max
      ? `${format(this.max)}+`
      : format(count);
  }

  private getBadgeRole() {
    return this.altText ? 'img' : nothing;
  }

  private getLabelId() {
    return `${this.id}-label`;
  }

  private renderBadgeContents() {
    return html`
      ${this.showCount()
        ? html`
            <span class="number">${this.truncatedNumber()}</span>
            <sl-visually-hidden>${this.badgeCountSuffix}</sl-visually-hidden>
          `
        : nothing}
      ${this.altText
        ? html`
            <sl-visually-hidden id=${this.getLabelId()}
              >${this.altText}</sl-visually-hidden
            >
          `
        : nothing}
    `;
  }

  override render() {
    return html`
      <div
        part="badge"
        id=${this.id}
        class="${classMap({
          'badge-indicator': true,
          'badge-indicator--with-number': this.showCount(),
          'badge-indicator--secondary': this.variant === 'secondary',
          'badge-indicator--inverse': this.variant === 'inverse',
        })}"
        role="${this.getBadgeRole()}"
        aria-labelledby="${this.altText ? this.getLabelId() : nothing}"
      >
        ${this.renderBadgeContents()}
      </div>
    `;
  }
}

if (!customElements.get('craft-badge-indicator')) {
  customElements.define('craft-badge-indicator', CraftBadgeIndicator);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-badge-indicator': CraftBadgeIndicator;
  }
}
