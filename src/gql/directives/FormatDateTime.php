<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\directives;

use Craft;
use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use craft\helpers\StringHelper;
use craft\i18n\Locale;
use DateTime;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Class FormatDateTime
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class FormatDateTime extends Directive
{
    public const DEFAULT_FORMAT = 'Y-m-d\TH:i:sP';
    public const DEFAULT_TIMEZONE = 'UTC';

    /**
     * @inheritdoc
     */
    public static function create(): GqlDirective
    {
        if ($type = GqlEntityRegistry::getEntity(self::name())) {
            return $type;
        }

        return GqlEntityRegistry::createEntity(static::name(), new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [
                new FieldArgument([
                    'name' => 'format',
                    'type' => Type::string(),
                    'defaultValue' => self::DEFAULT_FORMAT,
                    'description' => 'The format to use. Can be `short`, `medium`, `long`, `full`, an [ICU date format](http://userguide.icu-project.org/formatparse/datetime), or a [PHP date format](https://www.php.net/manual/en/function.date.php). Defaults to the [Atom date time format](https://www.php.net/manual/en/class.datetimeinterface.php#datetime.constants.atom]).',
                ]),
                new FieldArgument([
                    'name' => 'timezone',
                    'type' => Type::string(),
                    'description' => 'The full name of the timezone (e.g., America/New_York). Defaults to ' . self::defaultTimeZone() . ' if no timezone set on the field.',
                    'defaultValue' => self::defaultTimeZone(),
                ]),
                new FieldArgument([
                    'name' => 'locale',
                    'type' => Type::string(),
                    'description' => 'The locale to use when formatting the date. (E.g., en-US)',
                ]),
            ],
            'description' => 'Formats a date in the desired format. Can be applied to all fields, only changes output of DateTime fields.',
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function name(): string
    {
        return 'formatDateTime';
    }

    /**
     * @inheritdoc
     */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        if ($value instanceof DateTime) {
            /** @var DateTime $value */
            $format = $arguments['format'] ?? self::DEFAULT_FORMAT;

            // Is this a custom PHP date format?
            if ($format !== null && !in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
                if (str_starts_with($format, 'icu:')) {
                    $format = substr($format, 4);
                } else {
                    $format = StringHelper::ensureLeft($format, 'php:');
                }
            }

            if (!empty($arguments['locale'])) {
                $formatter = (new Locale($arguments['locale']))->getFormatter();
            } else {
                $formatter = Craft::$app->getFormatter();
            }

            $formatter->datetimeFormat = $format;

            // Leave timezone alone, unless directed to modify with arguments.
            if (!empty($arguments['timezone'])) {
                $timezone = $arguments['timezone'];
            } else {
                $timezone = $value->getTimezone()->getName();
            }

            $formatter->timeZone = $timezone;

            $value = $formatter->asDatetime($value, $format);
        }

        return $value;
    }

    /**
     * Returns the default time zone to be used.
     *
     * @since 4.0.0
     */
    public static function defaultTimeZone(): string
    {
        return Craft::$app->getConfig()->getGeneral()->setGraphqlDatesToSystemTimeZone ? Craft::$app->getTimeZone() : self::DEFAULT_TIMEZONE;
    }
}
