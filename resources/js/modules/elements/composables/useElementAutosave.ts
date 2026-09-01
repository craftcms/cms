import {actionClient} from '@craftcms/ui';
import {useDebounceFn} from '@vueuse/core';
import type {InertiaForm} from '@inertiajs/vue3';
import {computed, readonly, ref, shallowRef} from 'vue';
import type {
  FormChangeKind,
  FormPayload,
  FormValues,
} from '@/modules/forms/types';
import axios from 'axios';

export type AutosaveStatus = 'idle' | 'saving' | 'saved' | 'failed';

export interface ElementAutosaveOptions {
  /** The `elements/save-draft` endpoint. */
  url: string;
  /** The element class — the shared draft actions resolve the element by type. */
  elementType: string;
  /** The canonical element's id — what the draft is created against. */
  elementId: number | null;
  siteId: number | null;
  /** The provisional draft's id, once one exists. */
  draftId: number | null;
  /**
   * Whether the draft being edited is provisional. Sending `provisional` for a
   * named draft narrows the server's lookup to provisional drafts and it stops
   * resolving, so it's only ever sent when true — or when this request is the
   * one creating the draft, which always creates a provisional one.
   */
  isProvisional: boolean;
  /** Autosave is skipped entirely when this is false (revisions, read-only). */
  enabled: boolean;
  /** How long to wait after the last edit before saving, per change kind. */
  debounceMs?: Partial<Record<FormChangeKind, number>>;
  /**
   * Called after each successful save with the element's new last-modified
   * stamps. An autosave moves `dateUpdated`, so whoever is watching for
   * upstream edits has to re-baseline against these or it will report this
   * write as someone else's.
   */
  onSaved?: (timestamps: {
    element: number | null;
    canonical: number | null;
  }) => void;
}

export interface ElementAutosaveDependencies {
  post: typeof actionClient.post;
}

/**
 * Autosaves the editor's unsaved changes into a provisional draft, the way the
 * legacy element editor does.
 *
 * The first save creates the draft; `draftId` then identifies it for every
 * later save and is exposed so the host can submit it alongside the form. The
 * canonical `elementId` is always sent, matching the hidden input the legacy
 * editor posts.
 *
 * Saves are debounced and serialized — a save in flight defers the next one
 * rather than racing it, so a burst of typing collapses into one trailing
 * request per quiet period.
 */
