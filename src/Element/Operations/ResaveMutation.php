<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Operations;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\Contracts\DefaultableFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Support\Str;

use function CraftCms\Cms\renderObjectTemplate;

/** @internal */
class ResaveMutation
{
    /** @param FieldInterface[] $withFields */
    public static function apply(
        ElementInterface $element,
        ?string $set,
        ?string $to,
        bool $toDefault,
        array $withFields,
        bool $ifEmpty,
        bool $ifInvalid,
    ): void {
        if ($toDefault) {
            $layout = $element->getFieldLayout();
            $fields = $set
                ? [$layout?->getFieldByHandle($set)]
                : array_map(fn (FieldInterface $field) => $layout?->getFieldByUid($field->uid), $withFields);

            foreach ($fields as $field) {
                if ($field instanceof DefaultableFieldInterface && self::shouldSet($element, $field->handle, $ifEmpty, $ifInvalid, true)) {
                    $element->setFieldValue($field->handle, $field->getDefaultValue());
                }
            }
        } elseif ($set !== null && self::shouldSet($element, $set, $ifEmpty, $ifInvalid)) {
            $element->{$set} = self::normalizeTo($to)($element);
        }
    }

    private static function shouldSet(
        ElementInterface $element,
        string $attribute,
        bool $ifEmpty,
        bool $ifInvalid,
        bool $fieldOnly = false,
    ): bool {
        if ($ifEmpty) {
            return ElementHelper::isAttributeEmpty($element, $attribute);
        }

        if ($ifInvalid) {
            $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);

            return ! (($fieldOnly || $element->validate($attribute)) && $element->validate("field:$attribute"));
        }

        return true;
    }

    /** @return callable(ElementInterface): mixed */
    public static function normalizeTo(?string $to): callable
    {
        if ($to === ':empty:') {
            return fn () => '';
        }

        if (str_starts_with((string) $to, '=')) {
            $template = substr((string) $to, 1);

            return fn (ElementInterface $element) => renderObjectTemplate($template, $element);
        }

        if (preg_match('/^fn\s*\(\s*(?:\$(\w+)\s*)?\)\s*=>\s*(.+)/', (string) $to, $match)) {
            $var = $match[1];
            $php = sprintf('return %s;', Str::chopStart(rtrim($match[2], ';'), 'return '));

            return function (ElementInterface $element) use ($var, $php) {
                if ($var) {
                    ${$var} = $element;
                }

                return eval($php);
            };
        }

        return static fn (ElementInterface $element) => $element->$to;
    }
}
