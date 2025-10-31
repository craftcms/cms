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
 * import Cookies from "@/utilities/cookies";
 *
 * const cookies = new Cookies(Craft.defaultCookieOptions)
 * cookies.set("foo", "bar");
 * cookies.get("foo"); // "bar"
 * cookies.remove("foo");
 */
export declare class Cookies {
    static defaultCookieOptions: CookieOptions;
    config: CookieOptions;
    constructor(options?: CookieOptions);
    /**
     * Sets a cookie value.
     */
    set(name: string, value: string, overrides?: CookieOptions): void;
    get(name: string): string;
    remove(name: string): void;
}
export {};
