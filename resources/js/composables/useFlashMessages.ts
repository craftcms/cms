import {type MaybeRef, ref, unref} from 'vue';

interface FlashMessages {
  success: null | string;
  error: null | string;
}

let messages = ref<FlashMessages>({
  success: null,
  error: null,
});

interface UseFlashMessagesOptions {
  initialMessages: MaybeRef<FlashMessages>;
  duration: number;
}

/**
 * Composable for displaying temporary success/error flash messages.
 *
 * The messages state is shared globally, allowing any component to display
 * or read flash messages. Messages auto-clear after a configurable duration.
 *
 * @example
 * // Basic usage - show a success message
 * const {flash} = useFlashMessages();
 * flash('success', 'Changes saved successfully.');
 *
 * @example
 * // Show an error message
 * const {flash} = useFlashMessages();
 * flash('error', 'Failed to save changes.');
 *
 * @example
 * // Custom duration (10 seconds)
 * const {flash} = useFlashMessages();
 * flash('success', 'This message stays longer.', 10000);
 *
 * @example
 * // Reading messages in a component template
 * const {messages} = useFlashMessages();
 * // In template:
 * // <div v-if="messages.success" class="success">{{ messages.success }}</div>
 * // <div v-if="messages.error" class="error">{{ messages.error }}</div>
 *
 * @example
 * // Common use case - async operation with feedback
 * async function handleAction() {
 *   const {flash} = useFlashMessages();
 *   try {
 *     await performAction();
 *     flash('success', t('Action completed.'));
 *   } catch (error) {
 *     flash('error', t('Action failed.'));
 *   }
 * }
 *
 * @example
 * // With initial messages (e.g., from server-side flash)
 * const {messages} = useFlashMessages({
 *   initialMessages: {success: 'Welcome back!', error: null},
 * });
 */
export function useFlashMessages(
  options: Partial<UseFlashMessagesOptions> = {
    duration: 2000,
  }
) {
  if (options.initialMessages) {
    messages.value = unref(options.initialMessages);
  }

  function flash(
    type: 'success' | 'error',
    message: string | null,
    duration: number = options.duration ?? 5000
  ) {
    messages.value[type] = message;

    setTimeout(() => {
      messages.value[type] = null;
    }, duration);
  }

  return {
    flash,
    messages,
  };
}
