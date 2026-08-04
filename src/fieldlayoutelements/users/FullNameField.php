<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\users;

use craft\base\ElementInterface;
use craft\elements\User;
use craft\fieldlayoutelements\FullNameField as BaseFullNameField;
use yii\base\InvalidArgumentException;

/**
 * FullNameField represents a Full Name field that can be included in the user field layout.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class FullNameField extends BaseFullNameField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    protected function inputAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        if (!$element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        return [
            'autocomplete' => $element->getIsCurrent() ? 'name' : 'off',
        ];
    }
}
