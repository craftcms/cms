<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\addresses;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Address;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;
use craft\helpers\Html;
use yii\base\InvalidArgumentException;

/**
 * Class LatLongField.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class LatLongField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'latLong';

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
            $config['mandatory'],
            $config['translatable'],
            $config['maxlength'],
            $config['autofocus']
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
            $fields['mandatory'],
            $fields['translatable'],
            $fields['maxlength'],
            $fields['autofocus']
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function hasCustomWidth(): bool
    {
        return false;
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
        /** @var Address $element */
        return sprintf('%s, %s', $element->longitude ?? '0', $element->latitude ?? '0');
    }

    /**
     * @inheritdoc
     */
    protected function showLabel(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        // we need it for the card view designer
        return Craft::t('app', 'Latitude/Longitude');
    }

    /**
     * @inheritdoc
     */
    protected function selectorLabel(): ?string
    {
        return Craft::t('app', 'Latitude/Longitude');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Address) {
            throw new InvalidArgumentException(sprintf('%s can only be used in address field layouts.', self::class));
        }

        $isAdmin = Craft::$app->getUser()->getIsAdmin();

        return
            Html::beginTag('div', ['class' => 'flex-fields']) .
            Cp::textFieldHtml([
                'fieldClass' => 'width-50',
                'label' => Craft::t('app', 'Latitude'),
                'id' => 'latitude',
                'name' => 'latitude',
                'value' => $element->latitude,
                'required' => $this->required,
                'data' => [
                    'error-key' => 'latitude',
                ],
                'actionMenuItems' => array_filter([
                    $isAdmin ? $this->copyAttributeAction(['attribute' => 'latitude']) : null,
                ]),
            ]) .
            Cp::textFieldHtml([
                'fieldClass' => 'width-50',
                'label' => Craft::t('app', 'Longitude'),
                'id' => 'longitude',
                'name' => 'longitude',
                'value' => $element->longitude,
                'required' => $this->required,
                'data' => [
                    'error-key' => 'longitude',
                ],
                'actionMenuItems' => array_filter([
                    $isAdmin ? $this->copyAttributeAction(['attribute' => 'longitude']) : null,
                ]),
            ]) .
            Html::endTag('div');
    }

    /**
     * @inheritdoc
     */
    protected function errors(?ElementInterface $element = null): array
    {
        if (!$element) {
            return [];
        }
        return array_merge($element->getErrors('latitude'), $element->getErrors('longitude'));
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if ($element) {
            return $this->previewHtml($element);
        }

        return '61.108, -149.779';
    }
}
