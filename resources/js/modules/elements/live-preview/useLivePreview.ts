import {actionClient} from '@craftcms/ui';
import {useLocalStorage} from '@vueuse/core';
import {
  computed,
  getCurrentScope,
  onScopeDispose,
  readonly,
  ref,
  shallowReadonly,
  shallowRef,
  watch,
} from 'vue';
import type {DeviceOrientation, DeviceType} from './devicePreview';
import {
  broadcastPreviewEvent,
  createLivePreviewEmitter,
  registerPreview,
} from './previews';
import {isSameOrigin, tokenizedPreviewUrl} from './previewUrl';

/** Somewhere Live Preview can show the element. */
export interface LivePreviewTarget {
  label: string;
  url: string;
  /** Whether the target reloads as edits are autosaved. */
  refresh: boolean;
}

/** What the edit screen payload carries for Live Preview. */
export interface ElementPreview {
  targets: Array<LivePreviewTarget>;
  createTokenUrl: string;
  tokenParams: Record<string, string | number>;
  token: string;
  /** How long the token lasts once created, in seconds. */
  tokenDuration: number;
  tokenParam: string;
  paramValue: string;
  siteTokenParam: string;
  siteToken: string | null;
  /** `false` when `useIframeResizer` is off. */
  iframeResizerOptions: Record<string, unknown> | false;
}

/** One iframe; a refresh loads a new one behind the current one, then swaps. */
export interface LivePreviewFrame {
  id: number;
  url: string;
  ready: boolean;
}

interface Autosave {
  save: () => Promise<void>;
  readonly hasPendingChanges: boolean;
}

export interface LivePreviewDependencies {
  post: (url: string, data: object) => Promise<unknown>;
}

declare global {
  interface Window {
    iFrameResize?: (
      options: Record<string, unknown>,
      target: HTMLIFrameElement
    ) => unknown;
  }
}

/**
 * Drives Live Preview for an element editor: the token, the frames, and the
 * preferences that outlive it (device, orientation).
 */
