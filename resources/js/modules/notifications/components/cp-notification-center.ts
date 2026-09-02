import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {t} from '@craftcms/ui';
import {css, html, LitElement, nothing} from 'lit';
import {customElement, property, query} from 'lit/decorators.js';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';
import {markRead as markNotificationsRead} from '@actions/NotificationsController';

import '@craftcms/ui/components/badge-indicator/badge-indicator';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/icon/icon';
import '@craftcms/ui/components/popover/popover';
import '@craftcms/ui/components/thumbnail/thumbnail';

type NotificationData = CraftCms.Cms.Cp.Data.NotificationData;

@customElement('cp-notification-center')
class CpNotificationCenter extends LitElement {
  static override styles = css`
    :host {
      display: contents;
    }

    .notification-trigger {
      position: relative;
    }

    .notification-trigger__icon {
      font-size: 1.1em;
    }

    .notification-trigger__badge {
      display: inline-flex;
      position: absolute;
      inset-block-start: -0.125rem;
      inset-inline-end: -0.125rem;
    }

    .notification-trigger__badge::part(badge) {
      background-color: var(--c-color-danger-fill-loud);
    }

    craft-popover::part(popup) {
      width: min(26rem, calc(100vw - 2rem));
      max-width: none;
      overflow: hidden;
    }

    .notification-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 0.75rem 1rem;
    }

    .notification-heading {
      margin: 0;
      font-size: var(--c-text-lg);
      font-weight: var(--font-weight-semibold);
    }

    .notification-list {
      max-height: min(34rem, calc(100vh - 8rem));
      overflow-y: auto;
      border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    }

    .notification-item {
      position: relative;
      display: flex;
      gap: 0.75rem;
      padding: 1rem;
      border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    }

    .notification-item--read {
      opacity: 0.7;
    }

    .notification-card-link {
      position: absolute;
      z-index: 1;
      inset: 0;
    }

    .notification-visual {
      display: flex;
      flex: none;
      align-items: center;
      justify-content: center;
      width: 2.5rem;
      height: 2.5rem;
      overflow: hidden;
      border-radius: var(--c-radius-md);
    }

    .notification-visual--icon {
      color: var(--c-text-default);
      background-color: var(--c-color-neutral-fill-quiet);
    }

    .notification-visual--icon craft-icon {
      width: 1.5rem;
      height: 1.5rem;
      font-size: 1.5rem;
    }

    .notification-visual--icon svg {
      display: block;
      width: 100%;
      height: 100%;
    }

    .notification-visual craft-thumbnail {
      --c-thumbnail-size: 100%;
    }

    .notification-visual img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .notification-content {
      min-width: 0;
      flex-grow: 1;
    }

    .notification-title-row {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .notification-title {
      min-width: 0;
      margin: 0;
      font-size: var(--c-text-lg);
      font-weight: var(--font-weight-semibold);
      line-height: 1.25;
    }

    .notification-message {
      position: relative;
      margin-block-start: 0.25rem;
      font-size: var(--c-text-base);
    }

    .notification-message a {
      position: relative;
      z-index: 2;
    }

    .notification-message p {
      margin: 0;
    }

    .notification-meta {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.25rem;
      margin-block-start: 0.5rem;
      color: var(--c-text-quiet);
      font-size: var(--c-text-sm);
    }

    .notification-actions {
      position: relative;
      z-index: 2;
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-block-start: 0.75rem;
    }
  `;

  @property({type: Array})
  notifications: NotificationData[] = window.Craft.notifications ?? [];

  @query('craft-popover')
  private popoverElement?: HTMLElement & {hide(): Promise<void>};

