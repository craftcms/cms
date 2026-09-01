<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Directives;

use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Money as MoneyHelper;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class Money extends Directive
{
    public const string FORMAT_AMOUNT = 'amount';

    public const string FORMAT_DECIMAL = 'decimal';

    public const string FORMAT_NUMBER = 'number';

    public const string FORMAT_STRING = 'string';

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
                    'defaultValue' => self::FORMAT_STRING,
                    'description' => 'This specifies the format to output. This can be `amount`, `decimal`, `number`, or `string`. It defaults to the `string`.',
                ],
                [
                    'name' => 'locale',
                    'type' => Type::string(),
                    'description' => 'The locale to use when formatting the money value. (e.g. `en_US`). This argument is only valid with `number` and `string` formats.',
                ],
            ],
            'description' => 'Formats a money object to the desired format. It can be applied to any fields, but only changes a Money field.',
        ]));
    }

    public static function name(): string
    {
        return 'money';
    }

    /** @param array<string, mixed> $arguments */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): \Money\Money|string|false
    {
        if (! $value instanceof \Money\Money) {
            return $value;
        }

        $format = match ($arguments['format'] ?? self::FORMAT_STRING) {
            self::FORMAT_AMOUNT => self::FORMAT_AMOUNT,
            self::FORMAT_DECIMAL => self::FORMAT_DECIMAL,
            self::FORMAT_NUMBER => self::FORMAT_NUMBER,
            default => self::FORMAT_STRING,
        };

        $locale = $arguments['locale'] ?? I18N::getFormattingLocale()->id;

        return match ($format) {
            self::FORMAT_AMOUNT => $value->getAmount(),
            self::FORMAT_DECIMAL => MoneyHelper::toDecimal($value),
            self::FORMAT_NUMBER => MoneyHelper::toNumber($value, $locale),
            self::FORMAT_STRING => MoneyHelper::toString($value, $locale),
        };
    }
}
