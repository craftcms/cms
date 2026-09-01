<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Assets;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\FieldLayout\LayoutElements\TextareaField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class AltField extends TextareaField
{
    #[Override]
    public string $attribute = 'alt';

    #[Override]
    public bool $requirable = true;

    public function __construct($config = [])
    {
        parent::__construct(Arr::except($config, [
            'attribute',
            'autofocus',
            'mandatory',
            'maxlength',
            'requirable',
            'translatable',
        ]));
    }

    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), [
            'autofocus',
            'mandatory',
            'maxlength',
            'translatable',
        ]);
    }

    #[Override]
    public function previewable(): bool
    {
        return true;
    }

    #[Override]
    public function previewHtml(ElementInterface $element): string
    {
        return Html::tag('div', parent::previewHtml($element), [
            'aria' => [
                'hidden' => true,
            ],
        ]);
    }

    /**
     * @return array{class: list<string>, id: string, describedBy: string|null, rows: int|null, cols: int|null, name: string, value: mixed, maxlength: int|null, autofocus: bool, disabled: bool, readonly: bool, required: bool, title: string|null, placeholder: string|null}
     */
    #[Override]
    protected function inputTemplateVariables(?ElementInterface $element, bool $static): array
    {
        return Arr::merge(parent::inputTemplateVariables($element, $static), [
            'class' => ['nicetext'],
        ]);
    }

    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Alternative Text');
    }

    #[Override]
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        if (! $element instanceof Asset) {
            throw new InvalidArgumentException(sprintf('%s can only be used in asset field layouts.', self::class));
        }

        return $element->getVolume()->altTranslationMethod !== TranslationMethod::None;
    }

    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $element instanceof Asset) {
            throw new InvalidArgumentException(sprintf('%s can only be used in asset field layouts.', self::class));
        }

        return $element->getVolume()->altTranslationMethod->description();
    }

    #[Override]
    public function isCrossSiteCopyable(ElementInterface $element): bool
    {
        return true;
    }
}
