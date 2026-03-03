<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use craft\helpers\Cp;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;

use function CraftCms\Cms\t;

/**
 * Base text link type.
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

        return array_any((array) $this->urlPrefix(), fn ($prefix) => str_starts_with($value, (string) $prefix));
    }

    #[\Override]
    public function normalizeValue(string $value): string
    {
        if (str_contains($value, ':') || $this->supports($value)) {
            return $value;
        }

        // Only add a prefix if the end result validates
        $prefix = Arr::first((array) $this->urlPrefix());
        $normalized = "$prefix$value";

        return $this->validateValue($normalized) ? $normalized : $value;
    }

    public function linkLabel(string $value): string
    {
        foreach ((array) $this->urlPrefix() as $prefix) {
            $value = Str::chopStart($value, $prefix);
        }
        if (preg_match('/^[^\/]+\/$/', $value)) {
            return rtrim($value, '/');
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
                    'label' => t($field->name, category: 'site'),
                ],
            ],
        ], $this->inputAttributes());

        HtmlStack::jsWithVars(fn ($id, $settings) => <<<JS
(() => {
  new Craft.LinkInput('#' + $id, $settings)
})();
JS, [
            $containerId,
            [
                'prefixes' => (array) $this->urlPrefix(),
                'pattern' => $this->pattern(),
                'inputAttributes' => $textInputAttributes,
            ],
        ]);

        if ($value && $this->validateValue($value)) {
            $linkText = $this->linkLabel($value);
            $html =
                Html::beginTag('div', [
                    'class' => ['chip', 'chromeless'],
                ]).
                Html::beginTag('div', [
                    'class' => 'chip-content',
                ]).
                Html::a($linkText, str_replace(' ', '+', $value), [
                    'class' => ['truncate'],
                    'target' => '_blank',
                ]).
                Html::beginTag('div', [
                    'class' => 'chip-actions',
                ]).
                Cp::disclosureMenu([], [
                    'omitIfEmpty' => false,
                    'hiddenLabel' => t('Actions'),
                    'buttonAttributes' => [
                        'class' => ['action-btn'],
                        'removeClass' => 'menubtn',
                        'data' => ['icon' => 'ellipsis'],
                    ],
                ]).
                Html::endTag('div'). // .chip-actions
                Html::endTag('div'). // .chip-content
                Html::endTag('div'); // .chip;
        } else {
            $html = Cp::textHtml(array_merge($textInputAttributes, [
                'value' => $value,
            ]));
        }

        return $html.Html::hiddenInput($name, $value);
    }

    /**
     * Returns any additional attributes that should be set ot the text input.
     */
    protected function inputAttributes(): array
    {
        return [];
    }

    public function validateValue(string $value, ?string &$error = null): bool
    {
        $pattern = sprintf('/%s/i', $this->pattern());

        return (bool) preg_match($pattern, $value);
    }

    /**
     * Returns the regular expression pattern (sans delimiters) that should be used to validate link values.
     */
    protected function pattern(): string
    {
        $prefixes = array_map(fn (string $prefix) => preg_quote($prefix, '/'), (array) $this->urlPrefix());

        return sprintf('^(%s)', implode('|', $prefixes));
    }
}
