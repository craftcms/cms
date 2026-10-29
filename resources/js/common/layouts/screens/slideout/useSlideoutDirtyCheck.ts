import {
  onBeforeUnmount,
  onMounted,
  ref,
  toValue,
  type MaybeRefOrGetter,
} from 'vue';
import {useEventListener} from '@vueuse/core';
import {setSlideoutDirtyCheck} from '@/common/slideouts/store';
import type {SlideoutController} from '@/common/slideouts/types';

/**
 * Tells the slideout store whether the panel holds unsaved changes, so it can
 * prompt before discarding it.
 *
 * A Vue page has Inertia's own precise `isDirty`. A server-rendered screen
 * has no such tracking, and snapshot-diffing its markup isn't reliable —
 * `Craft.initUiElements()` rewrites inputs after load, which would read as
 * user edits. So that path tracks whether the user has interacted at all:
 * more conservative (undoing an edit still counts), but never wrong in the
 * direction that loses work.
 */
export function useSlideoutDirtyCheck({
  slideout,
  formEl,
  form,
  autosaves,
}: {
  slideout: SlideoutController | null;
  /** The panel's `<form>`, whose input marks a server-rendered screen touched. */
  formEl: MaybeRefOrGetter<HTMLFormElement | null>;
  /** The page's Inertia form, when it has one. */
  form: MaybeRefOrGetter<{isDirty?: boolean} | null>;
  /** Whether every edit is autosaved to a draft as it's made. */
  autosaves: MaybeRefOrGetter<boolean>;
}): void {
  const touched = ref(false);
  useEventListener(formEl, 'input', () => (touched.value = true));
  useEventListener(formEl, 'change', () => (touched.value = true));

  const isDirty = () => {
    // Autosaved drafts persist every edit as it's made, so there's nothing to
    // warn about — and warning anyway would make every element slideout
    // prompt on close.
    if (toValue(autosaves)) {
      return false;
    }

    const pageForm = toValue(form);

    return pageForm ? Boolean(pageForm.isDirty) : touched.value;
  };

  onMounted(() => {
    if (slideout) {
      setSlideoutDirtyCheck(slideout.instance.id, isDirty);
    }
  });

  onBeforeUnmount(() => {
    if (slideout) {
      setSlideoutDirtyCheck(slideout.instance.id, null);
    }
  });
}
