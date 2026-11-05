import {t} from '@craftcms/ui/utilities/translate';
import type {Notification} from '../notification';

const POSITIONS = ['start-start', 'start-end', 'end-start', 'end-end'];

/** How many of the newest notifications are shown at once. */
const VISIBLE_COUNT = 5;

interface Timed {
  pauseTimer(): void;
  resumeTimer(): void;
}

/**
 * The container messages render into. Both the Twig layout and the
 * Inertia root view output one, as `<cp-messages id="messages">`.
 *
 * It builds its own contents: a visually hidden heading and the stack, which
 * holds two live regions, `role="alert"` for errors and `role="status"` for
 * everything else. They're in place before any message arrives, so
 * screen readers announce what's added.
 *
 * It also manages the stack, as Sonner does: the newest notification in
 * front, the rest tucked behind it until the pointer, a press or keyboard
 * focus expands it. Timers pause while it's expanded or the page is hidden.
 *
 * Light DOM on purpose: notifications are added straight into the regions,
 * and `resources/css/messages.css` styles them.
 *
 * @attr position - Which corner the stack sits in: `start-start`,
 *   `start-end`, `end-start` or `end-end`, block axis first. Defaults to the
 *   user's preference (`Craft.notificationPosition`).
 */
export class CpMessages extends HTMLElement {
  #instances = new Map<Element, Timed>();
  #resizeObserver?: ResizeObserver;
  #hovered = false;
  #interacting = false;
  #focusBefore: Element | null = null;
  #listening = false;

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
    this.#listen();
    this.#connectLegacyNotifier();
  }

  /** Adds a notification to its live region, newest in front. */
  addNotification(notification: Notification): void {
    const element = notification.element!;
    this.#instances.set(element, notification);
    this.#region(notification.type).append(element);
    this.updateStack();
  }

  removeNotification(notification: Notification): void {
    const inner = notification.element?.querySelector('.notification-inner');
    if (inner) {
      this.#resizeObserver?.unobserve(inner);
    }
    if (notification.element) {
      this.#instances.delete(notification.element);
    }
    this.updateStack();
  }

  /** Errors are announced assertively, everything else politely. */
  #region(type: string): Element {
    const region = this.querySelector(
      `.message-region[role="${type === 'error' ? 'alert' : 'status'}"]`
    );

    return region ?? this;
  }

  /** The notifications on screen, newest first, not counting any leaving. */
  #stacked(): HTMLElement[] {
    return [
      ...this.querySelectorAll<HTMLElement>(
        '.notification:not(.notification--leaving)'
      ),
    ].sort(
      (a, b) => Number(b.dataset.shownAt ?? 0) - Number(a.dataset.shownAt ?? 0)
    );
  }

  /** Lays the stack out again: offsets, which are hidden, and its size. */
  updateStack(): void {
    const notifications = this.#stacked();

    if (!this.#resizeObserver && window.ResizeObserver) {
      this.#resizeObserver = new ResizeObserver(() => this.updateStack());
    }

    // A notification's own height, whether or not the collapsed stack is
    // forcing it to the front one's
    const naturalHeight = (notification: HTMLElement) => {
      const inner = notification.querySelector<HTMLElement>(
        '.notification-inner'
      );
      if (!inner) {
        return notification.offsetHeight;
      }
      const style = getComputedStyle(notification);
      return (
        inner.offsetHeight +
        (parseFloat(style.paddingBlockStart) || 0) +
        (parseFloat(style.paddingBlockEnd) || 0)
      );
    };

    let offset = 0;

    notifications.forEach((notification, index) => {
      const inner = notification.querySelector('.notification-inner');
      if (inner) {
        this.#resizeObserver?.observe(inner);
      }
      notification.style.setProperty('--cp-message-index', String(index));
      notification.style.setProperty('--cp-message-offset', `${offset}px`);
      notification.style.zIndex = String(notifications.length - index);
      notification.toggleAttribute('data-stack-behind', index > 0);
      notification.toggleAttribute('data-stack-hidden', index >= VISIBLE_COUNT);
      // Hidden ones can't be reached until newer ones close
      notification.inert = index >= VISIBLE_COUNT;
      if (index < VISIBLE_COUNT) {
        offset += naturalHeight(notification);
      }
    });

    this.style.setProperty(
      '--cp-messages-behind',
      String(Math.max(Math.min(notifications.length, VISIBLE_COUNT) - 1, 0))
    );
    this.style.setProperty(
      '--cp-messages-front-height',
      `${notifications[0] ? naturalHeight(notifications[0]) : 0}px`
    );
    this.style.setProperty('--cp-messages-height', `${offset}px`);
    this.style.setProperty(
      '--cp-messages-count',
      String(Math.min(notifications.length, VISIBLE_COUNT))
    );
    this.querySelector('#cp-notification-heading')?.classList.toggle(
      'hidden',
      notifications.length === 0
    );

    this.#updateExpanded();
  }

  /**
   * Whether notifications should hold off closing: while the stack is
   * expanded or pressed, or the page is hidden.
   */
  timersPaused(): boolean {
    return (
      document.visibilityState === 'hidden' ||
      this.hasAttribute('data-expanded') ||
      this.#interacting
    );
  }

  /** Dismisses every notification whose close button is showing. */
  clear(): void {
    this.#restoreFocus();

    for (const notification of this.#stacked()) {
      const button = notification.querySelector<HTMLElement>(
        '.notification-close-btn'
      );
      if (button && button.getClientRects().length) {
        button.click();
      }
    }
  }

  #listen(): void {
    if (this.#listening) {
      return;
    }

    this.#listening = true;
    this.setAttribute('role', 'region');
    this.setAttribute('aria-label', t('Messages'));
    this.tabIndex = -1;

    this.addEventListener('mouseenter', () => {
      this.#hovered = true;
      this.#updateExpanded();
    });
    this.addEventListener('mousemove', () => {
      if (!this.#hovered) {
        this.#hovered = true;
        this.#updateExpanded();
      }
    });
    this.addEventListener('mouseleave', () => {
      // A press that drags off the stack (a swipe) keeps it open
      if (!this.#interacting) {
        this.#hovered = false;
        this.#updateExpanded();
      }
    });
    this.addEventListener('pointerdown', () => {
      this.#interacting = true;
      this.#updateExpanded();
    });
    document.addEventListener('pointerup', () => {
      if (this.#interacting) {
        this.#interacting = false;
        this.#hovered = this.matches(':hover');
        this.#updateExpanded();
      }
    });
    this.addEventListener('focusin', (event) => {
      if (!this.contains(event.relatedTarget as Node | null)) {
        this.#focusBefore = event.relatedTarget as Element | null;
      }
      this.#updateExpanded();
    });
    this.addEventListener('focusout', () => {
      // Wait for focus to land, so moving between notifications doesn't
      // briefly collapse the stack
      setTimeout(() => this.#updateExpanded());
    });
    this.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        this.#restoreFocus();
        this.#updateExpanded();
      }
    });
    document.addEventListener('visibilitychange', () => this.#syncTimers());
  }

  #updateExpanded(): void {
    const expanded =
      this.#stacked().length > 0 &&
      (this.#hovered ||
        this.#interacting ||
        this.contains(document.activeElement));

    if (this.hasAttribute('data-expanded') !== expanded) {
      this.toggleAttribute('data-expanded', expanded);
    }

    this.#syncTimers();
  }

  #syncTimers(): void {
    const paused = this.timersPaused();

    for (const element of this.#stacked()) {
      const notification = this.#instance(element);
      if (paused) {
        notification?.pauseTimer();
      } else {
        notification?.resumeTimer();
      }
    }
  }

  /** The notification shown in `element`, including legacy ones. */
  #instance(element: Element): Timed | undefined {
    return (
      this.#instances.get(element) ??
      (window.jQuery?.(element).data('notification') as Timed | undefined)
    );
  }

  /** Moves focus back to where it was before it went into the stack. */
  #restoreFocus(): void {
    if (!this.contains(document.activeElement)) {
      return;
    }

    const target = this.#focusBefore;

    if (
      target instanceof HTMLElement &&
      target.isConnected &&
      !this.contains(target)
    ) {
      target.focus();
    } else {
      (document.activeElement as HTMLElement | null)?.blur();
    }
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
