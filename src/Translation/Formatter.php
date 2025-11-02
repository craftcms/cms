<?php

declare(strict_types=1);

namespace CraftCms\Cms\Translation;

use Carbon\CarbonInterface;
use CraftCms\Cms\Support\Str;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Date;
use IntlDateFormatter;
use IntlTimeZone;
use InvalidArgumentException;
use NumberFormatter;
use Throwable;

use function CraftCms\Cms\t;

#[Singleton]
final class Formatter
{
    public const string FORMAT_WIDTH_SHORT = 'short';

    public const string FORMAT_WIDTH_LONG = 'long';

    public ?string $locale {
        get => $this->locale ?? app()->getLocale();
    }

    public string $timeZone {
        get => $this->timeZone ?? app()->getTimezone();
    }

    public string $defaultDateFormat = 'medium';

    /**
     * @var int The base at which a kilobyte is calculated (1000 or 1024 bytes per kilobyte),
     *          used by {@see self::asShortSize}.
     */
    public int $sizeFormatBase = 1024;

    private array $defaultDateFormats = [
        'short' => IntlDateFormatter::SHORT,
        'medium' => IntlDateFormatter::MEDIUM,
        'long' => IntlDateFormatter::LONG,
        'full' => IntlDateFormatter::FULL,
    ];

    public array $dateTimeFormats = [
        'short' => IntlDateFormatter::SHORT,
        'medium' => IntlDateFormatter::MEDIUM,
        'long' => IntlDateFormatter::LONG,
        'full' => IntlDateFormatter::FULL,
    ];

    public function asCurrency(mixed $value, ?string $currency = null, bool $stripZeros = false): string
    {
        $value = $this->normalizeNumericValue($value);

        $formatter = new NumberFormatter(
            $this->locale,
            NumberFormatter::CURRENCY,
        );

        $omitDecimals = ($stripZeros && (int) $value == $value);

        if ($omitDecimals) {
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);
        }

        if (! $currency) {
            return $formatter->format($value);
        }

