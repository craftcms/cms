<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use CraftCms\Cms\Element\Element;
use craft\base\ElementInterface;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Support\Facades\Deprecator;
use yii\base\Behavior;

/**
 * BaseRevisionBehavior is the base implementation of draft & revision behaviors.
 *
 * @template T of Element
 * @extends Behavior<T>
 * @property User|null $creator
 * @property-read int $sourceId
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Concerns\Draftable} or {@see \CraftCms\Cms\Element\Concerns\Revisionable} instead.
 */
abstract class BaseRevisionBehavior extends Behavior
{
    /**
     * @var int|null The creator’s ID
     */
    public ?int $creatorId {
        get => match($this::class) {
            /** @phpstan-ignore-next-line */
            DraftBehavior::class => $this->owner->draftCreatorId,
            default => $this->owner->revisionCreatorId,
        };
        set(?int $value) => match($this::class) {
            /** @phpstan-ignore-next-line */
            DraftBehavior::class => $this->owner->draftCreatorId = $value,
            default => $this->owner->revisionCreatorId = $value,
        };
    }

    /**
     * Returns the draft’s creator.
     *
     * @return User|null
     */
    public function getCreator(): ?User
    {
        return match($this::class) {
            /** @phpstan-ignore-next-line */
            DraftBehavior::class => $this->owner->getDraftCreator(),
            default => $this->owner->getRevisionCreator(),
        };
    }

    /**
     * Sets the draft's creator.
     *
     * @param User|null $creator
     * @since 3.5.0
     */
    public function setCreator(?User $creator = null): void
    {
        match($this::class) {
            /** @phpstan-ignore-next-line */
            DraftBehavior::class => $this->owner->setDraftCreator($creator),
            default => $this->owner->setRevisionCreator($creator),
        };
    }

    /**
     * Returns the draft/revision’s source element.
     *
     * @return ElementInterface|null
     * @deprecated in 3.2.9. Use [[ElementInterface::getCanonical()]] instead.
     */
    public function getSource(): ?ElementInterface
    {
        Deprecator::log(__METHOD__, 'Elements’ `getSource()` method has been deprecated. Use `getCanonical()` instead.');
        if ($this->owner->getIsCanonical()) {
            return null;
        }
        return $this->owner->getCanonical();
    }

    /**
     * Returns the draft/revision's source element ID.
     *
     * @return int
     * @since 3.7.0
     * @deprecated in 3.7.0. Use [[ElementInterface::getCanonicalId()]] instead.
     */
    public function getSourceId(): int
    {
        Deprecator::log(__METHOD__, 'Elements’ `getSourceId()` method has been deprecated. Use `getCanonicalId()` instead.');
        return $this->owner->getCanonicalId();
    }
}
