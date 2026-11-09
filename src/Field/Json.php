<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Data\JsonData;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json as JsonHelper;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Textarea;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

/**
 * Icon represents an icon picker field.
 *
 * @since 6.0.0
 */
class Json extends Field implements MergeableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return 'JSON';
    }

    #[Override]
    public static function icon(): string
    {
        return 'brackets-curly';
    }

    #[Override]
    public static function phpType(): string
    {
        return 'array|null';
    }

    #[Override]
    public static function dbType(): string
    {
        return Query::TYPE_JSON;
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): ?JsonData
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof JsonData) {
            return $value;
        }

        return new JsonData($value);
    }

    #[Override]
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): ?JsonData
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            try {
                $value = JsonHelper::decode($value);
            } catch (InvalidArgumentException) {
                return JsonData::fromInvalidInput($value);
            }
        }

        return new JsonData($value);
    }

    #[Override]
    public function uiControl(FieldContext $context): Control
    {
        $value = $context->value instanceof JsonData
            ? $context->value->getJson(true)
            : $context->value;

        return Textarea::make($context->path)
            ->rows(12)
            ->monospace()
            // Only JSON is pasted in, as copied from another JSON field.
            ->valueScope('json')
            ->value($value);
    }

    /** @return list<Closure> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            function ($attribute, ?JsonData $value, Closure $fail) {
                if (is_null($value)) {
                    return;
                }

                if ($value->getInvalidInput() !== null) {
                    $fail(t('{attribute} must be valid JSON.', [
                        'attribute' => $this->getUiLabel(),
                    ]));
                }
            },
        ];
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($value === null) {
            return '';
        }

        /** @var JsonData $value */
        return Html::tag('code', $value->getJson());
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        return Html::tag('code', '{foo:"bar"}');
    }
}
