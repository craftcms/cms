<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\addresses;

use CommerceGuys\Addressing\Country\Country;
use craft\base\ElementInterface;
use craft\elements\Address;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;
use CraftCms\Cms\Addresses\Addresses;
use CraftCms\Cms\Support\Html;
use yii\base\InvalidArgumentException;
use function CraftCms\Cms\t;

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
    public function previewable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Country');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Address) {
            throw new InvalidArgumentException(sprintf('%s can only be used in address field layouts.', self::class));
        }

        return
            Html::beginTag('div', [
                'class' => ['flex', 'flex-nowrap'],
            ]) .
            Cp::selectizeHtml([
                'id' => 'countryCode',
                'name' => 'countryCode',
                'options' => app(Addresses::class)->getCountryList(app()->getLocale()),
                'value' => $element->countryCode,
                'autocomplete' => $element->getBelongsToCurrentUser() ? 'country' : 'off',
                'disabled' => $static,
            ]) .
            Html::tag('div', '', [
                'id' => 'countryCode-spinner',
                'class' => ['spinner', 'hidden'],
            ]) .
            Html::endTag('div');
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            $countries = app(Addresses::class)->getCountryRepository()->getList(app()->getLocale());
            $value = $countries['US'];
        } else {
            if ($value instanceof Country) {
                $value = $value->getName();
            }
        }

        return $value;
    }
}
