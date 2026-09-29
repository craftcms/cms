import {router} from '@inertiajs/vue3';
import {installMessages, showMessage, type CpMessage} from './index';

/**
 * Sets messages up on Inertia pages: starts the queue, and shows each
 * page response's messages while keeping the user's notification preferences
 * current. The `<cp-messages>` container comes from `app.blade.php`.
 */
export function setUpInertiaMessages(): void {
  installMessages();
  handleMessages();
}

/**
 * Shows the messages the server flashed with each page response. `success`
 * covers every visit (partial reloads and `replace` visits included) and
 * `navigate` the first page and history visits; the queue drops the repeats.
 *
 * Deferred a tick so the new page has mounted and registered any inline
 * outlets the messages target.
 */
function handleMessages() {
  const showPageMessages = (page: {props: Record<string, unknown>}) => {
    const messages = page.props.messages as CpMessage[] | undefined;

    if (messages?.length) {
      setTimeout(() => messages.forEach(showMessage));
    }
  };

  const handlePage = (page: {props: Record<string, unknown>}) => {
    applyMessagePreferences(page);
    showPageMessages(page);
  };

  router.on('success', (event) => handlePage(event.detail.page));
  router.on('navigate', (event) => handlePage(event.detail.page));
}

/**
 * Keeps messages in step with the user's preferences, which can change
 * on an Inertia visit (the Preferences page saves without a full reload):
 * `Craft.notificationPosition` and `Craft.notificationDuration`, which CP.js
 * reads, and the `position` of the `<cp-messages>` container.
 */
function applyMessagePreferences(page: {props: Record<string, unknown>}) {
  const craft = window.Craft as {
    notificationPosition?: string;
    notificationDuration?: number;
  };
  const general = (
    page.props.craft as
      | {
          general?: {
            notificationPosition?: string;
            notificationDuration?: number;
          };
        }
      | undefined
  )?.general;

  if (general?.notificationPosition) {
    craft.notificationPosition = general.notificationPosition;
  }

  if (typeof general?.notificationDuration === 'number') {
    craft.notificationDuration = general.notificationDuration;
  }

  if (craft.notificationPosition) {
    const container = document.querySelector('cp-messages');

    if (container && container.position !== craft.notificationPosition) {
      container.position = craft.notificationPosition;
    }
  }
}
