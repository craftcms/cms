import humanizeDuration from 'humanize-duration';
import type {WindowWithCraft} from '../types/globals.js';

// @TODO grab this from the config
const defaultCpLocale = 'en';

/**
 * The locale numbers and dates are formatted in: the user's formatting locale,
 * then the control panel's language.
 */
export function formattingLocale(): string {
  if (typeof window === 'undefined') {
    return defaultCpLocale;
  }

  return (
    (window as WindowWithCraft).Craft?.formattingLocale ||
    document.documentElement.lang ||
    defaultCpLocale
  );
}

/** A date, in the user's formatting locale. Long dates by default, e.g. “October 30, 2026”. */
export function formatDate(
  value: Date | string | number,
  options: Intl.DateTimeFormatOptions = {dateStyle: 'long'}
): string {
  return new Intl.DateTimeFormat(formattingLocale(), options).format(
    new Date(value)
  );
}

/** An amount of money, in the user's formatting locale unless given another. Whole amounts drop their cents. */
export function formatCurrency(
  value: number | string | null,
  currency: string,
  locale: string = formattingLocale()
): string {
  const amount = Number(value ?? 0);
  const fractionDigits = Number.isInteger(amount) ? 0 : undefined;

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits,
  }).format(amount);
}

export function formatNumber(number: number | string, format?: string) {
  // If d3 is available, use it for compatibility
  if (
    typeof d3 !== 'undefined' &&
    typeof d3FormatLocaleDefinition !== 'undefined'
  ) {
    if (typeof format === 'undefined') {
      format = ',.0f';
    }
    const formatter = d3.formatLocale(d3FormatLocaleDefinition).format(format);
    return formatter(number);
  }

  // Fall back to native JavaScript Intl.NumberFormat
  const num = typeof number === 'string' ? parseFloat(number) : number;

  if (isNaN(num)) {
    return String(number);
  }

  // Parse d3 format string if provided
  if (format) {
    // Basic d3 format parsing - supports common patterns like ',.0f' (thousand separators, no decimals)
    const hasThousandSeparator = format.includes(',');
    const decimalMatch = format.match(/\.(\d+)/);
    const maximumFractionDigits = decimalMatch
      ? parseInt(decimalMatch[1]!, 10)
      : 0;

    return new Intl.NumberFormat(formattingLocale(), {
      useGrouping: hasThousandSeparator,
      minimumFractionDigits: maximumFractionDigits,
      maximumFractionDigits: maximumFractionDigits,
    }).format(num);
  }

  // Default: format with thousand separators, no decimal places
  return new Intl.NumberFormat(formattingLocale(), {
    useGrouping: true,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(num);
}

export const cpHumanizer = humanizeDuration.humanizer({
  language: defaultCpLocale,
  fallbacks: ['en'],
  largest: 2,
  round: true,
});
