import {actionClient} from '@craftcms/ui';
import {useLocalStorage} from '@vueuse/core';
import {computed, readonly, ref, shallowRef, watch} from 'vue';
import type {DeviceOrientation, DeviceType} from './devicePreview';
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

/** The editor pane's share of the window, or its width in pixels. */
export const DEFAULT_EDITOR_WIDTH = 0.33;
export const MIN_PANE_WIDTH = 320;

/**
 * Drives Live Preview for an element editor: the token, the frames, and the
 * preferences that outlive it (pane width, device, orientation).
 */
export function useLivePreview(
  preview: () => ElementPreview | null,
  autosave: Autosave,
  dependencies: LivePreviewDependencies = actionClient
) {
  const isOpen = ref(false);
  const activeIndex = ref(0);
  const frames = ref<Array<LivePreviewFrame>>([]);
  const editorWidth = useLocalStorage<number>(
    'LivePreview.editorWidth',
    DEFAULT_EDITOR_WIDTH
  );
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

    current.created ??= dependencies
      .post(current.url, current.params)
      .catch((error) => {
        current.created = null;
        throw error;
      });
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

  let nextFrameId = 0;
  let readyFrame: HTMLIFrameElement | null = null;
  let pendingScroll: {x: number; y: number} | null = null;
  const error = ref<string | null>(null);

  /**
   * Loads the active target into a new frame.
   *
   * Targets that don't auto-refresh only reload when asked to: a refresh
   * button press (`force`), or a target switch (`resetScroll`).
   */
  async function refresh({
    resetScroll = false,
    force = false,
  }: {resetScroll?: boolean; force?: boolean} = {}): Promise<void> {
    const target = activeTarget.value;

    if (!isOpen.value || !target) {
      return;
    }

    if (!force && !resetScroll && frames.value.length > 0 && !target.refresh) {
      return;
    }

    let url: string;

    try {
      url = await urlFor(target);
      error.value = null;
    } catch (e) {
      error.value = e instanceof Error ? e.message : String(e);
      return;
    }

    if (!isOpen.value) {
      return;
    }

    pendingScroll = resetScroll ? null : readScroll(readyFrame);

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
    readyFrame = el;
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

    await flush();
    isOpen.value = true;
    await refresh({force: true});
  }

  async function close(): Promise<void> {
    if (!isOpen.value) {
      return;
    }

    await flush();
    isOpen.value = false;
    frames.value = [];
    readyFrame = null;
  }

  function switchTarget(index: number): void {
    if (index === activeIndex.value) {
      return;
    }

    activeIndex.value = index;
    void refresh({resetScroll: true});
  }

  // A canonical element's first autosave creates the draft the token has to
  // point at instead, so the frame reloads against the new one.
  watch(
    () => token.value?.identity,
    (identity, previous) => {
      if (previous !== undefined && identity !== previous) {
        void refresh({force: true});
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

  return {
    isOpen: readonly(isOpen),
    targets,
    activeIndex: readonly(activeIndex),
    activeTarget,
    frames: readonly(frames),
    error: readonly(error),
    editorWidth,
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
  };
}

export type LivePreviewController = ReturnType<typeof useLivePreview>;
