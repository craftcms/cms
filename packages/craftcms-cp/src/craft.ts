import type {AxiosProxyConfig} from 'axios';

/**
 * The contract for the Craft context that the @craftcms/cp package needs.
 *
 * This is intentionally minimal — only the capabilities the package actually
 * uses. The consuming application provides this via `configure()` at boot.
 */
export interface CraftContext {
  getActionUrl(action?: string, params?: string | object): string;
  getCpUrl(path?: string, params?: string | object): string;
  setCookie?: (name: string, value: string) => void;
  registeredAssetBundles: string[];
  registeredJsFiles: string[];
  apiParams?: Record<string, string | number | boolean | null | undefined>;
  httpProxy?: AxiosProxyConfig;
}

let context: CraftContext | null = null;

/**
 * Configure the package with a Craft context.
 * Must be called before any API utilities are used.
 */
export function configure(craft: CraftContext): void {
  context = craft;
}

/**
 * Get the configured Craft context.
 * Throws if `configure()` has not been called — use for required capabilities
 * (API clients, URL builders).
 */
export function getCraft(): CraftContext {
  if (!context) {
    throw new Error(
      '@craftcms/cp has not been configured. Call configure(craft) before using package utilities.'
    );
  }
  return context;
}

/**
 * Get the configured Craft context, or null if not yet configured.
 * Use for optional/graceful-degradation behaviors (e.g. cookie persistence).
 */
export function tryGetCraft(): CraftContext | null {
  return context;
}

/**
 * Reset configuration (for testing).
 */
export function resetConfiguration(): void {
  context = null;
}
