import { ref } from 'vue';

interface AnnouncerOptions {
  timeout: number;
}

const announcement = ref<string | null>(null);
const announcerTimeout = ref<ReturnType<typeof setTimeout> | null>(null);

export function useAnnouncementStore(options: Partial<AnnouncerOptions> = {}) {
  function announce(message: string | null) {
    if (!message) {
      return;
    }

    if (announcerTimeout.value !== null) {
      clearTimeout(announcerTimeout.value);
    }

    announcement.value = message;

    announcerTimeout.value = setTimeout(() => {
      announcement.value = null;
    }, options.timeout || 5000);
  }

  return {announcement, announce};
}