export function useLivePreview(
  preview: () => ElementPreview | null,
  autosave: Autosave,
  dependencies: LivePreviewDependencies = actionClient
) {
  const isOpen = ref(false);
  const activeIndex = ref(0);
  // Set once the controller exists; events can't fire before then.
  let self: unknown;
  const events = createLivePreviewEmitter((event, payload) =>
    broadcastPreviewEvent(self as LivePreviewController, event, payload)
  );
  /** The editor's pane while the preview is shown, set by the pane itself. */
  const editor = shallowRef<HTMLElement | null>(null);
  const frames = ref<Array<LivePreviewFrame>>([]);
  const device = useLocalStorage<DeviceType>('LivePreview.device', 'desktop');
  const orientation = useLocalStorage<DeviceOrientation>(
    'LivePreview.orientation',
    'portrait'
  );

  const targets = computed(() => preview()?.targets ?? []);
  const activeTarget = computed(
    () => targets.value[activeIndex.value] ?? targets.value[0] ?? null
  );

  /**
   * The token the frames use. Each autosave's payload brings a new one, but a
   * token only needs replacing when what it points at changes, so the first is
   * kept until then rather than created again on every save.
   */
  const token = shallowRef<{
    identity: string;
    value: string;
    created: Promise<unknown> | null;
    /** When `created` was asked for. */
    createdAt: number;
    params: ElementPreview['tokenParams'];
    url: string;
  } | null>(null);

  function identityOf(data: ElementPreview): string {
    const {previewToken: _, ...params} = data.tokenParams;

    return JSON.stringify(params);
  }

  watch(
    preview,
    (data) => {
      if (data && token.value?.identity !== identityOf(data)) {
        token.value = {
          identity: identityOf(data),
          value: data.token,
          created: null,
          createdAt: 0,
          params: data.tokenParams,
          url: data.createTokenUrl,
        };
      }
    },
    {immediate: true}
  );

  async function ensureToken(): Promise<string> {
    const current = token.value;

    if (!current) {
      throw new Error('Missing preview token');
    }

    // Asked for again halfway through its life, which also extends it, so a
    // preview left open doesn't outlive it.
    const duration = (preview()?.tokenDuration ?? 0) * 1000;
    if (
      current.created &&
      duration &&
      Date.now() - current.createdAt > duration / 2
    ) {
      current.created = null;
    }

    if (!current.created) {
      current.createdAt = Date.now();
      current.created = dependencies
        .post(current.url, current.params)
        .catch((error) => {
          current.created = null;
          throw error;
        });
    }
    await current.created;

    return current.value;
  }

  async function urlFor(target: LivePreviewTarget): Promise<string> {
    const data = preview();

    if (!data) {
      throw new Error('Live Preview is unavailable');
    }

    return tokenizedPreviewUrl(target.url, {
      tokenParam: data.tokenParam,
      token: await ensureToken(),
      previewParam: 'x-craft-live-preview',
      paramValue: data.paramValue,
      siteTokenParam: data.siteTokenParam,
      siteToken: data.siteToken,
    });
  }

  // Refreshes still waiting on their URL, before they have a frame to load.
  const resolving = ref(0);
  const isLoading = computed(
    () => resolving.value > 0 || frames.value.some((frame) => !frame.ready)
  );

  let nextFrameId = 0;
  const readyFrame = shallowRef<HTMLIFrameElement | null>(null);
  let pendingScroll: {x: number; y: number} | null = null;
  const error = ref<string | null>(null);

  /**
   * Loads the active target into a new frame.
   *
   * Targets that don't auto-refresh only reload when asked to: `reload: true`,
   * as the refresh button does, or a target switch (`resetScroll`). `reload:
   * false` keeps the current frame even for one that does.
   */
  async function refresh({
    resetScroll = false,
    reload,
  }: {resetScroll?: boolean; reload?: boolean} = {}): Promise<void> {
    const target = activeTarget.value;

    if (!isOpen.value || !target) {
      return;
    }

    const willReload =
      frames.value.length === 0 || (reload ?? (resetScroll || target.refresh));

    events.emit('before-update-iframe', {
      target,
      resetScroll,
      refresh: willReload,
    });

    if (!willReload) {
      return;
    }

    let url: string;
    resolving.value++;

    try {
      url = await urlFor(target);
      error.value = null;
    } catch (e) {
      error.value = e instanceof Error ? e.message : String(e);
      return;
    } finally {
      resolving.value--;
    }

    if (!isOpen.value) {
      return;
    }

    pendingScroll = resetScroll ? null : readScroll(readyFrame.value);

    frames.value = [
      // Anything still loading behind the current frame is out of date.
      ...frames.value.filter((frame) => frame.ready),
      {id: ++nextFrameId, url, ready: false},
    ];
  }

  function readScroll(
    frame: HTMLIFrameElement | null
  ): {x: number; y: number} | null {
    if (!frame || !isSameOrigin(frame.src)) {
      return null;
    }

    try {
      const win = frame.contentWindow;

      return win ? {x: win.scrollX, y: win.scrollY} : null;
    } catch {
      return null;
    }
  }

  /** Called once a frame has loaded, to show it in place of the last one. */
  function frameLoaded(id: number, el: HTMLIFrameElement): void {
    const frame = frames.value.find((frame) => frame.id === id);

    if (!frame || frame.ready) {
      return;
    }

    if (pendingScroll && isSameOrigin(frame.url)) {
      try {
        el.contentWindow?.scrollTo(pendingScroll.x, pendingScroll.y);
      } catch {
        // Navigated somewhere it can't be reached into.
      }
    }

    pendingScroll = null;
    readyFrame.value = el;
    frames.value = [{...frame, ready: true}];
  }

  /** Brings the preview up to date with any edits still on autosave's debounce. */
  async function flush(): Promise<void> {
    if (autosave.hasPendingChanges) {
      await autosave.save();
    }
  }

  async function open(): Promise<void> {
    if (isOpen.value || !activeTarget.value) {
      return;
    }

    events.emit('before-open');
    await flush();
    isOpen.value = true;
    events.emit('open');
    await refresh({reload: true});
  }

  async function close(): Promise<void> {
    if (!isOpen.value) {
      return;
    }

    await flush();
    events.emit('before-close');
    isOpen.value = false;
    frames.value = [];
    readyFrame.value = null;
    events.emit('close');
  }

  function switchTarget(index: number): void {
    if (index === activeIndex.value) {
      return;
    }

    activeIndex.value = index;
    events.emit('switch-target', {target: activeTarget.value!});
    void refresh({resetScroll: true});
  }

  /** Tells listeners about a draft the editor autosaved. */
  function draftSaved(data: unknown): void {
    events.emit('after-save-draft', {data});
  }

  // A canonical element's first autosave creates the draft the token has to
  // point at instead, so the frame reloads against the new one.
  watch(
    () => token.value?.identity,
    (identity, previous) => {
      if (previous !== undefined && identity !== previous) {
        void refresh({reload: true});
      }
    }
  );

  // The page can lose its targets mid-session — a URL format that renders
  // nothing for the edited values, say.
  watch(targets, (list) => {
    if (activeIndex.value >= list.length) {
      activeIndex.value = 0;
    }

    if (list.length === 0) {
      void close();
    }
  });

  const controller = {
    isOpen: readonly(isOpen),
    targets,
    activeIndex: readonly(activeIndex),
    activeTarget,
    frames: readonly(frames),
    error: readonly(error),
    isLoading,
    device,
    orientation,
    iframeResizerOptions: computed(
      () => preview()?.iframeResizerOptions ?? false
    ),
    open,
    close,
    refresh,
    switchTarget,
    frameLoaded,
    urlFor,
    draftSaved,
    editor,
    /** The frame being shown, once one has loaded. */
    iframe: shallowReadonly(readyFrame),
    on: events.on,
    emit: events.emit,
  };

  self = controller;

  if (getCurrentScope()) {
    onScopeDispose(registerPreview(controller));
  }

  return controller;
}

export type LivePreviewController = ReturnType<typeof useLivePreview>;
