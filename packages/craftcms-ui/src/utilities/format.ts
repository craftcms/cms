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

/**
 * A date, in the user's formatting locale. Long dates by default, e.g. “October 30, 2026”.
 * A value that isn't a date formats as an empty string.
 */
export function formatDate(
  value: Date | string | number,
  options: Intl.DateTimeFormatOptions = {dateStyle: 'long'}
): string {
  const date = new Date(value);

  if (isNaN(date.getTime())) {
    return '';
  }

  return new Intl.DateTimeFormat(formattingLocale(), options).format(date);
}

/** An amount of money, in the user's formatting locale unless given another. Whole amounts drop their cents. */
export function formatCurrency(
  value: number | string | null,
  currency: string,
  locale: string = formattingLocale()
): string {
  const amount = Number(value ?? 0);
  const formatter = new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
  });
  const scale = 10 ** (formatter.resolvedOptions().maximumFractionDigits ?? 2);

  if (!Number.isInteger(Math.round(amount * scale) / scale)) {
    return formatter.format(amount);
  }

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
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

type DurationUnit = 'days' | 'hours' | 'minutes' | 'seconds';

const durationUnits: Array<[DurationUnit, number]> = [
  ['days', 86_400],
  ['hours', 3_600],
  ['minutes', 60],
  ['seconds', 1],
];

/**
 * An amount of time in seconds, in the user's formatting locale, to its two largest units:
 * “1h 5m” and “1m 30s” by default, or “1 minute, 30 seconds” in the `long` style.
 */
export function formatDuration(
  seconds: number,
  style: 'long' | 'short' | 'narrow' = 'narrow'
): string {
  let remaining = Math.max(0, Math.round(seconds));
  const values = durationUnits.map(([unit, size]): [DurationUnit, number] => {
    const value = Math.floor(remaining / size);
    remaining -= value * size;
    return [unit, value];
  });
  const largest = values.findIndex(([, value]) => value > 0);
  const duration: Partial<Record<DurationUnit, number>> =
    largest === -1
      ? {seconds: 0}
      : Object.fromEntries(
          values.slice(largest, largest + 2).filter(([, value]) => value > 0)
        );
  const locale = formattingLocale();

  if ('DurationFormat' in Intl) {
    return new Intl.DurationFormat(locale, {style}).format(duration);
  }

  // where it isn't supported yet, each unit is formatted on its own
  return new Intl.ListFormat(locale, {type: 'unit', style}).format(
    Object.entries(duration).map(([unit, value]) =>
      new Intl.NumberFormat(locale, {
        style: 'unit',
        unit: unit.slice(0, -1),
        unitDisplay: style,
      }).format(value)
    )
  );
}
