import type {LivePreviewController, LivePreviewTarget} from './useLivePreview';

/** What each Live Preview event passes to its listeners. */
export interface LivePreviewEvents {
  beforeOpen: void;
  open: void;
  beforeClose: void;
  close: void;
  switchTarget: {target: LivePreviewTarget};
  /** Every time the frame might reload; `refresh` says whether it will. */
  beforeUpdateIframe: {
    target: LivePreviewTarget;
    resetScroll: boolean;
    refresh: boolean;
  };
  /** A new frame was added for the target, before it has loaded. */
  afterUpdateIframe: {target: LivePreviewTarget; iframe: HTMLIFrameElement};
  /** The editor autosaved a draft; `data` is the save's response. */
  afterSaveDraft: {data: unknown};
  /** The divider between the editor and the preview moved. */
  drag: void;
}

export type LivePreviewEventName = keyof LivePreviewEvents;

export type LivePreviewListener<E extends LivePreviewEventName> = (
  payload: LivePreviewEvents[E]
) => void;

type AnyListener = (payload: unknown) => void;

export interface LivePreviewEmitter {
  /** Listens for an event. Returns a function that stops listening. */
  on<E extends LivePreviewEventName>(
    event: E,
    listener: LivePreviewListener<E>
  ): () => void;
  emit<E extends LivePreviewEventName>(
    event: E,
    ...payload: LivePreviewEvents[E] extends void ? [] : [LivePreviewEvents[E]]
  ): void;
}

export function createLivePreviewEmitter(
  onEmit?: (event: LivePreviewEventName, payload: unknown) => void
): LivePreviewEmitter {
  const listeners = new Map<LivePreviewEventName, Set<AnyListener>>();

  return {
    on(event, listener) {
      const set = listeners.get(event) ?? new Set();
      listeners.set(event, set.add(listener as AnyListener));

      return () => void set.delete(listener as AnyListener);
    },
    emit(event, ...[payload]) {
      for (const listener of [...(listeners.get(event) ?? [])]) {
        listener(payload);
      }
      onEmit?.(event, payload);
    },
  };
}

/*
 * The page's previews
 */
const previews = new Set<LivePreviewController>();
const watchers = new Set<
  (preview: LivePreviewController) => void | (() => void)
>();
const cleanups = new Map<LivePreviewController, Array<() => void>>();

type GlobalListener<E extends LivePreviewEventName> = (
  payload: LivePreviewEvents[E],
  preview: LivePreviewController
) => void;

const globalListeners = new Map<
  LivePreviewEventName,
  Set<GlobalListener<LivePreviewEventName>>
>();

function runWatcher(
  watcher: (preview: LivePreviewController) => void | (() => void),
  preview: LivePreviewController
): void {
  const cleanup = watcher(preview);

  if (cleanup) {
    cleanups.set(preview, [...(cleanups.get(preview) ?? []), cleanup]);
  }
}

/** Adds a preview to the page's list. Returns a function that removes it. */
export function registerPreview(preview: LivePreviewController): () => void {
  previews.add(preview);
  watchers.forEach((watcher) => runWatcher(watcher, preview));

  return () => {
    previews.delete(preview);
    cleanups.get(preview)?.forEach((cleanup) => cleanup());
    cleanups.delete(preview);
  };
}

/** Passes an event from one preview on to listeners for every preview. */
export function broadcastPreviewEvent(
  preview: LivePreviewController,
  event: LivePreviewEventName,
  payload: unknown
): void {
  for (const listener of [...(globalListeners.get(event) ?? [])]) {
    listener(payload as never, preview);
  }
}

/** Every preview on the page, open or not. */
export function getPreviews(): LivePreviewController[] {
  return [...previews];
}

/** The open preview, if there is one. */
export function getActivePreview(): LivePreviewController | null {
  return getPreviews().find((preview) => preview.isOpen.value) ?? null;
}

/**
 * Reloads every open preview whose target refreshes automatically, for changes
 * made outside its editor — a related element saved in a slideout, say.
 */
export function refreshPreviews(): void {
  for (const preview of previews) {
    void preview.refresh();
  }
}

/**
 * Listens for an event from every preview on the page, including ones added
 * later. Returns a function that stops listening.
 */
export function onPreviewEvent<E extends LivePreviewEventName>(
  event: E,
  listener: GlobalListener<E>
): () => void {
  const set = globalListeners.get(event) ?? new Set();
  globalListeners.set(
    event,
    set.add(listener as GlobalListener<LivePreviewEventName>)
  );

  return () =>
    void set.delete(listener as GlobalListener<LivePreviewEventName>);
}

/**
 * Calls `callback` for every preview on the page, now and as they're added.
 * Whatever it returns is called when that preview goes away. Returns a function
 * that stops watching.
 */
export function watchPreviews(
  callback: (preview: LivePreviewController) => void | (() => void)
): () => void {
  watchers.add(callback);
  previews.forEach((preview) => runWatcher(callback, preview));

  return () => void watchers.delete(callback);
}
