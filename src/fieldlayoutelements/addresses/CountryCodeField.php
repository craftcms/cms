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
 * Class CountryCodeField.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class CountryCodeField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    public string $attribute = 'countryCode';

    /**
     * @inheritdoc
     */
    public bool $required = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        unset(
            $config['mandatory'],
            $config['translatable'],
            $config['maxlength'],
            $config['required'],
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
            $fields['required'],
            $fields['autofocus']
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Country');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Address) {
            throw new InvalidArgumentException('AddressField can only be used in address field layouts.');
        }

        return
            Html::beginTag('div', [
                'class' => ['flex', 'flex-nowrap'],
            ]) .
            Cp::selectizeHtml([
                'id' => 'countryCode',
                'name' => 'countryCode',
                'options' => Craft::$app->getAddresses()->getCountryRepository()->getList(Craft::$app->language),
                'value' => $element->countryCode,
            ]) .
            Html::tag('div', '', [
                'id' => 'countryCode-spinner',
                'class' => ['spinner', 'hidden'],
            ]) .
            Html::endTag('div');
    }
}
