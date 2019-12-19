<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\directives;

use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
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
                    'description' => 'This specifies the format to use. It defaults to the [Atom date time format](https://www.php.net/manual/en/class.datetimeinterface.php#datetime.constants.atom]).'
                ]),
                new FieldArgument([
                    'name' => 'timezone',
                    'type' => Type::string(),
                    'description' => 'The full name of the timezone, defaults to UTC. (E.g., America/New_York)',
                    'defaultValue' => self::DEFAULT_TIMEZONE
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
            $timezone = new \DateTimeZone($arguments['timezone'] ?? self::DEFAULT_TIMEZONE);

            $value = $value->setTimezone($timezone)->format($format);
        }

        return $value;
    }


}
