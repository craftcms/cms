<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\ElementInterface;
use craft\elements\User;
use DateTime;

/**
 * Element activity model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class ElementActivity
{
    public const TYPE_VIEW = 'view';
    public const TYPE_EDIT = 'edit';
    public const TYPE_SAVE = 'save';

    /**
     * @param User $user
     * @param ElementInterface $element
     * @param self::TYPE_* $type
     * @param DateTime $timestamp
     */
    public function __construct(
        public User $user,
        public ElementInterface $element,
        public string $type,
        public DateTime $timestamp,
    ) {
    }
}
