/**
 * Money amounts as money controls hold them: text formatted for a locale,
 * such as `1,999.50` in `en-US` or `1.999,50` in `de-DE`.
 */

/** A locale `Intl` takes, from one written either way, such as `en_US`. */
function intlLocale(locale: string | undefined): string | undefined {
  return locale ? locale.replace(/_/g, '-') : undefined;
}

/**
 * The amount `text` holds in `locale`, or `null` when it isn't a number
 * there.
 */
export function moneyAmount(text: string, locale?: string): number | null {
  let group = ',';
  let decimal = '.';

  try {
    for (const part of new Intl.NumberFormat(intlLocale(locale)).formatToParts(
      12345.6
    )) {
      if (part.type === 'group') {
        group = part.value;
      } else if (part.type === 'decimal') {
        decimal = part.value;
      }
    }
  } catch {
    // An unknown locale reads as plain digits, with a dot before the decimals.
  }

  // Spaces are dropped along with the group separators, as some locales
  // group with a no-break space, which is easy to type as a plain one.
  const normalized = text
    .replace(/\s/g, '')
    .split(group)
    .join('')
    .replace(decimal, '.');

  if (normalized === '' || !/^-?(\d+\.?\d*|\.\d+)$/.test(normalized)) {
    return null;
  }

  return Number(normalized);
}

/** `text`, an amount in `locale`, formatted with its currency, such as `$1,999.00`. */
export function formatMoney(
  text: string,
  currency: string | undefined,
  locale?: string
): string {
  const amount = moneyAmount(text, locale);

  if (amount === null || !currency) {
    return text.trim();
  }

  try {
    return new Intl.NumberFormat(intlLocale(locale), {
      style: 'currency',
      currency,
    }).format(amount);
  } catch {
    return `${text.trim()} ${currency}`;
  }
}
