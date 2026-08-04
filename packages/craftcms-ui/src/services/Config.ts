export class ConfigService {
  static #instance: ConfigService | null = null;

  #config: Map<string, any> = new Map();

  /**
   * Get the singleton instance. Note this never initializes the config —
   * call {@link initialize} with the CP config payload (the page bootstrap's
   * job) before using the URL helpers.
   */
  static getInstance(): ConfigService {
    if (!ConfigService.#instance) {
      ConfigService.#instance = new ConfigService();
    }
    return ConfigService.#instance;
  }

  /** Reset the singleton (mainly for testing) */
  static resetInstance(): void {
    if (ConfigService.#instance) {
      ConfigService.#instance = null;
    }
  }

  initialize(initialConfig = {}) {
    this.#config = new Map(Object.entries(initialConfig));
  }

  #buildUrl(baseUrl: string, path: string): string {
    const url = new URL(baseUrl);
    const cleanPath = path.startsWith('/') ? path.slice(1) : path;
    url.pathname = `${url.pathname}/${cleanPath}`;
    return url.toString();
  }

  /**
   * A config value that must be present, with a diagnosable failure when the
   * service was never initialized (rather than an opaque `Invalid URL`
   * TypeError from `new URL(undefined)` downstream).
   */
  #require(key: string): string {
    const value = this.#config.get(key);
    if (!value) {
      throw new Error(
        `ConfigService: "${key}" is not configured. The page bootstrap must ` +
          'call ConfigService.initialize() with the CP config payload before ' +
          'URL helpers can be used.'
      );
    }
    return value;
  }

  getCpUrl(path: string) {
    return this.#buildUrl(this.#require('baseCpUrl'), path);
  }

  getActionUrl(path: string) {
    return this.#buildUrl(this.#require('actionUrl'), path);
  }

  all() {
    return this.#config;
  }

  set(key: string, value: any): void {
    this.#config.set(key, value);
  }

  get(key: string, fallback: any = null): any {
    if (this.#config.has(key)) {
      return this.#config.get(key);
    }

    return fallback;
  }
}
