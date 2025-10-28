<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\errors\StructureNotFoundException;
use craft\events\MoveElementEvent;
use craft\models\Structure;
use CraftCms\Cms\Structure\Data\Structure as StructureData;
use CraftCms\Cms\Structure\Enums\Action;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Events\ElementInserted;
use CraftCms\Cms\Structure\Events\ElementUpdated;
use CraftCms\Cms\Structure\Events\InsertingElement;
use CraftCms\Cms\Structure\Events\UpdateElementEvent;
use CraftCms\Cms\Structure\Events\UpdatingElement;
use CraftCms\Cms\Support\Facades\Structures as StructuresFacade;
use Illuminate\Support\Facades\Event;
use yii\base\Component;

/**
 * Structures service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getStructures()|`Craft::$app->getStructures()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Structure\Structures} instead.
 */
class Structures extends Component
{
    /**
     * @event MoveElementEvent The event that is triggered before an element is inserted into a structure.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the
     * element from getting inserted.
     *
     * @since 4.5.0
     */
    public const EVENT_BEFORE_INSERT_ELEMENT = 'beforeInsertElement';

    /**
     * @event MoveElementEvent The event that is triggered after an element is inserted into a structure.
     * @since 4.5.0
     */
    public const EVENT_AFTER_INSERT_ELEMENT = 'afterInsertElement';

    /**
     * @event MoveElementEvent The event that is triggered before an element’s position is updated.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting repositioned.
     *
     * @since 5.9.0
     */
    public const EVENT_BEFORE_UPDATE_ELEMENT = 'beforeUpdateElement';

    /**
     * @event MoveElementEvent The event that is triggered after an element’s position is updated.
     * @since 5.9.0
     */
    public const EVENT_AFTER_UPDATE_ELEMENT = 'afterUpdateElement';

    /**
     * @event MoveElementEvent The event that is triggered before an element is moved.
     *
     * In Craft 4.5 and later, you may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the
     * element from getting moved.
     *
     * @deprecated in 5.9.0. [[EVENT_BEFORE_UPDATE_ELEMENT]] should be used instead.
     */
    public const EVENT_BEFORE_MOVE_ELEMENT = 'beforeMoveElement';

    /**
     * @event MoveElementEvent The event that is triggered after an element is moved.
     *
     * @deprecated in 5.9.0. [[EVENT_AFTER_UPDATE_ELEMENT]] should be used instead.
     */
    public const EVENT_AFTER_MOVE_ELEMENT = 'afterMoveElement';

    /** @since 3.4.21 */
    public const MODE_INSERT = Mode::Insert->value;
    /** @since 3.4.21 */
    public const MODE_UPDATE = Mode::Update->value;
    /** @since 3.4.21 */
    public const MODE_AUTO = Mode::Auto->value;

    /** @since 4.5.0 */
    public const ACTION_PREPEND = Action::Prepend->value;
    /** @since 4.5.0 */
    public const ACTION_APPEND = Action::Append->value;
    /** @since 4.5.0 */
    public const ACTION_PLACE_BEFORE = Action::PlaceBefore->value;
    /** @since 4.5.0 */
    public const ACTION_PLACE_AFTER = Action::PlaceAfter->value;

    /**
     * @var int The timeout to pass to [[\yii\mutex\Mutex::acquire()]] when acquiring a lock on the structure.
     * @since 3.0.19
     */
    public int $mutexTimeout = 3;

    // Structure CRUD
    // -------------------------------------------------------------------------

    /**
     * Returns a structure by its ID.
     *
     * @param int $structureId
     * @param bool $withTrashed
     *
     * @return Structure|null
     */
    public function getStructureById(int $structureId, bool $withTrashed = false): ?Structure
    {
        $structure = StructuresFacade::getStructureById($structureId, $withTrashed);

        if (!$structure) {
            return null;
        }

        return new Structure([
            'id' => $structure->id,
            'maxLevels' => $structure->maxLevels,
            'uid' => $structure->uid,
        ]);
    }

    /**
     * Returns a structure by its UID.
     *
     * @param string $structureUid
     * @param bool $withTrashed
     *
     * @return Structure|null
     */
    public function getStructureByUid(string $structureUid, bool $withTrashed = false): ?Structure
    {
        $structure = StructuresFacade::getStructureByUid($structureUid, $withTrashed);

        if (!$structure) {
            return null;
        }

        return new Structure([
            'id' => $structure->id,
            'maxLevels' => $structure->maxLevels,
            'uid' => $structure->uid,
        ]);
    }

    /**
     * Patches an array of entries, filling in any gaps in the tree.
     *
     * @template T of ElementInterface
     * @param T[] $elements
     *
     * @since 3.6.0
     */
    public function fillGapsInElements(array &$elements): void
    {
        StructuresFacade::fillGapsInElements($elements);
    }

