import {t} from '@craftcms/ui';
import {css, html, LitElement, nothing} from 'lit';
import {customElement} from 'lit/decorators.js';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';
import {
  getNotifications,
  markNotificationsRead,
  navigateToNotification,
  onNotificationsChange,
} from '../store';

import '@craftcms/ui/components/badge-indicator/badge-indicator';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/icon/icon';
import '@craftcms/ui/components/thumbnail/thumbnail';

type NotificationData = CraftCms.Cms.Cp.Data.NotificationData;

/**
 * Lists the current user's notifications, for the global header's
 * notification center and the command palette. Opening one marks it as read,
 * then fires a cancelable `cp-notification-navigate` event, with the URL in
 * its `detail`, before going there. Each notification is exposed as the `item`
 * part, and the first as `first-item` too.
 */
@customElement('cp-notification-list')
class CpNotificationList extends LitElement {
  static override styles = css`
    :host {
      display: block;
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

  #stopListening?: () => void;

  override connectedCallback(): void {
    super.connectedCallback();
    this.#stopListening = onNotificationsChange(() => this.requestUpdate());
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.#stopListening?.();
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
        void markNotificationsRead([notification.id]);
      }

      return;
    }

    event.preventDefault();

    try {
      await markNotificationsRead([notification.id]);
    } finally {
      const proceed = this.dispatchEvent(
        new CustomEvent('cp-notification-navigate', {
          bubbles: true,
          composed: true,
          cancelable: true,
          detail: {url: link.href},
        })
      );

      if (proceed) {
        navigateToNotification(link.href);
      }
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

  #renderNotification(notification: NotificationData, first: boolean) {
    return html`
      <article
        part=${first ? 'item first-item' : 'item'}
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
                            void markNotificationsRead([notification.id]);
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
    return html`
      <div role="list">
        ${getNotifications().map((notification, index) =>
          this.#renderNotification(notification, index === 0)
        )}
      </div>
    `;
  }
}

export default CpNotificationList;
