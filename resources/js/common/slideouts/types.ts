import type {InjectionKey} from 'vue';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import type {ScreenPageProps} from '@/common/composables/screen';

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
  props: ScreenPageProps;
  loading: boolean;
  error: string | null;
  /** Refocused when the panel closes. */
  opener: HTMLElement | null;
  /** See {@link OpenSlideoutOptions.onSaved}. */
  onSaved: ((result: SlideoutSaveResult) => void) | null;
}

export interface SlideoutSaveResult {
  /** Whatever the controller returned for the saved record, if anything. */
  data?: ScreenPageProps;
  /**
   * True for an autosaved draft rather than a finished save.
   *
   * The panel stays open and keeps saving as the user types, so an opener that
   * refreshes on this should debounce and must not treat it as "done".
   */
  draft?: boolean;
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
  /**
   * Called when the screen saves, before the panel closes.
   *
   * Registering one also takes over refreshing: without it a save reloads the
   * whole page behind the panel, which is the only thing a slideout opened
   * from an arbitrary place can safely do. Openers that know what changed
   * should say so here and refresh just that.
   */
  onSaved?: (result: SlideoutSaveResult) => void;
}

export interface SlideoutController {
  readonly instance: SlideoutInstance;
  /**
   * Close the panel. Prompts when there are unsaved changes; pass
   * `{force: true}` for a close that follows a successful save.
   */
  close(options?: {force?: boolean}): void;
  reload(): Promise<void>;
  /**
   * Report a successful save to whoever opened the panel.
   *
   * Returns `false` when nobody was listening, which is the caller's cue to
   * fall back to reloading the page behind.
   *
   * Call this *before* {@link close} — closing drops the panel from the store,
   * and its handler with it.
   */
  saved(result?: SlideoutSaveResult): boolean;
}

export const SlideoutControllerKey: InjectionKey<SlideoutController> =
  Symbol('slideoutController');
