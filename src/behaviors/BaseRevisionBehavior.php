<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use craft\base\ElementInterface;
use craft\elements\User;
use craft\helpers\ElementHelper;
use yii\base\Behavior;

/**
 * BaseRevisionBehavior is the base implementation of draft & revision behaviors.
 *
 * @property ElementInterface $owner
 * @property User|null $creator
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class BaseRevisionBehavior extends Behavior
{
    /**
     * @var int|null The source element’s ID
     */
    public $sourceId;

    /**
     * @var int|null The creator’s ID
     */
    public $creatorId;

    /**
     * @var ElementInterface|null
     * @see source()
     */
    private $_source;

    /**
     * @var User|null|false The creator
     */
    private $_creator;

    /**
     * Returns the draft’s creator.
     *
     * @return User|null
     */
    public function getCreator()
    {
        if ($this->_creator === null) {
            if (!$this->creatorId) {
                return null;
            }

            $this->_creator = User::find()
                    ->id($this->creatorId)
                    ->anyStatus()
                    ->one()
                ?? false;
        }

        return $this->_creator ?: null;
    }

    /**
     * Sets the draft's creator.
     *
     * @param User|null $creator
     * @since 3.5.0
     */
    public function setCreator(User $creator = null)
    {
        $this->_creator = $creator ?? false;
    }

    /**
     * Returns the source element.
     *
     * @return ElementInterface|null
     * @since 3.5.0
     */
    protected function source()
    {
        if (!$this->sourceId) {
            return null;
        }

        if ($this->_source !== null) {
            return $this->_source;
        }

        return $this->_source = ElementHelper::sourceElement($this->owner);
    }

    /**
     * Returns the draft/revision’s source element.
     *
     * @return ElementInterface|null
     * @deprecated in 3.2.9. Use [[ElementHelper::sourceElement()]] instead.
     */
    public function getSource()
    {
        return $this->source();
    }
}
