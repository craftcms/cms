import '@craftcms/ui/components/pane/pane';
import {t} from '@craftcms/ui/utilities/translate';
import {prefixedKey} from '@/common/composables/useStorage';
import {
  messagesElement,
  Notification,
  type NotificationSettings,
} from './notification';

/** The most of the copied text a card shows, or reads out. */
const TEXT_LENGTH = 500;

const MINIMIZED_KEY = 'copiedNotificationMinimized';

let buttonCount = 0;

/**
 * Opens the next "copied" card in full. Called when something new is
 * copied, so a card minimized for what was there before doesn't hide it.
 */
export function forgetCopiedMinimized(): void {
  storeMinimized(false);
}

function storeMinimized(minimized: boolean): void {
  try {
    if (minimized) {
      localStorage.setItem(prefixedKey(MINIMIZED_KEY), '1');
    } else {
      localStorage.removeItem(prefixedKey(MINIMIZED_KEY));
    }
  } catch {
    // Storage can be full, or unavailable in a private window
  }
}

function storedMinimized(): boolean {
  try {
    return localStorage.getItem(prefixedKey(MINIMIZED_KEY)) === '1';
  } catch {
    return false;
  }
}

/** A plain, small button. Pass `icon` for an icon-only one. */
function plainButton(className: string, icon?: string): HTMLElement {
  const button = document.createElement('craft-button');
  button.id = `copied-notification-button-${++buttonCount}`;
  button.className = className;
  button.setAttribute('type', 'button');
  button.setAttribute('variant', 'plain');
  button.setAttribute('size', 'small');

  if (icon) {
    button.setAttribute('icon', '');
    button.append(iconElement(icon));
  }

  return button;
}

function iconElement(name: string, slot?: string): HTMLElement {
  const icon = document.createElement('craft-icon');
  icon.setAttribute('name', name);

  if (slot) {
    icon.setAttribute('slot', slot);
  }

  return icon;
}

/**
 * A tooltip that labels a button with no text of its own, so its name shows
 * on hover and focus too.
 */
function labelTooltip(button: HTMLElement, label: string): HTMLElement {
  const tooltip = document.createElement('craft-tooltip');
  tooltip.setAttribute('for', button.id);
  tooltip.setAttribute('invoker-relation', 'label');
  tooltip.textContent = label;

  return tooltip;
}

/**
 * Copied text, such as a value's or a link's, in an outlined pane that sets
 * it apart from the card's message.
 */
export function copiedTextPane(...content: Node[]): HTMLElement {
  const pane = document.createElement('craft-pane');
  pane.className = 'copied-notification-pane';
  pane.setAttribute('appearance', 'outline');
  pane.setAttribute('padding', 'md');
  pane.append(...content);

  return pane;
}

/**
 * A "copied" card, for whatever's on the control panel's clipboard, with a
 * Cancel button that clears it.
 *
 * Its Minimize button docks it below other notifications, as just its icon,
 * how many things are copied, and a button that opens it again. It stays
 * minimized on later pages until something new is copied.
 */
export abstract class CopiedNotification extends Notification {
  #expandButton: HTMLElement | null = null;
  #expandTooltip: HTMLElement | null = null;
  #minimizeButton: HTMLElement | null = null;
  #count: HTMLElement | null = null;

  constructor(message: string, settings: NotificationSettings = {}) {
    super('notice', message, {
      icon: 'clone-dashed',
      persist: true,
      ...settings,
      class: ['copied-notification', settings.class].filter(Boolean).join(' '),
    });

    this.docked = storedMinimized();
  }

  /** How many things are copied, which the minimized card shows past one. */
  protected get copiedCount(): number {
    return 1;
  }

  override createCloseButton(): HTMLElement {
    return plainButton('notification-close-btn', 'xmark-large');
  }

  override createButtons(): HTMLElement[] {
    // The minimized card: its icon, how many things are copied, and which
    // way it opens
    const expand = plainButton('copied-notification-expand');
    this.#count = document.createElement('span');
    this.#count.className = 'copied-notification-count';
    expand.append(
      iconElement('custom-icons/clone-dashed', 'prefix'),
      this.#count,
      iconElement(
        messagesElement()?.position.startsWith('start-')
          ? 'chevron-down'
          : 'chevron-up',
        'suffix'
      )
    );
    expand.addEventListener('click', () => this.setMinimized(false));
    this.#expandButton = expand;
    this.#expandTooltip = labelTooltip(expand, this.message);
    this.#syncCount();

    const minimize = plainButton('copied-notification-minimize', 'minus');
    minimize.addEventListener('click', () => this.setMinimized(true));
    this.#minimizeButton = minimize;

    return [
      expand,
      this.#expandTooltip,
      minimize,
      labelTooltip(minimize, t('Minimize')),
      labelTooltip(this.closeButton!, t('Cancel')),
    ];
  }

  override updateMessage(message: string): void {
    super.updateMessage(message);

    if (this.#expandTooltip) {
      (
        this.#expandTooltip.querySelector(':scope > [slot="content"]') ??
        this.#expandTooltip
      ).textContent = message;
    }

    this.#syncCount();
  }

  /**
   * Docks the card below other notifications, minimized, or opens it in
   * full, and remembers which for later pages.
   */
  setMinimized(minimized: boolean): void {
    storeMinimized(minimized);

    if (this.docked === minimized) {
      return;
    }

    const hadFocus = !!this.element?.contains(document.activeElement);
    const messages = messagesElement();

    if (messages) {
      messages.dockNotification(this, minimized);
    } else {
      this.docked = minimized;
    }

    if (hadFocus) {
      (minimized ? this.#expandButton : this.#minimizeButton)?.focus();
    }
  }

  #syncCount(): void {
    if (this.#count) {
      const count = this.copiedCount;
      this.#count.textContent = count > 1 ? String(count) : '';
      this.#count.hidden = count <= 1;
    }
  }

  /**
   * The card's details: a preview of what's copied, or else its text. A
   * preview is only for looking at, so screen readers read the text with it.
   * Either one runs under the Cancel button, and off the card's right and
   * bottom edges when it's too big for the card, fading out as it reaches
   * them.
   */
  protected copiedDetails(
    preview: Node | null,
    text: string
  ): HTMLElement | null {
    if (!preview && !text) {
      return null;
    }

    const container = document.createElement('div');
    container.className = 'copied-notification-value';
    const content = document.createElement('div');
    const textElement = document.createElement('span');
    textElement.textContent =
      text.length > TEXT_LENGTH ? `${text.slice(0, TEXT_LENGTH)}…` : text;

    if (preview) {
      content.className = 'copied-notification-preview';
      // Neither focusable nor read out, so it can't hold up the card either.
      content.inert = true;
      content.append(preview);
      textElement.className = 'sr-only';
      container.append(content, textElement);
    } else {
      content.className = 'copied-notification-text';
      content.append(textElement);
      container.append(copiedTextPane(content));
    }

    // Fade out only the edges the preview actually runs off. Its size changes
    // as chips or images load.
    const observer = new ResizeObserver(() => {
      container.toggleAttribute(
        'data-overflow-block',
        container.scrollHeight > container.clientHeight + 1
      );
      container.toggleAttribute(
        'data-overflow-inline',
        container.scrollWidth > container.clientWidth + 1
      );
    });
    observer.observe(container);
    observer.observe(content);
    this.on('close', () => observer.disconnect());

    // Lays the card out around the value. A `:has()` rule for this would make
    // Chrome restyle the whole page each time any element is added anywhere.
    this.element?.classList.add('copied-notification--value');

    return container;
  }
}
