import {t} from '@craftcms/ui/utilities/translate';
import {iconSvg} from '@/common/craft/icon-svg';
import {initLegacyUi} from '@/common/craft/legacy-ui';
import {loadElementThumbs} from '@/modules/element-thumb-loader/globals';
import type {CpMessages} from './components/cp-messages';

export interface NotificationSettings {
  icon?: string;
  iconLabel?: string | null;
  /**
   * Shown below the message: HTML, an element, or a collection of them, which
   * is what Craft 5's jQuery-backed notifications took.
   */
  details?: string | Node | ArrayLike<Node> | null;
  /** Stays until it's dismissed. */
  persist?: boolean;
  class?: string | null;
  animate?: boolean;
  [key: string]: unknown;
}

type Listener = () => void;

const ACTIONABLE = 'a[href], button, input, select, textarea, craft-button';

function craftSetting<T>(name: string): T | undefined {
  return (window.Craft as Record<string, T> | undefined)?.[name];
}

/** The `<cp-messages>` element notifications are shown in. */
export function messagesElement(): CpMessages | null {
  return document.querySelector('cp-messages');
}

/**
 * A message card in the `<cp-messages>` stack. Errors, and cards with
 * something to act on in their details, stay until they're dismissed; the
 * rest close themselves after `Craft.notificationDuration`.
 *
 * Emits `show` once it's on screen and `close` once it's gone.
 */
export class Notification {
  /** Whether notifications close on their own. */
  static autoClose = true;

  type: string;
  message: string;
  settings: NotificationSettings;
  closing = false;
  element: HTMLElement | null = null;
  closeButton: HTMLElement | null = null;

  #messageElement: HTMLElement | null = null;
  #details: HTMLElement | null = null;
  #originalActiveElement: Element | null = null;
  #listeners = new Map<string, Set<Listener>>();
  #remaining: number | null = null;
  #timerStartedAt = 0;
  #closeTimeout: ReturnType<typeof setTimeout> | null = null;
  #preventClose = false;

  constructor(
    type: string,
    message: string,
    settings: NotificationSettings = {}
  ) {
    this.type = type;
    this.message = message;
    this.settings = {
      icon: 'info',
      iconLabel: null,
      details: null,
      persist: false,
      class: null,
      animate: true,
      ...settings,
    };
  }

  on(event: string, listener: Listener): void {
    if (!this.#listeners.has(event)) {
      this.#listeners.set(event, new Set());
    }
    this.#listeners.get(event)!.add(listener);
  }

  off(event: string, listener: Listener): void {
    this.#listeners.get(event)?.delete(listener);
  }

