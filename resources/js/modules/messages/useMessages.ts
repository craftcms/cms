import {onBeforeUnmount, onMounted} from 'vue';
import {
  registerMessageOutlet,
  showError,
  showMessage,
  showNotice,
  showSuccess,
  type MessageOutlet,
} from './messages';

/**
 * Shows control panel messages. Everything goes through the same queue as
 * server-flashed messages, so a message is never shown twice.
 *
 * @example
 * const messages = useMessages();
 * messages.success(t('Widget saved.'));
 * messages.error(t('Couldn’t save widget.'));
 */
export function useMessages() {
  return {
    showMessage,
    notice: showNotice,
    success: showSuccess,
    error: showError,
  };
}

/**
 * Registers an inline outlet for messages that target `name`, for as long as
 * the calling component is mounted.
 */
export function useMessageOutlet(name: string, outlet: MessageOutlet): void {
  let unregister: (() => void) | null = null;

  onMounted(() => {
    unregister = registerMessageOutlet(name, outlet);
  });

  onBeforeUnmount(() => unregister?.());
}
