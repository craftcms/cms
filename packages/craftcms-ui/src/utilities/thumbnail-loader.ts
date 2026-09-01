import {isVisible} from '@src/utilities/dom';
import {rand} from '@src/utilities/string';

export class ThumbnailLoader {
  queue: Array<HTMLElement> = [];
  workers: ThumbnailWorker[] = [];
  static invisibleThumbs: Record<string, [ThumbnailLoader, HTMLElement]> = {};

  #abortController: AbortController | null = null;

  constructor() {
    this.#abortController = new AbortController();

    for (let i = 0; i < 3; i++) {
      this.workers.push(new ThumbnailWorker(this));
    }
  }

  load(root: HTMLElement | Document = document, selector = '[data-sizes]') {
    const thumbnails = root.querySelectorAll<HTMLElement>(selector);

    thumbnails.forEach((thumbnail) => {
      if (isVisible(thumbnail)) {
        this.addToQueue(thumbnail);
      } else {
        const key = `thumb${rand()}`;
        ThumbnailLoader.invisibleThumbs[key] = [this, thumbnail];

        const handler = () => {
          if (ThumbnailLoader.invisibleThumbs[key] && isVisible(thumbnail)) {
            delete ThumbnailLoader.invisibleThumbs[key];
            this.addToQueue(thumbnail);
          }
        };

        document.addEventListener('scroll', handler, {
          signal: this.#abortController?.signal,
        });
        window.addEventListener('resize', handler, {
          signal: this.#abortController?.signal,
        });
      }
    });
  }

  addToQueue(thumbnail: HTMLElement) {
    this.queue.push(thumbnail);

    this.workers.forEach((worker) => {
      if (!worker.active) {
        worker.loadNext();
      }
    });
  }

  // Static because callers (`Preview`, `LivePreview`) retry thumbs queued by
  // whichever loader instances registered them, without holding a reference.
  static retryAll() {
    for (const key in ThumbnailLoader.invisibleThumbs) {
      const entry = ThumbnailLoader.invisibleThumbs[key];
      if (!entry) continue;
      const [queue, $thumb] = entry;
      delete ThumbnailLoader.invisibleThumbs[key];
      queue.load($thumb.parentElement ?? document);
    }
  }

  destroy() {
    this.workers.forEach((worker) => worker.deactivate());
    this.#abortController?.abort();
  }
}

export class ThumbnailWorker {
  active: boolean = false;
  container: HTMLElement | undefined = undefined;

  #loader: ThumbnailLoader | null = null;
  #interval: ReturnType<typeof setInterval> | null = null;
  #timeout: ReturnType<typeof setTimeout> | null = null;

  constructor(loader: ThumbnailLoader) {
    this.#loader = loader;
  }

  activate() {
    if (this.active) {
      return;
    }

    this.active = true;
    this.clearInterval();
    this.#interval = setInterval(() => {
      this.loadNextIfRemoved();
    }, 500);
  }

  deactivate() {
    if (!this.active) {
      return;
    }

    this.active = false;
    this.clearInterval();
    this.clearTimeout();
  }

  clearInterval() {
    if (this.#interval) {
      clearInterval(this.#interval);
      this.#interval = null;
    }
  }

  clearTimeout() {
    if (this.#timeout) {
      clearTimeout(this.#timeout);
      this.#timeout = null;
    }
  }

  loadNext() {
    this.clearTimeout();

    this.container = this.#loader?.queue.shift();
    if (typeof this.container === 'undefined') {
      this.deactivate();
      return;
    }

    if (this.loadNextIfRemoved()) {
      return;
    }

    if (this.container.querySelectorAll('img').length > 0) {
      this.loadNext();
      return;
    }

    this.activate();

    // Give up after 30 seconds
    this.#timeout = setTimeout(() => {
      this.loadNext();
    }, 30_000);

    const img = document.createElement('img');
    img.sizes = this.container.getAttribute('data-sizes') ?? '';
    img.srcset = this.container.getAttribute('data-srcset') ?? '';
    img.alt = this.container.getAttribute('data-alt') ?? '';
    img.setAttribute(
      'data-animated',
      this.container.getAttribute('data-animated') ?? ''
    );
    img.src = this.container.getAttribute('data-src') ?? '';

    // add our event listeners
    img.onload = () => this.loadNext();
    img.onabort = () => this.loadNext();
    img.onerror = () => this.loadNext();

    this.container.appendChild(img);

    // Picturefill was here.
  }

  loadNextIfRemoved() {
    if (this.container && !document.body.contains(this.container)) {
      this.loadNext();
      return true;
    }

    return false;
  }
}
