import {t} from '@craftcms/ui/utilities/translate';

const POSITIONS = ['start-start', 'start-end', 'end-start', 'end-end'];

/**
 * The container messages render into. Both the Twig layout and the
 * Inertia root view output one, as `<cp-messages id="messages">`.
 *
 * It builds its own contents: a visually hidden heading and the stack, which
 * holds two live regions, `role="alert"` for errors and `role="status"` for
 * everything else. They're in place before any message arrives, so
 * screen readers announce what's added.
 *
 * Light DOM on purpose: `Craft.CP.Notification` (CP.js) adds notifications
 * straight into the regions, and `resources/css/messages.css` styles them.
 *
 * @attr position - Which corner the stack sits in: `start-start`,
 *   `start-end`, `end-start` or `end-end`, block axis first. Defaults to the
 *   user's preference (`Craft.notificationPosition`).
 */
export class CpMessages extends HTMLElement {
  get position(): string {
    const position = this.getAttribute('position') ?? '';
    return POSITIONS.includes(position) ? position : 'end-start';
  }

  set position(position: string) {
    this.setAttribute('position', position);
  }

  connectedCallback(): void {
    this.id ||= 'messages';
    this.classList.add('cp-messages');

    if (!this.hasAttribute('position')) {
      const preferred = (window.Craft as {notificationPosition?: string})
        ?.notificationPosition;
      this.position = preferred ?? 'end-start';
    }

    this.#render();
    this.#connectLegacyNotifier();
  }

  #render(): void {
    if (this.querySelector(':scope > .messages-stack')) {
      return;
    }

    const heading = document.createElement('h2');
    heading.id = 'cp-notification-heading';
    heading.className = 'sr-only';
    heading.textContent = t('Messages');

    const alerts = document.createElement('div');
    alerts.className = 'message-region';
    alerts.setAttribute('role', 'alert');

    const statuses = document.createElement('div');
    statuses.className = 'message-region';
    statuses.setAttribute('role', 'status');

    // Anything shown before the element was upgraded lands on the element
    // itself; move it into its region.
    for (const card of this.querySelectorAll(':scope > .notification')) {
      (card.getAttribute('data-type') === 'error' ? alerts : statuses).append(
        card
      );
    }

    const stack = document.createElement('div');
    stack.className = 'messages-stack';
    stack.append(alerts, statuses);

    this.append(heading, stack);
  }

  /**
   * Points the CP singleton at this element, in case it looked for
   * `#notifications` (its old id) before the element existed.
   */
  #connectLegacyNotifier(): void {
    const cp = window.Craft?.cp;

    if (cp && window.$) {
      cp.$notificationContainer = window.$(this);
      cp.$notificationHeading = window.$('#cp-notification-heading', this);
    }
  }
}

if (!customElements.get('cp-messages')) {
  customElements.define('cp-messages', CpMessages);
}

declare global {
  interface HTMLElementTagNameMap {
    'cp-messages': CpMessages;
  }
}
