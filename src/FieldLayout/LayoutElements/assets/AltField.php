<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\assets;

use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\TextareaField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class AltField extends TextareaField
{
    /**
     * {@inheritdoc}
     */
    public string $attribute = 'alt';

    /**
     * {@inheritdoc}
     */
    public bool $requirable = true;

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function previewable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    #[Override]
    protected function inputTemplateVariables(?ElementInterface $element, bool $static): array
    {
        return Arr::merge(parent::inputTemplateVariables($element, $static), [
            'class' => ['nicetext'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Alternative Text');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        if (! $element instanceof Asset) {
            throw new InvalidArgumentException(sprintf('%s can only be used in asset field layouts.', self::class));
        }

        return $element->getVolume()->altTranslationMethod !== Field::TRANSLATION_METHOD_NONE;
    }

    /**
     * {@inheritdoc}
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $element instanceof Asset) {
            throw new InvalidArgumentException(sprintf('%s can only be used in asset field layouts.', self::class));
        }

        return ElementHelper::translationDescription($element->getVolume()->altTranslationMethod);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function isCrossSiteCopyable(ElementInterface $element): bool
    {
        return true;
    }
}
