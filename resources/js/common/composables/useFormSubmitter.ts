import {
  computed,
  ref,
  toValue,
  watch,
  type MaybeRefOrGetter,
  type Ref,
} from 'vue';
import type {InertiaForm} from '@inertiajs/vue3';

/** The key the form's own submit button claims submissions under. */
export const PRIMARY_SUBMITTER = 'primary';

const submitters = new WeakMap<object, Ref<string | null>>();

function submitterFor(form: object): Ref<string | null> {
  let submitter = submitters.get(form);

  if (!submitter) {
    submitter = ref(null);
    submitters.set(form, submitter);
  }

  return submitter;
}

/**
 * Which button started a form's in-flight submission, so only that one shows a
 * spinner while every button is disabled.
 *
 * Kept per form rather than per component: the save button belongs to the
 * shell, but the buttons beside it can come from the page — the element
 * editor's, say — and they all have to agree on who's submitting.
 */
export function useFormSubmitter(form: MaybeRefOrGetter<InertiaForm<any>>) {
  const submitter = computed(() => submitterFor(toValue(form)));

  watch(
    () => toValue(form).processing,
    (processing) => {
      if (processing) {
        // Submissions no button claimed (Enter, the save menu) belong to the
        // submit button.
        submitter.value.value ??= PRIMARY_SUBMITTER;
      } else {
        submitter.value.value = null;
      }
    }
  );

  return {
    isSubmitting(key: string): boolean {
      return toValue(form).processing && submitter.value.value === key;
    },

    /** Claims the submission a click is about to start. */
    claim(key: string): void {
      submitter.value.value = key;

      // Let go again if the click didn't start one.
      setTimeout(() => {
        if (!toValue(form).processing) {
          submitter.value.value = null;
        }
      });
    },
  };
}
