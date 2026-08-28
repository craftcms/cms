import {actionClient} from '@src/utilities/api/actionClient.js';
import {t} from '@src/utilities/translate.js';
import type {
  ElementIndexAdapter,
  ElementIndexBody,
  ElementInfo,
  ElementSelectorEvent,
  ElementSelectorEventMap,
  ElementSelectorListener,
  ElementSelectorOptions,
  ElementSelectorState,
  LoadIndexBody,
  ResolvedElementSelectorOptions,
  SelectMeta,
} from './types.js';

const defaultLoadIndexBody: LoadIndexBody = async (action, params) => {
  // A bare action path is fine: `actionClient`'s request interceptor expands it
  // against the runtime CP trigger, so there is no `Craft.getActionUrl` here.
  const {data} = await actionClient.post(action, params);

  return data as ElementIndexBody;
};

/**
 * The element selector's business logic, with no rendering concerns.
 *
 * Owns selection state, the rules about what may be selected, the index request
 * parameters, and the shape of the payload handed back to the opener. A
 * presentation layer binds to it by subscribing to `change` and reading
 * {@link state}; user intent goes back in through {@link submit}, {@link cancel}
 * and {@link setSelection}. Nothing flows the other way, which is what keeps a
 * web component and a Vue component from drifting apart.
 *
 * @example
 * const controller = new ElementSelectorController({
 *   elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
 *   onSelect: (elements) => console.log(elements),
 * });
 *
 * controller.on('change', (state) => render(state));
 * await controller.open();
 */
export class ElementSelectorController<
  A extends ElementIndexAdapter = ElementIndexAdapter,
