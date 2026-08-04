<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\User\Elements\User;
use DateTimeInterface;

/**
 * Element activity model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Data\ElementActivity} instead.
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
     * @param DateTimeInterface $timestamp
     */
    public function __construct(
        public User $user,
        public ElementInterface $element,
        public string $type,
        public DateTimeInterface $timestamp,
    ) {
    }
}
