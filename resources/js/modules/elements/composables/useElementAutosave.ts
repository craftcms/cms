import {actionClient} from '@craftcms/ui';
import {useDebounceFn} from '@vueuse/core';
import type {InertiaForm} from '@inertiajs/vue3';
import {computed, readonly, ref, shallowRef, type Ref} from 'vue';
import type {FormPayload} from '@/modules/forms/types';

export type AutosaveStatus = 'idle' | 'saving' | 'saved' | 'failed';

interface Options {
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
  /** How long to wait after the last edit before saving. */
  debounceMs?: number;
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
export function useElementAutosave(
  form: InertiaForm<Record<string, any>>,
  options: Options
): {
  status: Readonly<Ref<AutosaveStatus>>;
  draftId: Readonly<Ref<number | null>>;
  savedAt: Readonly<Ref<string | null>>;
  error: Readonly<Ref<string | null>>;
  form: Readonly<Ref<FormPayload | null>>;
  screen: Readonly<Ref<Record<string, unknown> | null>>;
  save: () => Promise<void>;
  schedule: () => void;
  cancel: () => void;
  suspend: (during: () => void) => void;
  setDraftId: (value: number | null) => void;
  clearSaved: () => void;
} {
  const status = ref<AutosaveStatus>('idle');
  const draftId = ref<number | null>(options.draftId);
  const savedAt = ref<string | null>(null);
  const error = ref<string | null>(null);

  // The two halves of what a save returned are held in one ref rather than two,
  // so they can only ever be read as a matched pair. The host watches them
  // together and reads them over its page props; updating them one at a time
  // would publish a half-written state — a fresh form beside the previous
  // screen, or worse, `clearSaved()` dropping the form and leaving the screen
  // standing long enough for the host to re-apply the screen it had just
  // dropped.
  const saved = shallowRef<{
    form: FormPayload | null;
    screen: Record<string, unknown> | null;
  }>({form: null, screen: null});

  const formPayload = computed(() => saved.value.form);
  const screenPayload = computed(() => saved.value.screen);

  let inFlight: Promise<void> | null = null;
  let pending = false;

  async function send(): Promise<void> {
    status.value = 'saving';
    error.value = null;

    const payload: Record<string, any> = {
      ...form.data(),
      elementType: options.elementType,
      elementId: options.elementId,
      siteId: options.siteId,
    };

    // No draft yet means this request creates one; an existing provisional
    // draft is targeted by id and stays provisional.
    if (draftId.value !== null) {
      payload.draftId = draftId.value;
    }

    if (options.isProvisional || draftId.value === null) {
      payload.provisional = 1;
    }

    try {
      const {data} = await actionClient.post(options.url, payload);

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
      };
      status.value = 'saved';

      options.onSaved?.({
        element: data.updatedTimestamp ?? null,
        canonical: data.canonicalUpdatedTimestamp ?? null,
      });
    } catch (e: any) {
      status.value = 'failed';
      error.value = e?.response?.data?.message ?? null;
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
  // Whether a scheduled save is still wanted by the time its timer fires. The
  // debounced function has no cancel of its own, so calling one off is a
  // matter of letting it run and do nothing.
  let scheduled = false;

  const debounced = useDebounceFn(() => {
    if (!scheduled) {
      return;
    }

    scheduled = false;
    void save();
  }, options.debounceMs ?? 1500);

  let suspended = false;

  function schedule(): void {
    // A submission in flight is authoritative, and its response re-seeds the
    // renderers with what it saved — the mutations that reconcile emits are the
    // server echoing the save back, not a fresh edit, and they arrive before
    // the visit's own callbacks get a chance to say so. Arming for them would
    // rebuild the very draft an applied save just consumed. Same rule as
    // {@link save}, one step earlier.
    if (suspended || form.processing) {
      return;
    }

    scheduled = true;
    void debounced();
  }

  /**
   * Calls off a save that hasn't gone out yet — the trailing edit before the
   * draft was discarded, which would otherwise recreate it.
   */
  function cancel(): void {
    scheduled = false;
    pending = false;
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
    saved.value = {form: null, screen: null};
  }

  return {
    status: readonly(status),
    draftId: readonly(draftId),
    savedAt: readonly(savedAt),
    error: readonly(error),
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
