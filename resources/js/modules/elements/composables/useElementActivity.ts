import {actionClient} from '@craftcms/ui';
import {useDocumentVisibility, useIntervalFn} from '@vueuse/core';
import {readonly, ref, watch, type Ref} from 'vue';

/** One person currently working on the element. */
export interface ElementActivityEntry {
  userId: number;
  userName: string;
  /** Server-rendered avatar. */
  userThumb: string;
  type: string;
  message: string;
}

interface Options {
  /** `elements/recent-activity`, or null when the screen shouldn't poll. */
  url: string | null;
  elementType: string;
  /** The canonical element — activity is tracked against it, not the draft. */
  elementId: number | null;
  /**
   * The draft the screen is editing, read per poll rather than captured:
   * autosave creates a provisional draft partway through a session, and the
   * poll has to follow it. Otherwise the server resolves — and timestamps —
   * the canonical element, while {@link rebase} is storing the draft's stamp,
   * and every poll after the first autosave reports a change nobody made.
   */
  draftId: () => number | null;
  siteId: number | null;
  isProvisionalDraft: () => boolean;
  /** Last-modified stamps as rendered, used to detect upstream changes. */
  updatedTimestamps: {element: number | null; canonical: number | null};
  intervalMs?: number;
}

/**
 * Polls for other people editing this element, and notices when it changes
 * underneath the person editing it.
 *
 * Polling pauses while the tab is hidden — the legacy editor keeps going, but
 * a backgrounded tab has nobody to show the avatars to, and the timestamps are
 * re-read on the first poll after it returns.
 */
export function useElementActivity(options: Options): {
  activity: Readonly<Ref<Array<ElementActivityEntry>>>;
  isStale: Readonly<Ref<boolean>>;
  staleType: Readonly<Ref<'element' | 'canonical' | null>>;
  poll: () => Promise<void>;
  rebase: (timestamps: Options['updatedTimestamps']) => void;
} {
  const activity = ref<Array<ElementActivityEntry>>([]);
  const isStale = ref(false);
  const staleType = ref<'element' | 'canonical' | null>(null);

  // Advanced as the server reports them, so the notice fires once per change
  // rather than on every poll after one.
  let elementStamp = options.updatedTimestamps.element;
  let canonicalStamp = options.updatedTimestamps.canonical;

  // Bumped by every re-baseline. A poll that was in flight when the screen
  // saved describes the state before that save, so its stamps would read as
  // someone else's change — they're dropped instead.
  let baseline = 0;

  async function poll(): Promise<void> {
    if (!options.url) {
      return;
    }

    const polled = baseline;

    try {
      const {data} = await actionClient.post(options.url, {
        elementType: options.elementType,
        elementId: options.elementId,
        draftId: options.draftId(),
        siteId: options.siteId,
        provisional: options.isProvisionalDraft() ? 1 : null,
      });

      activity.value = data.activity ?? [];

      if (polled !== baseline) {
        return;
      }

      const elementChanged =
        elementStamp !== null && data.updatedTimestamp !== elementStamp;
      const canonicalChanged =
        canonicalStamp !== null &&
        data.canonicalUpdatedTimestamp !== canonicalStamp;

      if (elementChanged || canonicalChanged) {
        isStale.value = true;
        staleType.value = elementChanged ? 'element' : 'canonical';
      }

      elementStamp = data.updatedTimestamp ?? elementStamp;
      canonicalStamp = data.canonicalUpdatedTimestamp ?? canonicalStamp;
    } catch {
      // A failed poll is not worth surfacing — the next one may well succeed,
      // and this is ambient information, not something the user asked for.
    }
  }

  /**
   * Re-baselines against the stamps the screen has just re-rendered with.
   *
   * Saving in place bumps the element's `dateUpdated` without navigating away,
   * which the next poll would otherwise report as someone else's change.
   */
  function rebase(timestamps: Options['updatedTimestamps']): void {
    baseline++;

    // Both stamps are comparable against what the poll reports: a save reports
    // the `dateUpdated` of the element it just wrote, and the server keeps that
    // the same instant as the one it reads back out of storage.
    elementStamp = timestamps.element;
    canonicalStamp = timestamps.canonical;

    isStale.value = false;
    staleType.value = null;
  }

  const visibility = useDocumentVisibility();

  const {pause, resume} = useIntervalFn(
    () => void poll(),
    options.intervalMs ?? 15000,
    {immediate: !!options.url, immediateCallback: !!options.url}
  );

  watch(visibility, (state, previous) => {
    // Skip the ref's initial resolution, which would otherwise duplicate the
    // poll `immediateCallback` already made.
    if (!options.url || previous === undefined) {
      return;
    }

    if (state === 'visible') {
      resume();
      void poll();
    } else {
      pause();
    }
  });

  return {
    activity: readonly(activity) as Readonly<Ref<Array<ElementActivityEntry>>>,
    isStale: readonly(isStale),
    staleType: readonly(staleType),
    poll,
    rebase,
  };
}
