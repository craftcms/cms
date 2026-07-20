interface CookieOptions {
  /** The cookie path. */
  path?: string;
  /** The cookie domain. Defaults to the `defaultCookieDomain` config setting. */
  domain?: string | null;
  /** The max age of the cookie (in seconds) */
  maxAge?: number;
  /** The expiry date of the cookie. Defaults to none (session-based cookie). */
  expires?: Date;
  /** Whether this is a secure cookie. Defaults to the `useSecureCookies` config setting. */
  secure?: boolean;
  /** The SameSite value (`lax` or `strict`). Defaults to the `sameSiteCookieValue` config setting. */
  sameSite?: string;
  /** Prefix used for all cookies. Defaults to `Craft-{systemUid}`. */
  prefix?: string;
}

/**
 * A simple cookie manager.
 *
 * @example
 * import Cookies from "@src/utilities/cookies";
 *
 * const cookies = new Cookies(Cp.defaultCookieOptions)
 * cookies.set("foo", "bar");
 * cookies.get("foo"); // "bar"
 * cookies.remove("foo");
 */
export class Cookies {
  static defaultCookieOptions: CookieOptions = {
    path: '/',
    domain: null,
    secure: false,
    sameSite: 'strict',
    prefix: 'Craft',
  };
  config: CookieOptions;

  constructor(options: CookieOptions = {}) {
    this.config = {
      ...Cookies.defaultCookieOptions,
      ...options,
    };
  }

  /**
   * Sets a cookie value.
   */
  public set(name: string, value: string, overrides: CookieOptions = {}) {
    const config = Object.assign({}, this.config, overrides);

    const {path, domain, maxAge, expires, secure, sameSite, prefix} = config;

    let cookie = `${this.config.prefix}:${name}=${encodeURIComponent(value)}`;
    if (path) {
      cookie += `;path=${path}`;
    }

    if (domain) {
      cookie += `;domain=${domain}`;
    }

    if (maxAge) {
      cookie += `;max-age-in-seconds=${maxAge}`;
    } else if (expires) {
      cookie += `;expires=${expires.toUTCString()}`;
    }

    if (secure) {
      cookie += ';secure';
    }

    document.cookie = cookie;
  }

  public get(name: string) {
    // Adapted from https://developer.mozilla.org/en-US/docs/Web/API/Document/cookie
    return document.cookie.replace(
      new RegExp(
        `(?:(?:^|.*;\\s*)${this.config.prefix}:${name}\\s*\\=\\s*([^;]*).*$)|^.*$`
      ),
      '$1'
    );
  }

  public remove(name: string) {
    this.set(name, '', {expires: new Date('1970-01-01T00:00:00')});
  }
}
