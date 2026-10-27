/**
 * `Craft.Preview`, Craft 5's Live Preview class, over the Vue Live Preview.
 *
 * Each preview on the page gets an instance that mirrors it: its state, its
 * methods, and its events under Craft 5's names and payloads, so listeners
 * added with `.on()` or `Garnish.on(Craft.Preview, …)` keep working.
 *
 * Constructing one no longer builds a preview: the editor owns it now. A
 * constructed instance opens the element's standalone preview page instead.
 */
import {Base, type GarnishBaseSettings} from '@craftcms/garnish';
import {
  refreshPreviews,
  watchPreviews,
} from '@/modules/elements/live-preview/previews';
import type {DeviceType} from '@/modules/elements/live-preview/devicePreview';
import type {LivePreviewController} from '@/modules/elements/live-preview/useLivePreview';

declare const Craft: any;
declare const $: any;

export interface LegacyPreviewSettings extends GarnishBaseSettings {
  elementType: string | null;
  elementId: number | null;
  draftId: number | null;
  revisionId: number | null;
  siteId: number | null;
  standaloneMode: boolean;
  redirectUrl: string | null;
  onBeforeLoad: () => Promise<void>;
}

interface LegacyClassHandler {
  target: Function;
  type: string;
  data: unknown;
  handler: (event: Record<string, unknown>) => void;
}

export class LegacyPreview extends Base<LegacyPreviewSettings> {
  static defaults: LegacyPreviewSettings = {
    elementType: null,
    elementId: null,
    draftId: null,
    revisionId: null,
    siteId: null,
    standaloneMode: false,
    redirectUrl: null,
    onBeforeLoad: async () => {},
  };

  static defaultEditorWidth = 0.33;
  static minEditorWidthInPx = 320;
  static instances: LegacyPreview[] = [];

  static refresh(): void {
    refreshPreviews();
  }

  static getActive(): LegacyPreview | undefined {
    return LegacyPreview.instances.find((preview) => preview.isActive);
  }

  readonly #controller: LivePreviewController | null;
  readonly #stops: Array<() => void> = [];

  /** Craft 5 exposed its `Craft.ElementEditor`; the Vue editor has none. */
  readonly elementEditor = null;

  constructor(
    settings?: Partial<LegacyPreviewSettings>,
    controller?: LivePreviewController
  ) {
    super();
    this.setSettings(settings, LegacyPreview.defaults);
    this.#controller = controller ?? null;

    if (!controller) {
      console.warn(
        'Constructing Craft.Preview no longer builds a preview; it opens the element’s preview page. Use getActivePreview() from @craftcms/cp to work with the page’s own.'
      );

      if (this.settings!.standaloneMode) {
        void this.open();
      }
      return;
    }

    LegacyPreview.instances.push(this);
    this.#bridgeEvents(controller);
  }

  /**
   * Also tells class-level listeners added with the page's legacy
   * `Garnish.on()`, which keeps its own list apart from this class's.
   */
  override trigger(type: string, data?: Record<string, unknown>): void {
    super.trigger(type, data);

    const handlers: LegacyClassHandler[] | undefined = (
      window as {Garnish?: {_eventHandlers?: LegacyClassHandler[]}}
    ).Garnish?._eventHandlers;

    for (const handler of handlers?.filter(
      (handler) =>
        handler?.target &&
        this instanceof handler.target &&
        handler.type === type
    ) ?? []) {
      handler.handler({data: handler.data, ...data, type, target: this});
    }
  }

  #bridgeEvents(controller: LivePreviewController): void {
    const on = controller.on;

