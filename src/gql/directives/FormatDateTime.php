<?php
namespace craft\gql\directives;

use craft\gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
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
                    'defaultValue' => self::DEFAULT_FORMAT
                ]),
                new FieldArgument([
                    'name' => 'timezone',
                    'type' => Type::string(),
                    'description' => 'The full name of the timezone, defaults to UTC. (E.g., America/New_York)',
                    'defaultValue' => self::DEFAULT_TIMEZONE
                ])
            ],
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
    public static function applyDirective($source, $value, array $arguments)
    {
        if ($value) {
            /** @var \DateTime $value */
            $format = $arguments['format'] ?? self::DEFAULT_FORMAT;
            $timezone = new \DateTimeZone($arguments['timezone'] ?? self::DEFAULT_TIMEZONE);

            return $value->setTimezone($timezone)->format($format);
        }
    }


}
