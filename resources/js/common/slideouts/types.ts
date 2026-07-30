import type {InjectionKey} from 'vue';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';

export interface SlideoutInstance {
  id: string;
  /** Overrides `--slideout-width` for this panel. Any CSS length. */
  width: string | null;
  /**
   * Sent as `X-Craft-Container-Id`. The server namespaces the screen's inputs
   * against it, so two slideouts of the same screen don't collide.
   */
  containerId: string;
  href: string;
  component: InertiaPageComponent | null;
  props: Record<string, unknown>;
  loading: boolean;
  error: string | null;
  /** Refocused when the panel closes. */
  opener: HTMLElement | null;
}

export interface OpenSlideoutOptions {
  /** Element to refocus on close. Defaults to whatever had focus at open time. */
  opener?: HTMLElement | null;
  /**
   * Width for this panel, as any CSS length (`'40rem'`, `'70vw'`, …).
   *
   * Defaults to `--slideout-width`, which can be set globally on `:root` to
   * change every slideout at once.
   */
  width?: string;
}

export interface SlideoutController {
  readonly instance: SlideoutInstance;
  close(): void;
  reload(): Promise<void>;
}

export const SlideoutControllerKey: InjectionKey<SlideoutController> =
  Symbol('slideoutController');
