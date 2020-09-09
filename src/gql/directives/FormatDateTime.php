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
    const DEFAULT_FORMAT = 'Y-m-d\TH:i:sP';
    const DEFAULT_TIMEZONE = 'UTC';

    /**
     * @inheritdoc
     */
    public static function create(): GqlDirective
    {
        if ($type = GqlEntityRegistry::getEntity(self::name())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(static::name(), new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [
                new FieldArgument([
                    'name' => 'format',
                    'type' => Type::string(),
                    'defaultValue' => self::DEFAULT_FORMAT,
                    'description' => 'This specifies the format to use. This can be `short`, `medium`, `long`, `full`, an [ICU date format](http://userguide.icu-project.org/formatparse/datetime), or a [PHP date format](https://www.php.net/manual/en/function.date.php). It defaults to the [Atom date time format](https://www.php.net/manual/en/class.datetimeinterface.php#datetime.constants.atom]).'
                ]),
                new FieldArgument([
                    'name' => 'timezone',
                    'type' => Type::string(),
                    'description' => 'The full name of the timezone, defaults to UTC. (E.g., America/New_York)',
                    'defaultValue' => self::DEFAULT_TIMEZONE
                ]),
                new FieldArgument([
                    'name' => 'locale',
                    'type' => Type::string(),
                    'description' => 'The locale to use when formatting the date. (E.g., en-US)',
                ])
            ],
            'description' => 'This directive allows for formatting any date to the desired format. It can be applied to all fields, but changes anything only when applied to a DateTime field.'
        ]));

        return $type;
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
    public static function apply($source, $value, array $arguments, ResolveInfo $resolveInfo)
    {
        if ($value instanceof \DateTime) {
            /** @var \DateTime $value */
            $format = $arguments['format'] ?? self::DEFAULT_FORMAT;
            $timezone = $arguments['timezone'] ?? self::DEFAULT_TIMEZONE;

            // Is this a custom PHP date format?
            if ($format !== null && !in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
                if (strpos($format, 'icu:') === 0) {
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
            $formatter->timeZone = $timezone;
            $value = $formatter->asDatetime($value, $format);
        }

        return $value;
    }
}
