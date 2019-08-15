<?php
namespace craft\gql\directives;

use craft\gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Class FormatDate
 */
class FormatDateTime extends BaseDirective
{
    const DEFAULT_FORMAT = 'Y-m-d\TH:i:sP';
    const DEFAULT_TIMEZONE = 'UTC';

    /**
     * @inheritdoc
     */
    public static function getDirective(): Directive
    {
        if ($type = GqlEntityRegistry::getEntity(self::class)) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(static::getName(), new self([
            'name' => static::getName(),
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
    public static function getName(): string
    {
        return 'formatDateTime';
    }

    /**
     * @inheritdoc
     */
    public static function applyDirective($source, $value, array $arguments, ResolveInfo $resolveInfo)
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