  get #unread(): NotificationData[] {
    return this.notifications.filter((notification) => notification.unread);
  }

  get #triggerLabel(): string {
    return this.#unread.length
      ? t('Notifications, {count} unread', {count: this.#unread.length})
      : t('Notifications');
  }

  async #markRead(ids: string[]): Promise<void> {
    const changedIds = this.notifications
      .filter(
        (notification) => notification.unread && ids.includes(notification.id)
      )
      .map((notification) => notification.id);

    if (!changedIds.length) {
      return;
    }

    this.#setUnread(changedIds, false);

    try {
      await actionClient.post(markNotificationsRead().url, {ids: changedIds});
    } catch {
      this.#setUnread(changedIds, true);
      Craft.cp?.displayError?.(t('Couldn’t mark notifications as read.'));
    }
  }

  #setUnread(ids: string[], unread: boolean): void {
    this.notifications = this.notifications.map((notification) =>
      ids.includes(notification.id) ? {...notification, unread} : notification
    );
  }

  #preservesNativeNavigation(
    event: MouseEvent,
    link: HTMLAnchorElement
  ): boolean {
    return (
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey ||
      (link.target !== '' && link.target !== '_self')
    );
  }

  async #handleLinkClick(
    notification: NotificationData,
    event: MouseEvent
  ): Promise<void> {
    const link = event
      .composedPath()
      .find(
        (target): target is HTMLAnchorElement =>
          target instanceof HTMLAnchorElement
      );

    if (!link) {
      return;
    }

    if (this.#preservesNativeNavigation(event, link)) {
      if (notification.unread) {
        void this.#markRead([notification.id]);
      }

      return;
    }

    event.preventDefault();

    try {
      await this.#markRead([notification.id]);
    } finally {
      this.#navigate(link.href);
    }
  }

  #navigate(url: string): void {
    void this.popoverElement?.hide();

    if (new URL(url).origin !== window.location.origin) {
      window.location.assign(url);
      return;
    }

    const proceed = window.dispatchEvent(
      new CustomEvent('action:redirect', {
        cancelable: true,
        detail: {url},
      })
    );

    if (proceed) {
      window.location.assign(url);
    }
  }

  #renderVisual(notification: NotificationData) {
    return html`
      <div
        class="notification-visual ${!notification.image
          ? 'notification-visual--icon'
          : ''}"
      >
        ${notification.image
          ? html`
              <craft-thumbnail checkered="false">
                <img
                  src=${notification.image}
                  alt=${notification.imageAlt ?? ''}
                />
              </craft-thumbnail>
            `
          : notification.icon
            ? html`
                <craft-icon
                  name=${notification.icon}
                  appearance="plain"
                  aria-hidden="true"
                ></craft-icon>
              `
            : nothing}
      </div>
    `;
  }

  #renderNotification(notification: NotificationData) {
    return html`
      <article
        class="notification-item ${notification.unread
          ? ''
          : 'notification-item--read'}"
        role="listitem"
        @click=${(event: MouseEvent) =>
          this.#handleLinkClick(notification, event)}
      >
        ${notification.url
          ? html`
              <a
                class="notification-card-link"
                href=${notification.url}
                aria-label=${notification.title ?? t('Open notification')}
              ></a>
            `
          : nothing}
        ${this.#renderVisual(notification)}

        <div class="notification-content">
          <div class="notification-title-row">
            ${notification.title
              ? html`<h3 class="notification-title">${notification.title}</h3>`
              : nothing}
            ${notification.unread
              ? html`
                  <craft-badge-indicator
                    alt-text=${t('Unread')}
                  ></craft-badge-indicator>
                `
              : nothing}
          </div>

          <div class="notification-message">
            ${unsafeHTML(notification.messageHtml)}
          </div>

          <div class="notification-meta">
            ${notification.byline
              ? html`
                  <span>${notification.byline}</span>
                  <span aria-hidden="true">·</span>
                `
              : nothing}
            <time datetime=${notification.createdAt}
              >${notification.createdAtLabel}</time
            >
          </div>

          ${notification.buttons.length || notification.unread
            ? html`
                <div class="notification-actions">
                  ${notification.buttons.map(
                    (button) => html`
                      <craft-button
                        .href=${button.url}
                        .target=${button.target}
                        .icon=${button.icon}
                        variant=${button.variant}
                        size="small"
                      >
                        ${button.label}
                      </craft-button>
                    `
                  )}
                  ${notification.unread
                    ? html`
                        <craft-button
                          type="button"
                          size="small"
                          variant="plain"
                          @click=${(event: MouseEvent) => {
                            event.stopPropagation();
                            void this.#markRead([notification.id]);
                          }}
                        >
                          ${t('Mark as read')}
                        </craft-button>
                      `
                    : nothing}
                </div>
              `
            : nothing}
        </div>
      </article>
    `;
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
          variant="none"
          aria-label=${this.#triggerLabel}
        >
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
        </craft-button>

        <div slot="content">
          <header class="notification-header">
            <h2 class="notification-heading">${t('Notifications')}</h2>
            ${this.#unread.length
              ? html`
                  <craft-button
                    type="button"
                    size="small"
                    variant="plain"
                    @click=${() =>
                      this.#markRead(
                        this.#unread.map((notification) => notification.id)
                      )}
                  >
                    ${t('Mark all as read')}
                  </craft-button>
                `
              : nothing}
          </header>
          <div class="notification-list" role="list">
            ${this.notifications.map((notification) =>
              this.#renderNotification(notification)
            )}
          </div>
        </div>
      </craft-popover>
    `;
  }
}

export default CpNotificationCenter;
