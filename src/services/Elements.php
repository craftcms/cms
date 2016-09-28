<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Element;
use craft\app\base\Field;
use craft\app\db\Query;
use craft\app\base\ElementActionInterface;
use craft\app\elements\Asset;
use craft\app\elements\Category;
use craft\app\base\ElementInterface;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\Entry;
use craft\app\elements\GlobalSet;
use craft\app\elements\MissingElement;
use craft\app\elements\MatrixBlock;
use craft\app\elements\Tag;
use craft\app\elements\User;
use craft\app\errors\ElementNotFoundException;
use craft\app\errors\MissingComponentException;
use craft\app\events\DeleteElementsEvent;
use craft\app\events\ElementEvent;
use craft\app\events\MergeElementsEvent;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\Component as ComponentHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\ElementHelper;
use craft\app\helpers\StringHelper;
use craft\app\records\Element as ElementRecord;
use craft\app\records\Element_SiteSettings as Element_SiteSettingsRecord;
use craft\app\records\StructureElement as StructureElementRecord;
use craft\app\tasks\FindAndReplace;
use craft\app\tasks\UpdateElementSlugsAndUris;
use yii\base\Component;
use yii\base\Exception;

/**
 * The Elements service provides APIs for managing elements.
 *
 * An instance of the Elements service is globally accessible in Craft via [[Application::elements `Craft::$app->getElements()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Elements extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event MergeElementsEvent The event that is triggered after two elements are merged together.
     */
    const EVENT_AFTER_MERGE_ELEMENTS = 'afterMergeElements';

    /**
     * @event DeleteElementsEvent The event that is triggered before one or more elements are deleted.
     */
    const EVENT_BEFORE_DELETE_ELEMENTS = 'beforeDeleteElements';

    /**
     * @event ElementEvent The event that is triggered before an element is saved.
     *
     * You may set [[ElementEvent::isValid]] to `false` to prevent the element from getting saved.
     */
    const EVENT_BEFORE_SAVE_ELEMENT = 'beforeSaveElement';

    /**
     * @event ElementEvent The event that is triggered after an element is saved.
     */
    const EVENT_AFTER_SAVE_ELEMENT = 'afterSaveElement';

    /**
     * @event ElementActionEvent The event that is triggered before an element action is performed.
     *
     * You may set [[ElementActionEvent::isValid]] to `false` to prevent the action from being performed.
     */
    const EVENT_BEFORE_PERFORM_ACTION = 'beforePerformAction';

    /**
     * @event ElementActionEvent The event that is triggered after an element action is performed.
     */
    const EVENT_AFTER_PERFORM_ACTION = 'afterPerformAction';

    // Properties
    // =========================================================================

    /**
     * @var array
     */
    private $_placeholderElements;

    // Public Methods
    // =========================================================================

    /**
     * Creates an element with a given config.
     *
     * @param mixed $config The field’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @return ElementInterface The element
     */
    public function createElement($config)
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        try {
            return ComponentHelper::createComponent($config, ElementInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();

            return MissingElement::create($config);
        }
    }

    // Finding Elements
    // -------------------------------------------------------------------------

    /**
     * Returns an element by its ID.
     *
     * If no element type is provided, the method will first have to run a DB query to determine what type of element
     * the $elementId is, so you should definitely pass it if it’s known.
     *
     * The element’s status will not be a factor when using this method.
     *
     * @param integer                      $elementId   The element’s ID.
     * @param string|null|ElementInterface $elementType The element class.
     * @param integer|null                 $siteId      The site to fetch the element in.
     *                                                  Defaults to the current site.
     *
     * @return ElementInterface|null The matching element, or `null`.
     */
    public function getElementById($elementId, $elementType = null, $siteId = null)
    {
        if (!$elementId) {
            return null;
        }

        if (!$elementType) {
            $elementType = $this->getElementTypeById($elementId);

            if (!$elementType) {
                return null;
            }
        }

        /** @var ElementQuery $query */
        $query = $elementType::find();
        $query->id = $elementId;
        $query->siteId = $siteId;
        $query->status = null;
        $query->enabledForSite = false;

        return $query->one();
    }

    /**
     * Returns an element by its URI.
     *
     * @param string       $uri         The element’s URI.
     * @param integer|null $siteId      The site to look for the URI in, and to return the element in.
     *                                  Defaults to the current site.
     * @param boolean      $enabledOnly Whether to only look for an enabled element. Defaults to `false`.
     *
     * @return ElementInterface|null The matching element, or `null`.
     */
    public function getElementByUri($uri, $siteId = null, $enabledOnly = false)
    {
        if ($uri === '') {
            $uri = '__home__';
        }

        if (!$siteId) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        // First get the element ID and type

        $conditions = [
            'and',
            'elements_i18n.uri = :uri',
            'elements_i18n.siteId = :siteId'
        ];

        $params = [
            ':uri' => $uri,
            ':siteId' => $siteId
        ];

        if ($enabledOnly) {
            $conditions[] = 'elements_i18n.enabled = 1';
            $conditions[] = 'elements.enabled = 1';
            $conditions[] = 'elements.archived = 0';
        }

        $result = (new Query())
            ->select('elements.id, elements.type')
            ->from('{{%elements}} elements')
            ->innerJoin('{{%elements_i18n}} elements_i18n', 'elements_i18n.elementId = elements.id')
            ->where($conditions, $params)
            ->one();

        if ($result) {
            // Return the actual element
            return $this->getElementById($result['id'], $result['type'], $siteId);
        }

        return null;
    }

    /**
     * Returns the class(es) of an element with a given ID(s).
     *
     * If a single ID is passed in (an int), then a single element class will be returned (a string), or `null` if
     * no element exists by that ID.
     *
     * If an array is passed in, then an array will be returned.
     *
     * @param integer|array $elementId An element’s ID, or an array of elements’ IDs.
     *
     * @return ElementInterface|ElementInterface[]|Element|Element[]|null The element class(es).
     */
    public function getElementTypeById($elementId)
    {
        if (is_array($elementId)) {
            return (new Query())
                ->select('type')
                ->distinct(true)
                ->from('{{%elements}}')
                ->where(['in', 'id', $elementId])
                ->column();
        } else {
            return (new Query())
                ->select('type')
                ->from('{{%elements}}')
                ->where(['id' => $elementId])
                ->scalar();
        }
    }

    /**
     * Returns an element’s URI for a given site.
     *
     * @param integer $elementId The element’s ID.
     * @param integer $siteId    The site to search for the element’s URI in.
     *
     * @return string|null The element’s URI, or `null`.
     */
    public function getElementUriForSite($elementId, $siteId)
    {
        return (new Query())
            ->select('uri')
            ->from('{{%elements_i18n}}')
            ->where(['elementId' => $elementId, 'siteId' => $siteId])
            ->scalar();
    }

    /**
     * Returns the site IDs that a given element is enabled in.
     *
     * @param integer $elementId The element’s ID.
     *
     * @return integer[] The site IDs that the element is enabled in. If the element could not be found, an empty array
     *                   will be returned.
     */
    public function getEnabledSiteIdsForElement($elementId)
    {
        return (new Query())
            ->select('siteId')
            ->from('{{%elements_i18n}}')
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
     * - Ensuring the element has a title if its type [[Element::hasTitles() has titles]], and giving it a
     *   default title in the event that $validateContent is set to `false`
     * - Saving a row in the `elements` table
     * - Assigning the element’s ID on the element model, if it’s a new element
     * - Assigning the element’s ID on the element’s content model, if there is one and it’s a new set of content
     * - Updating the search index with new keywords from the element’s content
     * - Setting a unique URI on the element, if it’s supposed to have one.
     * - Saving the element’s row(s) in the `elements_i18n` and `content` tables
     * - Deleting any rows in the `elements_i18n` and `content` tables that no longer need to be there
     * - Calling the field types’ [[Field::onAfterElementSave() onAfterElementSave()]] methods
     * - Cleaing any template caches that the element was involved in
     *
     * This method should be called by a service’s “saveX()” method, _after_ it is done validating any attributes on
     * the element that are of particular concern to its element type. For example, if the element were an entry,
     * saveElement() should be called only after the entry’s sectionId and typeId attributes had been validated to
     * ensure that they point to valid section and entry type IDs.
     *
     * @param ElementInterface $element                 The element that is being saved
     * @param boolean|null     $validateContent         Whether the element's content should be validated. If left 'null', it
     *                                                  will depend on whether the element is enabled or not.
     *
     * @return boolean
     * @throws ElementNotFoundException if $element has an invalid $id
     * @throws Exception if the $element doesn’t have any supported sites
     * @throws \Exception if reasons
     */
    public function saveElement(ElementInterface $element, $validateContent = null)
    {
        /** @var Element $element */
        $isNewElement = !$element->id;

        // Validation
        $element->validate();
        if ($element->hasContent() && ($validateContent || ($validateContent === null && $element->enabled))) {
            Craft::$app->getContent()->validateContent($element);
        }
        if ($element->hasErrors()) {
            return false;
        }

        // Make sure the element is cool with this
        if (!$element->beforeSave()) {
            return false;
        }

        // Set a dummy title if there isn't one already and the element type has titles
        if ($element->hasContent() && $element->hasTitles() && !$element->validate(['title'])) {
            if ($isNewElement) {
                $element->title = 'New '.$element->classHandle();
            } else {
                $element->title = $element->classHandle().' '.$element->id;
            }
        }

        // Get the element record
        if (!$isNewElement) {
            $elementRecord = ElementRecord::findOne([
                'id' => $element->id,
                'type' => $element::className()
            ]);

            if (!$elementRecord) {
                throw new ElementNotFoundException("No element exists with the ID '{$element->id}'");
            }
        } else {
            $elementRecord = new ElementRecord();
            $elementRecord->type = $element::className();
        }

        // Set the attributes
        $elementRecord->enabled = (bool)$element->enabled;
        $elementRecord->archived = (bool)$element->archived;

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeSaveElement' event
            $event = new ElementEvent([
                'element' => $element,
                'isNew' => $isNewElement
            ]);

            $this->trigger(self::EVENT_BEFORE_SAVE_ELEMENT, $event);

            // Is the event giving us the go-ahead?
            if ($event->isValid) {
                // Save the element record
                $success = $elementRecord->save(false);

                if ($success) {
                    // Save the new dateCreated and dateUpdated dates on the model
                    $element->dateCreated = DateTimeHelper::toDateTime($elementRecord->dateCreated);
                    $element->dateUpdated = DateTimeHelper::toDateTime($elementRecord->dateUpdated);

                    if ($isNewElement) {
                        // Save the element ID on the element model, in case {id} is in the URL format
                        $element->id = $elementRecord->id;
                    }

                    // Update the site settings records and content

                    // We're saving all of the element's site settings here to ensure that they all exist and to update the URI in
                    // the event that the URL format includes some value that just changed

                    if (!$isNewElement) {
                        $siteSettingsRecords = Element_SiteSettingsRecord::find()
                            ->where([
                                'elementId' => $element->id
                            ])
                            ->indexBy('siteId')
                            ->all();
                    } else {
                        $siteSettingsRecords = [];
                    }

                    $masterSiteId = $element->siteId;

                    $supportedSites = ElementHelper::getSupportedSitesForElement($element);

                    if (!$supportedSites) {
                        throw new Exception('All elements must have at least one site associated with them.');
                    }

                    $supportedSiteIds = [];

                    foreach ($supportedSites as $siteInfo) {
                        $supportedSiteIds[] = $siteInfo['siteId'];
                    }

                    // Make sure the element actually supports this site
                    if (array_search($element->siteId, $supportedSiteIds) === false) {
                        throw new Exception('Attempting to save an element in an unsupported site.');
                    }

                    if ($element::hasContent()) {
                        // Are we dealing with translations?
                        if ($element::isLocalized() && Craft::$app->getIsMultiSite()) {
                            $translateContent = true;

                            // Get all of the field translation keys
                            $masterFieldTranslationKeys = [];

                            foreach ($element->getFieldLayout()->getFields() as $field) {
                                /** @var Field $field */
                                if ($field->getContentColumnType()) {
                                    $masterFieldTranslationKeys[$field->id] = $field->getTranslationKey($element);
                                }
                            }
                        } else {
                            $translateContent = false;
                        }

                        $masterFieldValues = $element->getFieldValues();
                    }

                    foreach ($supportedSites as $siteInfo) {
                        if (isset($siteSettingsRecords[$siteInfo['siteId']])) {
                            $siteSettingsRecord = $siteSettingsRecords[$siteInfo['siteId']];
                        } else {
                            $siteSettingsRecord = new Element_SiteSettingsRecord();

                            $siteSettingsRecord->elementId = $element->id;
                            $siteSettingsRecord->siteId = $siteInfo['siteId'];
                            $siteSettingsRecord->enabled = $siteInfo['enabledByDefault'];
                        }

                        // Is this the master site?
                        $isMasterSite = ($siteInfo['siteId'] == $masterSiteId);

                        if ($isMasterSite) {
                            $localizedElement = $element;
                        } else {
                            // Copy the element for this site
                            $localizedElement = $element->copy();
                            $localizedElement->siteId = $siteInfo['siteId'];
                            $localizedElement->contentId = null;

                            if ($siteSettingsRecord->id) {
                                // Keep the original slug
                                $localizedElement->slug = $siteSettingsRecord->slug;
                            } else {
                                // Default to the master site's slug
                                $localizedElement->slug = $element->slug;
                            }
                        }

                        if ($element->hasContent()) {
                            if (!$isMasterSite) {
                                $fieldValues = false;

                                if (!$isNewElement) {
                                    // Do we already have a content row for this site?
                                    $fieldValues = Craft::$app->getContent()->getContentRow($localizedElement);

                                    if ($fieldValues !== false) {
                                        $localizedElement->contentId = $fieldValues['id'];
                                        if (isset($fieldValues['title'])) {
                                            $localizedElement->title = $fieldValues['title'];
                                        }
                                        unset($fieldValues['id'], $fieldValues['elementId'], $fieldValues['siteId'], $fieldValues['title'], $fieldValues['dateCreated'], $fieldValues['dateUpdated'], $fieldValues['uid']);

                                        // Are we worried about translations?
                                        if ($translateContent) {
                                            foreach ($localizedElement->getFieldLayout()->getFields() as $field) {
                                                /** @var Field $field */
                                                if (isset($masterFieldTranslationKeys[$field->id])) {
                                                    // Does this field produce the same translation key as it did for the master element?
                                                    $fieldTranslationKey = $field->getTranslationKey($localizedElement);

                                                    if ($fieldTranslationKey == $masterFieldTranslationKeys[$field->id]) {
                                                        // Copy the master element's value over
                                                        $fieldValues[$field->handle] = $masterFieldValues[$field->handle];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($fieldValues === false) {
                                    // Just default to whatever's on the master element we're saving here
                                    $fieldValues = $masterFieldValues;
                                }

                                $localizedElement->setFieldValues($fieldValues);
                            }

                            Craft::$app->getContent()->saveContent($localizedElement, false);
                        }

                        // Capture the original slug, in case it's entirely composed of invalid characters
                        $originalSlug = $localizedElement->slug;

                        // Clean up the slug
                        ElementHelper::setValidSlug($localizedElement);

                        // If the slug was entirely composed of invalid characters, it will be blank now.
                        if ($originalSlug && !$localizedElement->slug) {
                            $localizedElement->slug = $originalSlug;
                            $element->addError('slug', Craft::t('app', '{attribute} is invalid.', ['attribute' => Craft::t('app', 'Slug')]));

                            // Don't bother with any of the other sites
                            $success = false;
                            break;
                        }

                        ElementHelper::setUniqueUri($localizedElement);

                        $siteSettingsRecord->slug = $localizedElement->slug;
                        $siteSettingsRecord->uri = $localizedElement->uri;

                        if ($isMasterSite) {
                            $siteSettingsRecord->enabled = (bool)$element->enabledForSite;
                        }

                        $success = $siteSettingsRecord->save();

                        if (!$success) {
                            // Pass any validation errors on to the element
                            $element->addErrors($siteSettingsRecord->getErrors());

                            // Don't bother with any of the other sites
                            break;
                        }
                    }

                    // Update the search index
                    Craft::$app->getSearch()->indexElementAttributes($element);

                    if (!$isNewElement) {
                        // Delete the rows that don't need to be there anymore

                        Craft::$app->getDb()->createCommand()
                            ->delete(
                                '{{%elements_i18n}}',
                                [
                                    'and',
                                    'elementId = :elementId',
                                    ['not in', 'siteId', $supportedSiteIds]
                                ],
                                [
                                    ':elementId' => $element->id
                                ])
                            ->execute();

                        if ($element::hasContent()) {
                            Craft::$app->getDb()->createCommand()
                                ->delete(
                                    $element->getContentTable(),
                                    [
                                        'and',
                                        'elementId = :elementId',
                                        ['not in', 'siteId', $supportedSiteIds]
                                    ],
                                    [
                                        ':elementId' => $element->id
                                    ])
                                ->execute();
                        }
                    }

                    // Tell the element it was just saved
                    $element->afterSave();

                    // Finally, delete any caches involving this element. (Even do this for new elements, since they
                    // might pop up in a cached criteria.)
                    Craft::$app->getTemplateCaches()->deleteCachesByElement($element);
                }
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we saved the user, in case something changed
            // in onBeforeSaveElement
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterSaveElement' event
            $this->trigger(self::EVENT_AFTER_SAVE_ELEMENT, new ElementEvent([
                'element' => $element,
                'isNew' => $isNewElement,
            ]));
        } else {
            if ($isNewElement) {
                $element->id = null;

                if ($element->hasContent()) {
                    $element->contentId = null;
                }
            }
        }

        return $success;
    }

    /**
     * Updates an element’s slug and URI, along with any descendants.
     *
     * @param ElementInterface $element           The element to update.
     * @param boolean          $updateOtherSites  Whether the element’s other sites should also be updated.
     * @param boolean          $updateDescendants Whether the element’s descendants should also be updated.
     * @param boolean          $asTask            Whether the element’s slug and URI should be updated via a background task.
     *
     * @return void
     */
    public function updateElementSlugAndUri(ElementInterface $element, $updateOtherSites = true, $updateDescendants = true, $asTask = false)
    {
        /** @var Element $element */
        if ($asTask) {
            Craft::$app->getTasks()->queueTask([
                'type' => UpdateElementSlugsAndUris::class,
                'elementId' => $element->id,
                'elementType' => $element::className(),
                'siteId' => $element->siteId,
                'updateOtherSites' => $updateOtherSites,
                'updateDescendants' => $updateDescendants,
            ]);

            return;
        }

        ElementHelper::setUniqueUri($element);

        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%elements_i18n}}',
                [
                    'slug' => $element->slug,
                    'uri' => $element->uri
                ],
                [
                    'elementId' => $element->id,
                    'siteId' => $element->siteId
                ])
            ->execute();

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
     *
     * @return void
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
     * @param ElementInterface $element          The element whose descendants should be updated.
     * @param boolean          $updateOtherSites Whether the element’s other sites should also be updated.
     * @param boolean          $asTask           Whether the descendants’ slugs and URIs should be updated via a background task.
     *
     * @return void
     */
    public function updateDescendantSlugsAndUris(ElementInterface $element, $updateOtherSites = true, $asTask = false)
    {
        /** @var Element $element */
        /** @var ElementQuery $query */
        $query = $element::find()
            ->descendantOf($element)
            ->descendantDist(1)
            ->status(null)
            ->enabledForSite(false)
            ->siteId($element->siteId);

        if ($asTask) {
            $childIds = $query->ids();

            if ($childIds) {
                Craft::$app->getTasks()->queueTask([
                    'type' => UpdateElementSlugsAndUris::class,
                    'elementId' => $childIds,
                    'elementType' => $element::className(),
                    'siteId' => $element->siteId,
                    'updateOtherSites' => $updateOtherSites,
                    'updateDescendants' => true,
                ]);
            }
        } else {
            $children = $query->all();

            foreach ($children as $child) {
                $this->updateElementSlugAndUri($child, $updateOtherSites, true, false);
            }
        }
    }

    /**
     * Merges two elements together.
     *
     * This method will update the following:
     *
     * - Any relations involving the merged element
     * - Any structures that contain the merged element
     * - Any reference tags in textual custom fields referencing the merged element
     *
     * @param integer $mergedElementId     The ID of the element that is going away.
     * @param integer $prevailingElementId The ID of the element that is sticking around.
     *
     * @return boolean Whether the elements were merged successfully.
     * @throws \Exception if reasons
     */
    public function mergeElementsByIds($mergedElementId, $prevailingElementId)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Update any relations that point to the merged element
            $relations = (new Query())
                ->select(['id', 'fieldId', 'sourceId', 'sourceSiteId'])
                ->from('{{%relations}}')
                ->where(['targetId' => $mergedElementId])
                ->all();

            foreach ($relations as $relation) {
                // Make sure the persisting element isn't already selected in the same field
                $persistingElementIsRelatedToo = (new Query())
                    ->from('{{%relations}}')
                    ->where([
                        'fieldId' => $relation['fieldId'],
                        'sourceId' => $relation['sourceId'],
                        'sourceSiteId' => $relation['sourceSiteId'],
                        'targetId' => $prevailingElementId
                    ])
                    ->exists();

                if (!$persistingElementIsRelatedToo) {
                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%relations}}',
                            [
                                'targetId' => $prevailingElementId
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
                ->from('{{%structureelements}}')
                ->where(['elementId' => $mergedElementId])
                ->all();

            foreach ($structureElements as $structureElement) {
                // Make sure the persisting element isn't already a part of that structure
                $persistingElementIsInStructureToo = (new Query())
                    ->from('{{%structureElements}}')
                    ->where([
                        'structureId' => $structureElement['structureId'],
                        'elementId' => $prevailingElementId
                    ])
                    ->exists();

                if (!$persistingElementIsInStructureToo) {
                    Craft::$app->getDb()->createCommand()
                        ->update('{{%relations}}',
                            [
                                'elementId' => $prevailingElementId
                            ],
                            [
                                'id' => $structureElement['id']
                            ])
                        ->execute();
                }
            }

            // Update any reference tags
            $elementType = $this->getElementTypeById($prevailingElementId);

            if ($elementType && ($elementTypeHandle = $elementType::classHandle())) {
                $refTagPrefix = "{{$elementTypeHandle}:";

                Craft::$app->getTasks()->queueTask([
                    'type' => FindAndReplace::class,
                    'description' => Craft::t('app', 'Updating element references'),
                    'find' => $refTagPrefix.$mergedElementId.':',
                    'replace' => $refTagPrefix.$prevailingElementId.':',
                ]);

                Craft::$app->getTasks()->queueTask([
                    'type' => FindAndReplace::class,
                    'description' => Craft::t('app', 'Updating element references'),
                    'find' => $refTagPrefix.$mergedElementId.'}',
                    'replace' => $refTagPrefix.$prevailingElementId.'}',
                ]);
            }

            // Fire an 'afterMergeElements' event
            $this->trigger(self::EVENT_AFTER_MERGE_ELEMENTS,
                new MergeElementsEvent([
                    'mergedElementId' => $mergedElementId,
                    'prevailingElementId' => $prevailingElementId
                ]));

            // Now delete the merged element
            $success = $this->deleteElementById($mergedElementId);

            $transaction->commit();

            return $success;
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * Deletes an element(s) by its ID(s).
     *
     * @param integer|array $elementIds The element’s ID, or an array of elements’ IDs.
     *
     * @return boolean Whether the element(s) were deleted successfully.
     * @throws \Exception
     */
    public function deleteElementById($elementIds)
    {
        if (!$elementIds) {
            return false;
        }

        if (!is_array($elementIds)) {
            $elementIds = [$elementIds];
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeDeleteElements' event
            $this->trigger(self::EVENT_BEFORE_DELETE_ELEMENTS,
                new DeleteElementsEvent([
                    'elementIds' => $elementIds
                ]));

            // First delete any structure nodes with these elements, so NestedSetBehavior can do its thing. We need to
            // go one-by-one in case one of theme deletes the record of another in the process.
            foreach ($elementIds as $elementId) {
                /** @var StructureElementRecord[] $records */
                $records = StructureElementRecord::findAll([
                    'elementId' => $elementId
                ]);

                foreach ($records as $record) {
                    // If this element still has any children, move them up before the one getting deleted.
                    /** @var StructureElementRecord[] $children */
                    $children = $record->children()->all();

                    foreach ($children as $child) {
                        $child->insertBefore($record);
                    }

                    // Delete this element's node
                    $record->deleteWithChildren();
                }
            }

            // Delete the caches before they drop their elementId relations (passing `false` because there's no chance
            // this element is suddenly going to show up in a new query)
            Craft::$app->getTemplateCaches()->deleteCachesByElementId($elementIds, false);

            // Now delete the rows in the elements table
            if (count($elementIds) == 1) {
                $condition = ['id' => $elementIds[0]];
                $matrixBlockCondition = ['ownerId' => $elementIds[0]];
                $searchIndexCondition = ['elementId' => $elementIds[0]];
            } else {
                $condition = ['in', 'id', $elementIds];
                $matrixBlockCondition = ['in', 'ownerId', $elementIds];
                $searchIndexCondition = ['in', 'elementId', $elementIds];
            }

            // First delete any Matrix blocks that belong to this element(s)
            $matrixBlockIds = (new Query())
                ->select('id')
                ->from('{{%matrixblocks}}')
                ->where($matrixBlockCondition)
                ->column();

            if ($matrixBlockIds) {
                Craft::$app->getMatrix()->deleteBlockById($matrixBlockIds);
            }

            // Delete the elements table rows, which will cascade across all other InnoDB tables
            $affectedRows = Craft::$app->getDb()->createCommand()
                ->delete('{{%elements}}', $condition)
                ->execute();

            // The searchindex table is MyISAM, though
            Craft::$app->getDb()->createCommand()
                ->delete('{{%searchindex}}', $searchIndexCondition)
                ->execute();

            $transaction->commit();

            return (bool)$affectedRows;
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    // Element classes
    // -------------------------------------------------------------------------

    /**
     * Returns all available element classes.
     *
     * @return ElementInterface[] The available element classes.
     */
    public function getAllElementTypes()
    {
        // TODO: Come up with a way for plugins to add more element classes
        return [
            Asset::class,
            Category::class,
            Entry::class,
            GlobalSet::class,
            MatrixBlock::class,
            Tag::class,
            User::class,
        ];
    }

    // Element Actions
    // -------------------------------------------------------------------------

    /**
     * Creates an element action with a given config.
     *
     * @param mixed $config The element action’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @return ElementActionInterface The element action
     */
    public function createAction($config)
    {
        return ComponentHelper::createComponent($config, ElementActionInterface::class);
    }

    // Misc
    // -------------------------------------------------------------------------

    /**
     * Returns an element class by its handle.
     *
     * @param string $handle The element class handle
     *
     * @return ElementInterface|null The element class, or null if it could not be found
     */
    public function getElementTypeByHandle($handle)
    {
        foreach ($this->getAllElementTypes() as $class) {
            if (strcasecmp($class::classHandle(), $handle) === 0) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Parses a string for element [reference tags](http://craftcms.com/docs/reference-tags).
     *
     * @param string $str The string to parse.
     *
     * @return string The parsed string.
     */
    public function parseRefs($str)
    {
        if (StringHelper::contains($str, '{')) {
            global $refTagsByElementHandle;
            $refTagsByElementHandle = [];

            $str = preg_replace_callback('/\{(\w+)\:([^\:\}]+)(?:\:([^\:\}]+))?\}/',
                function ($matches) {
                    global $refTagsByElementHandle;

                    if (strpos($matches[1], '_') === false) {
                        $elementTypeHandle = ucfirst($matches[1]);
                    } else {
                        $elementTypeHandle = preg_replace_callback('/^\w|_\w/', function ($matches) {
                            return strtoupper($matches[0]);
                        }, $matches[1]);
                    }

                    $token = '{'.StringHelper::randomString(9).'}';

                    $refTagsByElementHandle[$elementTypeHandle][] = [
                        'token' => $token,
                        'matches' => $matches
                    ];

                    return $token;
                }, $str);

            if ($refTagsByElementHandle) {
                $search = [];
                $replace = [];

                $things = ['id', 'ref'];

                foreach ($refTagsByElementHandle as $elementTypeHandle => $refTags) {
                    $elementType = $this->getElementTypeByHandle($elementTypeHandle);

                    if (!$elementType) {
                        // Just put the ref tags back the way they were
                        foreach ($refTags as $refTag) {
                            $search[] = $refTag['token'];
                            $replace[] = $refTag['matches'][0];
                        }
                    } else {
                        $refTagsById = [];
                        $refTagsByRef = [];

                        foreach ($refTags as $refTag) {
                            // Searching by ID?
                            if (is_numeric($refTag['matches'][2])) {
                                $refTagsById[$refTag['matches'][2]][] = $refTag;
                            } else {
                                $refTagsByRef[$refTag['matches'][2]][] = $refTag;
                            }
                        }

                        // Things are about to get silly...
                        foreach ($things as $thing) {
                            $refTagsByThing = ${'refTagsBy'.ucfirst($thing)};

                            if ($refTagsByThing) {
                                $elements = $elementType::find()
                                    ->status(null)
                                    ->limit(null)
                                    ->$thing(array_keys($refTagsByThing))
                                    ->all();

                                $elementsByThing = [];

                                foreach ($elements as $element) {
                                    $elementsByThing[$element->$thing] = $element;
                                }

                                foreach ($refTagsByThing as $thingVal => $refTags) {
                                    if (isset($elementsByThing[$thingVal])) {
                                        $element = $elementsByThing[$thingVal];
                                    } else {
                                        $element = false;
                                    }

                                    foreach ($refTags as $refTag) {
                                        $search[] = $refTag['token'];

                                        if ($element) {
                                            if (!empty($refTag['matches'][3]) && isset($element->{$refTag['matches'][3]})) {
                                                try {
                                                    $value = $element->{$refTag['matches'][3]};

                                                    if (is_object($value) && !method_exists($value, '__toString')) {
                                                        throw new Exception('Object of class '.get_class($value).' could not be converted to string');
                                                    }

                                                    $replace[] = $this->parseRefs((string)$value);
                                                } catch (\Exception $e) {
                                                    // Log it
                                                    Craft::error('An exception was thrown when parsing the ref tag "'.$refTag['matches'][0]."\":\n".$e->getMessage());

                                                    // Replace the token with the original ref tag
                                                    $replace[] = $refTag['matches'][0];
                                                }
                                            } else {
                                                // Default to the URL
                                                $replace[] = $element->getUrl();
                                            }
                                        } else {
                                            $replace[] = $refTag['matches'][0];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $str = str_replace($search, $replace, $str);
            }

            unset ($refTagsByElementHandle);
        }

        return $str;
    }

    /**
     * Stores a placeholder element that [[findElements()]] should use instead of populating a new element with a
     * matching ID and site ID.
     *
     * This is used by Live Preview and Sharing features.
     *
     * @param ElementInterface $element The element currently being edited by Live Preview.
     *
     * @see getPlaceholderElement()
     */
    public function setPlaceholderElement(ElementInterface $element)
    {
        /** @var Element $element */
        // Won't be able to do anything with this if it doesn't have an ID or site ID
        if (!$element->id || !$element->siteId) {
            return;
        }

        $this->_placeholderElements[$element->id][$element->siteId] = $element;
    }

    /**
     * Returns a placeholder element by its ID and site ID.
     *
     * @param integer $id     The element’s ID
     * @param integer $siteId The element’s site ID
     *
     * @return ElementInterface|null The placeholder element if one exists, or null.
     * @see setPlaceholderElement()
     */
    public function getPlaceholderElement($id, $siteId)
    {
        if (isset($this->_placeholderElements[$id][$siteId])) {
            return $this->_placeholderElements[$id][$siteId];
        }

        return null;
    }

    /**
     * Eager-loads additional elements onto a given set of elements.
     *
     * @param ElementInterface|string $elementType The root element type
     * @param ElementInterface[]      $elements    The root element models that should be updated with the eager-loaded elements
     * @param string|array            $with        Dot-delimited paths of the elements that should be eager-loaded into the root elements
     *
     * @return void
     */
    public function eagerLoadElements($elementType, $elements, $with)
    {
        // Bail if there aren't even any elements
        if (!$elements) {
            return;
        }

        // Normalize the paths and find any custom path criterias
        $with = ArrayHelper::toArray($with);
        $paths = [];
        $pathCriterias = [];

        foreach ($with as $path) {
            // Using the array syntax?
            // ['foo.bar'] or ['foo.bar', criteria]
            if (is_array($path)) {
                if (!empty($path[1])) {
                    $pathCriterias['__root__.'.$path[0]] = $path[1];
                }

                $paths[] = $path[0];
            } else {
                $paths[] = $path;
            }
        }

        // Load 'em up!
        $elementsByPath = ['__root__' => $elements];
        $elementTypesByPath = ['__root__' => $elementType::className()];

        foreach ($paths as $path) {
            $pathSegments = explode('.', $path);
            $sourcePath = '__root__';

            foreach ($pathSegments as $segment) {
                $targetPath = $sourcePath.'.'.$segment;

                // Figure out the path mapping wants a custom order
                $useCustomOrder = !empty($pathCriterias[$targetPath]['order']);

                // Make sure we haven't already eager-loaded this target path
                if (!isset($elementsByPath[$targetPath])) {
                    // Guilty until proven innocent
                    $elementsByPath[$targetPath] = $targetElements = $targetElementsById = $targetElementIdsBySourceIds = false;

                    // Get the eager-loading map from the source element type
                    /** @var Element $sourceElementType */
                    $sourceElementType = $elementTypesByPath[$sourcePath];
                    $map = $sourceElementType::getEagerLoadingMap($elementsByPath[$sourcePath], $segment);

                    if ($map && !empty($map['map'])) {
                        // Remember the element type in case there are more segments after this
                        $elementTypesByPath[$targetPath] = $map['elementType'];

                        // Loop through the map to find:
                        // - unique target element IDs
                        // - target element IDs indexed by source element IDs
                        $uniqueTargetElementIds = [];
                        $targetElementIdsBySourceIds = [];

                        foreach ($map['map'] as $mapping) {
                            if (!in_array($mapping['target'], $uniqueTargetElementIds)) {
                                $uniqueTargetElementIds[] = $mapping['target'];
                            }

                            $targetElementIdsBySourceIds[$mapping['source']][] = $mapping['target'];
                        }

                        // Get the target elements
                        $customParams = array_merge(
                        // Default to no order and limit, but allow the element type/path criteria to override
                            ['orderBy' => null, 'limit' => null],
                            (isset($map['criteria']) ? $map['criteria'] : []),
                            (isset($pathCriterias[$targetPath]) ? $pathCriterias[$targetPath] : [])
                        );
                        /** @var Element $targetElementType */
                        $targetElementType = $map['elementType'];
                        /** @var ElementQuery $query */
                        $query = $targetElementType::find()
                            ->configure($customParams);
                        $query->id = $uniqueTargetElementIds;
                        /** @var Element[] $targetElements */
                        $targetElements = $query->all();

                        if ($targetElements) {
                            // Success! Store those elements on $elementsByPath FFR
                            $elementsByPath[$targetPath] = $targetElements;

                            // Index the target elements by their IDs if we are using the map-defined order
                            if (!$useCustomOrder) {
                                $targetElementsById = [];

                                foreach ($targetElements as $targetElement) {
                                    $targetElementsById[$targetElement->id] = $targetElement;
                                }
                            }
                        }
                    }

                    // Tell the source elements about their eager-loaded elements (or lack thereof, as the case may be)
                    foreach ($elementsByPath[$sourcePath] as $sourceElement) {
                        /** @var Element $sourceElement */
                        $sourceElementId = $sourceElement->id;
                        $targetElementsForSource = [];

                        if (isset($targetElementIdsBySourceIds[$sourceElementId])) {
                            if ($useCustomOrder) {
                                // Assign the elements in the order they were returned from the query
                                foreach ($targetElements as $targetElement) {
                                    if (in_array($targetElement->id, $targetElementIdsBySourceIds[$sourceElementId])) {
                                        $targetElementsForSource[] = $targetElement;
                                    }
                                }
                            } else {
                                // Assign the elements in the order defined by the map
                                foreach ($targetElementIdsBySourceIds[$sourceElementId] as $targetElementId) {
                                    if (isset($targetElementsById[$targetElementId])) {
                                        $targetElementsForSource[] = $targetElementsById[$targetElementId];
                                    }
                                }
                            }
                        }

                        $sourceElement->setEagerLoadedElements($segment, $targetElementsForSource);
                    }
                }

                if (!$elementsByPath[$targetPath]) {
                    // Dead end - stop wasting time on this path
                    break;
                }

                // Update the source path
                $sourcePath = $targetPath;
            }
        }
    }
}
