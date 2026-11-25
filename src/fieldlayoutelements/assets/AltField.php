<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\assets;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Asset;
use craft\fieldlayoutelements\TextareaField;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use yii\base\InvalidArgumentException;

/**
 * AltField represents an Alternative Text field that can be included within a volume’s field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AltField extends TextareaField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'alt';

    /**
     * @inheritdoc
     */
    public bool $requirable = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        unset(
            $config['attribute'],
            $config['autofocus'],
            $config['mandatory'],
            $config['maxlength'],
            $config['requirable'],
            $config['translatable'],
        );

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        unset(
            $fields['autofocus'],
            $fields['mandatory'],
            $fields['maxlength'],
            $fields['translatable'],
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function previewable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function previewHtml(ElementInterface $element): string
    {
        return Html::tag('div', parent::previewHtml($element), [
            'aria' => [
                'hidden' => true,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputTemplateVariables(?ElementInterface $element, bool $static): array
    {
        return ArrayHelper::merge(parent::inputTemplateVariables($element, $static), [
            'class' => ['nicetext'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Alternative Text');
    }

    /**
     * @inheritdoc
     */
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        if (!$element instanceof Asset) {
            throw new InvalidArgumentException(sprintf('%s can only be used in asset field layouts.', self::class));
        }

        return $element->getVolume()->altTranslationMethod !== Field::TRANSLATION_METHOD_NONE;
    }

    /**
     * @inheritdoc
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Asset) {
            throw new InvalidArgumentException(sprintf('%s can only be used in asset field layouts.', self::class));
        }

        return ElementHelper::translationDescription($element->getVolume()->altTranslationMethod);
    }

    /**
     * @inheritdoc
     */
    public function isCrossSiteCopyable(ElementInterface $element): bool
    {
        return true;
    }
}
