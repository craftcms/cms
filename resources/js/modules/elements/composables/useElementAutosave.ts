import {actionClient} from '@craftcms/ui';
import {useDebounceFn} from '@vueuse/core';
import type {InertiaForm} from '@inertiajs/vue3';
import {readonly, ref, type Ref} from 'vue';

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
  /** Autosave is skipped entirely when this is false (revisions, read-only). */
  enabled: boolean;
  /** How long to wait after the last edit before saving. */
  debounceMs?: number;
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
  save: () => Promise<void>;
  schedule: () => void;
  suspend: (during: () => void) => void;
  setDraftId: (value: number | null) => void;
} {
  const status = ref<AutosaveStatus>('idle');
  const draftId = ref<number | null>(options.draftId);
  const savedAt = ref<string | null>(null);
  const error = ref<string | null>(null);

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

    payload.provisional = 1;

    try {
      const {data} = await actionClient.post(options.url, payload);

      draftId.value = data.draftId ?? draftId.value;
      savedAt.value = data.timestamp ?? null;
      status.value = 'saved';
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
  const debounced = useDebounceFn(
    () => void save(),
    options.debounceMs ?? 1500
  );

  let suspended = false;

  function schedule(): void {
    if (suspended) {
      return;
    }

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

  return {
    status: readonly(status),
    draftId: readonly(draftId),
    savedAt: readonly(savedAt),
    error: readonly(error),
    save,
    schedule,
    suspend,
    setDraftId,
  };
}
