import {actionClient} from '@craftcms/ui';
import {useDebounceFn} from '@vueuse/core';
import type {InertiaForm} from '@inertiajs/vue3';
import {readonly, ref, type Ref} from 'vue';
import type {FormChangeKind, FormPayload} from '@/modules/forms/types';

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
  /** How long to wait after the last edit before saving, per change kind. */
  debounceMs?: Partial<Record<FormChangeKind, number>>;
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
  httpStatus: Readonly<Ref<number | null>>;
  modified: Readonly<Ref<string[]>>;
  form: Readonly<Ref<FormPayload | null>>;
  save: () => Promise<void>;
  schedule: (kind?: FormChangeKind) => void;
  cancel: () => void;
  suspend: (during: () => void) => void;
  setDraftId: (value: number | null) => void;
  clearForm: () => void;
} {
  const status = ref<AutosaveStatus>('idle');
  const draftId = ref<number | null>(options.draftId);
  const savedAt = ref<string | null>(null);
  const error = ref<string | null>(null);
  const httpStatus = ref<number | null>(null);
  const modified = ref<string[]>([]);
  const formPayload = ref<FormPayload | null>(null);

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
      const {data} = await actionClient.post(options.url, payload, {
        signal: controller.signal,
      });

      draftId.value = data.draftId ?? draftId.value;
      savedAt.value = data.timestamp ?? null;
      // The response carries the field layout as the server now sees it, which
      // is the only place a nested element created by this save (a new Matrix
      // entry or address) can get its own Form payload from.
      formPayload.value = data.form ?? formPayload.value;
      // The element's whole modified set, not just this request's changes.
      modified.value = data.modifiedAttributes ?? [];
      status.value = 'saved';
    } catch (e: any) {
      // Our own abort, not a failure.
      if (cancelled) {
        return;
      }

      status.value = 'failed';
      error.value = e?.response?.data?.message ?? null;
      httpStatus.value = e?.response?.status ?? null;
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
  const debounced = useDebounceFn(() => void save(), delay);

  let suspended = false;

  function schedule(kind: FormChangeKind = 'discrete'): void {
    if (suspended) {
      return;
    }

    delay.value = delays[kind];
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
   * Drops the saved layout, so a payload arriving from the server by another
   * route — an Inertia visit — takes over again.
   */
  function clearForm(): void {
    formPayload.value = null;
  }

  /**
   * Abandons the in-flight save and any queued follow-up. The baseline only
   * advances on a save that lands, so the edits go out with the next mutation.
   */
  function cancel(): void {
    cancelled = true;
    pending = false;
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
    modified: readonly(modified) as Readonly<Ref<string[]>>,
    form: readonly(formPayload) as Readonly<Ref<FormPayload | null>>,
    save,
    schedule,
    cancel,
    suspend,
    setDraftId,
    clearForm,
  };
}
