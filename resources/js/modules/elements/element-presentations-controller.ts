import type {ReactiveController, ReactiveControllerHost} from 'lit';
import {effectScope, ref, toRaw, watch, type EffectScope} from 'vue';
import {
  useElementPresentations,
  type PresentationQuery,
  type PresentedElement,
} from '@/modules/elements/element-presentations';
import type {ElementPresentation} from '@/modules/ui/element-select/types';

type Presentations = ReturnType<typeof useElementPresentations>;

/**
 * Lets a Lit component draw elements through the shared cache in
 * `element-presentations.ts`, as Vue components do, and re-renders it when one
 * of its elements is refetched.
 *
 * `query` and `elements` read the component's own state. The controller tells
 * the cache when they change, each time the component updates.
 *
 * The component keeps its place in the cache while it's moved, which
 * disconnects and reconnects it in one go: leaving would drop its elements
 * from the cache, and the answer to a refetch already on its way with them.
 */
export class ElementPresentationsController implements ReactiveController {
  private scope: EffectScope | null = null;

  private presentations: Presentations | null = null;

  /** Bumped when `query` or `elements` change, so the cache reads them again. */
  private readonly version = ref(0);

  private lastKey = '';

  constructor(
    private readonly host: ReactiveControllerHost & Element,
    private readonly query: () => PresentationQuery,
    private readonly elements: () => PresentedElement[]
  ) {
    host.addController(this);
  }

  hostConnected(): void {
    if (this.scope) {
      return;
    }

    const elements = () => {
      void this.version.value;
      return this.elements();
    };

    this.scope = effectScope(true);
    this.scope.run(() => {
      this.presentations = useElementPresentations(this.query, elements);
      watch(
        () => elements().map(({id, siteId}) => this.get(id, siteId)),
        () => this.host.requestUpdate()
      );
    });
  }

  hostDisconnected(): void {
    queueMicrotask(() => {
      if (!this.host.isConnected) {
        this.scope?.stop();
        this.scope = null;
        this.presentations = null;
      }
    });
  }

  hostUpdate(): void {
    const key = JSON.stringify([this.query(), this.elements()]);

    if (key !== this.lastKey) {
      this.lastKey = key;
      this.version.value++;
    }
  }

  /** The cached presentation of an element, if there is one. */
  get(id: number, siteId: unknown): ElementPresentation | undefined {
    const presentation = this.presentations?.get(id, siteId);
    return presentation && toRaw(presentation);
  }

  /** Caches what the server rendered. See `useElementPresentations()`. */
  seed(
    presentations: ElementPresentation[],
    fallbackSiteId: unknown,
    options?: {replace?: boolean}
  ): void {
    this.presentations?.seed(presentations, fallbackSiteId, options);
  }

  /** Fetches elements nothing has rendered yet. */
  async load(elements: PresentedElement[]): Promise<void> {
    await this.presentations?.load(elements);
  }
}
