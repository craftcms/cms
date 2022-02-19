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
use craft\fieldlayoutelements\BaseField;
use yii\base\InvalidArgumentException;

/**
 * AddressField represents an Address field that can be included within an Address field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AddressField extends BaseField
{
    /**
     * @inheritdoc
     */
    public function attribute(): string
    {
        return 'address';
    }

    /**
     * @inheritdoc
     */
    public function mandatory(): bool
    {
        return true;
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
     * @inerhitdoc
     */
    public function label(): ?string
    {
        return Craft::t('app', 'Address');
    }

    /**
     * @inheritdoc
     */
    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Address');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Address) {
            throw new InvalidArgumentException('AddressField can only be used in address field layouts.');
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/address-standard', [
            'address' => $element,
            'static' => $static,
        ]);
    }
}
