import '@craftcms/ui/components/pane/pane';
import {t} from '@craftcms/ui/utilities/translate';
import {Notification, type NotificationSettings} from './notification';

/** The most of the copied text a card shows, or reads out. */
const TEXT_LENGTH = 500;

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
 */
export abstract class CopiedNotification extends Notification {
  constructor(message: string, settings: NotificationSettings = {}) {
    super('notice', message, {
      icon: 'clone-dashed',
      persist: true,
      ...settings,
      class: ['copied-notification', settings.class].filter(Boolean).join(' '),
    });
  }

  override createCloseButton(): HTMLElement {
    return Notification.createIconButton(t('Cancel'));
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

    return container;
  }
}
