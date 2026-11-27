<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Directives;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Translation\Locale;
use DateTimeInterface;
use GraphQL\Error\UserError;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Date;

/**
 * @since 6.0.0
 */
class FormatDateTime extends Directive
{
    public const string DEFAULT_FORMAT = 'Y-m-d\TH:i:sP';

    public const string DEFAULT_TIMEZONE = 'UTC';

    public const string DEFAULT_DATE_FORMAT = 'Y-m-d';

    public const string DEFAULT_TIME_FORMAT = 'H:i:sP';

    public const string OUTPUT_DATETIME = 'datetime';

    public const string OUTPUT_DATE = 'date';

    public const string OUTPUT_TIME = 'time';

    public static function create(): GqlDirective
    {
        $typeName = static::name();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new self([
            'name' => $typeName,
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [
                [
                    'name' => 'format',
                    'type' => Type::string(),
                    'defaultValue' => self::DEFAULT_FORMAT,
                    'description' => 'The format to use. Can be `short`, `medium`, `long`, `full`, an [ICU date format](http://userguide.icu-project.org/formatparse/datetime), or a [PHP date format](https://www.php.net/manual/en/function.date.php). Defaults to the [Atom date time format](https://www.php.net/manual/en/class.datetimeinterface.php#datetime.constants.atom]).',
                ],
                [
                    'name' => 'timezone',
                    'type' => Type::string(),
                    'description' => 'The full name of the timezone (e.g., America/New_York). Defaults to '.self::defaultTimeZone().' if no timezone set on the field.',
                    'defaultValue' => self::defaultTimeZone(),
                ],
                [
                    'name' => 'locale',
                    'type' => Type::string(),
                    'description' => 'The locale to use when formatting the date. (E.g., en-US)',
                ],
                [
                    'name' => 'output',
                    'type' => Type::string(),
                    'defaultValue' => self::OUTPUT_DATETIME,
                    'description' => 'Which part of the date to output: `datetime`, `date`, or `time`. With `date` or `time`, `format` defaults to `'.self::DEFAULT_DATE_FORMAT.'` or `'.self::DEFAULT_TIME_FORMAT.'`, and `short`, `medium`, `long`, and `full` use the locale’s date or time format.',
                ],
                [
                    'name' => 'withTimeZone',
                    'type' => Type::boolean(),
                    'description' => 'Whether the time zone abbreviation should be appended to the formatted time.',
                    'defaultValue' => false,
                ],
            ],
            'description' => 'Formats a date in the desired format. Can be applied to all fields, only changes output of DateTime fields.',
        ]));
    }

    public static function name(): string
    {
        return 'formatDateTime';
    }

    /** @param array<string, mixed> $arguments */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        if (! $value instanceof DateTimeInterface) {
            return $value;
        }

        /** @var DateTimeInterface $value */
        $output = $arguments['output'] ?? self::OUTPUT_DATETIME;
        $format = $arguments['format'] ?? self::DEFAULT_FORMAT;

        // The format argument always defaults to the date time format, so swap in the output's own default
        if ($format === self::DEFAULT_FORMAT) {
            $format = match ($output) {
                self::OUTPUT_DATE => self::DEFAULT_DATE_FORMAT,
                self::OUTPUT_TIME => self::DEFAULT_TIME_FORMAT,
                default => $format,
            };
        }

        // Is this a custom PHP date format?
        if (! in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
            if (str_starts_with((string) $format, 'icu:')) {
                $format = substr((string) $format, 4);
            } else {
                $format = Str::start($format, 'php:');
            }
        }

        if (! empty($arguments['locale'])) {
            $formatter = I18N::getLocaleById($arguments['locale'])->getFormatter();
        } else {
            $formatter = I18N::getFormatter();
        }

        // Leave timezone alone, unless directed to modify with arguments.
        if (! empty($arguments['timezone'])) {
            $timezone = $arguments['timezone'];
        } else {
            $timezone = $value->getTimezone()->getName();
        }

        $formatter->timeZone = $timezone;

        // PHP formats are shown in the date's own time zone, so convert it first
        $value = Date::instance($value)->setTimezone($timezone);

        $withTimeZone = $arguments['withTimeZone'] ?? false;

        return match ($output) {
            self::OUTPUT_DATE => $formatter->asDate($value, $format),
            self::OUTPUT_TIME => $formatter->asTime($value, $format, $withTimeZone),
            self::OUTPUT_DATETIME => $formatter->asDatetime($value, $format, $withTimeZone),
            default => throw new UserError("Invalid output: $output"),
        };
    }

    public static function defaultTimeZone(): string
    {
        return Cms::config()->setGraphqlDatesToSystemTimeZone ? Cms::timezone() : self::DEFAULT_TIMEZONE;
    }
}
