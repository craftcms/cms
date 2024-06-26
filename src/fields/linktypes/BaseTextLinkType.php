<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\fields\Link;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;

/**
 * Base text link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
abstract class BaseTextLinkType extends BaseLinkType
{
    /**
     * Returns the prefix(es) that supported URLs must start with.
     *
     * @return string|string[]
     */
    abstract protected static function urlPrefix(): string|array;

    public static function supports(string $value): bool
    {
        $value = mb_strtolower($value);
        foreach ((array)static::urlPrefix() as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }
        return false;
    }

    public static function normalize(string $value): string
    {
        if (static::supports($value)) {
            return $value;
        }

        // Only add a prefix if the end result validates
        $prefix = ArrayHelper::firstValue((array)static::urlPrefix());
        $normalized = "$prefix$value";
        return static::validate($normalized) ? $normalized : $value;
    }

    public static function linkLabel(string $value): string
    {
        foreach ((array)static::urlPrefix() as $prefix) {
            $value = StringHelper::removeLeft($value, $prefix);
        }
        return $value;
    }

    public static function inputHtml(Link $field, ?string $value, string $containerId): string
    {
        $name = 'value';
        $textInputAttributes = array_merge([
            'describedBy' => $field->describedBy,
            'class' => ['fullwidth', 'text-link-input'],
            'textInputAttributes' => [
                'aria' => [
                    'label' => Craft::t('site', $field->name),
                ],
            ],
        ], static::inputAttributes());

        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn($id, $settings) => <<<JS
(() => {
  new Craft.LinkInput('#' + $id, $settings);
})();
JS, [
            $containerId,
            [
                'prefixes' => (array)static::urlPrefix(),
                'pattern' => static::pattern(),
                'inputAttributes' => $textInputAttributes,
            ],
        ]);

        if ($value && static::validate($value)) {
            $linkText = self::linkLabel($value);
            $html =
                Html::beginTag('div', [
                    'class' => ['chip', 'small'],
                ]) .
                Html::beginTag('div', [
                    'class' => 'chip-content',
                ]) .
                Html::a($linkText, $value, [
                    'target' => '_blank',
                ]) .
                Html::endTag('div') . // .chip-content
                Cp::disclosureMenu([], [
                    'omitIfEmpty' => false,
                    'hiddenLabel' => Craft::t('app', 'Actions'),
                    'buttonAttributes' => [
                        'class' => ['action-btn'],
                        'removeClass' => 'menubtn',
                        'data' => ['icon' => 'ellipsis'],
                    ],
                ]) .
                Html::endTag('div'); // .chip;
        } else {
            $html = Cp::textHtml(array_merge($textInputAttributes, [
                'value' => $value,
            ]));
        }

        return $html . Html::hiddenInput($name, $value);
    }

    /**
     * Returns any additional attributes that should be set ot the text input.
     *
     * @return array
     */
    protected static function inputAttributes(): array
    {
        return [];
    }

    public static function validate(string $value, ?string &$error = null): bool
    {
        $pattern = sprintf('/%s/i', static::pattern());
        return (bool)preg_match($pattern, $value);
    }

    /**
     * Returns the regular expression pattern (sans delimiters) that should be used to validate link values.
     *
     * @return string
     */
    protected static function pattern(): string
    {
        $prefixes = array_map(fn(string $prefix) => preg_quote($prefix, '/'), (array)static::urlPrefix());
        return sprintf('^(%s)', implode('|', $prefixes));
    }
}
