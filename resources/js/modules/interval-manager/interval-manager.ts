import {Base, type GarnishBaseSettings} from '@craftcms/garnish';

export interface IntervalManagerSettings extends GarnishBaseSettings {
  interval: number;
  onInterval: () => void;
}

/**
 * IntervalManager — a port of `Craft.IntervalManager` onto `@craftcms/garnish`
 * `Base`. A thin `setInterval` wrapper: `start()` begins calling
 * `settings.onInterval` every `settings.interval` ms, `stop()` clears it.
 *
 * Instantiated imperatively (`new Craft.IntervalManager(...)` — only
 * `Craft.ProgressBar` does so today), so the class is exposed on `window.Craft`
 * as a seam rather than as a custom element.
 */
export class IntervalManager extends Base<IntervalManagerSettings> {
  static defaults: IntervalManagerSettings = {
    interval: 5000,
    onInterval: () => {},
  };

  #intervalId: ReturnType<typeof setInterval> | null = null;

  constructor(settings?: Partial<IntervalManagerSettings>) {
    super();
    if (new.target === IntervalManager) {
      this.init(settings);
    }
  }

  init(settings?: Partial<IntervalManagerSettings>): void {
    this.setSettings(settings, IntervalManager.defaults);
  }

  start(): void {
    this.#intervalId = setInterval(() => {
      this.settings!.onInterval();
    }, this.settings!.interval);
  }

  stop(): void {
    if (this.#intervalId !== null) {
      clearInterval(this.#intervalId);
      this.#intervalId = null;
    }
  }
}
