<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use CraftCms\Cms\Component\Concerns\SavableComponent;
use DateTime;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0
     */
    trait SavableComponentTrait
    {
        /**
         * @event ModelEvent The event that is triggered before the component is saved.
         *
         * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the component from getting saved.
         */
        public const EVENT_BEFORE_SAVE = 'beforeSave';

        /**
         * @event ModelEvent The event that is triggered after the component is saved.
         */
        public const EVENT_AFTER_SAVE = 'afterSave';

        /**
         * @event ModelEvent The event that is triggered before the component is deleted.
         *
         * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the component from getting deleted.
         */
        public const EVENT_BEFORE_DELETE = 'beforeDelete';

        /**
         * @event ModelEvent The event that is triggered before the delete is applied to the database.
         */
        public const EVENT_BEFORE_APPLY_DELETE = 'beforeApplyDelete';

        /**
         * @event \yii\base\Event The event that is triggered after the component is deleted.
         */
        public const EVENT_AFTER_DELETE = 'afterDelete';

        /**
         * @var int|string|null The component’s ID (could be a temporary one: "new:X")
         */
        public int|string|null $id = null;

        /**
         * @var DateTime|null The date that the component was created
         */
        public ?DateTime $dateCreated = null;

        /**
         * @var DateTime|null The date that the component was last updated
         */
        public ?DateTime $dateUpdated = null;
    }
}

class_alias(SavableComponent::class, SavableComponentTrait::class);
