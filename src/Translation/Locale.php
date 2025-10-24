<?php

declare(strict_types=1);

namespace CraftCms\Cms\Translation;

use DateTime;
use Exception;
use Illuminate\Support\Collection;
use IntlDateFormatter;
use NumberFormatter;
use RuntimeException;
use Stringable;

final class Locale implements Stringable
{
    public const int ATTR_POSITIVE_PREFIX = 0;

    public const int ATTR_POSITIVE_SUFFIX = 1;

    public const int ATTR_NEGATIVE_PREFIX = 2;

    public const int ATTR_NEGATIVE_SUFFIX = 3;

    public const int ATTR_PADDING_CHARACTER = 4;

    public const int ATTR_CURRENCY_CODE = 5;

    /**
     * @var int The default rule set. This is only available with rule-based
     *          formatters.
     */
    public const int ATTR_DEFAULT_RULESET = 6;

    /**
     * @var int The public rule sets. This is only available with rule-based
     *          formatters. This is a read-only attribute. The public rulesets are
     *          returned as a single string, with each ruleset name delimited by ';'
     *          (semicolon).
     */
    public const int ATTR_PUBLIC_RULESETS = 7;

    public const int STYLE_DECIMAL = 1;

    public const int STYLE_CURRENCY = 2;

    public const int STYLE_PERCENT = 3;

    public const int STYLE_SCIENTIFIC = 4;

    public const int SYMBOL_DECIMAL_SEPARATOR = 0;

    public const int SYMBOL_GROUPING_SEPARATOR = 1;

    public const int SYMBOL_PATTERN_SEPARATOR = 2;

    public const int SYMBOL_PERCENT = 3;

    public const int SYMBOL_ZERO_DIGIT = 4;

    /**
     * @var int Character representing a digit in the pattern.
     */
    public const int SYMBOL_DIGIT = 5;

    public const int SYMBOL_MINUS_SIGN = 6;

    public const int SYMBOL_PLUS_SIGN = 7;

    public const int SYMBOL_CURRENCY = 8;

    public const int SYMBOL_INTL_CURRENCY = 9;

    public const int SYMBOL_MONETARY_SEPARATOR = 10;

    public const int SYMBOL_EXPONENTIAL = 11;

    public const int SYMBOL_PERMILL = 12;

    public const int SYMBOL_PAD_ESCAPE = 13;

    public const int SYMBOL_INFINITY = 14;

    public const int SYMBOL_NAN = 15;

    public const int SYMBOL_SIGNIFICANT_DIGIT = 16;

    public const int SYMBOL_MONETARY_GROUPING_SEPARATOR = 17;

    public const string LENGTH_ABBREVIATED = 'abbreviated';

    public const string LENGTH_SHORT = 'short';

    public const string LENGTH_MEDIUM = 'medium';

    public const string LENGTH_LONG = 'long';

    public const string LENGTH_FULL = 'full';

    public const string FORMAT_ICU = 'icu';

    public const string FORMAT_PHP = 'php';

    public const string FORMAT_JUI = 'jui';

    public const string FORMAT_HUMAN = 'human';

    /**
     * @var array The languages that use RTL orientation.
     */
    private static array $rtlLanguages = [
        'ar',
        'fa',
        'he',
        'ps',
        'ur',
    ];

    public function __construct(
        public string $id,

        /** @var string|null The original locale ID, if this is an alias. */
        public ?string $aliasOf = null,

        /**
         * @var string|null The locale’s custom display name.
         *
         * @see getDisplayName()
         * @see setDisplayName()
         */
        public private(set) ?string $displayName = null,

        private ?Formatter $formatter = null,
    ) {
        if (str_contains($this->id, '_')) {
            $this->id = str_replace('_', '-', $this->id);
        }
    }

    /**
     * Use the ID as the string representation of locales.
     */
    public function __toString(): string
    {
        return $this->id;
    }

    public function getLanguageID(): string
    {
        if (($pos = strpos($this->id, '-')) !== false) {
            return substr($this->id, 0, $pos);
        }

        return $this->id;
    }

    /**
     * Returns this locale’s script ID.
     *
     * A script ID consists of only the last four characters after a dash in the locale ID.
     */
    public function getScriptID(): ?string
    {
        if (! str_contains($this->id, '-')) {
            return null;
        }

        // Find sub tags
        $subTag = explode('-', $this->id);

        // Script sub tags can be distinguished from territory sub tags by length
        if (strlen($subTag[1]) === 4) {
            return $subTag[1];
        }

        return null;
    }