    /**
     * Filters an array of elements down to only <= X branches.
     *
     * @template T of ElementInterface
     * @param T[] $elements
     * @param int $branchLimit
     *
     * @since 3.6.0
     */
    public function applyBranchLimitToElements(array &$elements, int $branchLimit): void
    {
        StructuresFacade::applyBranchLimitToElements($elements, $branchLimit);
    }

    /**
     * Saves a structure
     *
     * @param Structure $structure
     *
     * @return bool Whether the structure was saved successfully
     * @throws StructureNotFoundException if $structure->id is invalid
     */
    public function saveStructure(Structure $structure): bool
    {
        return StructuresFacade::saveStructure(StructureData::from($structure->toArray()));
    }

    /**
     * Deletes a structure by its ID.
     *
     * @param int $structureId
     *
     * @return bool
     */
    public function deleteStructureById(int $structureId): bool
    {
        return StructuresFacade::deleteStructureById($structureId);
    }

    /**
     * Returns the descendant level delta for a given element.
     *
     * @param int $structureId
     * @param ElementInterface $element
     *
     * @return int
     */
    public function getElementLevelDelta(int $structureId, ElementInterface $element): int
    {
        return StructuresFacade::getElementLevelDelta($structureId, $element);
    }

    // Moving elements around
    // -------------------------------------------------------------------------

    /**
     * Prepends an element to another within a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param int|ElementInterface $parentElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     *
     * @return bool
     */
    public function prepend(
        int $structureId,
        ElementInterface $element,
        ElementInterface|int $parentElement,
        string $mode = self::MODE_AUTO,
    ): bool {
        return StructuresFacade::prepend($structureId, $element, $parentElement, Mode::from($mode));
    }

    /**
     * Appends an element to another within a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param int|ElementInterface $parentElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     *
     * @return bool
     */
    public function append(
        int $structureId,
        ElementInterface $element,
        ElementInterface|int $parentElement,
        string $mode = self::MODE_AUTO,
    ): bool {
        return StructuresFacade::append($structureId, $element, $parentElement, Mode::from($mode));
    }

    /**
     * Prepends an element to the root of a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param string $mode Whether this is an "insert", "update", or "auto".
     *
     * @return bool
     */
    public function prependToRoot(int $structureId, ElementInterface $element, string $mode = self::MODE_AUTO): bool
    {
        return StructuresFacade::prependToRoot($structureId, $element, Mode::from($mode));
    }

    /**
     * Appends an element to the root of a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param string $mode Whether this is an "insert", "update", or "auto".
     *
     * @return bool
     */
    public function appendToRoot(int $structureId, ElementInterface $element, string $mode = self::MODE_AUTO): bool
    {
        return StructuresFacade::appendToRoot($structureId, $element, Mode::from($mode));
    }

    /**
     * Moves an element before another within a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param int|ElementInterface $nextElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     *
     * @return bool
     */
    public function moveBefore(
        int $structureId,
        ElementInterface $element,
        ElementInterface|int $nextElement,
        string $mode = self::MODE_AUTO,
    ): bool {
        return StructuresFacade::moveBefore($structureId, $element, $nextElement, Mode::from($mode));
    }

    /**
     * Moves an element after another within a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param int|ElementInterface $prevElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     *
     * @return bool
     */
    public function moveAfter(
        int $structureId,
        ElementInterface $element,
        ElementInterface|int $prevElement,
        string $mode = self::MODE_AUTO,
    ): bool {
        return StructuresFacade::moveAfter($structureId, $element, $prevElement, Mode::from($mode));
    }

    /**
     * Removes an element from a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     *
     * @return bool
     * @since 3.7.19
     */
    public function remove(int $structureId, ElementInterface $element): bool
    {
        return StructuresFacade::remove($structureId, $element);
    }

    public static function registerEvents(): void
    {
        foreach ([
            self::EVENT_BEFORE_INSERT_ELEMENT => InsertingElement::class,
            self::EVENT_AFTER_INSERT_ELEMENT => ElementInserted::class,
            self::EVENT_BEFORE_MOVE_ELEMENT => UpdatingElement::class,
            self::EVENT_AFTER_MOVE_ELEMENT => ElementUpdated::class,
            self::EVENT_BEFORE_UPDATE_ELEMENT => UpdatingElement::class,
            self::EVENT_AFTER_UPDATE_ELEMENT => ElementUpdated::class,
        ] as $old => $new) {
            Event::listen($new, function(UpdateElementEvent $event) use ($old) {
                if (Craft::$app->getStructures()->hasEventHandlers($old)) {
                    $oldEvent = new MoveElementEvent([
                        'element' => $event->element,
                        'structureId' => $event->structureId,
                        'targetElementId' => $event->targetElementId,
                        'action' => $event->action->value,
                    ]);

                    Craft::$app->getStructures()->trigger($old, $oldEvent);

                    if (property_exists($event, 'isValid') && !$oldEvent->isValid) {
                        $event->isValid = false;
                    }
                }
            });
        }
    }
}
