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
  let minus = '-';
  let digits = [...'0123456789'];

  try {
    const format = new Intl.NumberFormat(intlLocale(locale));

    for (const part of format.formatToParts(-12345.6)) {
      if (part.type === 'group') {
        group = part.value;
      } else if (part.type === 'decimal') {
        decimal = part.value;
      } else if (part.type === 'minusSign') {
        minus = part.value;
      }
    }

    // The locale's own digits, such as Arabic-Indic ones.
    digits = Array.from({length: 10}, (_, digit) => format.format(digit));
  } catch {
    // An unknown locale reads as plain digits, with a dot before the decimals.
  }

  // Spaces are dropped along with the group separators, as some locales
  // group with a no-break space, which is easy to type as a plain one, and
  // so are the marks that set the text's direction.
  const normalized = [
    ...text
      .replace(/[\s\u061c\u200e\u200f]/gu, '')
      .split(group)
      .join('')
      .replace(decimal, '.')
      .replace(minus, '-')
      .replace('\u2212', '-'),
  ]
    .map((char) => {
      const digit = digits.indexOf(char);

      return digit === -1 ? char : String(digit);
    })
    .join('');

  if (normalized === '' || !/^-?(\d+\.?\d*|\.\d+)$/.test(normalized)) {
    return null;
  }

  const amount = Number(normalized);

  // Too many digits read as Infinity, which no money input can hold.
  return Number.isFinite(amount) ? amount : null;
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