export function useElementAutosave<T extends object>(
  form: InertiaForm<T>,
  options: ElementAutosaveOptions,
  dependencies: ElementAutosaveDependencies = actionClient
) {
  const status = ref<AutosaveStatus>('idle');
  const draftId = ref<number | null>(options.draftId);
  const savedAt = ref<string | null>(null);
  const error = ref<string | null>(null);
  const httpStatus = ref<number | null>(null);

  // The three pieces of what a save returned are held in one ref rather than
  // separately, so they can only ever be read as a matched set. The host
  // watches them together and reads them over its page props; updating them
  // one at a time would publish a half-written state — a fresh form beside
  // the previous screen, or worse, `clearSaved()` dropping the form and
  // leaving the screen standing long enough for the host to re-apply the
  // screen it had just dropped.
  const saved = shallowRef<{
    form: FormPayload | null;
    screen: FormValues | null;
    modified: string[];
  }>({form: null, screen: null, modified: []});

  const formPayload = computed(() => saved.value.form);
  const screenPayload = computed(() => saved.value.screen);
  const modified = computed(() => saved.value.modified);

  let inFlight: Promise<void> | null = null;
  let pending = false;
  let controller: AbortController | null = null;
  let cancelled = false;

  async function send(): Promise<void> {
    status.value = 'saving';
    error.value = null;
    httpStatus.value = null;
    cancelled = false;
    controller = new AbortController();

    const payload: FormValues = {
      elementType: options.elementType,
      elementId: options.elementId,
      siteId: options.siteId,
    };
    Object.assign(payload, form.data());

    // No draft yet means this request creates one; an existing provisional
    // draft is targeted by id and stays provisional.
    if (draftId.value !== null) {
      payload.draftId = draftId.value;
    }

    if (options.isProvisional || draftId.value === null) {
      payload.provisional = 1;
    }

    try {
      const {data} = await dependencies.post(options.url, payload, {
        signal: controller.signal,
      });

      draftId.value = data.draftId ?? draftId.value;
      savedAt.value = data.timestamp ?? null;
      saved.value = {
        // The response carries the field layout as the server now sees it,
        // which is the only place a nested element created by this save (a new
        // Matrix entry or address) can get its own Form payload from.
        form: data.form ?? saved.value.form,
        // …and the rest of the edit screen as a fresh page load would render
        // it. The first save of a canonical element creates a provisional
        // draft, and from that moment the screen around the form is a draft's —
        // the unsaved changes notice, the Discard changes button, the drafts
        // menu.
        screen: data.screen ?? saved.value.screen,
        // The element's whole modified set, not just this request's changes.
        modified: data.modifiedAttributes ?? [],
      };
      status.value = 'saved';

      options.onSaved?.({
        element: data.updatedTimestamp ?? null,
        canonical: data.canonicalUpdatedTimestamp ?? null,
      });
    } catch (e) {
      // Our own abort, not a failure.
      if (cancelled) {
        return;
      }

      status.value = 'failed';
      if (axios.isAxiosError<{message?: string}>(e)) {
        error.value = e.response?.data?.message ?? null;
        httpStatus.value = e.response?.status ?? null;
      }
    } finally {
      controller = null;
    }
  }

  async function save(): Promise<void> {
    // A real submission is authoritative — don't race a draft write against it,
    // and don't autosave the values it just wrote back.
    if (!options.enabled || form.processing) {
      return;
    }

    // Coalesce: whoever is already saving will pick up the newer values on its
    // trailing run, so at most one extra request is ever queued.
    if (inFlight) {
      pending = true;

      return inFlight;
    }

    inFlight = send().finally(async () => {
      inFlight = null;

      if (pending) {
        pending = false;
        await save();
      }
    });

    return inFlight;
  }

  // Driven by the Form renderers' change callbacks rather than a deep watch on
  // the form: the form is created empty and its keys are added dynamically, so
  // watching `form.data()` doesn't reliably track them.
  const delays: Record<FormChangeKind, number> = {
    typing: options.debounceMs?.typing ?? 1000,
    discrete: options.debounceMs?.discrete ?? 100,
  };

  // Re-armed per call, so a pending save always waits out the newest change.
  const delay = ref(delays.discrete);

  // `useDebounceFn` hands back no reference to the timer it arms, so there is
  // nothing to clear when a save is called off mid-debounce. The callback is
  // gated instead: `cancel()` disarms it and the timer fires into a no-op.
  let armed = false;
  const debounced = useDebounceFn(() => {
    if (!armed) {
      return;
    }

    armed = false;
    void save();
  }, delay);

  let suspended = false;

  function schedule(kind: FormChangeKind = 'discrete'): void {
    // A submission in flight is authoritative, and its response re-seeds the
    // renderers with what it saved — the mutations that reconcile emits are the
    // server echoing the save back, not a fresh edit, and they arrive before
    // the visit's own callbacks get a chance to say so. Arming for them would
    // rebuild the very draft an applied save just consumed. Same rule as
    // {@link save}, one step earlier.
    if (suspended || form.processing) {
      return;
    }

    delay.value = delays[kind];
    armed = true;
    void debounced();
  }

  /**
   * Runs `during` without arming autosave. Re-baselining after a save emits
   * mutations of its own, which would otherwise recreate the very draft the
   * save just consumed.
   */
  function suspend(during: () => void): void {
    suspended = true;

    try {
      during();
    } finally {
      // Released on a microtask so synchronously-emitted mutations are covered.
      void Promise.resolve().then(() => (suspended = false));
    }
  }

  function setDraftId(value: number | null): void {
    draftId.value = value;
  }

  /**
   * Drops what the last save returned, so a payload arriving from the server by
   * another route — an Inertia visit — takes over again.
   *
   * One write, so nothing can observe the form gone while the screen is still
   * standing. `draftId` deliberately survives: it's the live pointer, not a
   * stashed payload.
   */
  function clearSaved(): void {
    saved.value = {form: null, screen: null, modified: []};
  }

  /**
   * Abandons the in-flight save and any queued follow-up — the one coalesced
   * behind a request already out, and the one a keystroke armed but whose
   * debounce has not run yet. The baseline only advances on a save that lands,
   * so the edits go out with the next mutation.
   */
  function cancel(): void {
    cancelled = true;
    pending = false;
    armed = false;
    controller?.abort();

    if (status.value === 'saving') {
      status.value = 'idle';
    }
  }

  return {
    status: readonly(status),
    draftId: readonly(draftId),
    savedAt: readonly(savedAt),
    error: readonly(error),
    httpStatus: readonly(httpStatus),
    modified,
    form: formPayload,
    screen: screenPayload,
    save,
    schedule,
    cancel,
    suspend,
    setDraftId,
    clearSaved,
  };
}
