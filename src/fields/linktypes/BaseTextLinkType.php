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
    abstract protected function urlPrefix(): string|array;

    public function supports(string $value): bool
    {
        $value = mb_strtolower($value);
        foreach ((array)$this->urlPrefix() as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }
        return false;
    }

    public function normalizeValue(string $value): string
    {
        if (str_contains($value, ':') || $this->supports($value)) {
            return $value;
        }

        // Only add a prefix if the end result validates
        $prefix = ArrayHelper::firstValue((array)$this->urlPrefix());
        $normalized = "$prefix$value";
        return $this->validateValue($normalized) ? $normalized : $value;
    }

    public function linkLabel(string $value): string
    {
        foreach ((array)$this->urlPrefix() as $prefix) {
            $value = StringHelper::removeLeft($value, $prefix);
        }
        if (preg_match('/^[^\/]+\/$/', $value)) {
            $value = rtrim($value, '/');
        }
        return $value;
    }

    public function inputHtml(Link $field, ?string $value, string $containerId): string
    {
        $name = 'value';
        $textInputAttributes = array_merge([
            'describedBy' => $field->describedBy,
            'class' => ['fullwidth', 'text-link-input'],
            'inputAttributes' => [
                'aria' => [
                    'label' => Craft::t('site', $field->name),
                ],
            ],
        ], $this->inputAttributes());

        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn($id, $settings) => <<<JS
(() => {
  new Craft.LinkInput('#' + $id, $settings);
})();
JS, [
            $containerId,
            [
                'prefixes' => (array)$this->urlPrefix(),
                'pattern' => $this->pattern(),
                'inputAttributes' => $textInputAttributes,
            ],
        ]);

        if ($value && $this->validateValue($value)) {
            $linkText = $this->linkLabel($value);
            $html =
                Html::beginTag('div', [
                    'class' => ['chip', 'chromeless'],
                ]) .
                Html::beginTag('div', [
                    'class' => 'chip-content',
                ]) .
                Html::a($linkText, str_replace(' ', '+', $value), [
                    'class' => ['truncate'],
                    'target' => '_blank',
                ]) .
                Html::beginTag('div', [
                    'class' => 'chip-actions',
                ]) .
                Cp::disclosureMenu([], [
                    'omitIfEmpty' => false,
                    'hiddenLabel' => Craft::t('app', 'Actions'),
                    'buttonAttributes' => [
                        'class' => ['action-btn'],
                        'removeClass' => 'menubtn',
                        'data' => ['icon' => 'ellipsis'],
                    ],
                ]) .
                Html::endTag('div') . // .chip-actions
                Html::endTag('div') . // .chip-content
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
    protected function inputAttributes(): array
    {
        return [];
    }

    public function validateValue(string $value, ?string &$error = null): bool
    {
        $pattern = sprintf('/%s/i', $this->pattern());
        return (bool)preg_match($pattern, $value);
    }

    /**
     * Returns the regular expression pattern (sans delimiters) that should be used to validate link values.
     *
     * @return string
     */
    protected function pattern(): string
    {
        $prefixes = array_map(fn(string $prefix) => preg_quote($prefix, '/'), (array)$this->urlPrefix());
        return sprintf('^(%s)', implode('|', $prefixes));
    }
}
