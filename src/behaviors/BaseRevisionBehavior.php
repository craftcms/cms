<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use Craft;
use craft\base\ElementInterface;
use craft\elements\User;
use yii\base\Behavior;

/**
 * BaseRevisionBehavior is the base implementation of draft & revision behaviors.
 *
 * @property ElementInterface $owner
 * @property User|null $creator
 * @property-read int $sourceId
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class BaseRevisionBehavior extends Behavior
{
    /**
     * @var int|null The creator’s ID
     */
    public ?int $creatorId = null;

    /**
     * @var User|null|false The creator
     */
    private User|false|null $_creator = null;

    /**
     * Returns the draft’s creator.
     *
     * @return User|null
     */
    public function getCreator(): ?User
    {
        if (!isset($this->_creator)) {
            if (!$this->creatorId) {
                return null;
            }

            /** @var User|null $creator */
            $creator = User::find()
                ->id($this->creatorId)
                ->status(null)
                ->one();
            $this->_creator = $creator ?? false;
        }

        return $this->_creator ?: null;
    }

    /**
     * Sets the draft's creator.
     *
     * @param User|null $creator
     * @since 3.5.0
     */
    public function setCreator(?User $creator = null): void
    {
        $this->_creator = $creator ?? false;
    }

    /**
     * Returns the draft/revision’s source element.
     *
     * @return ElementInterface|null
     * @deprecated in 3.2.9. Use [[ElementInterface::getCanonical()]] instead.
     */
    public function getSource(): ?ElementInterface
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Elements’ `getSource()` method has been deprecated. Use `getCanonical()` instead.');
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
        Craft::$app->getDeprecator()->log(__METHOD__, 'Elements’ `getSourceId()` method has been deprecated. Use `getCanonicalId()` instead.');
        return $this->owner->getCanonicalId();
    }
}