        return $formatter->formatCurrency($value, $currency);
    }

    public function asDate(int|string|DateTimeInterface $value, ?string $format = null): string
    {
        $format ??= $this->defaultDateFormat;
        [$value] = $this->parseDate($value, $format);

        if (is_string($value)) {
            return $value;
        }

        $format = $this->dateTimeFormats[$format]['date'] ?? $format;

        if (isset($this->defaultDateFormats[$format])) {
            return new IntlDateFormatter(
                locale: $this->locale,
                dateType: $this->defaultDateFormats[$format] ?? IntlDateFormatter::NONE,
                timeType: IntlDateFormatter::NONE,
                timezone: $this->timeZone,
            )->format($value->toDateTime());
        }

        return new IntlDateFormatter(
            locale: $this->locale,
            timezone: $this->timeZone,
            pattern: $format,
        )->format($value->toDateTime());
    }

    public function asDateTime(int|string|DateTimeInterface $value, ?string $format = null): string
    {
        $format ??= $this->defaultDateFormat;
        [$value] = $this->parseDate($value, $format);

        if (is_string($value)) {
            return $value;
        }

        $format = $this->dateTimeFormats[$format]['datetime'] ?? $format;

        if (isset($this->defaultDateFormats[$format])) {
            return new IntlDateFormatter(
                locale: $this->locale,
                dateType: $this->defaultDateFormats[$format] ?? IntlDateFormatter::NONE,
                timeType: $this->defaultDateFormats[$format] ?? IntlDateFormatter::NONE,
                timezone: $this->timeZone,
            )->format($value->toDateTime());
        }

        return new IntlDateFormatter(
            locale: $this->locale,
            timezone: $this->timeZone,
            pattern: $format,
        )->format($value->toDateTime());
    }

    public function asDecimal(mixed $value, ?int $decimals = null, array $attributes = []): string
    {
        if (is_null($value)) {
            return '';
        }

        if (is_string($value) && $this->willBeMisrepresented($value)) {
            return $this->asDecimalFallback($value, $decimals);
        }

        $value = $this->normalizeNumericValue($value);

        $formatter = new NumberFormatter(
            locale: $this->locale,
            style: NumberFormatter::DECIMAL,
        );

        if ($decimals !== null) {
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        }

        foreach ($attributes as $attribute => $attributeValue) {
            $formatter->setAttribute($attribute, $attributeValue);
        }

        return $formatter->format($value);
    }

    private function asDecimalFallback(mixed $value, ?int $decimals = null): string
    {
        if (empty($value)) {
            $value = 0;
        }

        $value = $this->normalizeNumericStringValue($value);

        if (str_contains((string) $value, '.')) {
            [$integerPart, $fractionalPart] = explode('.', (string) $value, 2);
        } else {
            $integerPart = $value;
            $fractionalPart = null;
        }

        $decimalOutput = '';
        $decimals ??= 2;
        $carry = 0;

        if ($decimals > 0) {
            $decimalSeparator = new Locale($this->locale)->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);

            if ($fractionalPart === null) {
                $fractionalPart = str_repeat('0', $decimals);
            } elseif (strlen($fractionalPart) > $decimals) {
                $cursor = $decimals;

                // checking if fractional part must be rounded
                if ((int) substr($fractionalPart, $cursor, 1) >= 5) {
                    while (--$cursor >= 0) {
                        $carry = 0;

                        $oneUp = (int) substr($fractionalPart, $cursor, 1) + 1;
                        if ($oneUp === 10) {
                            $oneUp = 0;
                            $carry = 1;
                        }

                        $fractionalPart = substr($fractionalPart, 0, $cursor).$oneUp.substr($fractionalPart, $cursor + 1);

                        if ($carry === 0) {
                            break;
                        }
                    }
                }

                $fractionalPart = substr($fractionalPart, 0, $decimals);
            } elseif (strlen($fractionalPart) < $decimals) {
                $fractionalPart = str_pad($fractionalPart, $decimals, '0');
            }

            $decimalOutput .= $decimalSeparator.$fractionalPart;
        }

        // checking if integer part must be rounded
        if ($carry || ($decimals === 0 && $fractionalPart !== null && (int) substr($fractionalPart, 0, 1) >= 5)) {
            $integerPartLength = strlen((string) $integerPart);
            $cursor = 0;

            while (++$cursor <= $integerPartLength) {
                $carry = 0;

                $oneUp = (int) substr((string) $integerPart, -$cursor, 1) + 1;
                if ($oneUp === 10) {
                    $oneUp = 0;
                    $carry = 1;
                }

                $integerPart = substr((string) $integerPart, 0, -$cursor).$oneUp.substr((string) $integerPart, $integerPartLength - $cursor + 1);

                if ($carry === 0) {
                    break;
                }
            }
            if ($carry === 1) {
                $integerPart = '1'.$integerPart;
            }
        }

        if (strlen((string) $integerPart) > 3) {
            $thousandSeparator = new Locale($this->locale)->getNumberSymbol(Locale::SYMBOL_GROUPING_SEPARATOR);

            $integerPart = strrev(implode(',', str_split(strrev((string) $integerPart), 3)));
            if ($thousandSeparator !== ',') {
                $integerPart = str_replace(',', $thousandSeparator, $integerPart);
            }
        }

        return $integerPart.$decimalOutput;
    }

    public function asDuration(DateInterval|string|int|float|null $value, $implodeString = ', ', $negativeSign = '-'): string
    {
        if (is_null($value)) {
            return '';
        }

        [$interval, $isNegative] = match (true) {
            $value instanceof DateInterval => [
                $value,
                $value->invert,
            ],
            is_numeric($value) => [
                new DateTime()->setTimestamp(abs((int) $value))->diff(new DateTime()->setTimestamp(0)),
                $value < 0,
            ],
            str_starts_with($value, 'P-') => [
                new DateInterval('P'.substr($value, 2)),
                true,
            ],
            default => [
                $interval = new DateInterval($value),
                $interval->invert,
            ]
        };

        $parts = [];
        if ($interval->y > 0) {
            $parts[] = t('{delta, plural, =1{1 year} other{# years}}', ['delta' => $interval->y], locale: $this->locale);
        }
        if ($interval->m > 0) {
            $parts[] = t('{delta, plural, =1{1 month} other{# months}}', ['delta' => $interval->m], locale: $this->locale);
        }
        if ($interval->d > 0) {
            $parts[] = t('{delta, plural, =1{1 day} other{# days}}', ['delta' => $interval->d], locale: $this->locale);
        }
        if ($interval->h > 0) {
            $parts[] = t('{delta, plural, =1{1 hour} other{# hours}}', ['delta' => $interval->h], locale: $this->locale);
        }
        if ($interval->i > 0) {
            $parts[] = t('{delta, plural, =1{1 minute} other{# minutes}}', ['delta' => $interval->i], locale: $this->locale);
        }
        if ($interval->s > 0) {
            $parts[] = t('{delta, plural, =1{1 second} other{# seconds}}', ['delta' => $interval->s], locale: $this->locale);
        }
        if ($interval->s === 0 && empty($parts)) {
            $parts[] = t('{delta, plural, =1{1 second} other{# seconds}}', ['delta' => $interval->s], locale: $this->locale);
            $isNegative = false;
        }

        if (empty($parts)) {
            return '';
        }

        return ($isNegative ? $negativeSign : '').implode($implodeString, $parts);
    }

    public function asInteger(mixed $value): string
    {
        return $this->asDecimal($value, 0, [
            NumberFormatter::ROUNDING_MODE => NumberFormatter::ROUND_FLOOR,
        ]);
    }

    public function asPercent(mixed $value, ?int $decimals = null): string
    {
        if (is_string($value) && $this->willBeMisrepresented($value)) {
            return $this->asPercentFallback($value, $decimals);
        }

        if (empty($value)) {
            $value = 0;
        } elseif ($decimals === null && is_numeric($value)) {
            $decimals = strpos(strrev((string) ($value * 100)), '.') ?: 0;
        }

        $value = $this->normalizeNumericValue($value);

        $formatter = new NumberFormatter(
            locale: $this->locale,
            style: NumberFormatter::PERCENT,
        );

        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals ?? 0);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals ?? 0);

        return $formatter->format($value);
    }

    private function asPercentFallback(mixed $value, ?int $decimals = null): string
    {
        if (empty($value)) {
            $value = 0;
        }

        $decimals ??= 0;
        $value = $this->normalizeNumericStringValue((string) $value);
        $separatorPosition = strrpos((string) $value, '.');

        if ($separatorPosition !== false) {
            $integerPart = substr((string) $value, 0, $separatorPosition);
            $fractionalPart = str_pad(substr((string) $value, $separatorPosition + 1), 2, '0');

            $integerPart .= substr($fractionalPart, 0, 2);
            $fractionalPart = substr($fractionalPart, 2);

            if ($fractionalPart === '') {
                $multipliedValue = $integerPart;
            } else {
                $multipliedValue = $integerPart.'.'.$fractionalPart;
            }
        } else {
            $multipliedValue = $value.'00';
        }

        return $this->asDecimalFallback($multipliedValue, $decimals).'%';
    }

    public function asSize(string|int|float|null $bytes, ?int $decimals = null): string
    {
        if (is_null($bytes)) {
            return '';
        }

        $bytes = $this->normalizeNumericValue($bytes);

        if ($bytes < $this->sizeFormatBase) {
            return t('{nFormatted} {n, plural, =1{byte} other{bytes}}', [
                'n' => $bytes,
                'nFormatted' => $this->asDecimal($bytes, 0),
            ], locale: $this->locale);
        }

        $units = $this->sizeFormatBase === 1024
            ? [['byte', 'bytes'], ['kibibyte', 'kibibytes'], ['mebibyte', 'mebibytes'], ['gibibyte', 'gibibytes'], ['tebibyte', 'tebibytes'], ['pebibyte', 'pebibytes']]
            : [['byte', 'bytes'], ['kilobyte', 'kilobytes'], ['megabyte', 'megabytes'], ['gigabyte', 'gigabytes'], ['terabyte', 'terabytes'], ['petabyte', 'petabytes']];

        $index = floor(log($bytes, $this->sizeFormatBase));
        $value = $bytes / ($this->sizeFormatBase ** $index);
        $unit = $units[(int) $index];

        return t("{nFormatted} {n, plural, =1{{$unit[0]}} other{{$unit[1]}}}", [
            'n' => abs($value),
            'nFormatted' => $this->asDecimal($value, $decimals),
        ], locale: $this->locale);
    }

    public function asShortSize(string|int|float|null $bytes, ?int $decimals = null): string
    {
        if (is_null($bytes)) {
            return '';
        }

        $bytes = $this->normalizeNumericValue($bytes);

        if ($bytes < $this->sizeFormatBase) {
            return t('{nFormatted} B', [
                'nFormatted' => $this->asDecimal($bytes, 0),
            ], locale: $this->locale);
        }

        $units = $this->sizeFormatBase === 1024
            ? ['B', 'KIB', 'MIB', 'GIB', 'TIB', 'PIB']
            : ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $index = floor(log($bytes, $this->sizeFormatBase));
        $value = $bytes / ($this->sizeFormatBase ** $index);

        return t("{nFormatted} {$units[(int) $index]}", [
            'nFormatted' => $this->asDecimal($value, $decimals),
        ], locale: $this->locale);
    }

    public function asTime(int|string|DateTimeInterface $value, ?string $format = null): string
    {
        $format ??= $this->defaultDateFormat;
        [$value] = $this->parseDate($value, $format);

        if (is_string($value)) {
            return $value;
        }

        $format = $this->dateTimeFormats[$format]['time'] ?? $format;

        if (isset($this->defaultDateFormats[$format])) {
            return new IntlDateFormatter(
                locale: $this->locale,
                dateType: IntlDateFormatter::NONE,
                timeType: $this->defaultDateFormats[$format] ?? IntlDateFormatter::NONE,
                timezone: $this->timeZone,
            )->format($value->toDateTime());
        }

        return new IntlDateFormatter(
            locale: $this->locale,
            timezone: $this->timeZone,
            pattern: $format,
        )->format($value->toDateTime());
    }

    public function asTimestamp(int|string|DateTimeInterface $value, ?string $format = null, bool $withPreposition = false): string
    {
        $format ??= $this->defaultDateFormat;
        [$dateTime, $hasTimeInfo] = $this->parseDate($value, $format);

        if (is_string($dateTime)) {
            return $dateTime;
        }

        if ($dateTime->isToday()) {
            if (! $hasTimeInfo) {
                return $withPreposition
                    ? t('today', locale: $this->locale)
                    : t('Today', locale: $this->locale);
            }

            $time = $this->asTime($dateTime, $format);

            return $withPreposition
                ? t('at {time}', ['time' => $time], locale: $this->locale)
                : $time;
        }

        if ($dateTime->isYesterday()) {
            return $withPreposition
                ? t('yesterday', locale: $this->locale)
                : t('Yesterday', locale: $this->locale);
        }

        if ($dateTime->clone()->addDays(7)->isNowOrFuture()) {
            $day = (int) $dateTime->format('w');
            $dayName = new Locale($this->locale)->getWeekDayName($day);

            return $withPreposition
                ? t('on {day}', ['day' => $dayName], locale: $this->locale)
                : $dayName;
        }

        $date = $this->asDate($dateTime, $format);

        return $withPreposition
            ? t('on {date}', ['date' => $date], locale: $this->locale)
            : $date;
    }

    public function willBeMisrepresented(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (empty($value)) {
            $value = 0;
        }

        return (string) $this->normalizeNumericValue($value) !== $this->normalizeNumericStringValue((string) $value);
    }

    private function normalizeNumericStringValue($value): ?string
    {
        $powerPosition = strrpos((string) $value, 'E');
        if ($powerPosition !== false) {
            $valuePart = substr((string) $value, 0, $powerPosition);
            $powerPart = substr((string) $value, $powerPosition + 1);
        } else {
            $powerPart = null;
            $valuePart = $value;
        }

        $separatorPosition = strrpos((string) $valuePart, '.');

        if ($separatorPosition !== false) {
            $integerPart = substr((string) $valuePart, 0, $separatorPosition);
            $fractionalPart = substr((string) $valuePart, $separatorPosition + 1);
        } else {
            $integerPart = $valuePart;
            $fractionalPart = null;
        }

        // truncate insignificant zeros, keep minus
        $integerPart = preg_replace('/^\+?(-?)0*(\d+)$/', '$1$2', (string) $integerPart);
        // for zeros only leave one zero, keep minus
        $integerPart = preg_replace('/^\+?(-?)0*$/', '${1}0', (string) $integerPart);

        if ($fractionalPart !== null) {
            // truncate insignificant zeros
            $fractionalPart = rtrim($fractionalPart, '0');

            if (empty($fractionalPart)) {
                $fractionalPart = $powerPart !== null ? '0' : null;
            }
        }

        $normalizedValue = $integerPart;
        if ($fractionalPart !== null) {
            $normalizedValue .= '.'.$fractionalPart;
        } elseif ($normalizedValue === '-0') {
            $normalizedValue = '0';
        }

        if ($powerPart !== null) {
            $normalizedValue .= 'E'.$powerPart;
        }

        return $normalizedValue;
    }

    /**
     * @see https://github.com/craftcms/cms/issues/16953
     */
    private function getCorrectOffset(DateTimeInterface $value): ?int
    {
        $intlTimeZone = IntlTimeZone::fromDateTimeZone($value->getTimezone());
        $intlTimeZone->getOffset($value->getTimestamp(), true, $o1, $o2);
        $intlTimeZone->getOffset($value->getTimestamp(), false, $o3, $o4);

        // get PHP DateTime offset for this date
        $phpOffset = $value->getTimezone()->getOffset($value);

        // get DST offset that intl time zone would use (divided by 1000, b/c different units)
        $dstIntlOffset = ($intlTimeZone->getRawOffset() + $intlTimeZone->getDSTSavings()) / 1000;

        // get raw offset that intl time zone would use (divided by 1000, b/c different units)
        $rawIntlOffset = $intlTimeZone->getRawOffset() / 1000;

        if ($phpOffset === $dstIntlOffset || $phpOffset === $rawIntlOffset) {
            return null;
        }

        return $phpOffset;
    }

    /**
     * @return array{CarbonInterface|string, bool}
     */
    private function parseDate(int|string|DateTimeInterface $value, string $format): array
    {
        if ($value instanceof CarbonInterface) {
            return [$value, true];
        }

        /** Int is always a timestamp */
        if (is_int($value)) {
            $dateTime = Date::createFromTimestamp($value);
        }

        // Try parsing a date first
        $hasTimeInfo = true;
        if (is_string($value)) {
            while (true) {
                try {
                    $dateTime = Date::createFromFormat('Y-m-d', $value, 'UTC');
                    $hasTimeInfo = false;
                    break;
                } catch (Throwable) {
                }

                try {
                    $dateTime = Date::createFromFormat('Y-m-d H:i:s', $value, 'UTC');
                    break;
                } catch (Throwable) {
                }

                break;
            }
        }

        if (! isset($dateTime)) {
            $dateTime = Date::parse($value);
        }

        if ($offset = $this->getCorrectOffset($dateTime)) {
            $dateTime = $dateTime->addHours($offset / 3600);
        }

        if (str_starts_with($format, 'php:')) {
            return [$dateTime->format(Str::after($format, 'php:')), $hasTimeInfo];
        }

        return [$dateTime, $hasTimeInfo];
    }

    private function normalizeNumericValue(mixed $value): float|int
    {
        if (empty($value)) {
            return 0;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw_unless(is_numeric($value), InvalidArgumentException::class, "'$value' is not a numeric value.");

        return $value;
    }
}
