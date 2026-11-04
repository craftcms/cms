import {t} from '@craftcms/ui';
import {css, html, LitElement, nothing} from 'lit';
import {customElement, property, query} from 'lit/decorators.js';
import {
  getNotifications,
  markNotificationsRead,
  onNotificationsChange,
  setNotifications,
} from '../store';

import '@craftcms/ui/components/badge-indicator/badge-indicator';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/icon/icon';
import '@craftcms/ui/components/popover/popover';
import './cp-notification-list';

type NotificationData = CraftCms.Cms.Cp.Data.NotificationData;

@customElement('cp-notification-center')
class CpNotificationCenter extends LitElement {
  static override styles = css`
    :host {
      display: contents;
    }

    .notification-trigger {
      --c-icon-size: 1em;
      position: relative;
    }

    .notification-trigger__badge {
      --badge-border-width: 2px;
      display: inline-flex;
      position: absolute;
      inset-block-start: 0;
      inset-inline-end: 0;
    }

    .notification-trigger__badge::part(badge) {
      background-color: var(--c-color-danger-fill-loud);
    }

    craft-popover::part(popup) {
      width: min(26rem, calc(100vw - 2rem));
      max-width: none;
      max-height: none;
      overflow: hidden;
    }

    .notification-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding-block: 0.75rem;
      padding-inline: 1rem;
    }

    .notification-heading {
      margin: 0;
      font-size: var(--c-text-lg);
      font-weight: var(--font-weight-semibold);
    }

    cp-notification-list {
      max-height: min(34rem, calc(100vh - 8rem));
      overflow-y: auto;
      border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    }
  `;

  /** The page's notifications, which replace the shared ones. */
  @property({type: Array, attribute: false})
  set notifications(value: NotificationData[]) {
    setNotifications(value);
  }

  get notifications(): NotificationData[] {
    return getNotifications();
  }

  @query('craft-popover')
  private popoverElement?: HTMLElement & {hide(): Promise<void>};

  @query('.notification-heading')
  private headingElement?: HTMLElement;

  #stopListening?: () => void;

  override connectedCallback(): void {
    super.connectedCallback();
    this.#stopListening = onNotificationsChange(() => this.requestUpdate());
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.#stopListening?.();
  }

  get #unread(): NotificationData[] {
    return this.notifications.filter((notification) => notification.unread);
  }

  get #triggerLabel(): string {
    return this.#unread.length
      ? t('Notifications, {count} unread', {count: this.#unread.length})
      : t('Notifications');
  }

  async #markAllRead(): Promise<void> {
    const marking = markNotificationsRead(
      this.#unread.map((notification) => notification.id)
    );
    await this.updateComplete;
    this.headingElement?.focus();
    await marking;
  }

  protected override render() {
    if (!this.notifications.length) {
      return nothing;
    }

    return html`
      <craft-popover placement="bottom-end" .distance=${8}>
        <craft-button
          slot="invoker"
          class="notification-trigger"
          type="button"
          size="small"
          variant="plain"
          icon
          aria-label=${this.#triggerLabel}
        >
          <span slot="prefix">
            <craft-icon
              class="notification-trigger__icon"
              name="bell"
            ></craft-icon>
            ${this.#unread.length
              ? html`
                  <craft-badge-indicator
                    class="notification-trigger__badge"
                  ></craft-badge-indicator>
                `
              : nothing}
          </span>
        </craft-button>

        <div slot="content">
          <header class="notification-header">
            <h2 class="notification-heading" tabindex="-1">
              ${t('Notifications')}
            </h2>
            ${this.#unread.length
              ? html`
                  <craft-button
                    type="button"
                    size="small"
                    variant="plain"
                    @click=${() => this.#markAllRead()}
                  >
                    ${t('Mark all as read')}
                  </craft-button>
                `
              : nothing}
          </header>
          <cp-notification-list
            @cp-notification-navigate=${() => void this.popoverElement?.hide()}
          ></cp-notification-list>
        </div>
      </craft-popover>
    `;
  }
}

export default CpNotificationCenter;
