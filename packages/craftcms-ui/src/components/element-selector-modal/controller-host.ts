import type {ReactiveController, ReactiveControllerHost} from 'lit';
import type {
  ElementSelectorController,
  ElementSelectorState,
} from '@src/core/element-selector/index.js';

/**
 * Bridges an {@link ElementSelectorController}'s `change` event to a Lit host's
 * update cycle.
 *
 * The core is a plain emitter with no knowledge of Lit — that is what lets a Vue
 * component bind to the same instance — so something has to translate "state
 * changed" into `requestUpdate()`. A `ReactiveController` is the idiomatic place
 * for it, and keeping it in the component folder rather than in `src/core/`
 * is what keeps the core free of a Lit import.
 */
export class ElementSelectorHostController implements ReactiveController {
  #off: (() => void) | null = null;

  constructor(
    private readonly host: ReactiveControllerHost,
    private readonly source: () => ElementSelectorController | null
  ) {
    host.addController(this);
  }

  hostConnected(): void {
    this.#subscribe();
  }

  hostDisconnected(): void {
    this.#unsubscribe();
  }

  /** Call when the host's controller reference changes. */
  resubscribe(): void {
    this.#unsubscribe();
    this.#subscribe();
  }

  get state(): ElementSelectorState | null {
    return this.source()?.state ?? null;
  }

  #subscribe(): void {
    this.#off =
      this.source()?.on('change', () => this.host.requestUpdate()) ?? null;
  }

  #unsubscribe(): void {
    this.#off?.();
    this.#off = null;
  }
}