> {
  static readonly defaults = {
    multiSelect: false,
    disabledElementIds: [] as number[],
    disableElementsOnSelect: false,
    hideOnSelect: true,
    showTitle: false,
    fullscreen: false,
    bodyAction: 'element-selector-modals/body',
    indexSettings: {} as Record<string, unknown>,
    preferStoredSource: false,
    showSourcePath: true,
    hideSidebar: false,
  };

  readonly options: ResolvedElementSelectorOptions;

  #index: A | null = null;
  #selection: ElementInfo[] = [];
  #disabledElementIds: number[] = [];
  #indexBody: ElementIndexBody | null = null;
  #open = false;
  #loading = false;
  #busy = false;
  #error: Error | null = null;
  #state: ElementSelectorState;

  #listeners = new Map<ElementSelectorEvent, Set<(data: never) => void>>();

  constructor(options: ElementSelectorOptions) {
    this.options = {
      ...ElementSelectorController.defaults,
      // Resolved here rather than at module scope, where the translation
      // catalogue may not have loaded yet.
      modalTitle: t('Select element'),
      selectBtnLabel: t('Select'),
      ...stripUndefined(options),
    } as ResolvedElementSelectorOptions;

    this.#disabledElementIds = [...(this.options.disabledElementIds ?? [])];
    this.#state = this.#buildState();
  }

  get elementType(): string {
    return this.options.elementType;
  }

  get state(): ElementSelectorState {
    return this.#state;
  }

  get index(): A | null {
    return this.#index;
  }

  get hasSelection(): boolean {
    return this.#selection.length > 0;
  }

  // ───────────────────────────── observation ─────────────────────────────

  /** Subscribe to an event. Returns an unsubscribe function. */
  on<E extends ElementSelectorEvent>(
    event: E,
    listener: ElementSelectorListener<E>
  ): () => void {
    let set = this.#listeners.get(event);

    if (!set) {
      set = new Set();
      this.#listeners.set(event, set);
    }

    set.add(listener as (data: never) => void);

    return () => {
      this.#listeners.get(event)?.delete(listener as (data: never) => void);
    };
  }

  // ───────────────────────────── lifecycle ──────────────────────────────

  /**
   * Show the selector, loading the index body the first time.
   *
   * Reopening keeps the adapter, the loaded body and `disabledElementIds`, but
   * clears the selection — openers such as the relation field's element select
   * input cache one instance and reopen it, and a stale selection would leave
   * the Select button enabled before the user has picked anything.
   */
  async open(): Promise<void> {
    this.#selection = [];
    this.#index?.clearSelection();

    if (!this.#open) {
      this.#open = true;
      this.#emit('open', undefined);
    }

    this.#update();

    if (!this.#indexBody) {
      await this.reload();
    }
  }

  /** Hide the selector. Deliberately preserves selection and disabled ids. */
  close(): void {
    if (!this.#open) {
      return;
    }

    this.#open = false;
    this.#emit('close', undefined);
    this.options.onClose?.();
    this.#update();
  }

  destroy(): void {
    this.#index?.destroy?.();
    this.#index = null;
    this.#selection = [];
    this.#indexBody = null;
    this.#open = false;
    // Cleared too, so the last state anyone sees is idle. An opener that
    // destroys the modal from inside `onSelect` gets here while `submit()` is
    // still holding `busy`, and the release in its `finally` lands after
    // `#listeners.clear()` — leaving a subscriber frozen mid-save.
    this.#busy = false;
    this.#loading = false;
    this.#error = null;
    this.#update();
    this.#listeners.clear();
  }

  /** (Re)fetch the index body. */
  async reload(): Promise<void> {
    const load = this.options.loadIndexBody ?? defaultLoadIndexBody;

    this.#loading = true;
    this.#error = null;
    this.#update();

    try {
      this.#indexBody = await load(this.options.bodyAction, this.indexParams());
    } catch (error) {
      this.#fail(error);
    } finally {
      this.#loading = false;
      this.#update();
    }
  }

  // ─────────────────────────────── intent ───────────────────────────────

  /**
   * Hand the current selection to the opener.
   *
   * Holds `busy` until `onSelect` settles, so a slow or failing handler can't be
   * raced by a second click and can't leave the chrome stuck.
   */
  async submit(): Promise<void> {
    if (!this.#state.canSubmit) {
      return;
    }

    const elements = this.buildElementInfo(this.#selection);
    const meta = this.selectMeta();

    this.#busy = true;
    this.#update();

    try {
      this.#emit('select', {elements, meta});
      await this.options.onSelect?.(elements, meta);
    } catch (error) {
      this.#fail(error);
      return;
    } finally {
      this.#busy = false;
      this.#update();
    }

    if (this.options.disableElementsOnSelect) {
      this.setDisabledElementIds([
        ...this.#disabledElementIds,
        ...elements.map((element) => Number(element.id)),
      ]);
      this.#index?.clearSelection();
    }

    if (this.options.hideOnSelect) {
      this.close();
    }
  }

  cancel(): void {
    if (!this.#state.canCancel) {
      return;
    }

    this.#emit('cancel', undefined);
    this.options.onCancel?.();
    this.close();
  }

  // ──────────────────────────── from the index ───────────────────────────

  attachIndex(adapter: A): void {
    this.#index = adapter;
    this.#update();
  }

  detachIndex(): void {
    this.#index = null;
    this.#update();
  }

  setSelection(elements: ElementInfo[]): void {
    this.#selection = [...elements];
    this.#update();
  }

  // ─────────────────────────── from the opener ───────────────────────────

  /**
   * Replace the set of elements that may not be selected.
   *
   * A whole-set assignment rather than add/remove calls, because that is how the
   * relation field thinks about it — and because pushing the new set through
   * `change` is what keeps the index in sync. The old modal mutated a settings
   * array that had already been copied into the index by value, so the index
   * never saw the update.
   */
  setDisabledElementIds(ids: number[]): void {
    this.#disabledElementIds = [...new Set(ids.map(Number))];
    this.#update();
  }

  /** Escape hatch for openers doing async work outside `onSelect`. */
  setBusy(busy: boolean): void {
    this.#busy = busy;
    this.#update();
  }

  // ─────────────────────────── index bootstrapping ───────────────────────

  /** Identifies the index to the server. */
  indexParams(): Record<string, unknown> {
    const {options} = this;

    const params: Record<string, unknown> = {
      context: 'modal',
      elementType: this.elementType,
      sources: options.sources,
      condition: options.condition,
    };

    // `null` and `'auto'` both mean "server decides", so the key is omitted.
    if (options.showSiteMenu != null && options.showSiteMenu !== 'auto') {
      params.showSiteMenu = options.showSiteMenu ? '1' : '0';
    }

    if (options.siteIds) {
      params.siteIds = options.siteIds;
    }

    return params;
  }

  /** Configuration for the index itself, as opposed to the request that loads it. */
  indexSettings(): Record<string, unknown> {
    const {options} = this;

    return {
      context: 'modal',
      storageKey: options.storageKey,
      condition: options.condition,
      referenceElementId: options.referenceElementId,
      referenceElementOwnerId: options.referenceElementOwnerId,
      referenceElementSiteId: options.referenceElementSiteId,
      criteria: {...options.criteria},
      disabledElementIds: [...this.#disabledElementIds],
      selectable: true,
      multiSelect: options.multiSelect,
      waitForDoubleClicks: true,
      hideSidebar: options.hideSidebar,
      defaultSiteId: options.defaultSiteId,
      defaultSource: options.defaultSource,
      defaultSourcePath: options.defaultSourcePath,
      preferStoredSource: options.preferStoredSource,
      showSourcePath: options.showSourcePath,
      ...options.indexSettings,
    };
  }

  // ────────────────────────── subclass hooks ────────────────────────────

  /** Whether the current selection may be submitted. */
  protected canSubmitSelection(): boolean {
    return this.#selection.length > 0;
  }

  /** Shapes the payload handed to `onSelect`. */
  protected buildElementInfo(selection: readonly ElementInfo[]): ElementInfo[] {
    return selection.map((element) => ({...element}));
  }

  /** Extra context accompanying a selection. */
  protected selectMeta(): SelectMeta {
    return {};
  }

  /** For subclasses that keep their own derived state in sync. */
  protected notifyChange(): void {
    this.#update();
  }

  /** Read access to the raw selection for subclasses. */
  protected get selection(): readonly ElementInfo[] {
    return this.#selection;
  }

  protected get disabledElementIds(): readonly number[] {
    return this.#disabledElementIds;
  }

  // ─────────────────────────────── internals ─────────────────────────────

  #buildState(): ElementSelectorState {
    const busy = this.#busy;
    const loading = this.#loading;

    return Object.freeze({
      open: this.#open,
      loading,
      busy,
      selection: Object.freeze([...this.#selection]),
      disabledElementIds: Object.freeze([...this.#disabledElementIds]),
      indexBody: this.#indexBody,
      error: this.#error,
      title: this.options.modalTitle,
      showTitle: this.options.showTitle,
      selectLabel: this.options.selectBtnLabel,
      canSubmit: !busy && !loading && this.canSubmitSelection(),
      canCancel: !busy,
    });
  }

  #update(): void {
    this.#state = this.#buildState();
    this.#emit('change', this.#state);
  }

  #fail(error: unknown): void {
    this.#error =
      error instanceof Error ? error : new Error(String(error ?? 'Unknown'));
    this.#emit('error', this.#error);
  }

  #emit<E extends ElementSelectorEvent>(
    event: E,
    data: ElementSelectorEventMap[E]
  ): void {
    // Copied before iterating: a listener that unsubscribes itself (or another)
    // would otherwise mutate the set mid-iteration.
    const listeners = this.#listeners.get(event);

    if (listeners) {
      [...listeners].forEach((listener) =>
        (listener as (value: ElementSelectorEventMap[E]) => void)(data)
      );
    }
  }
}

/**
 * Drops explicitly-`undefined` keys so they don't shadow defaults.
 *
 * Callers build settings blobs by spreading partial objects, which routinely
 * yields `{hideOnSelect: undefined}`; a plain spread would overwrite the default
 * with `undefined` rather than leaving it alone.
 */
function stripUndefined<T extends object>(source: T): Partial<T> {
  return Object.fromEntries(
    Object.entries(source).filter(([, value]) => value !== undefined)
  ) as Partial<T>;
}
