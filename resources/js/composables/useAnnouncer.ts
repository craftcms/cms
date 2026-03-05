import {ref} from 'vue';

interface AnnouncerOptions {
  timeout: number;
}

const announcement = ref<string | null>(null);
const announcerTimeout = ref(0);

/**
 * Composable for announcing messages to screen readers and displaying temporary notifications.
 *
 * The announcement state is shared globally, so only one announcement can be active at a time.
 * New announcements will replace any existing ones.
 *
 * @example
 * // Basic usage - announce a success message
 * const {announce} = useAnnouncer();
 * announce('Changes saved successfully');
 *
 * @example
 * // With custom timeout (3 seconds)
 * const {announce} = useAnnouncer({timeout: 3000});
 * announce('Quick notification');
 *
 * @example
 * // Reading the current announcement (e.g., in a toast component)
 * const {announcement} = useAnnouncer();
 * // In template: <div v-if="announcement">{{ announcement }}</div>
 *
 * @example
 * // Common use case - form submission feedback
 * async function handleSubmit() {
 *   const {announce} = useAnnouncer();
 *   try {
 *     await saveData();
 *     announce(t('Data saved.'));
 *   } catch (error) {
 *     announce(t('Failed to save data.'));
 *   }
 * }
 */
export function useAnnouncer(options: Partial<AnnouncerOptions> = {}) {
  function announce(message: string | null) {
    if (!message) {
      return;
    }

    if (announcerTimeout.value) {
      clearTimeout(announcerTimeout.value);
    }

    announcement.value = message;

    announcerTimeout.value = setTimeout(() => {
      announcement.value = null;
    }, options.timeout || 5000);
  }

  return {announcement, announce};
}
