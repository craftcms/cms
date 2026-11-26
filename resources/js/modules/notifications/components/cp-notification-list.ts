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
import '@craftcms/ui/components/media-item/media-item';
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

    /* Padding rather than a gap, so the command palette can trim it through the item parts. */
    .notification-item {
      position: relative;
      display: block;
      padding-block: calc(var(--c-spacing) * 1.5);
      padding-inline: var(--c-spacing-lg);
    }

    .notification-item:first-child {
      padding-block-start: calc(var(--c-spacing) * 3);
    }

    .notification-item:last-child {
      padding-block-end: calc(var(--c-spacing) * 3);
    }

    .notification-item--read {
      opacity: 0.7;
    }

    .notification-item:has(.notification-card-link):hover:not(
        :has(craft-button:hover)
      ) {
      background-color: color-mix(in oklab, currentColor 5%, transparent);

      .notification-title {
        text-decoration-line: underline;
      }
    }

    .notification-card-link {
      position: absolute;
      z-index: 1;
      inset: 0;
    }

    .notification-thumbnail img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .notification-title-row {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .notification-title {
      min-width: 0;
      margin: 0;
      font-size: var(--c-text-base);
      font-weight: var(--font-weight-semibold);
      line-height: 1.25;
    }

    .notification-message {
      position: relative;
      color: var(--c-text-default);
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
      gap: var(--c-spacing-sm);
      margin-block-start: var(--c-spacing-xs);
    }

    .notification-mark-read {
      position: relative;
      z-index: 2;
      font-size: inherit;
    }

    .notification-actions {
      position: relative;
      z-index: 2;
      display: flex;
      flex-wrap: wrap;
      gap: var(--c-spacing-md);
      margin-block-start: var(--c-spacing-md);
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

  // CONFLICT-REVIEW: 6.x moved this list out of cp-notification-center, so the
  // panel heading that used to be the fallback focus target is no longer
  // reachable from here. Focus still moves to the notification's link; when a
  // notification has no link, focus isn't moved.
  async #markReadFromButton(
    ids: string[],
    focusTarget: () => HTMLElement | null | undefined
  ): Promise<void> {
    const marking = markNotificationsRead(ids);
    await this.updateComplete;
    focusTarget()?.focus();
    await marking;
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

  #renderNotification(notification: NotificationData, first: boolean) {
    return html`
      <article
        part=${first ? 'item first-item' : 'item'}
        class="notification-item ${
          notification.unread ? '' : 'notification-item--read'
        }"
        role="listitem"
        @click=${(event: MouseEvent) =>
          this.#handleLinkClick(notification, event)}
      >
        ${
          notification.url
            ? html`
                <a
                  class="notification-card-link"
                  href=${notification.url}
                  aria-label=${notification.title ?? t('Open notification')}
                ></a>
              `
            : nothing
        }
        <craft-media-item
          icon=${notification.image ? nothing : (notification.icon ?? 'bell')}
        >
          ${
            notification.image
              ? html`
                  <craft-thumbnail
                    slot="thumbnail"
                    class="notification-thumbnail"
                  >
                    <img
                      src=${notification.image}
                      alt=${notification.imageAlt ?? ''}
                    />
                  </craft-thumbnail>
                `
              : nothing
          }
          <div slot="heading" class="notification-title-row">
            ${
              notification.title
                ? html`<h3 class="notification-title">
                    ${notification.title}
                  </h3>`
                : nothing
            }
            ${
              notification.unread
                ? html`
                    <craft-badge-indicator
                      alt-text=${t('Unread')}
                    ></craft-badge-indicator>
                  `
                : nothing
            }
          </div>

          <div class="notification-message">
            ${unsafeHTML(notification.messageHtml)}
          </div>

          <div class="notification-meta">
            ${
              notification.byline
                ? html`
                    <span>${notification.byline}</span>
                    <span aria-hidden="true">·</span>
                  `
                : nothing
            }
            <time datetime=${notification.createdAt}
              >${notification.createdAtLabel}</time
            >
            ${
              notification.unread
                ? html`
                    <span aria-hidden="true">·</span>
                    <craft-button
                      class="notification-mark-read"
                      type="button"
                      size="small"
                      variant="link"
                      @click=${(event: MouseEvent) => {
                        event.stopPropagation();
                        const item = (
                          event.currentTarget as HTMLElement
                        ).closest('article');
                        void this.#markReadFromButton([notification.id], () =>
                          item?.querySelector<HTMLElement>(
                            '.notification-card-link'
                          )
                        );
                      }}
                    >
                      ${t('Mark as read')}
                    </craft-button>
                  `
                : nothing
            }
          </div>

          ${
            notification.buttons.length
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
                  </div>
                `
              : nothing
          }
        </craft-media-item>
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
