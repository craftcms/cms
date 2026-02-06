import {ref} from 'vue';

interface AnnouncerOptions {
  timeout: number;
}

const announcement = ref<string | null>(null);
const announcerTimeout = ref(0);

export function useAnnouncer(options: Partial<AnnouncerOptions> = {}) {
  function announce(message: string) {
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