    /**
     * Returns this locale’s territory ID.
     *
     * A territory ID consists of only the last two to three letter or digits after a dash in the locale ID.
     */
    public function getTerritoryID(): ?string
    {
        if (! str_contains($this->id, '-')) {
            return null;
        }

        // Find sub tags
        $subTag = explode('-', $this->id);

        // Territory sub tags can be distinguished from script sub tags by length
        if (isset($subTag[2]) && strlen($subTag[2]) < 4) {
            return $subTag[2];
        }

        if (strlen($subTag[1]) < 4) {
            return $subTag[1];
        }

        return null;
    }

    /**
     * Returns the locale name in a given language.
     *
     * If a custom display name has been set via [[setDisplayName()]],
     * that will be returned regardless of `$inLocale`.
     */
    public function getDisplayName(?string $inLocale = null): string
    {
        if (isset($this->displayName)) {
            return $this->displayName;
        }

        // If no target locale is specified, default to this locale
        if ($inLocale === null) {
            $inLocale = $this->id;
        }

        return \Locale::getDisplayName($this->id, $inLocale);
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Returns the language’s orientation (ltr or rtl).
     */
    public function getOrientation(): string
    {
        if (in_array($this->getLanguageID(), self::$rtlLanguages, true)) {
            return 'rtl';
        }

        return 'ltr';
    }

    public function getFormatter(): Formatter
    {
        if (isset($this->formatter)) {
            return $this->formatter;
        }

        $formatter = new Formatter;
        $formatter->locale = $this->aliasOf ?? $this->id;
        $formatter->sizeFormatBase = 1000;
        $formatter->dateTimeFormats = [
            self::LENGTH_SHORT => [
                'date' => $this->getDateFormat(self::LENGTH_SHORT),
                'time' => $this->getTimeFormat(self::LENGTH_SHORT),
                'datetime' => $this->getDateTimeFormat(self::LENGTH_SHORT),
            ],
            self::LENGTH_MEDIUM => [
                'date' => $this->getDateFormat(self::LENGTH_MEDIUM),
                'time' => $this->getTimeFormat(self::LENGTH_MEDIUM),
                'datetime' => $this->getDateTimeFormat(self::LENGTH_MEDIUM),
            ],
            self::LENGTH_LONG => [
                'date' => $this->getDateFormat(self::LENGTH_LONG),
                'time' => $this->getTimeFormat(self::LENGTH_LONG),
                'datetime' => $this->getDateTimeFormat(self::LENGTH_LONG),
            ],
            self::LENGTH_FULL => [
                'date' => $this->getDateFormat(self::LENGTH_FULL),
                'time' => $this->getTimeFormat(self::LENGTH_FULL),
                'datetime' => $this->getDateTimeFormat(self::LENGTH_FULL),
            ],
        ];

        return $this->formatter = $formatter;
    }

    // Date/Time Formatting
    // -------------------------------------------------------------------------

    /**
     * Returns the localized ICU date format.
     *
     * @param  string|null  $length  The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param  string  $format  The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     * @return string The localized ICU date format.
     */
    public function getDateFormat(?string $length = null, string $format = self::FORMAT_ICU): string
    {
        return $this->getDateTimeFormatInternal(
            length: $length,
            withDate: true,
            withTime: false,
            format: $format,
        );
    }

    /**
     * Returns the localized ICU time format.
     *
     * @param  string|null  $length  The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param  string  $format  The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     * @return string The localized ICU time format.
     */
    public function getTimeFormat(?string $length = null, string $format = self::FORMAT_ICU): string
    {
        return $this->getDateTimeFormatInternal(
            length: $length,
            withDate: false,
            withTime: true,
            format: $format,
        );
    }

    /**
     * Returns the localized ICU date + time format.
     *
     * @param  string|null  $length  The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param  string  $format  The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     * @return string The localized ICU date + time format.
     */
    public function getDateTimeFormat(?string $length = null, string $format = self::FORMAT_ICU): string
    {
        return $this->getDateTimeFormatInternal(
            length: $length,
            withDate: true,
            withTime: true,
            format: $format,
        );
    }

    /**
     * Returns a localized month name.
     *
     * @param  int  $month  The month to return (1-12).
     * @param  string|null  $length  The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param  bool  $standAlone  Whether to return the "stand alone" month name.
     * @return string The localized month name.
     */
    public function getMonthName(int $month, ?string $length = null, bool $standAlone = true): string
    {
        $length ??= self::LENGTH_FULL;

        $formatter = new IntlDateFormatter(
            locale: $this->aliasOf ?? $this->id,
            dateType: IntlDateFormatter::NONE,
            timeType: IntlDateFormatter::NONE,
        );

        switch ($length) {
            case self::LENGTH_ABBREVIATED:
                $formatter->setPattern($standAlone ? 'LLLLL' : 'MMMMM');
                break; // S
            case self::LENGTH_SHORT:
            case self::LENGTH_MEDIUM:
                $formatter->setPattern($standAlone ? 'LLL' : 'MMM');
                break; // Sep
            default:
                $formatter->setPattern($standAlone ? 'LLLL' : 'MMMM');
                break; // September
        }

        return $formatter->format(new DateTime('1970-'.sprintf('%02d', $month).'-01'));
    }

    /**
     * Returns all of the localized month names.
     *
     * @param  string|null  $length  The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param  bool  $standAlone  Whether to return the "stand alone" month names.
     * @return array The localized month names.
     */
    public function getMonthNames(?string $length = null, bool $standAlone = true): array
    {
        return Collection::times(12)
            ->map(fn (int $month): string => $this->getMonthName($month, $length, $standAlone))
            ->all();
    }

    /**
     * Returns a localized day of the week name.
     *
     * @param  int  $day  The day of the week to return (0-6), where 0 stands for Sunday.
     * @param  string|null  $length  The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::SHORT, ::MEDIUM, ::FULL
     * @param  bool  $standAlone  Whether to return the "stand alone" day of the week name.
     * @return string The localized day of the week name.
     */
    public function getWeekDayName(int $day, ?string $length = null, bool $standAlone = true): string
    {
        $length ??= self::LENGTH_FULL;

        $formatter = new IntlDateFormatter(
            locale: $this->aliasOf ?? $this->id,
            dateType: IntlDateFormatter::NONE,
            timeType: IntlDateFormatter::NONE,
        );

        switch ($length) {
            case self::LENGTH_ABBREVIATED:
                // T
                $formatter->setPattern($standAlone ? 'ccccc' : 'eeeee');
                break;
            case self::LENGTH_SHORT:
                // Tu
                $formatter->setPattern($standAlone ? 'cccccc' : 'eeeeee');
                break;
            case self::LENGTH_MEDIUM:
                // Tue
                $formatter->setPattern($standAlone ? 'ccc' : 'eee');
                break;
            default:
                // Tuesday
                $formatter->setPattern($standAlone ? 'cccc' : 'eeee');
                break;
        }

        // 1970-01-04 => Sunday (0 + 4)
        // 1970-01-05 => Monday (1 + 4)
        // 1970-01-06 => Tuesday (2 + 4)
        // 1970-01-07 => Wednesday (3 + 4)
        // 1970-01-08 => Thursday (4 + 4)
        // 1970-01-09 => Friday (5 + 4)
        // 1970-01-10 => Saturday (6 + 4)
        return $formatter->format(new DateTime('1970-01-'.sprintf('%02d', $day + 4)));
    }

    /**
     * Returns all of the localized day of the week names.
     *
     * @param  string|null  $length  The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param  bool  $standAlone  Whether to return the "stand alone" day of the week names.
     * @return array The localized day of the week names.
     */
    public function getWeekDayNames(?string $length = null, bool $standAlone = true): array
    {
        return Collection::times(7)
            ->map(fn (int $day): string => $this->getWeekDayName($day - 1, $length, $standAlone))
            ->all();
    }

    /**
     * Returns the "AM" name for this locale.
     */
    public function getAMName(): string
    {
        return $this->getFormatter()->asDate(new DateTime('00:00'), 'a');
    }

    /**
     * Returns the "PM" name for this locale.
     */
    public function getPMName(): string
    {
        return $this->getFormatter()->asDate(new DateTime('12:00'), 'a');
    }

    // Text Attributes and Symbols
    // -------------------------------------------------------------------------

    /**
     * Returns a text attribute used by this locale.
     *
     * @param  int  $attribute  The attribute to return. Values: Locale::
     */
    public function getTextAttribute(int $attribute): string
    {
        return new NumberFormatter(
            locale: $this->aliasOf ?? $this->id,
            style: NumberFormatter::DECIMAL,
        )->getTextAttribute($attribute);
    }

    /**
     * Returns a number pattern used by this locale.
     *
     * @param  int  $style  The pattern style to return.
     *                      Accepted values: Locale::STYLE_DECIMAL, ::STYLE_CURRENCY, ::STYLE_PERCENT, ::STYLE_SCIENTIFIC
     */
    public function getNumberPattern(int $style): string
    {
        return new NumberFormatter(
            locale: $this->aliasOf ?? $this->id,
            style: $style,
        )->getPattern();
    }

    /**
     * Returns a number symbol used by this locale.
     *
     * @param  int  $symbol  The symbol to return. Accepted values: Locale::SYMBOL_DECIMAL_SEPARATOR,
     *                       ::SYMBOL_GROUPING_SEPARATOR, ::SYMBOL_PATTERN_SEPARATOR, ::SYMBOL_PERCENT, ::SYMBOL_ZERO_DIGIT,
     *                       ::SYMBOL_DIGIT, ::SYMBOL_MINUS_SIGN, ::SYMBOL_PLUS_SIGN, ::SYMBOL_CURRENCY, ::SYMBOL_INTL_CURRENCY,
     *                       ::SYMBOL_MONETARY_SEPARATOR, ::SYMBOL_EXPONENTIAL, ::SYMBOL_PERMILL, ::SYMBOL_PAD_ESCAPE,
     *                       ::SYMBOL_INFINITY, ::SYMBOL_NAN, ::SYMBOL_SIGNIFICANT_DIGIT, ::SYMBOL_MONETARY_GROUPING_SEPARATOR
     */
    public function getNumberSymbol(int $symbol): string
    {
        return new NumberFormatter(
            locale: $this->aliasOf ?? $this->id,
            style: NumberFormatter::DECIMAL,
        )->getSymbol($symbol);
    }

    /**
     * Returns this locale’s symbol for a given currency.
     *
     * @param  string  $currency  The 3-letter ISO 4217 currency code indicating the currency to use.
     * @return string The currency symbol.
     */
    public function getCurrencySymbol(string $currency): string
    {
        // hat tip: https://stackoverflow.com/a/30026774
        $locale = $this->aliasOf ?? $this->id;

        return new NumberFormatter(
            locale: "$locale@currency=$currency",
            style: NumberFormatter::CURRENCY,
        )->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Returns a localized date/time format.
     *
     * @param  string  $length  The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param  bool  $withDate  Whether the date should be included in the format.
     * @param  bool  $withTime  Whether the time should be included in the format.
     * @param  string  $format  The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI, ::FORMAT_HUMAN
     * @return string The date/time format
     */
    private function getDateTimeFormatInternal(string $length, bool $withDate, bool $withTime, string $format): string
    {
        $icuFormat = $this->_getDateTimeIcuFormat($length, $withDate, $withTime);

        if ($format === self::FORMAT_ICU) {
            return $icuFormat;
        }

        $type = ($withDate ? 'date' : '').($withTime ? 'time' : '');
        $locale = $this->aliasOf ?? $this->id;

        return match ($format) {
            self::FORMAT_PHP => FormatConverter::convertDateIcuToPhp($icuFormat, $type, $locale),
            self::FORMAT_JUI => FormatConverter::convertDateIcuToJui($icuFormat, $type, $locale),
            self::FORMAT_HUMAN => FormatConverter::convertDatePhpToHuman(
                FormatConverter::convertDateIcuToPhp($icuFormat, $type, $locale),
            ),
            default => throw new RuntimeException("Invalid format type: $format"),
        };
    }

    /**
     * Returns a localized ICU date/time format.
     *
     * @param  string  $length  The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param  bool  $withDate  Whether the date should be included in the format.
     * @param  bool  $withTime  Whether the time should be included in the format.
     * @return string The ICU date/time format
     *
     * @throws Exception if $length is invalid
     */
    private function _getDateTimeIcuFormat(string $length, bool $withDate, bool $withTime): string
    {
        // Convert length to IntlDateFormatter constants
        $length = match ($length) {
            self::LENGTH_FULL => IntlDateFormatter::FULL,
            self::LENGTH_LONG => IntlDateFormatter::LONG,
            self::LENGTH_MEDIUM => IntlDateFormatter::MEDIUM,
            self::LENGTH_SHORT => IntlDateFormatter::SHORT,
            default => throw new Exception('Invalid date/time format length: '.$length),
        };

        $dateType = ($withDate ? $length : IntlDateFormatter::NONE);
        $timeType = ($withTime ? $length : IntlDateFormatter::NONE);
        $formatter = new IntlDateFormatter($this->aliasOf ?? $this->id, $dateType, $timeType);
        $pattern = $formatter->getPattern();

        // Use 4-digit years
        return strtr($pattern, [
            'yyyy' => 'yyyy',
            'yy' => 'yyyy',
        ]);
    }
}