  #emit(event: string): void {
    this.#listeners.get(event)?.forEach((listener) => listener());
  }

  async show(): Promise<void> {
    const messages = messagesElement();

    if (!messages) {
      return;
    }

    const element = document.createElement('div');
    element.className = 'notification';
    element.dataset.type = this.type;
    if (this.settings.class) {
      element.classList.add(...this.settings.class.split(/\s+/));
    }
    this.element = element;

    const inner = document.createElement('div');
    inner.className = 'notification-inner';
    const body = document.createElement('div');
    body.className = 'notification-body';
    inner.append(body);
    element.append(inner);

    // `<craft-icon>` sizes the SVG and makes it an image or decorative,
    // depending on whether it has a label
    const icon = document.createElement('craft-icon');
    icon.className = 'notification-icon';
    if (this.settings.iconLabel) {
      icon.setAttribute('label', this.settings.iconLabel);
    }
    const svg = await iconSvg(this.settings.icon ?? 'info').catch(() => null);
    if (svg) {
      icon.append(svg);
    }
    body.append(icon);

    const main = document.createElement('div');
    main.className = 'notification-main';
    this.#messageElement = document.createElement('div');
    this.#messageElement.className = 'notification-message';
    this.#messageElement.textContent = this.message;
    main.append(this.#messageElement);
    body.append(main);

    const closeContainer = document.createElement('div');
    this.closeButton = this.createCloseButton();
    this.closeButton.addEventListener('click', () =>
      this.handleCloseButtonClick()
    );
    closeContainer.append(this.closeButton);
    inner.append(closeContainer);

    const details = await this.getDetails();
    if (details) {
      this.#details = document.createElement('div');
      this.#details.className = 'notification-details';
      if (typeof details === 'string') {
        this.#details.innerHTML = details;
      } else if (details instanceof Node) {
        this.#details.append(details);
      } else {
        // A jQuery collection, which `.append()` used to unwrap for us.
        this.#details.append(...Array.from<Node>(details));
      }
      main.append(this.#details);

      if (this.#details.querySelector('button, input')) {
        this.#originalActiveElement = document.activeElement;
        element.tabIndex = -1;
        element.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            void this.close();
          }
        });
      }
    }

    initLegacyUi(inner);

    // `close()` can land while the icon is still loading. It can't take this
    // one off screen yet, so stop before putting it there.
    if (this.closing) {
      this.#emit('close');

      return;
    }

    element.dataset.shownAt = String(Date.now());
    element.classList.toggle('notification--entering', this.#animate());
    messages.addNotification(this);

    if (this.#originalActiveElement) {
      element.focus();
    }

    if (this.#animate()) {
      // Let the entering state render before transitioning out of it
      await new Promise((resolve) => requestAnimationFrame(resolve));
      element.classList.remove('notification--entering');
      await this.#waitForTransition();
    }

    if (this.closing) {
      return;
    }

    if (this.#details) {
      loadElementThumbs(this.#details);
    }

    if (
      Notification.autoClose &&
      craftSetting<number>('notificationDuration') &&
      !this.settings.persist &&
      !this.#staysUntilDismissed()
    ) {
      this.#remaining = craftSetting<number>('notificationDuration')!;
      this.resumeTimer();
    }

    this.#initSwipe();
    this.#emit('show');
  }

  createCloseButton(): HTMLElement {
    return Notification.createIconButton(t('Close'));
  }

  updateMessage(message: string): void {
    this.message = message;
    if (this.#messageElement) {
      this.#messageElement.textContent = message;
    }
  }

  async getDetails(): Promise<
    string | Node | ArrayLike<Node> | null | undefined
  > {
    return this.settings.details;
  }

  handleCloseButtonClick(): void {
    void this.close();
  }

  #staysUntilDismissed(): boolean {
    return this.type === 'error' || !!this.#details?.querySelector(ACTIONABLE);
  }

  /** Starts the countdown to closing, with whatever time it had left. */
  resumeTimer(): void {
    if (
      this.#remaining == null ||
      this.#preventClose ||
      this.closing ||
      this.#closeTimeout ||
      messagesElement()?.timersPaused()
    ) {
      return;
    }

    this.#timerStartedAt = Date.now();
    this.#closeTimeout = setTimeout(() => void this.close(), this.#remaining);
  }

  pauseTimer(): void {
    if (!this.#closeTimeout) {
      return;
    }

    clearTimeout(this.#closeTimeout);
    this.#closeTimeout = null;
    this.#remaining = Math.max(
      this.#remaining! - (Date.now() - this.#timerStartedAt),
      0
    );
  }

  /** Stops it closing itself. */
  preventDelayedClose(): void {
    this.#preventClose = true;
    this.pauseTimer();
  }

  /**
   * Swiping toward the screen edge dismisses it: past 45px, or fast enough.
   * Anything less springs back, and movement the other way is damped.
   */
  #initSwipe(): void {
    const element = this.element!;
    const towardEdge = () =>
      (craftSetting<string>('notificationPosition') ?? '').startsWith('start-')
        ? -1
        : 1;
    let start: {y: number; time: number} | null = null;

    const reset = () => {
      start = null;
      element.removeAttribute('data-swiping');
      element.style.removeProperty('--swipe-amount');
    };

    element.addEventListener('pointerdown', (event) => {
      if (
        event.button !== 0 ||
        this.closing ||
        (event.target as Element).closest(
          'button, a, input, textarea, select, craft-button'
        )
      ) {
        return;
      }
      start = {y: event.clientY, time: Date.now()};
      element.setPointerCapture?.(event.pointerId);
    });

    element.addEventListener('pointermove', (event) => {
      if (!start) {
        return;
      }
      const delta = event.clientY - start.y;
      const amount =
        Math.sign(delta) === towardEdge()
          ? delta
          : delta / (1.5 + Math.abs(delta) / 20);
      if (Math.abs(delta) > 2) {
        element.setAttribute('data-swiping', '');
      }
      element.style.setProperty('--swipe-amount', `${amount}px`);
    });

    element.addEventListener('pointerup', (event) => {
      if (!start) {
        return;
      }
      const delta = (event.clientY - start.y) * towardEdge();
      const velocity = Math.abs(delta) / (Date.now() - start.time || 1);
      if (delta >= 45 || (delta > 0 && velocity > 0.11)) {
        element.setAttribute('data-swipe-out', '');
        start = null;
        element.removeAttribute('data-swiping');
        void this.close();
        return;
      }
      reset();
    });
    element.addEventListener('pointercancel', reset);
  }

  #animate(): boolean {
    return (
      !!this.settings.animate &&
      !window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
    );
  }

  #waitForTransition(): Promise<void> {
    if (!this.#animate() || !this.element) {
      return Promise.resolve();
    }

    const element = this.element;

    return new Promise((resolve) => {
      const done = (event?: TransitionEvent) => {
        if (event && event.target !== element) {
          return;
        }
        element.removeEventListener('transitionend', done);
        clearTimeout(timeout);
        resolve();
      };
      const timeout = setTimeout(done, 500);
      element.addEventListener('transitionend', done);
    });
  }

  async close(): Promise<void> {
    if (this.closing) {
      return;
    }

    if (this.#closeTimeout) {
      clearTimeout(this.#closeTimeout);
      this.#closeTimeout = null;
    }

    this.closing = true;

    if (!this.element) {
      return;
    }

    if (
      this.#originalActiveElement instanceof HTMLElement &&
      this.element.contains(document.activeElement)
    ) {
      this.#originalActiveElement.focus();
    }

    const messages = messagesElement();
    this.element.classList.add('notification--leaving');
    messages?.updateStack();
    await this.#waitForTransition();

    messages?.removeNotification(this);
    this.element.remove();
    this.#emit('close');
  }

  /** An icon-only `<craft-button>` with an `xmark`, labelled `label`. */
  static createIconButton(label: string): HTMLElement {
    const button = document.createElement('craft-button');
    button.className = 'notification-close-btn';
    button.setAttribute('type', 'button');
    button.setAttribute('variant', 'plain');
    button.setAttribute('size', 'small');
    button.setAttribute('icon', '');
    const icon = document.createElement('craft-icon');
    icon.setAttribute('name', 'xmark-large');
    icon.setAttribute('label', label);
    button.append(icon);

    return button;
  }
}
