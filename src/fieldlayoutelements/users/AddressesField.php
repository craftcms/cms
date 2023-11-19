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
use craft\enums\ElementIndexViewMode;
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
    public const VIEW_MODE_CARDS = 'cards';
    public const VIEW_MODE_INDEX = 'index';

    /**
     * @inheritdoc
     */
    public string $attribute = 'addresses';

    /**
     * @var string The view mode
     * @phpstan-var self::VIEW_MODE_*
     */
    public string $viewMode = self::VIEW_MODE_CARDS;

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
    protected function settingsHtml(): ?string
    {
        return
            parent::settingsHtml() .
            Cp::selectFieldHtml([
                'id' => 'view-mode',
                'label' => Craft::t('app', 'View Mode'),
                'instructions' => Craft::t('app', 'Choose how nested {type} should be presented to authors.', [
                    'type' => Craft::t('app', 'addresses'),
                ]),
                'name' => 'viewMode',
                'options' => [
                    ['label' => Craft::t('app', 'As cards'), 'value' => self::VIEW_MODE_CARDS],
                    ['label' => Craft::t('app', 'As an element index'), 'value' => self::VIEW_MODE_INDEX],
                ],
                'value' => $this->viewMode,
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof User) {
            throw new InvalidArgumentException('AddressesField can only be used in the user field layout.');
        }

        $config = [
            'canCreate' => true,
        ];

        if ($this->viewMode === self::VIEW_MODE_CARDS) {
            return $element->getAddressManager()->getCardsHtml($element, $config);
        }

        $config += [
            'allowedViewModes' => [ElementIndexViewMode::Cards],
        ];

        return $element->getAddressManager()->getIndexHtml($element, $config);
    }
}
