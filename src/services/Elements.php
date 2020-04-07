<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\base\ElementActionInterface;
use craft\base\ElementExporterInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\elements\Tag;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidElementException;
use craft\errors\OperationAbortedException;
use craft\errors\SiteNotFoundException;
use craft\events\BatchElementActionEvent;
use craft\events\DeleteElementEvent;
use craft\events\ElementEvent;
use craft\events\ElementQueryEvent;
use craft\events\MergeElementsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use craft\queue\jobs\FindAndReplace;
use craft\queue\jobs\UpdateElementSlugsAndUris;
use craft\queue\jobs\UpdateSearchIndex;
use craft\records\Element as ElementRecord;
use craft\records\Element_SiteSettings as Element_SiteSettingsRecord;
use craft\records\StructureElement as StructureElementRecord;
use craft\validators\SlugValidator;
use yii\base\Behavior;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\Exception as DbException;

/**
 * The Elements service provides APIs for managing elements.
 * An instance of the Elements service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getElements()|`Craft::$app->elements`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Elements extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering element types.
     *
     * Element types must implement [[ElementInterface]]. [[Element]] provides a base implementation.
     *
     * See [Element Types](https://docs.craftcms.com/v3/element-types.html) for documentation on creating element types.
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\services\Elements;
     * use yii\base\Event;
     *
     * Event::on(Elements::class,
     *     Elements::EVENT_REGISTER_ELEMENT_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyElementType::class;
     *     }
     * );
     * ```
     */
    const EVENT_REGISTER_ELEMENT_TYPES = 'registerElementTypes';

    /**
     * @event MergeElementsEvent The event that is triggered after two elements are merged together.
     */
    const EVENT_AFTER_MERGE_ELEMENTS = 'afterMergeElements';

    /**
     * @event DeleteElementEvent The event that is triggered before an element is deleted.
     */
    const EVENT_BEFORE_DELETE_ELEMENT = 'beforeDeleteElement';

    /**
     * @event ElementEvent The event that is triggered after an element is deleted.
     */
    const EVENT_AFTER_DELETE_ELEMENT = 'afterDeleteElement';

    /**
     * @event ElementEvent The event that is triggered before an element is restored.
     * @since 3.1.0
     */
    const EVENT_BEFORE_RESTORE_ELEMENT = 'beforeRestoreElement';

    /**
     * @event ElementEvent The event that is triggered after an element is restored.
     * @since 3.1.0
     */
    const EVENT_AFTER_RESTORE_ELEMENT = 'afterRestoreElement';

    /**
     * @event ElementEvent The event that is triggered before an element is saved.
     *
     * If you want to ignore events for drafts or revisions, call [[\craft\helpers\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use craft\events\ElementEvent;
     * use craft\helpers\ElementHelper;
     * use craft\services\Elements;
     *
     * Craft::$app->elements->on(Elements::EVENT_BEFORE_SAVE_ELEMENT, function(ElementEvent $e) {
     *     if (ElementHelper::isDraftOrRevision($e->element) {
     *         return;
     *     }
     *
     *     // ...
     * });
     * ```
     */
    const EVENT_BEFORE_SAVE_ELEMENT = 'beforeSaveElement';

    /**
     * @event ElementEvent The event that is triggered after an element is saved.
     *
     * If you want to ignore events for drafts or revisions, call [[\craft\helpers\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use craft\events\ElementEvent;
     * use craft\helpers\ElementHelper;
     * use craft\services\Elements;
     *
     * Craft::$app->elements->on(Elements::EVENT_AFTER_SAVE_ELEMENT, function(ElementEvent $e) {
     *     if (ElementHelper::isDraftOrRevision($e->element) {
     *         return;
     *     }
     *
     *     // ...
     * });
     * ```
     */
    const EVENT_AFTER_SAVE_ELEMENT = 'afterSaveElement';

    /**
     * @event ElementQueryEvent The event that is triggered before resaving a batch of elements.
     */
    const EVENT_BEFORE_RESAVE_ELEMENTS = 'beforeResaveElements';

    /**
     * @event ElementQueryEvent The event that is triggered after resaving a batch of elements.
     */
    const EVENT_AFTER_RESAVE_ELEMENTS = 'afterResaveElements';

    /**
     * @event BatchElementActionEvent The event that is triggered before an element is resaved.
     */
    const EVENT_BEFORE_RESAVE_ELEMENT = 'beforeResaveElement';

    /**
     * @event BatchElementActionEvent The event that is triggered after an element is resaved.
     */
    const EVENT_AFTER_RESAVE_ELEMENT = 'afterResaveElement';

    /**
     * @event ElementQueryEvent The event that is triggered before propagating a batch of elements.
     */
    const EVENT_BEFORE_PROPAGATE_ELEMENTS = 'beforePropagateElements';

    /**
     * @event ElementQueryEvent The event that is triggered after propagating a batch of elements.
     */
    const EVENT_AFTER_PROPAGATE_ELEMENTS = 'afterPropagateElements';

    /**
     * @event BatchElementActionEvent The event that is triggered before an element is propagated.
     */
    const EVENT_BEFORE_PROPAGATE_ELEMENT = 'beforePropagateElement';

    /**
     * @event BatchElementActionEvent The event that is triggered after an element is propagated.
     */
    const EVENT_AFTER_PROPAGATE_ELEMENT = 'afterPropagateElement';

    /**
     * @event ElementEvent The event that is triggered before an element’s slug and URI are updated, usually following a Structure move.
     */
    const EVENT_BEFORE_UPDATE_SLUG_AND_URI = 'beforeUpdateSlugAndUri';

    /**
     * @event ElementEvent The event that is triggered after an element’s slug and URI are updated, usually following a Structure move.
     */
    const EVENT_AFTER_UPDATE_SLUG_AND_URI = 'afterUpdateSlugAndUri';

    /**
     * @event \craft\events\ElementActionEvent The event that is triggered before an element action is performed.
     *
     * You may set [[\craft\events\ElementActionEvent::isValid]] to `false` to prevent the action from being performed.
     */
    const EVENT_BEFORE_PERFORM_ACTION = 'beforePerformAction';

    /**
     * @event \craft\events\ElementActionEvent The event that is triggered after an element action is performed.
     */
    const EVENT_AFTER_PERFORM_ACTION = 'afterPerformAction';

    /**
     * @var int[] Stores a mapping of source element IDs to their duplicated element IDs.
     */
    public static $duplicatedElementIds = [];

    /**
     * @var int[] Stores a mapping of duplicated element IDs to their source element IDs.
     * @since 3.4.0
     */
    public static $duplicatedElementSourceIds = [];

    /**
     * @var array|null
     */
    private $_placeholderElements;

    /**
     * @var array
     * @see setPlaceholderElement()
     * @see getElementByUri()
     */
    private $_placeholderUris;

    /**
     * @var string[]
     */
    private $_elementTypesByRefHandle = [];

    /**
     * @var bool|null Whether we should be updating search indexes for elements if not told explicitly.
     * @since 3.1.2
     */
    private $_updateSearchIndex;

    /**
     * Creates an element with a given config.
     *
     * @param mixed $config The field’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return ElementInterface The element
     */
    public function createElement($config): ElementInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ComponentHelper::createComponent($config, ElementInterface::class);
    }

    // Finding Elements
    // -------------------------------------------------------------------------

    /**
     * Returns an element by its ID.
     *
     * If no element type is provided, the method will first have to run a DB query to determine what type of element
     * the $id is, so you should definitely pass it if it’s known.
     * The element’s status will not be a factor when using this method.
     *
     * @param int $elementId The element’s ID.
     * @param string|null $elementType The element class.
     * @param int|int[]|string|null $siteId The site(s) to fetch the element in.
     * Defaults to the current site.
     * @param array $criteria
     * @return ElementInterface|null The matching element, or `null`.
     */
    public function getElementById(int $elementId, string $elementType = null, $siteId = null, array $criteria = [])
    {
        if (!$elementId) {
            return null;
        }

        if ($elementType === null) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $elementType = $this->getElementTypeById($elementId);

            if ($elementType === null) {
                return null;
            }
        }

        if (!class_exists($elementType)) {
            return null;
        }

        /** @var Element $elementType */
        /** @var ElementQuery $query */
        $query = $elementType::find();
        $query->id = $elementId;
        $query->siteId = $siteId;
        $query->anyStatus();

        // Is this a draft/revision?
        try {
            $data = (new Query())
                ->select(['draftId', 'revisionId'])
                ->from([Table::ELEMENTS])
                ->where(['id' => $elementId])
                ->one();
        } catch (DbException $e) {
            // Not on schema 3.2.6+ yet
        }

        if (!empty($data['draftId'])) {
            $query->draftId($data['draftId']);
        } else if (!empty($data['revisionId'])) {
            $query->revisionId($data['revisionId']);
        }

        Craft::configure($query, $criteria);
        return $query->one();
    }

    /**
     * Returns an element by its URI.
     *
     * @param string $uri The element’s URI.
     * @param int|null $siteId The site to look for the URI in, and to return the element in.
     * Defaults to the current site.
     * @param bool $enabledOnly Whether to only look for an enabled element. Defaults to `false`.
     * @return ElementInterface|null The matching element, or `null`.
     */
    public function getElementByUri(string $uri, int $siteId = null, bool $enabledOnly = false)
    {
        if ($uri === '') {
            $uri = Element::HOMEPAGE_URI;
        }

        if ($siteId === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        // See if we already have a placeholder for this element URI
        if (isset($this->_placeholderUris[$uri][$siteId])) {
            return $this->_placeholderUris[$uri][$siteId];
        }

        // First get the element ID and type
        $query = (new Query())
            ->select(['elements.id', 'elements.type'])
            ->from(['{{%elements}} elements'])
            ->innerJoin('{{%elements_sites}} elements_sites', '[[elements_sites.elementId]] = [[elements.id]]')
            ->where([
                'elements_sites.siteId' => $siteId,
            ]);

        // todo: remove schema version conditions after next beakpoint
        $schemaVersion = Craft::$app->getInstalledSchemaVersion();
        if (version_compare($schemaVersion, '3.1.0', '>=')) {
            $query->andWhere(['elements.dateDeleted' => null]);
        }
        if (version_compare($schemaVersion, '3.2.6', '>=')) {
            $query->andWhere([
                'elements.draftId' => null,
                'elements.revisionId' => null,
            ]);
        }

        if (Craft::$app->getDb()->getIsMysql()) {
            $query->andWhere([
                'elements_sites.uri' => $uri,
            ]);
        } else {
            $query->andWhere([
                'lower([[elements_sites.uri]])' => mb_strtolower($uri),
            ]);
        }

        if ($enabledOnly) {
            $query->andWhere([
                'elements_sites.enabled' => true,
                'elements.enabled' => true,
                'elements.archived' => false,
            ]);
        }

        $result = $query->one();
        return $result ? $this->getElementById($result['id'], $result['type'], $siteId) : null;
    }

    /**
     * Returns the class of an element with a given ID.
     *
     * @param int $elementId The element’s ID
     * @return string|null The element’s class, or null if it could not be found
     */
    public function getElementTypeById(int $elementId)
    {
        $class = (new Query())
            ->select(['type'])
            ->from([Table::ELEMENTS])
            ->where(['id' => $elementId])
            ->scalar();

        return $class !== false ? $class : null;
    }

    /**
     * Returns the classes of elements with the given IDs.
     *
     * @param int[] $elementIds The elements’ IDs
     * @return string[]
     */
    public function getElementTypesByIds(array $elementIds): array
    {
        return (new Query())
            ->select(['type'])
            ->distinct(true)
            ->from([Table::ELEMENTS])
            ->where(['id' => $elementIds])
            ->column();
    }

    /**
     * Returns an element’s URI for a given site.
     *
     * @param int $elementId The element’s ID.
     * @param int $siteId The site to search for the element’s URI in.
     * @return string|null|false The element’s URI or `null`, or `false` if the element doesn’t exist.
     */
    public function getElementUriForSite(int $elementId, int $siteId)
    {
        return (new Query())
            ->select(['uri'])
            ->from([Table::ELEMENTS_SITES])
            ->where(['elementId' => $elementId, 'siteId' => $siteId])
            ->scalar();
    }

    /**
     * Returns the site IDs that a given element is enabled in.
     *
     * @param int $elementId The element’s ID.
     * @return int[] The site IDs that the element is enabled in. If the element could not be found, an empty array
     * will be returned.
     */
    public function getEnabledSiteIdsForElement(int $elementId): array
    {
        return (new Query())
            ->select(['siteId'])
            ->from([Table::ELEMENTS_SITES])
            ->where(['elementId' => $elementId, 'enabled' => 1])
            ->column();
    }

    // Saving Elements
    // -------------------------------------------------------------------------

    /**
     * Handles all of the routine tasks that go along with saving elements.
     *
     * Those tasks include:
     *
     * - Validating its content (if $validateContent is `true`, or it’s left as `null` and the element is enabled)
     * - Ensuring the element has a title if its type [[Element::hasTitles()|has titles]], and giving it a
     *   default title in the event that $validateContent is set to `false`
     * - Saving a row in the `elements` table
     * - Assigning the element’s ID on the element model, if it’s a new element
     * - Assigning the element’s ID on the element’s content model, if there is one and it’s a new set of content
     * - Updating the search index with new keywords from the element’s content
     * - Setting a unique URI on the element, if it’s supposed to have one.
     * - Saving the element’s row(s) in the `elements_sites` and `content` tables
     * - Deleting any rows in the `elements_sites` and `content` tables that no longer need to be there
     * - Cleaning any template caches that the element was involved in
     *
     * The function will fire `beforeElementSave` and `afterElementSave` events, and will call `beforeSave()`
     *  and `afterSave()` methods on the passed-in element, giving the element opportunities to hook into the
     * save process.
     *
     * Example usage - creating a new entry:
     *
     * ```php
     * $entry = new Entry();
     * $entry->sectionId = 10;
     * $entry->typeId = 1;
     * $entry->authorId = 5;
     * $entry->enabled = true;
     * $entry->title = "Hello World!";
     * $entry->setFieldValues([
     *     'body' => "<p>I can’t believe I literally just called this “Hello World!”.</p>",
     * ]);
     * $success = Craft::$app->elements->saveElement($entry);
     * if (!$success) {
     *     Craft::error('Couldn’t save the entry "'.$entry->title.'"', __METHOD__);
     * }
     * ```
     *
     * @param ElementInterface $element The element that is being saved
     * @param bool $runValidation Whether the element should be validated
     * @param bool $propagate Whether the element should be saved across all of its supported sites
     * (this can only be disabled when updating an existing element)
     * @param bool|null $updateSearchIndex Whether to update the element search index for the element
     * (this will happen via a background job if this is a web request)
     * @return bool
     * @throws ElementNotFoundException if $element has an invalid $id
     * @throws Exception if the $element doesn’t have any supported sites
     * @throws \Throwable if reasons
     */
    public function saveElement(ElementInterface $element, bool $runValidation = true, bool $propagate = true, bool $updateSearchIndex = null): bool
    {
        // Force propagation for new elements
        /** @var Element $element */
        $propagate = !$element->id || $propagate;

        return $this->_saveElementInternal($element, $runValidation, $propagate, $updateSearchIndex);
    }

    /**
     * Resaves all elements that match a given element query.
     *
     * @param ElementQueryInterface $query The element query to fetch elements with
     * @param bool $continueOnError Whether to continue going if an error occurs
     * @param bool $skipRevisions Whether elements that are (or belong to) a revision should be skipped
     * @param bool|null $updateSearchIndex Whether to update the element search index for the element
     * (this will happen via a background job if this is a web request)
     * @throws \Throwable if reasons
     * @since 3.2.0
     */
    public function resaveElements(ElementQueryInterface $query, bool $continueOnError = false, $skipRevisions = true, bool $updateSearchIndex = null)
    {
        // Fire a 'beforeResaveElements' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_RESAVE_ELEMENTS)) {
            $this->trigger(self::EVENT_BEFORE_RESAVE_ELEMENTS, new ElementQueryEvent([
                'query' => $query,
            ]));
        }

        $position = 0;

        try {
            /** @var ElementQuery $query */
            foreach ($query->each() as $element) {
                $position++;

                /** @var Element $element */
                $element->setScenario(Element::SCENARIO_ESSENTIALS);
                $element->resaving = true;

                // Fire a 'beforeResaveElement' event
                if ($this->hasEventHandlers(self::EVENT_BEFORE_RESAVE_ELEMENT)) {
                    $this->trigger(self::EVENT_BEFORE_RESAVE_ELEMENT, new BatchElementActionEvent([
                        'query' => $query,
                        'element' => $element,
                        'position' => $position,
                    ]));
                }

                $e = null;
                try {
                    // Make sure the element was queried with its content
                    if ($element::hasContent() && $element->contentId === null) {
                        throw new InvalidElementException($element, "Skipped resaving {$element} ({$element->id}) because it wasn’t loaded with its content.");
                    }

                    // Make sure this isn't a revision
                    if ($skipRevisions) {
                        try {
                            $root = ElementHelper::rootElement($element);
                        } catch (\Throwable $rootException) {
                            throw new InvalidElementException($element, "Skipped resaving {$element} ({$element->id}) due to an error obtaining its root element: " . $rootException->getMessage());
                        }
                        if ($root->getIsRevision()) {
                            throw new InvalidElementException($element, "Skipped resaving {$element} ({$element->id}) because it's a revision.");
                        }
                    }
                } catch (InvalidElementException $e) {
                }

                if ($e === null) {
                    try {
                        $this->_saveElementInternal($element, true, true, $updateSearchIndex);
                    } catch (\Throwable $e) {
                        if (!$continueOnError) {
                            throw $e;
                        }
                        Craft::$app->getErrorHandler()->logException($e);
                    }
                }

                // Fire an 'afterResaveElement' event
                if ($this->hasEventHandlers(self::EVENT_AFTER_RESAVE_ELEMENT)) {
                    $this->trigger(self::EVENT_AFTER_RESAVE_ELEMENT, new BatchElementActionEvent([
                        'query' => $query,
                        'element' => $element,
                        'position' => $position,
                        'exception' => $e,
                    ]));
                }
            }
        } catch (QueryAbortedException $e) {
            // Fail silently
        }

        // Fire an 'afterResaveElements' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_RESAVE_ELEMENTS)) {
            $this->trigger(self::EVENT_AFTER_RESAVE_ELEMENTS, new ElementQueryEvent([
                'query' => $query,
            ]));
        }
    }

    /**
     * Propagates all elements that match a given element query to another site(s).
     *
     * @param ElementQueryInterface $query The element query to fetch elements with
     * @param bool $continueOnError Whether to continue going if an error occurs
     * @throws \Throwable if reasons
     * @var int|int[]|null The site ID(s) that the elements should be propagated to. If null, elements will be
     * propagated to all supported sites, except the one they were queried in.
     * @since 3.2.0
     */
    public function propagateElements(ElementQueryInterface $query, $siteIds = null, bool $continueOnError = false)
    {
        // Fire a 'beforePropagateElements' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_PROPAGATE_ELEMENTS)) {
            $this->trigger(self::EVENT_BEFORE_PROPAGATE_ELEMENTS, new ElementQueryEvent([
                'query' => $query,
            ]));
        }

        if ($siteIds !== null) {
            $siteIds = (array)$siteIds;
        }

        $position = 0;

        try {
            /** @var ElementQuery $query */
            foreach ($query->each() as $element) {
                $position++;

                /** @var Element $element */
                $element->setScenario(Element::SCENARIO_ESSENTIALS);
                $elementSiteIds = $siteIds ?? ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($element), 'siteId');
                /** @var ElementInterface|string $elementType */
                $elementType = get_class($element);

                // Fire a 'beforePropagateElement' event
                if ($this->hasEventHandlers(self::EVENT_BEFORE_PROPAGATE_ELEMENT)) {
                    $this->trigger(self::EVENT_BEFORE_PROPAGATE_ELEMENT, new BatchElementActionEvent([
                        'query' => $query,
                        'element' => $element,
                        'position' => $position,
                    ]));
                }

                $e = null;
                try {
                    $element->newSiteIds = [];

                    foreach ($elementSiteIds as $siteId) {
                        if ($siteId != $element->siteId) {
                            // Make sure the site element wasn't updated more recently than the main one
                            /** @var Element $siteElement */
                            $siteElement = $this->getElementById($element->id, $elementType, $siteId);
                            if ($siteElement === null || $siteElement->dateUpdated < $element->dateUpdated) {
                                $this->propagateElement($element, $siteId, $siteElement ?? false);
                            }
                        }
                    }

                    // It's now fully duplicated and propagated
                    $element->markAsDirty();
                    $element->afterPropagate(false);
                } catch (\Throwable $e) {
                    if (!$continueOnError) {
                        throw $e;
                    }
                    Craft::$app->getErrorHandler()->logException($e);
                }

                // Fire an 'afterPropagateElement' event
                if ($this->hasEventHandlers(self::EVENT_AFTER_PROPAGATE_ELEMENT)) {
                    $this->trigger(self::EVENT_AFTER_PROPAGATE_ELEMENT, new BatchElementActionEvent([
                        'query' => $query,
                        'element' => $element,
                        'position' => $position,
                        'exception' => $e,
                    ]));
                }
            }
        } catch (QueryAbortedException $e) {
            // Fail silently
        }

        // Fire an 'afterPropagateElements' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_PROPAGATE_ELEMENTS)) {
            $this->trigger(self::EVENT_AFTER_PROPAGATE_ELEMENTS, new ElementQueryEvent([
                'query' => $query,
            ]));
        }
    }

    /**
     * Duplicates an element.
     *
     * @param ElementInterface $element the element to duplicate
     * @param array $newAttributes any attributes to apply to the duplicate
     * @return ElementInterface the duplicated element
     * @throws InvalidElementException if saveElement() returns false for any of the sites
     * @throws \Throwable if reasons
     */
    public function duplicateElement(ElementInterface $element, array $newAttributes = []): ElementInterface
    {
        // Make sure the element exists
        /** @var Element $element */
        if (!$element->id) {
            throw new Exception('Attempting to duplicate an unsaved element.');
        }

        // Create our first clone for the $element's site
        $element->getFieldValues();
        /** @var Element $mainClone */
        $mainClone = clone $element;
        $mainClone->id = null;
        $mainClone->uid = null;
        $mainClone->contentId = null;
        $mainClone->dateCreated = null;
        $mainClone->duplicateOf = $element;

        $behaviors = ArrayHelper::remove($newAttributes, 'behaviors', []);
        $mainClone->setRevisionNotes(ArrayHelper::remove($newAttributes, 'revisionNotes'));
        $mainClone->setAttributes($newAttributes, false);

        // Attach behaviors
        foreach ($behaviors as $name => $behavior) {
            if ($behavior instanceof Behavior) {
                $behavior = clone $behavior;
            }
            $mainClone->attachBehavior($name, $behavior);
        }

        // Make sure the element actually supports its own site ID
        $supportedSites = ElementHelper::supportedSitesForElement($mainClone);
        $supportedSiteIds = ArrayHelper::getColumn($supportedSites, 'siteId');
        if (!in_array($mainClone->siteId, $supportedSiteIds, false)) {
            throw new Exception('Attempting to duplicate an element in an unsupported site.');
        }

        // Validate
        $mainClone->setScenario(Element::SCENARIO_ESSENTIALS);
        $mainClone->validate();

        // If there are any errors on the URI, re-validate as disabled
        if ($mainClone->hasErrors('uri') && $mainClone->enabled) {
            $mainClone->enabled = false;
            $mainClone->validate();
        }

        if ($mainClone->hasErrors()) {
            throw new InvalidElementException($mainClone, 'Element ' . $element->id . ' could not be duplicated because it doesn\'t validate.');
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Start with $element's site
            if (!$this->_saveElementInternal($mainClone, false, false)) {
                throw new InvalidElementException($mainClone, 'Element ' . $element->id . ' could not be duplicated for site ' . $element->siteId);
            }

            // Map it
            static::$duplicatedElementIds[$element->id] = $mainClone->id;
            static::$duplicatedElementSourceIds[$mainClone->id] = $element->id;

            // Propagate it
            foreach ($supportedSites as $siteInfo) {
                if ($siteInfo['siteId'] != $mainClone->siteId) {
                    $siteQuery = $element::find()
                        ->id($element->id ?: false)
                        ->siteId($siteInfo['siteId'])
                        ->anyStatus();

                    if ($element->getIsDraft()) {
                        $siteQuery->drafts();
                    } else if ($element->getIsRevision()) {
                        $siteQuery->revisions();
                    }

                    $siteElement = $siteQuery->one();

                    if ($siteElement === null) {
                        Craft::warning('Element ' . $element->id . ' doesn’t exist in the site ' . $siteInfo['siteId']);
                        continue;
                    }

                    $siteElement->getFieldValues();
                    /** @var Element $siteClone */
                    $siteClone = clone $siteElement;
                    $siteClone->duplicateOf = $siteElement;
                    $siteClone->propagating = true;
                    $siteClone->id = $mainClone->id;
                    $siteClone->uid = $mainClone->uid;
                    $siteClone->enabled = $mainClone->enabled;
                    $siteClone->contentId = null;
                    $siteClone->dateCreated = null;

                    // Attach behaviors
                    foreach ($behaviors as $name => $behavior) {
                        if ($behavior instanceof Behavior) {
                            $behavior = clone $behavior;
                        }
                        $siteClone->attachBehavior($name, $behavior);
                    }

                    $siteClone->setAttributes($newAttributes, false);
                    $siteClone->siteId = $siteInfo['siteId'];

                    if ($element::hasUris()) {
                        // Make sure it has a valid slug
                        (new SlugValidator())->validateAttribute($siteClone, 'slug');
                        if ($siteClone->hasErrors('slug')) {
                            throw new InvalidElementException($siteClone, "Element {$element->id} could not be duplicated for site {$siteInfo['siteId']}: " . $siteClone->getFirstError('slug'));
                        }

                        // Set a unique URI on the site clone
                        try {
                            ElementHelper::setUniqueUri($siteClone);
                        } catch (OperationAbortedException $e) {
                            // Oh well, not worth bailing over
                        }
                    }

                    if (!$this->_saveElementInternal($siteClone, false, false)) {
                        throw new InvalidElementException($siteClone, "Element {$element->id} could not be duplicated for site {$siteInfo['siteId']}: " . implode(', ', $siteClone->getFirstErrors()));
                    }
                }
            }

            // It's now fully duplicated and propagated
            $mainClone->afterPropagate(empty($newAttributes['id']));

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clean up our tracks
        $mainClone->duplicateOf = null;

        return $mainClone;
    }

    /**
     * Updates an element’s slug and URI, along with any descendants.
     *
     * @param ElementInterface $element The element to update.
     * @param bool $updateOtherSites Whether the element’s other sites should also be updated.
     * @param bool $updateDescendants Whether the element’s descendants should also be updated.
     * @param bool $queue Whether the element’s slug and URI should be updated via a job in the queue.
     */
    public function updateElementSlugAndUri(ElementInterface $element, bool $updateOtherSites = true, bool $updateDescendants = true, bool $queue = false)
    {
        /** @var Element $element */
        if ($queue) {
            Craft::$app->getQueue()->push(new UpdateElementSlugsAndUris([
                'elementId' => $element->id,
                'elementType' => get_class($element),
                'siteId' => $element->siteId,
                'updateOtherSites' => $updateOtherSites,
                'updateDescendants' => $updateDescendants,
            ]));

            return;
        }

        if ($element::hasUris()) {
            ElementHelper::setUniqueUri($element);
        }

        // Fire a 'beforeUpdateSlugAndUri' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_UPDATE_SLUG_AND_URI)) {
            $this->trigger(self::EVENT_BEFORE_UPDATE_SLUG_AND_URI, new ElementEvent([
                'element' => $element
            ]));
        }

        Craft::$app->getDb()->createCommand()
            ->update(
                Table::ELEMENTS_SITES,
                [
                    'slug' => $element->slug,
                    'uri' => $element->uri
                ],
                [
                    'elementId' => $element->id,
                    'siteId' => $element->siteId
                ])
            ->execute();

        // Fire a 'afterUpdateSlugAndUri' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UPDATE_SLUG_AND_URI)) {
            $this->trigger(self::EVENT_AFTER_UPDATE_SLUG_AND_URI, new ElementEvent([
                'element' => $element
            ]));
        }

        // Delete any caches involving this element
        Craft::$app->getTemplateCaches()->deleteCachesByElement($element);

        if ($updateOtherSites) {
            $this->updateElementSlugAndUriInOtherSites($element);
        }

        if ($updateDescendants) {
            $this->updateDescendantSlugsAndUris($element, $updateOtherSites);
        }
    }

    /**
     * Updates an element’s slug and URI, for any sites besides the given one.
     *
     * @param ElementInterface $element The element to update.
     */
    public function updateElementSlugAndUriInOtherSites(ElementInterface $element)
    {
        /** @var Element $element */
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if ($siteId == $element->siteId) {
                continue;
            }

            $elementInOtherSite = $element::find()
                ->id($element->id)
                ->siteId($siteId)
                ->one();

            if ($elementInOtherSite) {
                $this->updateElementSlugAndUri($elementInOtherSite, false, false);
            }
        }
    }

    /**
     * Updates an element’s descendants’ slugs and URIs.
     *
     * @param ElementInterface $element The element whose descendants should be updated.
     * @param bool $updateOtherSites Whether the element’s other sites should also be updated.
     * @param bool $queue Whether the descendants’ slugs and URIs should be updated via a job in the queue.
     */
    public function updateDescendantSlugsAndUris(ElementInterface $element, bool $updateOtherSites = true, bool $queue = false)
    {
        /** @var Element $element */
        /** @var ElementQuery $query */
        $query = $element::find()
            ->descendantOf($element)
            ->descendantDist(1)
            ->anyStatus()
            ->siteId($element->siteId);

        if ($queue) {
            $childIds = $query->ids();

            if (!empty($childIds)) {
                Craft::$app->getQueue()->push(new UpdateElementSlugsAndUris([
                    'elementId' => $childIds,
                    'elementType' => get_class($element),
                    'siteId' => $element->siteId,
                    'updateOtherSites' => $updateOtherSites,
                    'updateDescendants' => true,
                ]));
            }
        } else {
            $children = $query->all();

            foreach ($children as $child) {
                $this->updateElementSlugAndUri($child, $updateOtherSites, true, false);
            }
        }
    }

    /**
     * Merges two elements together by their IDs.
     *
     * This method will update the following:
     * - Any relations involving the merged element
     * - Any structures that contain the merged element
     * - Any reference tags in textual custom fields referencing the merged element
     *
     * @param int $mergedElementId The ID of the element that is going away.
     * @param int $prevailingElementId The ID of the element that is sticking around.
     * @return bool Whether the elements were merged successfully.
     * @throws ElementNotFoundException if one of the element IDs don’t exist.
     * @throws \Throwable if reasons
     */
    public function mergeElementsByIds(int $mergedElementId, int $prevailingElementId): bool
    {
        // Get the elements
        $mergedElement = $this->getElementById($mergedElementId);
        if (!$mergedElement) {
            throw new ElementNotFoundException("No element exists with the ID '{$mergedElementId}'");
        }
        $prevailingElement = $this->getElementById($prevailingElementId);
        if (!$prevailingElement) {
            throw new ElementNotFoundException("No element exists with the ID '{$prevailingElementId}'");
        }

        // Merge them
        return $this->mergeElements($mergedElement, $prevailingElement);
    }

    /**
     * Merges two elements together.
     *
     * This method will update the following:
     * - Any relations involving the merged element
     * - Any structures that contain the merged element
     * - Any reference tags in textual custom fields referencing the merged element
     *
     * @param ElementInterface $mergedElement The element that is going away.
     * @param ElementInterface $prevailingElement The element that is sticking around.
     * @return bool Whether the elements were merged successfully.
     * @throws \Throwable if reasons
     * @since 3.1.31
     */
    public function mergeElements(ElementInterface $mergedElement, ElementInterface $prevailingElement): bool
    {
        /** @var Element $mergedElement */
        /** @var Element $prevailingElement */
        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();
        try {
            // Update any relations that point to the merged element
            $relations = (new Query())
                ->select(['id', 'fieldId', 'sourceId', 'sourceSiteId'])
                ->from([Table::RELATIONS])
                ->where(['targetId' => $mergedElement->id])
                ->all();

            foreach ($relations as $relation) {
                // Make sure the persisting element isn't already selected in the same field
                $persistingElementIsRelatedToo = (new Query())
                    ->from([Table::RELATIONS])
                    ->where([
                        'fieldId' => $relation['fieldId'],
                        'sourceId' => $relation['sourceId'],
                        'sourceSiteId' => $relation['sourceSiteId'],
                        'targetId' => $prevailingElement->id
                    ])
                    ->exists();

                if (!$persistingElementIsRelatedToo) {
                    $db->createCommand()
                        ->update(
                            Table::RELATIONS,
                            [
                                'targetId' => $prevailingElement->id
                            ],
                            [
                                'id' => $relation['id']
                            ])
                        ->execute();
                }
            }

            // Update any structures that the merged element is in
            $structureElements = (new Query())
                ->select(['id', 'structureId'])
                ->from([Table::STRUCTUREELEMENTS])
                ->where(['elementId' => $mergedElement->id])
                ->all();

            foreach ($structureElements as $structureElement) {
                // Make sure the persisting element isn't already a part of that structure
                $persistingElementIsInStructureToo = (new Query())
                    ->from([Table::STRUCTUREELEMENTS])
                    ->where([
                        'structureId' => $structureElement['structureId'],
                        'elementId' => $prevailingElement->id
                    ])
                    ->exists();

                if (!$persistingElementIsInStructureToo) {
                    $db->createCommand()
                        ->update(Table::STRUCTUREELEMENTS,
                            [
                                'elementId' => $prevailingElement->id
                            ],
                            [
                                'id' => $structureElement['id']
                            ])
                        ->execute();
                }
            }

            // Update any reference tags
            /** @var ElementInterface|null $elementType */
            $elementType = $this->getElementTypeById($prevailingElement->id);

            if ($elementType !== null && ($refHandle = $elementType::refHandle()) !== null) {
                $refTagPrefix = "{{$refHandle}:";
                $queue = Craft::$app->getQueue();

                $queue->push(new FindAndReplace([
                    'description' => Craft::t('app', 'Updating element references'),
                    'find' => $refTagPrefix . $mergedElement->id . ':',
                    'replace' => $refTagPrefix . $prevailingElement->id . ':',
                ]));

                $queue->push(new FindAndReplace([
                    'description' => Craft::t('app', 'Updating element references'),
                    'find' => $refTagPrefix . $mergedElement->id . '}',
                    'replace' => $refTagPrefix . $prevailingElement->id . '}',
                ]));
            }

            // Fire an 'afterMergeElements' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_MERGE_ELEMENTS)) {
                $this->trigger(self::EVENT_AFTER_MERGE_ELEMENTS, new MergeElementsEvent([
                    'mergedElementId' => $mergedElement->id,
                    'prevailingElementId' => $prevailingElement->id
                ]));
            }

            // Now delete the merged element
            $success = $this->deleteElement($mergedElement);

            $transaction->commit();

            return $success;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes an element by its ID.
     *
     * @param int $elementId The element’s ID
     * @param string|null $elementType The element class.
     * @param int|null $siteId The site to fetch the element in.
     * Defaults to the current site.
     * @param bool Whether the element should be hard-deleted immediately, instead of soft-deleted
     * @return bool Whether the element was deleted successfully
     * @throws \Throwable
     */
    public function deleteElementById(int $elementId, string $elementType = null, int $siteId = null, bool $hardDelete = false): bool
    {
        /** @var ElementInterface|string|null $elementType */
        if ($elementType === null) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $elementType = $this->getElementTypeById($elementId);

            if ($elementType === null) {
                return false;
            }
        }

        if ($siteId === null && $elementType::isLocalized() && Craft::$app->getIsMultiSite()) {
            // Get a site this element is enabled in
            $siteId = (int)(new Query())
                ->select('siteId')
                ->from(Table::ELEMENTS_SITES)
                ->where(['elementId' => $elementId])
                ->scalar();

            if ($siteId === 0) {
                return false;
            }
        }

        $element = $this->getElementById($elementId, $elementType, $siteId);

        if (!$element) {
            return false;
        }

        return $this->deleteElement($element, $hardDelete);
    }

    /**
     * Deletes an element.
     *
     * @param ElementInterface $element The element to be deleted
     * @param bool Whether the element should be hard-deleted immediately, instead of soft-deleted
     * @return bool Whether the element was deleted successfully
     * @throws \Throwable
     */
    public function deleteElement(ElementInterface $element, bool $hardDelete = false): bool
    {
        /** @var Element $element */
        // Fire a 'beforeDeleteElement' event
        $event = new DeleteElementEvent([
            'element' => $element,
            'hardDelete' => $hardDelete,
        ]);
        $this->trigger(self::EVENT_BEFORE_DELETE_ELEMENT, $event);

        $element->hardDelete = $hardDelete || $event->hardDelete;

        if (!$element->beforeDelete()) {
            return false;
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();
        try {
            // First delete any structure nodes with this element, so NestedSetBehavior can do its thing.
            while (($record = StructureElementRecord::findOne(['elementId' => $element->id])) !== null) {
                // If this element still has any children, move them up before the one getting deleted.
                while (($child = $record->children(1)->one()) !== null) {
                    $child->insertBefore($record);
                    // Re-fetch the record since its lft and rgt attributes just changed
                    $record = StructureElementRecord::findOne($record->id);
                }
                // Delete this element's node
                $record->deleteWithChildren();
            }

            // Delete the caches before they drop their elementId relations (passing `false` because there's no chance
            // this element is suddenly going to show up in a new query)
            Craft::$app->getTemplateCaches()->deleteCachesByElementId($element->id, false);

            if ($element->hardDelete) {
                $db->createCommand()
                    ->delete(Table::ELEMENTS, ['id' => $element->id])
                    ->execute();
                $db->createCommand()
                    ->delete(Table::SEARCHINDEX, ['elementId' => $element->id])
                    ->execute();
            } else {
                // Soft delete the elements table row
                $db->createCommand()
                    ->softDelete(Table::ELEMENTS, ['id' => $element->id])
                    ->execute();

                // Also soft delete the element's drafts & revisions
                $this->_cascadeDeleteDraftsAndRevisions($element->id);
            }

            $element->afterDelete();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterDeleteElement' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_ELEMENT)) {
            $this->trigger(self::EVENT_AFTER_DELETE_ELEMENT, new ElementEvent([
                'element' => $element,
            ]));
        }

        return true;
    }

    /**
     * Restores an element.
     *
     * @param ElementInterface $element
     * @return bool Whether the element was restored successfully
     * @throws Exception if the $element doesn’t have any supported sites
     * @throws \Throwable if reasons
     * @since 3.1.0
     */
    public function restoreElement(ElementInterface $element): bool
    {
        return $this->restoreElements([$element]);
    }

    /**
     * Restores multiple elements.
     *
     * @param ElementInterface[] $elements
     * @return bool Whether at least one element was restored successfully
     * @throws Exception if an $element doesn’t have any supported sites
     * @throws \Throwable if reasons
     */
    public function restoreElements(array $elements): bool
    {
        // Fire "before" events
        foreach ($elements as $element) {
            /** @var Element $element */
            // Fire a 'beforeRestoreElement' event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_RESTORE_ELEMENT)) {
                $this->trigger(self::EVENT_BEFORE_RESTORE_ELEMENT, new ElementEvent([
                    'element' => $element,
                ]));
            }

            if (!$element->beforeRestore()) {
                return false;
            }
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();
        try {
            // Restore the elements
            foreach ($elements as $element) {
                // Get the sites supported by this element
                if (empty($supportedSites = ElementHelper::supportedSitesForElement($element))) {
                    throw new Exception("Element {$element->id} has no supported sites.");
                }

                // Make sure the element actually supports the site it's being saved in
                $supportedSiteIds = ArrayHelper::getColumn($supportedSites, 'siteId');
                if (!in_array($element->siteId, $supportedSiteIds, false)) {
                    throw new Exception('Attempting to restore an element in an unsupported site.');
                }

                // Get the element in each supported site
                $siteElements = [];
                /** @var Element|string $class */
                $class = get_class($element);
                foreach ($supportedSites as $siteInfo) {
                    $siteId = $siteInfo['siteId'];
                    if ($siteId != $element->siteId) {
                        $siteElement = $class::find()
                            ->id($element->id)
                            ->siteId($siteId)
                            ->anyStatus()
                            ->trashed(null)
                            ->one();
                        if ($siteElement) {
                            $siteElements[] = $siteElement;
                        }
                    }
                }

                // Make sure it still passes essential validation
                $element->setScenario(Element::SCENARIO_ESSENTIALS);
                if (!$element->validate()) {
                    Craft::warning("Unable to restore element {$element->id}: doesn't pass essential validation: " . print_r($element->errors, true), __METHOD__);
                    $transaction->rollBack();
                    return false;
                }

                foreach ($siteElements as $siteElement) {
                    if ($siteElement !== $element) {
                        $siteElement->setScenario(Element::SCENARIO_ESSENTIALS);
                        if (!$siteElement->validate()) {
                            Craft::warning("Unable to restore element {$element->id}: doesn't pass essential validation for site {$element->siteId}: " . print_r($element->errors, true), __METHOD__);
                            throw new Exception("Element {$element->id} doesn't pass essential validation for site {$element->siteId}.");
                        }
                    }
                }

                // Restore it
                $db->createCommand()
                    ->restore(Table::ELEMENTS, ['id' => $element->id])
                    ->execute();

                // Also restore the element's drafts & revisions
                $this->_cascadeDeleteDraftsAndRevisions($element->id, false);

                // Restore its search indexes
                $searchService = Craft::$app->getSearch();
                $searchService->indexElementAttributes($element);
                foreach ($siteElements as $siteElement) {
                    $searchService->indexElementAttributes($siteElement);
                }
            }

            // Fire "after" events
            foreach ($elements as $element) {
                $element->afterRestore();
                $element->trashed = false;

                // Fire an 'afterRestoreElement' event
                if ($this->hasEventHandlers(self::EVENT_AFTER_RESTORE_ELEMENT)) {
                    $this->trigger(self::EVENT_AFTER_RESTORE_ELEMENT, new ElementEvent([
                        'element' => $element,
                    ]));
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    // Element classes
    // -------------------------------------------------------------------------

    /**
     * Returns all available element classes.
     *
     * @return string[] The available element classes.
     */
    public function getAllElementTypes(): array
    {
        $elementTypes = [
            Asset::class,
            Category::class,
            Entry::class,
            GlobalSet::class,
            MatrixBlock::class,
            Tag::class,
            User::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $elementTypes
        ]);
        $this->trigger(self::EVENT_REGISTER_ELEMENT_TYPES, $event);

        return $event->types;
    }

    // Element Actions & Exporters
    // -------------------------------------------------------------------------

    /**
     * Creates an element action with a given config.
     *
     * @param mixed $config The element action’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return ElementActionInterface The element action
     */
    public function createAction($config): ElementActionInterface
    {
        /** @var ElementAction $action */
        $action = ComponentHelper::createComponent($config, ElementActionInterface::class);

        return $action;
    }

    /**
     * Creates an element exporter with a given config.
     *
     * @param mixed $config The element exporter’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return ElementExporterInterface The element exporter
     */
    public function createExporter($config): ElementExporterInterface
    {
        return ComponentHelper::createComponent($config, ElementExporterInterface::class);
    }

    // Misc
    // -------------------------------------------------------------------------

    /**
     * Returns an element class by its handle.
     *
     * @param string $refHandle The element class handle
     * @return string|null The element class, or null if it could not be found
     */
    public function getElementTypeByRefHandle(string $refHandle)
    {
        if (array_key_exists($refHandle, $this->_elementTypesByRefHandle)) {
            return $this->_elementTypesByRefHandle[$refHandle];
        }

        foreach ($this->getAllElementTypes() as $class) {
            /** @var string|ElementInterface $class */
            if (
                ($elementRefHandle = $class::refHandle()) !== null &&
                strcasecmp($elementRefHandle, $refHandle) === 0
            ) {
                return $this->_elementTypesByRefHandle[$refHandle] = $class;
            }
        }

        return $this->_elementTypesByRefHandle[$refHandle] = null;
    }

    /**
     * Parses a string for element [reference tags](http://craftcms.com/docs/reference-tags).
     *
     * @param string $str The string to parse
     * @param int|null $defaultSiteId The default site ID to query the elements in
     * @return string The parsed string
     */
    public function parseRefs(string $str, int $defaultSiteId = null): string
    {
        if (!StringHelper::contains($str, '{')) {
            return $str;
        }

        // First catalog all of the ref tags by element type, ref type ('id' or 'ref'), and ref name,
        // and replace them with placeholder tokens
        $sitesService = Craft::$app->getSites();
        $allRefTagTokens = [];
        $str = preg_replace_callback(
            '/\{([\w\\\\]+)\:([^@\:\}]+)(?:@([^\:\}]+))?(?:\:([^\}]+))?\}/',
            function($matches) use (
                $defaultSiteId,
                $sitesService,
                &$allRefTagTokens
            ) {
                // Does it already have a full element type class name?
                if (is_subclass_of($matches[1], ElementInterface::class)) {
                    $elementType = $matches[1];
                } else if (($elementType = $this->getElementTypeByRefHandle($matches[1])) === null) {
                    // Leave the tag alone
                    return $matches[0];
                }

                // Get the site
                if (!empty($matches[3])) {
                    if (is_numeric($matches[3])) {
                        $siteId = (int)$matches[3];
                    } else {
                        try {
                            if (StringHelper::isUUID($matches[3])) {
                                $site = $sitesService->getSiteByUid($matches[3]);
                            } else {
                                $site = $sitesService->getSiteByHandle($matches[3]);
                            }
                        } catch (SiteNotFoundException $e) {
                            $site = null;
                        }
                        if (!$site) {
                            // Leave the tag alone
                            return $matches[0];
                        }
                        $siteId = $site->id;
                    }
                } else {
                    $siteId = $defaultSiteId;
                }

                $refType = is_numeric($matches[2]) ? 'id' : 'ref';
                $token = '{' . StringHelper::randomString(9) . '}';
                $allRefTagTokens[$siteId][$elementType][$refType][$matches[2]][] = [$token, $matches];

                return $token;
            }, $str, -1, $count);

        if ($count === 0) {
            // No ref tags
            return $str;
        }

        // Now swap them with the resolved values
        $search = [];
        $replace = [];

        foreach ($allRefTagTokens as $siteId => $siteTokens) {
            foreach ($siteTokens as $elementType => $tokensByType) {
                /** @var Element|string $elementType */
                foreach ($tokensByType as $refType => $tokensByName) {
                    // Get the elements, indexed by their ref value
                    $refNames = array_keys($tokensByName);
                    $elementQuery = $elementType::find()
                        ->siteId($siteId)
                        ->anyStatus();

                    if ($refType === 'id') {
                        $elementQuery->id($refNames);
                    } else {
                        $elementQuery->ref($refNames);
                    }

                    $elements = ArrayHelper::index($elementQuery->all(), $refType);

                    // Now append new token search/replace strings
                    foreach ($tokensByName as $refName => $tokens) {
                        $element = $elements[$refName] ?? null;

                        foreach ($tokens as list($token, $matches)) {
                            $search[] = $token;
                            $replace[] = $this->_getRefTokenReplacement($element, $matches);
                        }
                    }
                }
            }
        }

        // Swap the tokens with the references
        $str = str_replace($search, $replace, $str);

        return $str;
    }

    /**
     * Stores a placeholder element that element queries should use instead of populating a new element with a
     * matching ID and site ID.
     *
     * This is used by Live Preview and Sharing features.
     *
     * @param ElementInterface $element The element currently being edited by Live Preview.
     * @throws InvalidArgumentException if the element is missing an ID
     * @see getPlaceholderElement()
     */
    public function setPlaceholderElement(ElementInterface $element)
    {
        /** @var Element $element */
        // Won't be able to do anything with this if it doesn't have an ID or site ID
        if (!$element->id || !$element->siteId) {
            throw new InvalidArgumentException('Placeholder element is missing an ID');
        }

        $this->_placeholderElements[$element->getSourceId()][$element->siteId] = $element;

        if ($element->uri) {
            $this->_placeholderUris[$element->uri][$element->siteId] = $element;
        }
    }

    /**
     * Returns all placeholder elements.
     *
     * @return ElementInterface[]
     * @since 3.2.5
     */
    public function getPlaceholderElements(): array
    {
        if ($this->_placeholderElements === null) {
            return [];
        }

        return call_user_func_array('array_merge', $this->_placeholderElements);
    }

    /**
     * Returns a placeholder element by its ID and site ID.
     *
     * @param int $sourceId The element’s ID
     * @param int $siteId The element’s site ID
     * @return ElementInterface|null The placeholder element if one exists, or null.
     * @see setPlaceholderElement()
     */
    public function getPlaceholderElement(int $sourceId, int $siteId)
    {
        return $this->_placeholderElements[$sourceId][$siteId] ?? null;
    }

    /**
     * Eager-loads additional elements onto a given set of elements.
     *
     * @param string $elementType The root element type class
     * @param ElementInterface[] $elements The root element models that should be updated with the eager-loaded elements
     * @param string|array $with Dot-delimited paths of the elements that should be eager-loaded into the root elements
     */
    public function eagerLoadElements(string $elementType, array $elements, $with)
    {
        /** @var Element[] $elements */
        // Bail if there aren't even any elements
        if (empty($elements)) {
            return;
        }

        // Normalize the paths and find any custom path criterias
        if (is_string($with)) {
            $with = StringHelper::split($with);
        }

        $paths = [];
        $pathCriterias = [];
        $countPaths = [];

        foreach ($with as $path) {
            // Using the array syntax?
            // ['foo.bar'] or ['foo.bar', criteria]
            if (is_array($path)) {
                if (!empty($path[1])) {
                    // Is this a count path?
                    if (ArrayHelper::remove($path[1], 'count', false)) {
                        $countPaths[$path[0]] = true;
                    }
                    $pathCriterias['__root__.' . $path[0]] = $path[1];
                }

                $paths[] = $path[0];
            } else {
                $paths[] = $path;
            }
        }

        // Load 'em up!
        $elementsByPath = ['__root__' => $elements];
        $elementTypesByPath = ['__root__' => $elementType];
        $eagerLoadingMapsByPath = [];

        foreach ($paths as $path) {
            $pathSegments = explode('.', $path);
            $totalSegments = count($pathSegments);
            $sourcePath = '__root__';

            foreach ($pathSegments as $segIndex => $segment) {
                $targetPath = $sourcePath . '.' . $segment;
                $pathCriteria = $pathCriterias[$targetPath] ?? [];

                // Are we just fetching the count?
                $getCount = isset($countPaths[$path]) && $segIndex === $totalSegments - 1;

                // Figure out the path mapping wants a custom order
                $useCustomOrder = !$getCount && (
                        !empty($pathCriteria['orderBy']) ||
                        !empty($pathCriteria['order'])
                    );

                // Make sure we haven't already eager-loaded this target path
                if ($getCount || !isset($elementsByPath[$targetPath])) {
                    // Have we already fetched the map from an earlier `count` path?
                    if (array_key_exists($targetPath, $eagerLoadingMapsByPath)) {
                        $map = $eagerLoadingMapsByPath[$targetPath];
                    } else {
                        // Get the eager-loading map from the source element type
                        /** @var Element $sourceElementType */
                        $sourceElementType = $elementTypesByPath[$sourcePath];
                        $map = $eagerLoadingMapsByPath[$targetPath] = $sourceElementType::eagerLoadingMap(array_values($elementsByPath[$sourcePath]), $segment);
                    }

                    if ($map === null) {
                        break;
                    }

                    $targetElementIdsBySourceIds = null;
                    $query = null;
                    $offset = 0;
                    $limit = null;

                    if ($map && !empty($map['map'])) {
                        // Remember the element type in case there are more segments after this
                        $elementTypesByPath[$targetPath] = $map['elementType'];

                        // Loop through the map to find:
                        // - unique target element IDs
                        // - target element IDs indexed by source element IDs
                        $uniqueTargetElementIds = [];
                        $targetElementIdsBySourceIds = [];

                        foreach ($map['map'] as $mapping) {
                            if (!in_array($mapping['target'], $uniqueTargetElementIds, false)) {
                                $uniqueTargetElementIds[] = $mapping['target'];
                            }

                            $targetElementIdsBySourceIds[$mapping['source']][$mapping['target']] = true;
                        }

                        // Get the target elements
                        /** @var Element $targetElementType */
                        $targetElementType = $map['elementType'];
                        /** @var ElementQuery $query */
                        $query = $targetElementType::find();

                        // Default to no order, offset, or limit, but allow the element type/path criteria to override
                        $query->orderBy = null;
                        $query->offset = null;
                        $query->limit = null;

                        $criteria = array_merge(
                            $map['criteria'] ?? [],
                            $pathCriteria
                        );

                        // Save the offset & limit params for later
                        $offset = ArrayHelper::remove($criteria, 'offset', 0);
                        $limit = ArrayHelper::remove($criteria, 'limit');

                        Craft::configure($query, $criteria);

                        if (!$query->siteId) {
                            $query->siteId = reset($elements)->siteId;
                        }

                        $query->andWhere(['elements.id' => $uniqueTargetElementIds]);
                    }

                    if ($getCount) {
                        // Just fetch the target elements’ IDs
                        $targetElementIds = $query ? array_flip($query->ids()) : [];

                        // Loop through the source elements and count up their targets
                        foreach ($elementsByPath[$sourcePath] as $sourceElement) {
                            $count = 0;
                            if (!empty($targetElementIds) && isset($targetElementIdsBySourceIds[$sourceElement->id])) {
                                foreach (array_keys($targetElementIdsBySourceIds[$sourceElement->id]) as $targetElementId) {
                                    if (isset($targetElementIds[$targetElementId])) {
                                        $count++;
                                    }
                                }
                            }
                            $sourceElement->setEagerLoadedElementCount($segment, $count);
                        }
                    } else {
                        /** @var array|ElementInterface[] $targetElements */
                        $targetElements = $query ? $query->asArray()->all() : [];
                        $elementsByPath[$targetPath] = [];

                        // Index the target elements by their IDs if we are using the map-defined order
                        if (!$useCustomOrder) {
                            /** @var array|ElementInterface[] $targetElementsById */
                            $targetElementsById = [];
                            foreach ($targetElements as &$targetElement) {
                                $targetElementsById[$targetElement['id']] = &$targetElement;
                            }
                        }

                        // Tell the source elements about their eager-loaded elements (or lack thereof, as the case may be)
                        foreach ($elementsByPath[$sourcePath] as $sourceElement) {
                            /** @var Element $sourceElement */
                            $targetElementsForSource = [];

                            if (isset($targetElementIdsBySourceIds[$sourceElement->id])) {
                                if ($useCustomOrder) {
                                    // Assign the elements in the order they were returned from the query
                                    foreach ($targetElements as &$targetElement) {
                                        /** @var array|ElementInterface $targetElement */
                                        $targetElementId = ArrayHelper::getValue($targetElement, 'id');
                                        if (isset($targetElementIdsBySourceIds[$sourceElement->id][$targetElementId])) {
                                            $targetElementsForSource[] = &$targetElement;
                                        }
                                    }
                                } else {
                                    // Assign the elements in the order defined by the map
                                    foreach (array_keys($targetElementIdsBySourceIds[$sourceElement->id]) as $targetElementId) {
                                        if (isset($targetElementsById[$targetElementId])) {
                                            $targetElementsForSource[] = &$targetElementsById[$targetElementId];
                                        }
                                    }
                                }

                                // Ignore elements that don't fall within the offset & limit
                                if ($offset || $limit) {
                                    $targetElementsForSource = array_slice($targetElementsForSource, $offset, $limit);
                                }

                                foreach ($targetElementsForSource as &$targetElement) {
                                    // Make sure the element has been instantiated
                                    if (is_array($targetElement)) {
                                        $targetElement = $query->createElement($targetElement);
                                    }

                                    // Store it on $elementsByPath FFR
                                    if (!isset($elementsByPath[$targetPath][$targetElement->id])) {
                                        $elementsByPath[$targetPath][$targetElement->id] = $targetElement;
                                    }
                                }
                                unset($targetElement);
                            }

                            $sourceElement->setEagerLoadedElements($segment, $targetElementsForSource);
                        }
                    }
                }

                if (empty($elementsByPath[$targetPath])) {
                    // Dead end - stop wasting time on this path
                    break;
                }

                // Update the source path
                $sourcePath = $targetPath;
            }
        }
    }

    /**
     * Propagates an element to a different site.
     *
     * @param ElementInterface $element The element to propagate
     * @param int $siteId The site ID that the element should be propagated to
     * @param ElementInterface|false|null $siteElement The element loaded for the propagated site (only pass this if you
     * already had a reason to load it). Set to `false` if it is known to not exist yet.
     * @throws Exception if the element couldn't be propagated
     * @since 3.0.13
     */
    public function propagateElement(ElementInterface $element, int $siteId, $siteElement = null)
    {
        // Get the sites supported by this element
        if (empty($supportedSites = ElementHelper::supportedSitesForElement($element))) {
            throw new Exception('All elements must have at least one site associated with them.');
        }

        // Make sure the element actually supports the site it's being saved in
        $supportedSites = ArrayHelper::index($supportedSites, 'siteId');
        $siteInfo = $supportedSites[(string)$siteId] ?? null;
        if ($siteInfo === null) {
            throw new Exception('Attempting to propagate an element to an unsupported site.');
        }

        $this->_propagateElement($element, $siteInfo, $siteElement);
    }

    /**
     * Saves an element.
     *
     * @param ElementInterface $element The element that is being saved
     * @param bool $runValidation Whether the element should be validated
     * @param bool $propagate Whether the element should be saved across all of its supported sites
     * @param bool|null $updateSearchIndex Whether to update the element search index for the element
     * (this will happen via a background job if this is a web request)
     * @return bool
     * @throws ElementNotFoundException if $element has an invalid $id
     * @throws Exception if the $element doesn’t have any supported sites
     * @throws \Throwable if reasons
     */
    private function _saveElementInternal(ElementInterface $element, bool $runValidation = true, bool $propagate = true, bool $updateSearchIndex = null): bool
    {
        /** @var Element|DraftBehavior|RevisionBehavior $element */
        $isNewElement = !$element->id;

        /** @var DraftBehavior|null $draftBehavior */
        $draftBehavior = $element->getIsDraft() ? $element->getBehavior('draft') : null;

        $db = Craft::$app->getDb();

        // Are we tracking changes?
        // todo: remove the tableExists condition after the next breakpoint
        $trackChanges = (
            !$isNewElement &&
            $element->duplicateOf === null &&
            $element::trackChanges() &&
            ($draftBehavior->trackChanges ?? true) &&
            !($draftBehavior->mergingChanges ?? false) &&
            $db->tableExists(Table::CHANGEDATTRIBUTES)
        );
        $dirtyAttributes = [];

        // Force propagation for new elements
        $propagate = $propagate && $element::isLocalized() && Craft::$app->getIsMultiSite();

        if ($isNewElement) {
            // Give it a UID right away
            if (!$element->uid) {
                $element->uid = StringHelper::UUID();
            }

            if (!$element->getIsDraft() && !$element->getIsRevision()) {
                // Let Matrix fields, etc., know they should be duplicating their values across all sites.
                $element->propagateAll = true;
            }
        }

        // Fire a 'beforeSaveElement' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ELEMENT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_ELEMENT, new ElementEvent([
                'element' => $element,
                'isNew' => $isNewElement
            ]));
        }

        if (!$element->beforeSave($isNewElement)) {
            return false;
        }

        // Get the sites supported by this element
        if (empty($supportedSites = ElementHelper::supportedSitesForElement($element))) {
            throw new Exception('All elements must have at least one site associated with them.');
        }

        // Make sure the element actually supports the site it's being saved in
        $supportedSiteIds = ArrayHelper::getColumn($supportedSites, 'siteId');
        if (!in_array($element->siteId, $supportedSiteIds, false)) {
            throw new Exception('Attempting to save an element in an unsupported site.');
        }

        // If the element only supports a single site, ensure it's enabled for that site
        if (count($supportedSites) === 1) {
            $element->enabledForSite = true;
        }

        // Set a dummy title if there isn't one already and the element type has titles
        if (!$runValidation && $element::hasContent() && $element::hasTitles() && !$element->validate(['title'])) {
            if ($isNewElement) {
                $element->title = Craft::t('app', 'New {type}', ['type' => $element::displayName()]);
            } else {
                $element->title = $element::displayName() . ' ' . $element->id;
            }
        }

        // Validate
        if ($runValidation && !$element->validate()) {
            Craft::info('Element not saved due to validation error: ' . print_r($element->errors, true), __METHOD__);
            return false;
        }

        // Figure out whether we will be updating the search index (and memoize that for nested element saves)
        $oldUpdateSearchIndex = $this->_updateSearchIndex;
        $updateSearchIndex = $this->_updateSearchIndex = $updateSearchIndex ?? $this->_updateSearchIndex ?? true;

        $transaction = Craft::$app->getDb()->beginTransaction();
        $e = null;

        try {
            // No need to save the element record multiple times
            if (!$element->propagating) {
                // Get the element record
                if (!$isNewElement) {
                    $elementRecord = ElementRecord::findOne($element->id);

                    if (!$elementRecord) {
                        throw new ElementNotFoundException("No element exists with the ID '{$element->id}'");
                    }
                } else {
                    $elementRecord = new ElementRecord();
                    $elementRecord->type = get_class($element);
                    $elementRecord->uid = $element->uid;
                }

                // Set the attributes
                $elementRecord->uid = $element->uid;
                $elementRecord->draftId = (int)$element->draftId ?: null;
                $elementRecord->revisionId = (int)$element->revisionId ?: null;
                $elementRecord->fieldLayoutId = $element->fieldLayoutId = (int)($element->fieldLayoutId ?? $element->getFieldLayout()->id ?? 0) ?: null;
                $elementRecord->enabled = (bool)$element->enabled;
                $elementRecord->archived = (bool)$element->archived;

                if ($isNewElement) {
                    if (isset($element->dateCreated)) {
                        $elementRecord->dateCreated = Db::prepareValueForDb($element->dateCreated);
                    }
                    if (isset($element->dateUpdated)) {
                        $elementRecord->dateUpdated = Db::prepareValueForDb($element->dateUpdated);
                    }
                } else if ($element->propagating || $element->resaving) {
                    // Prevent ActiveRecord::prepareForDb() from changing the dateUpdated
                    $elementRecord->markAttributeDirty('dateUpdated');
                } else {
                    // Force a new dateUpdated value
                    $elementRecord->dateUpdated = Db::prepareValueForDb(new \DateTime());
                }

                // Update our list of dirty attributes
                if ($trackChanges) {
                    ArrayHelper::append($dirtyAttributes, ...array_keys($elementRecord->getDirtyAttributes([
                        'fieldLayoutId',
                        'enabled',
                        'archived',
                    ])));
                }

                // Save the element record
                $elementRecord->save(false);

                $dateCreated = DateTimeHelper::toDateTime($elementRecord->dateCreated);

                if ($dateCreated === false) {
                    throw new Exception('There was a problem calculating dateCreated.');
                }

                $dateUpdated = DateTimeHelper::toDateTime($elementRecord->dateUpdated);

                if ($dateUpdated === false) {
                    throw new Exception('There was a problem calculating dateUpdated.');
                }

                // Save the new dateCreated and dateUpdated dates on the model
                $element->dateCreated = $dateCreated;
                $element->dateUpdated = $dateUpdated;

                if ($isNewElement) {
                    // Save the element ID on the element model
                    $element->id = $elementRecord->id;

                    // If there's a temp ID, update the URI
                    if ($element->tempId && $element->uri) {
                        $element->uri = str_replace($element->tempId, $element->id, $element->uri);
                        $element->tempId = null;
                    }
                }
            }

            // Save the element's site settings record
            if (!$isNewElement) {
                $siteSettingsRecord = Element_SiteSettingsRecord::findOne([
                    'elementId' => $element->id,
                    'siteId' => $element->siteId,
                ]);
            }

            if (empty($siteSettingsRecord)) {
                // First time we've saved the element for this site
                $siteSettingsRecord = new Element_SiteSettingsRecord();
                $siteSettingsRecord->elementId = $element->id;
                $siteSettingsRecord->siteId = $element->siteId;
            }

            $siteSettingsRecord->slug = $element->slug;
            $siteSettingsRecord->uri = $element->uri;

            // Avoid `enabled` getting marked as dirty if it's not really changing
            $enabledForSite = $element->getEnabledForSite();
            if ($siteSettingsRecord->getIsNewRecord() || $siteSettingsRecord->enabled != $enabledForSite) {
                $siteSettingsRecord->enabled = $enabledForSite;
            }

            // Update our list of dirty attributes
            if ($trackChanges && !$siteSettingsRecord->getIsNewRecord()) {
                ArrayHelper::append($dirtyAttributes, ...array_keys($siteSettingsRecord->getDirtyAttributes([
                    'slug',
                    'uri',
                ])));
                if ($siteSettingsRecord->isAttributeChanged('enabled')) {
                    $dirtyAttributes[] = 'enabledForSite';
                }
            }

            if (!$siteSettingsRecord->save(false)) {
                throw new Exception('Couldn’t save elements’ site settings record.');
            }

            // Save the content
            if ($element::hasContent()) {
                Craft::$app->getContent()->saveContent($element);
            }

            // It is now officially saved
            $element->afterSave($isNewElement);

            // Update the element across the other sites?
            if ($propagate) {
                $element->newSiteIds = [];

                foreach ($supportedSites as $siteInfo) {
                    // Skip the master site
                    if ($siteInfo['siteId'] != $element->siteId) {
                        $this->_propagateElement($element, $siteInfo, $isNewElement ? false : null);
                    }
                }
            }

            // It's now fully saved and propagated
            if (
                !$element->propagating &&
                !$element->duplicateOf &&
                !($draftBehavior->mergingChanges ?? false)
            ) {
                $element->afterPropagate($isNewElement);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
        }

        $this->_updateSearchIndex = $oldUpdateSearchIndex;

        if ($e !== null) {
            throw $e;
        }

        $isDraftOrRevision = ElementHelper::isDraftOrRevision($element);

        if (!$element->propagating) {
            // Delete the rows that don't need to be there anymore
            if (!$isNewElement) {
                Db::deleteIfExists(
                    Table::ELEMENTS_SITES,
                    [
                        'and',
                        ['elementId' => $element->id],
                        ['not', ['siteId' => $supportedSiteIds]]
                    ]
                );

                if ($element::hasContent()) {
                    Db::deleteIfExists(
                        $element->getContentTable(),
                        [
                            'and',
                            ['elementId' => $element->id],
                            ['not', ['siteId' => $supportedSiteIds]]
                        ]
                    );
                }
            }

            if (!$isDraftOrRevision) {
                // Delete any caches involving this element. (Even do this for new elements, since they
                // might pop up in a cached criteria.)
                Craft::$app->getTemplateCaches()->deleteCachesByElement($element);
            }
        }

        // Update search index
        if ($updateSearchIndex && !$isDraftOrRevision) {
            if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                Craft::$app->getSearch()->indexElementAttributes($element);
            } else {
                $queue = Craft::$app->getQueue();
                $job = new UpdateSearchIndex([
                    'elementType' => get_class($element),
                    'elementId' => $element->id,
                    'siteId' => $propagate ? '*' : $element->siteId,
                    'fieldHandles' => $element->getDirtyFields(),
                ]);
                try {
                    $queue->priority(2048)->push($job);
                } catch (NotSupportedException $e) {
                    // The queue probably doesn't support custom push priorities. Try again without one.
                    $queue->push($job);
                }
            }
        }

        // Fire an 'afterSaveElement' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ELEMENT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ELEMENT, new ElementEvent([
                'element' => $element,
                'isNew' => $isNewElement,
            ]));
        }

        // Update the changed attributes & fields
        if ($trackChanges) {
            $userId = Craft::$app->getUser()->getId();
            $timestamp = Db::prepareDateForDb(new \DateTime());
            ArrayHelper::append($dirtyAttributes, ...$element->getDirtyAttributes());

            foreach ($dirtyAttributes as $attributeName) {
                $db->createCommand()
                    ->upsert(Table::CHANGEDATTRIBUTES, [
                        'elementId' => $element->id,
                        'siteId' => $element->siteId,
                        'attribute' => $attributeName,
                    ], [
                        'dateUpdated' => $timestamp,
                        'propagated' => $element->propagating,
                        'userId' => $userId,
                    ], [], false)
                    ->execute();
            }

            if (($fieldLayout = $element->getFieldLayout()) !== null) {
                foreach ($element->getDirtyFields() as $fieldHandle) {
                    if (($field = $fieldLayout->getFieldByHandle($fieldHandle)) !== null) {
                        $db->createCommand()
                            ->upsert(Table::CHANGEDFIELDS, [
                                'elementId' => $element->id,
                                'siteId' => $element->siteId,
                                'fieldId' => $field->id,
                            ], [
                                'dateUpdated' => $timestamp,
                                'propagated' => $element->propagating,
                                'userId' => $userId,
                            ], [], false)
                            ->execute();
                    }
                }
            }
        }

        // Clear the element's record of dirty fields
        $element->markAsClean();

        return true;
    }

    /**
     * Propagates an element to a different site
     *
     * @param ElementInterface $element
     * @param array $siteInfo
     * @param ElementInterface|false|null $siteElement The element loaded for the propagated site
     * @throws Exception if the element couldn't be propagated
     */
    private function _propagateElement(ElementInterface $element, array $siteInfo, $siteElement = null)
    {
        /** @var Element $element */
        // Try to fetch the element in this site
        /** @var Element|null $siteElement */
        if ($siteElement === null && $element->id) {
            $siteElement = $this->getElementById($element->id, get_class($element), $siteInfo['siteId']);
        } else if (!$siteElement) {
            $siteElement = null;
        }

        // If it doesn't exist yet, just clone the master site
        if ($isNewSiteForElement = ($siteElement === null)) {
            $siteElement = clone $element;
            $siteElement->siteId = $siteInfo['siteId'];
            $siteElement->contentId = null;
            $siteElement->enabledForSite = $siteInfo['enabledByDefault'];

            // Keep track of this new site ID
            $element->newSiteIds[] = $siteInfo['siteId'];
        } else if ($element->propagateAll) {
            $oldSiteElement = $siteElement;
            $siteElement = clone $element;
            $siteElement->siteId = $oldSiteElement->siteId;
            $siteElement->contentId = $oldSiteElement->contentId;
            $siteElement->enabledForSite = $oldSiteElement->enabledForSite;
        } else {
            $siteElement->enabled = $element->enabled;
            $siteElement->resaving = $element->resaving;
        }

        // Does the main site's element specify a status for this site?
        $enabledForSite = $element->getEnabledForSite($siteElement->siteId);
        if ($enabledForSite !== null) {
            $siteElement->setEnabledForSite($enabledForSite);
        }

        // Copy any non-translatable field values
        if ($element::hasContent()) {
            if ($isNewSiteForElement) {
                // Copy all the field values
                $siteElement->setFieldValues($element->getFieldValues());
            } else if (($fieldLayout = $element->getFieldLayout()) !== null) {
                // Only copy the non-translatable field values
                foreach ($fieldLayout->getFields() as $field) {
                    /** @var Field $field */
                    // Has this field changed, and does it produce the same translation key as it did for the master element?
                    if (
                        $element->isFieldDirty($field->handle) &&
                        $field->getTranslationKey($siteElement) === $field->getTranslationKey($element)
                    ) {
                        // Copy the master element's value over
                        $siteElement->setFieldValue($field->handle, $element->getFieldValue($field->handle));
                    }
                }
            }
        }

        // Save it
        $siteElement->setScenario(Element::SCENARIO_ESSENTIALS);
        $siteElement->propagating = true;

        if ($this->_saveElementInternal($siteElement, true, false) === false) {
            // Log the errors
            $error = 'Couldn’t propagate element to other site due to validation errors:';
            foreach ($siteElement->getFirstErrors() as $attributeError) {
                $error .= "\n- " . $attributeError;
            }
            Craft::error($error);
            throw new Exception('Couldn’t propagate element to other site.');
        }
    }

    /**
     * Soft-deletes or restores the drafts and revisions of the given element.
     *
     * @param int $sourceId The source element ID
     * @param bool $delete `true` if the drafts/revisions should be soft-deleted; `false` if they should be restored
     */
    private function _cascadeDeleteDraftsAndRevisions(int $sourceId, bool $delete = true)
    {
        $params = [
            'dateDeleted' => $delete ? Db::prepareDateForDb(new \DateTime()) : null,
            'sourceId' => $sourceId,
        ];

        $db = Craft::$app->getDb();

        foreach (['draftId' => Table::DRAFTS, 'revisionId' => Table::REVISIONS] as $fk => $table) {
            if ($db->getIsMysql()) {
                $sql = <<<SQL
UPDATE {{%elements}} [[e]]
INNER JOIN {$table} [[t]] ON [[t.id]] = [[e.{$fk}]]
SET [[e.dateDeleted]] = :dateDeleted
WHERE [[t.sourceId]] = :sourceId
SQL;
            } else {
                $sql = <<<SQL
UPDATE {{%elements}} [[e]]
SET [[dateDeleted]] = :dateDeleted
FROM {$table} [[t]]
WHERE [[t.id]] = [[e.{$fk}]]
AND [[t.sourceId]] = :sourceId
SQL;
            }

            $db->createCommand($sql, $params)->execute();
        }
    }

    /**
     * Returns the replacement for a given reference tag.
     *
     * @param ElementInterface|null $element
     * @param array $matches
     * @return string
     * @see parseRefs()
     */
    private function _getRefTokenReplacement(ElementInterface $element = null, array $matches): string
    {
        if ($element === null) {
            // Put the ref tag back
            return $matches[0];
        }

        if (empty($matches[4]) || !isset($element->{$matches[4]})) {
            // Default to the URL
            return (string)$element->getUrl();
        }

        try {
            $value = $element->{$matches[4]};

            if (is_object($value) && !method_exists($value, '__toString')) {
                throw new Exception('Object of class ' . get_class($value) . ' could not be converted to string');
            }

            return $this->parseRefs((string)$value);
        } catch (\Throwable $e) {
            // Log it
            Craft::error('An exception was thrown when parsing the ref tag "' . $matches[0] . "\":\n" . $e->getMessage(), __METHOD__);

            // Replace the token with the original ref tag
            return $matches[0];
        }
    }
}
