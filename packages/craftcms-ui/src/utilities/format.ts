import humanizeDuration from 'humanize-duration';

// @TODO grab this from the config
const defaultCpLocale = 'en';

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

    return new Intl.NumberFormat(defaultCpLocale, {
      useGrouping: hasThousandSeparator,
      minimumFractionDigits: maximumFractionDigits,
      maximumFractionDigits: maximumFractionDigits,
    }).format(num);
  }

  // Default: format with thousand separators, no decimal places
  return new Intl.NumberFormat(defaultCpLocale, {
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
