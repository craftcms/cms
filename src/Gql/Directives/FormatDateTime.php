<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Directives;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Translation\Locale;
use DateTimeInterface;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class FormatDateTime extends Directive
{
    public const string DEFAULT_FORMAT = 'Y-m-d\TH:i:sP';

    public const string DEFAULT_TIMEZONE = 'UTC';

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
        $format = $arguments['format'] ?? self::DEFAULT_FORMAT;

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

        return $formatter->asDatetime($value, $format, $arguments['withTimeZone'] ?? false);
    }

    public static function defaultTimeZone(): string
    {
        return Cms::config()->setGraphqlDatesToSystemTimeZone ? Cms::timezone() : self::DEFAULT_TIMEZONE;
    }
}
