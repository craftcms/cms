import {ThumbnailLoader} from '@src/utilities/thumbnail-loader.js';

/**
 * @summary Boots a {@link ThumbnailLoader} over the server-rendered markup it
 * wraps, lazy-loading every `[data-sizes]` thumb placeholder inside it — so
 * markup can opt into thumb loading declaratively instead of relying on an
 * imperative `Craft.cp.elementThumbLoader.load(...)` boot.
 *
 * A light-DOM controller element (no shadow root): the loader scans and
 * mutates the wrapped markup directly. Booting waits (an animation frame at a
 * time) until thumb markup has parsed, since the element may upgrade before
 * its children exist (initial HTML parse, or an injected fragment).
 * Disconnecting destroys the loader — its workers and offscreen-thumb
 * visibility listeners.
 *
 * @example
 * ```html
 * <craft-thumbnail-loader>
 *   <div class="thumb" data-sizes="…" data-srcset="…" data-src="…"></div>
 * </craft-thumbnail-loader>
 * ```
 */
export default class CraftThumbnailLoader extends HTMLElement {
  #loader: ThumbnailLoader | null = null;

  connectedCallback() {
    this.#boot();
  }

  #boot(): void {
    if (this.#loader || !this.isConnected) {
      return;
    }

    if (!this.querySelector('[data-sizes]')) {
      requestAnimationFrame(() => this.#boot());
      return;
    }

    this.#loader = new ThumbnailLoader();
    this.#loader.load(this);
  }

  disconnectedCallback() {
    this.#loader?.destroy();
    this.#loader = null;
  }
}

if (!customElements.get('craft-thumbnail-loader')) {
  customElements.define('craft-thumbnail-loader', CraftThumbnailLoader);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-thumbnail-loader': CraftThumbnailLoader;
  }
}
