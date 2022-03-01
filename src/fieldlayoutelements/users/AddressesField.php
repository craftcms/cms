<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\users;

use Craft;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;
use yii\base\InvalidArgumentException;

/**
 * AddressesField represents an Addresses field that can be included in the user field layout.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AddressesField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'addresses';

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        unset(
            $config['attribute'],
            $config['mandatory'],
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
            $fields['mandatory'],
            $fields['translatable'],
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Addresses');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof User) {
            throw new InvalidArgumentException('AddressesField can only be used in the user field layout.');
        }

        if (!$element->id) {
            return null;
        }

        return Cp::addressCardsHtml($element->getAddresses(), [
            'ownerId' => $element->id,
        ]);
    }
}
