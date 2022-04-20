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
    protected function showLabel(): bool
    {
        return false;
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
            throw new InvalidArgumentException('LatLongField can only be used in address field layouts.');
        }

        return
            Html::beginTag('div', ['class' => 'flex-fields']) .
            Cp::textFieldHtml([
                'fieldClass' => 'width-50',
                'label' => Craft::t('app', 'Latitude'),
                'id' => 'latitude',
                'name' => 'latitude',
                'value' => $element->latitude,
                'required' => $this->required,
            ]) .
            Cp::textFieldHtml([
                'fieldClass' => 'width-50',
                'label' => Craft::t('app', 'Longitude'),
                'id' => 'longitude',
                'name' => 'longitude',
                'value' => $element->longitude,
                'required' => $this->required,
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
}
