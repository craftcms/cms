import {ConfigService} from '@craftcms/ui/services/Config';

export interface CookieOptions {
  path?: string;
  domain?: string | null;
  /** In seconds. */
  maxAge?: number;
  expires?: Date;
  secure?: boolean;
  sameSite?: string;
}

// The server reads some of these cookies, so the name format is fixed.
function cookieName(name: string): string {
  return `Craft-${ConfigService.getInstance().get('systemUid', '')}_${name}`;
}

/** Returns a control panel cookie's value, or an empty string if it isn't set. */
export function getCookie(name: string): string {
  const prefix = `${cookieName(name)}=`;

  for (const cookie of document.cookie.split(/;\s*/)) {
    if (cookie.startsWith(prefix)) {
      return cookie.slice(prefix.length);
    }
  }

  return '';
}

export function setCookie(
  name: string,
  value: string,
  options: CookieOptions = {}
): void {
  const {path, domain, maxAge, expires, secure} = {
    ...(ConfigService.getInstance().get(
      'defaultCookieOptions',
      {}
    ) as CookieOptions),
    ...options,
  };
  let cookie = `${cookieName(name)}=${encodeURIComponent(value)}`;

  if (path) {
    cookie += `;path=${path}`;
  }
  if (domain) {
    cookie += `;domain=${domain}`;
  }
  if (maxAge) {
    cookie += `;max-age=${maxAge}`;
  } else if (expires) {
    cookie += `;expires=${expires.toUTCString()}`;
  }
  if (secure) {
    cookie += ';secure';
  }

  document.cookie = cookie;
}
