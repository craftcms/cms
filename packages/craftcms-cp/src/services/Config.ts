export class ConfigService {
  static #instance: ConfigService | null = null;

  #config: Map<string, any> = new Map();

  /** Get the singleton instance */
  static getInstance(initialConfig = {}): ConfigService {
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
