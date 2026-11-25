<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\services\Structures;
use yii\base\InvalidConfigException;

/**
 * Move element event class.
 *
 * @property-read ?ElementInterface $targetElement
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MoveElementEvent extends ElementEvent
{
    /**
     * @var int The ID of the structure the element is being moved within.
     */
    public int $structureId;

    /**
     * @var int|null The ID of the element that [[element]] is being moved in reference to, or `null` if
     * [[element]] is being appended/prepended to the root of the structure.
     * @since 4.5.0
     */
    public ?int $targetElementId = null;

    /**
     * @var string The type of structure action being performed (one of [[Structures::ACTION_PREPEND]],
     * [[Structures::ACTION_APPEND|ACTION_APPEND]], [[Structures::ACTION_PLACE_BEFORE|ACTION_PLACE_BEFORE]],
     * or [[Structures::ACTION_PLACE_AFTER|ACTION_PLACE_AFTER]]).
     * @phpstan-var Structures::ACTION_*
     * @since 4.5.0
     */
    public string $action;

    private ElementInterface $_targetElement;

    /**
     * Returns the element that [[element]] is being moved in reference to, or `null` if [[element]] is being
     * appended/prepended to the root of the structure.
     *
     * @return ElementInterface|null
     * @since 4.5.0
     */
    public function getTargetElement(): ?ElementInterface
    {
        if (!isset($this->targetElementId)) {
            return null;
        }

        if (!isset($this->_targetElement)) {
            $targetElement = $this->element::find()
                ->id($this->targetElementId)
                ->site('*')
                ->preferSites([$this->element->siteId])
                ->status(null)
                ->drafts(null)
                ->provisionalDrafts(null)
                ->revisions(null)
                ->structureId($this->structureId)
                ->one();

            if (!$targetElement) {
                throw new InvalidConfigException("Invalid target element ID: $this->targetElementId");
            }

            $this->_targetElement = $targetElement;
        }

        return $this->_targetElement;
    }
}
