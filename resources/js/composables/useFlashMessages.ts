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
