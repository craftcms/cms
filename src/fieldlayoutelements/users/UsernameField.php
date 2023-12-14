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
use craft\fieldlayoutelements\TextField;
use yii\base\InvalidArgumentException;

/**
 * UsernameField represents a Username field that can be included in the user field layout.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class UsernameField extends TextField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    public string $attribute = 'username';

    /**
     * @inheritdoc
     */
    public ?int $maxlength = 255;

    /**
     * @inheritdoc
     */
    public bool $autofocus = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        unset(
            $config['mandatory'],
            $config['attribute'],
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
            $fields['attribute'],
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
        return Craft::t('app', 'Username');
    }

    /**
     * @inheritdoc
     */
    protected function inputAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        if (!$element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', __CLASS__));
        }

        return [
            'autocomplete' => $element->getIsCurrent() ? 'username' : 'off',
        ];
    }
}