    this.#stops.push(
      on('before-open', () => this.trigger('beforeOpen')),
      on('open', () => {
        this.trigger('slideIn');
        this.trigger('open');
      }),
      on('before-close', () => this.trigger('beforeClose')),
      on('close', () => {
        this.trigger('close');
        this.trigger('slideOut');
      }),
      on('switch-target', ({target}) =>
        this.trigger('switchTarget', {previewTarget: target})
      ),
      on('before-update-iframe', ({target, resetScroll, refresh}) =>
        this.trigger('beforeUpdateIframe', {
          previewTarget: target,
          resetScroll,
          refresh,
        })
      ),
      on('after-update-iframe', ({target, iframe}) =>
        this.trigger('afterUpdateIframe', {
          previewTarget: target,
          $iframe: $(iframe),
        })
      ),
      // Craft 5 passed the request's response, with the body under `data`.
      on('after-save-draft', ({data}) =>
        this.trigger('afterSaveDraft', {response: {data}})
      ),
      on('drag', () => this.trigger('drag'))
    );
  }

  get isActive(): boolean {
    return this.#controller?.isOpen.value ?? false;
  }

  get isVisible(): boolean {
    return this.isActive;
  }

  /** The index of the target being previewed. */
  get activeTarget(): number {
    return this.#controller?.activeIndex.value ?? 0;
  }

  /** The URL the preview frame loaded. */
  get url(): string | null {
    const frames = this.#controller?.frames.value ?? [];

    return (frames.find((frame) => frame.ready) ?? frames.at(-1))?.url ?? null;
  }

  get $iframe(): any {
    const iframe = this.#controller?.iframe.value;

    return iframe ? $(iframe) : null;
  }

  get $editorContainer(): any {
    const editor = this.#controller?.editor.value;

    return editor ? $(editor) : null;
  }

  get editorWidthInPx(): number {
    return this.#controller?.editor.value?.offsetWidth ?? 0;
  }

  /** The editor's width as a fraction of the window's. */
  get editorWidth(): number {
    return this.editorWidthInPx / window.innerWidth;
  }

  get currentDeviceType(): DeviceType {
    return this.#controller?.device.value ?? 'desktop';
  }

  get deviceOrientation(): string {
    return this.#controller?.orientation.value ?? 'portrait';
  }

  async open(): Promise<void> {
    if (this.#controller) {
      await this.#controller.open();
      return;
    }

    await this.settings!.onBeforeLoad();
    window.location.href = this.#standaloneUrl();
  }

  async close(): Promise<void> {
    await this.#controller?.close();
  }

  /**
   * Reloads the frame. `refresh` forces a reload (`true`) or skips it
   * (`false`); left out, the target's auto-refresh setting decides.
   */
  updateIframe(resetScroll?: boolean, refresh?: boolean): false | void {
    if (!this.isActive) {
      return false;
    }

    void this.#controller!.refresh({
      resetScroll: resetScroll === true,
      reload: refresh,
    });
  }

  switchTarget(index: number): void {
    this.#controller?.switchTarget(index);
  }

  /** Takes a device type, or Craft 5's device button with it in its data. */
  switchDeviceType(
    device: DeviceType | {data: (key: string) => unknown}
  ): void {
    const type =
      typeof device === 'string'
        ? device
        : (device.data('deviceType') as DeviceType);

    if (this.#controller && type) {
      this.#controller.device.value = type;
    }
  }

  switchOrientation(): false | void {
    if (!this.#controller || this.#controller.device.value === 'desktop') {
      return false;
    }

    const orientation = this.#controller.orientation;
    orientation.value =
      orientation.value === 'landscape' ? 'portrait' : 'landscape';
  }

  override destroy(): void {
    this.#stops.splice(0).forEach((stop) => stop());
    LegacyPreview.instances = LegacyPreview.instances.filter(
      (preview) => preview !== this
    );
    super.destroy();
  }

  #standaloneUrl(): string {
    const {elementId, siteId, draftId, revisionId} = this.settings!;
    const params = Object.fromEntries(
      Object.entries({siteId, draftId, revisionId}).filter(
        ([, value]) => value != null
      )
    );

    return Craft.getCpUrl(`preview/${elementId}`, params);
  }
}

/** Mirrors every preview on the page with a `Craft.Preview` instance. */
export function mirrorPreviews(): () => void {
  return watchPreviews((controller) => {
    const preview = new LegacyPreview({}, controller);

    return () => preview.destroy();
  });
}
