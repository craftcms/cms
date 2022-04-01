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
use craft\helpers\MoneyHelper;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Class Money
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Money extends Directive
{
    public const FORMAT_AMOUNT = 'amount';
    public const FORMAT_DECIMAL = 'decimal';
    public const FORMAT_NUMBER = 'number';
    public const FORMAT_STRING = 'string';

    private const FORMATS = [
        self::FORMAT_AMOUNT,
        self::FORMAT_DECIMAL,
        self::FORMAT_NUMBER,
        self::FORMAT_STRING,
    ];

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
                    'defaultValue' => self::FORMAT_STRING,
                    'description' => 'This specifies the format to output. This can be `amount`, `decimal`, `number`, or `string`. It defaults to the `string`.',
                ]),
                new FieldArgument([
                    'name' => 'locale',
                    'type' => Type::string(),
                    'description' => 'The locale to use when formatting the money value. (e.g. `en_US`). This argument is only valid with `number` and `string` formats.',
                ]),
            ],
            'description' => 'Formats a money object to the desired format. It can be applied to any fields, but only changes a Money field.',
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function name(): string
    {
        return 'money';
    }

    /**
     * @inheritdoc
     */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        if ($value instanceof \Money\Money) {
            /** @var \Money\Money $value */
            $format = (isset($arguments['format']) && in_array($arguments['format'], self::FORMATS, true)) ? $arguments['format'] : self::FORMAT_STRING;

            $locale = $arguments['locale'] ?? Craft::$app->getFormattingLocale()->id;

            switch ($format) {
                case self::FORMAT_AMOUNT:
                    return $value->getAmount();
                case self::FORMAT_DECIMAL:
                    return MoneyHelper::toDecimal($value);
                case self::FORMAT_NUMBER:
                    return MoneyHelper::toNumber($value, $locale);
                case self::FORMAT_STRING:
                    return MoneyHelper::toString($value, $locale);
            }
        }

        return $value;
    }

    /**
     * Returns the default time zone to be used.
     *
     * @return string
     */
    public static function defaultTimeZone(): string
    {
        return Craft::$app->getConfig()->getGeneral()->setGraphqlDatesToSystemTimeZone ? Craft::$app->getTimeZone() : FormatDateTime::DEFAULT_TIMEZONE;
    }
}
