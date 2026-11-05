/**
 * String helpers formerly defined on the legacy `Craft` global. The legacy
 * bundle still exposes them there (`Craft.trim()` and so on) for plugins.
 */

/** Escapes special regular expression characters. */
export function escapeRegex(str: string): string {
  return str.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
}

/**
 * Places a backslash before each item, for use in a regex character class.
 * A string is treated as a single item.
 */
export function escapeChars(chars: string | string[]): string {
  const list = Array.isArray(chars) ? chars : [chars];

  return list.map((chr) => `\\${chr}`).join('');
}

/** Trims whitespace, or the given characters, off the start of a string. */
export function ltrim(str: string, chars?: string | string[]): string {
  if (!str) {
    return str;
  }
  if (chars === undefined) {
    return str.replace(/^\s+/, '');
  }

  return str.replace(new RegExp(`^[${escapeChars(chars)}]+`), '');
}

/** Trims whitespace, or the given characters, off the end of a string. */
export function rtrim(str: string, chars?: string | string[]): string {
  if (!str) {
    return str;
  }
  if (chars === undefined) {
    return str.replace(/\s+$/, '');
  }

  return str.replace(new RegExp(`[${escapeChars(chars)}]+$`), '');
}

/** Trims whitespace, or the given characters, off both ends of a string. */
export function trim(str: string, chars?: string | string[]): string {
  if (!str) {
    return str;
  }
  if (chars === undefined) {
    return str.trim();
  }

  return rtrim(ltrim(str, chars), chars);
}

export function startsWith(
  str: string,
  substr: string,
  caseInsensitive = false
): boolean {
  return caseInsensitive
    ? str.toLowerCase().startsWith(substr.toLowerCase())
    : str.startsWith(substr);
}

export function endsWith(
  str: string,
  substr: string,
  caseInsensitive = false
): boolean {
  return caseInsensitive
    ? str.toLowerCase().endsWith(substr.toLowerCase())
    : str.endsWith(substr);
}

export function ensureStartsWith(
  str: string,
  substr: string,
  caseInsensitive = false
): string {
  return startsWith(str, substr, caseInsensitive) ? str : substr + str;
}

export function ensureEndsWith(
  str: string,
  substr: string,
  caseInsensitive = false
): string {
  return endsWith(str, substr, caseInsensitive) ? str : str + substr;
}

export function removeLeft(
  str: string,
  substr: string,
  caseInsensitive = false
): string {
  return startsWith(str, substr, caseInsensitive)
    ? str.slice(substr.length)
    : str;
}

export function removeRight(
  str: string,
  substr: string,
  caseInsensitive = false
): string {
  return endsWith(str, substr, caseInsensitive)
    ? str.slice(0, -substr.length)
    : str;
}

export function uppercaseFirst(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

export function lowercaseFirst(str: string): string {
  return str.charAt(0).toLowerCase() + str.slice(1);
}

export function uuid(): string {
  if (typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }

  return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, (c) => {
    const [byte = 0] = crypto.getRandomValues(new Uint8Array(1));

    return (Number(c) ^ (byte & (15 >> (Number(c) / 4)))).toString(16);
  });
}

/** A random string of ASCII letters and digits. Not for anything secret. */
export function randomString(length: number): string {
  const characters =
    'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let result = '';
  for (let i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * 62));
  }

  return result;
}
