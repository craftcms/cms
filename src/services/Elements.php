<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\base\ElementActionInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\elements\Tag;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\events\ElementEvent;
use craft\events\MergeElementsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use craft\records\Element as ElementRecord;
use craft\records\Element_SiteSettings as Element_SiteSettingsRecord;
use craft\records\StructureElement as StructureElementRecord;
use craft\tasks\FindAndReplace;
use craft\tasks\UpdateElementSlugsAndUris;
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
     * @event RegisterComponentTypesEvent The event that is triggered when registering element types.
     */
    const EVENT_REGISTER_ELEMENT_TYPES = 'registerElementTypes';

    /**
     * @event MergeElementsEvent The event that is triggered after two elements are merged together.
     */
    const EVENT_AFTER_MERGE_ELEMENTS = 'afterMergeElements';

    /**
     * @event ElementEvent The event that is triggered before an element is deleted.
     */
    const EVENT_BEFORE_DELETE_ELEMENT = 'beforeDeleteElement';

    /**
     * @event ElementEvent The event that is triggered after an element is deleted.
     */
    const EVENT_AFTER_DELETE_ELEMENT = 'afterDeleteElement';

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
     * @var array|null
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
     *
     * The element’s status will not be a factor when using this method.
     *
     * @param int         $elementId    The element’s ID.
     * @param string|null $elementType  The element class.
     * @param int|null    $siteId       The site to fetch the element in.
     *                                  Defaults to the current site.
     *
     * @return ElementInterface|null The matching element, or `null`.
     */
    public function getElementById(int $elementId, string $elementType = null, int $siteId = null)
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

        /** @var Element $elementType */
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
     * @param string   $uri             The element’s URI.
     * @param int|null $siteId          The site to look for the URI in, and to return the element in.
     *                                  Defaults to the current site.
     * @param bool     $enabledOnly     Whether to only look for an enabled element. Defaults to `false`.
     *
     * @return ElementInterface|null The matching element, or `null`.
     */
    public function getElementByUri(string $uri, int $siteId = null, bool $enabledOnly = false)
    {
        if ($uri === '') {
            $uri = '__home__';
        }

        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        // First get the element ID and type

        $query = (new Query())
            ->select(['elements.id', 'elements.type'])
            ->from(['{{%elements}} elements'])
            ->innerJoin('{{%elements_i18n}} elements_i18n', '[[elements_i18n.elementId]] = [[elements.id]]')
            ->where([
                'elements_i18n.uri' => $uri,
                'elements_i18n.siteId' => $siteId
            ]);

        if ($enabledOnly) {
            $query->andWhere([
                'elements_i18n.enabled' => '1',
                'elements.enabled' => '1',
                'elements.archived' => '0',
            ]);
        }

        $result = $query->one();

        if (!$result) {
            return null;
        }

        // Return the actual element
        return $this->getElementById($result['id'], $result['type'], $siteId);
    }

    /**
     * Returns the class of an element with a given ID.
     *
     * @param int $elementId The element’s ID
     *
     * @return string|null The element’s class, or null if it could not be found
     */
    public function getElementTypeById(int $elementId)
    {
        $class = (new Query())
            ->select(['type'])
            ->from(['{{%elements}}'])
            ->where(['id' => $elementId])
            ->scalar();

        return $class !== false ? $class : null;
    }

    /**
     * Returns the classes of elements with the given IDs.
     *
     * @param int[] $elementIds The elements’ IDs
     *
     * @return string[]
     */
    public function getElementTypesByIds(array $elementIds): array
    {
        return (new Query())
            ->select(['type'])
            ->distinct(true)
            ->from(['{{%elements}}'])
            ->where(['id' => $elementIds])
            ->column();
    }

    /**
     * Returns an element’s URI for a given site.
     *
     * @param int $elementId The element’s ID.
     * @param int $siteId    The site to search for the element’s URI in.
     *
     * @return string|null The element’s URI, or `null`.
     */
    public function getElementUriForSite(int $elementId, int $siteId)
    {
        return (new Query())
            ->select(['uri'])
            ->from(['{{%elements_i18n}}'])
            ->where(['elementId' => $elementId, 'siteId' => $siteId])
            ->scalar();
    }

    /**
     * Returns the site IDs that a given element is enabled in.
     *
     * @param int $elementId The element’s ID.
     *
     * @return int[] The site IDs that the element is enabled in. If the element could not be found, an empty array
     *                   will be returned.
     */
    public function getEnabledSiteIdsForElement(int $elementId): array
    {
        return (new Query())
            ->select(['siteId'])
            ->from(['{{%elements_i18n}}'])
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
     * Example usage - creating a new entry:
     *
     * ```php
     * $entry = new Entry();
     * $entry->sectionId = 10;
     * $entry->typeId = 1;
     * $entry->authorId = 5;
     * $entry->enabled = true;
     * $entry->title = "Hello World!";
     *
     * $entry->setFieldValues([
     *     'body' => "<p>I can’t believe I literally just called this “Hello World!”.</p>",
     * ]);
     *
     * $success = Craft::$app->elements->saveElement($entry);
     *
     * if (!$success) {
     *     Craft::error('Couldn’t save the entry "'.$entry->title.'"', __METHOD__);
     * }
     * ```
     *
     * @param ElementInterface $element       The element that is being saved
     * @param bool             $runValidation Whether the element should be validated
     *
     * @return bool
     * @throws ElementNotFoundException if $element has an invalid $id
     * @throws Exception if the $element doesn’t have any supported sites
     * @throws \Exception if reasons
     */
    public function saveElement(ElementInterface $element, bool $runValidation = true): bool
    {
        /** @var Element $element */
        $isNewElement = !$element->id;

        // Set a dummy title if there isn't one already and the element type has titles
        if (!$runValidation && $element::hasContent() && $element::hasTitles() && !$element->validate(['title'])) {
            $humanClass = ucfirst(App::humanizeClass(get_class($element)));
            if ($isNewElement) {
                $element->title = Craft::t('app', 'New {class}', ['class' => $humanClass]);
            } else {
                $element->title = "{$humanClass} {$element->id}";
            }
        }

        if ($runValidation && !$element->validate()) {
            Craft::info('Element not saved due to validation error.', __METHOD__);

            return false;
        }

        // Fire a 'beforeSaveElement' event
        $this->trigger(self::EVENT_BEFORE_SAVE_ELEMENT, new ElementEvent([
            'element' => $element,
            'isNew' => $isNewElement
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$element->beforeSave($isNewElement)) {
                $transaction->rollBack();

                return false;
            }

            // Get the element record
            if (!$isNewElement) {
                $elementRecord = ElementRecord::findOne($element->id);

                if (!$elementRecord) {
                    throw new ElementNotFoundException("No element exists with the ID '{$element->id}'");
                }
            } else {
                $elementRecord = new ElementRecord();
                $elementRecord->type = get_class($element);
            }

            // Set the attributes
            $elementRecord->enabled = (bool)$element->enabled;
            $elementRecord->archived = (bool)$element->archived;

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
                // Save the element ID on the element model, in case {id} is in the URL format
                $element->id = $elementRecord->id;
                $element->uid = $elementRecord->uid;
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

            $supportedSites = ElementHelper::supportedSitesForElement($element);

            if (empty($supportedSites)) {
                throw new Exception('All elements must have at least one site associated with them.');
            }

            $supportedSiteIds = [];

            foreach ($supportedSites as $siteInfo) {
                $supportedSiteIds[] = $siteInfo['siteId'];
            }

            $translateContent = false;

            // Make sure the element actually supports this site
            if (!in_array($element->siteId, $supportedSiteIds, false)) {
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
                }

                $masterFieldValues = $element->getFieldValues();
            }

            $contentService = Craft::$app->getContent();

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
                            $fieldValues = $contentService->getContentRow($localizedElement);

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

                                            if ($fieldTranslationKey === $masterFieldTranslationKeys[$field->id]) {
                                                // Copy the master element's value over
                                                /** @noinspection PhpUndefinedVariableInspection */
                                                $fieldValues[$field->handle] = $masterFieldValues[$field->handle];
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($fieldValues === false) {
                            // Just default to whatever's on the master element we're saving here
                            /** @noinspection PhpUndefinedVariableInspection */
                            $fieldValues = $masterFieldValues;
                        }

                        $localizedElement->setFieldValues($fieldValues);
                    }

                    $contentService->saveContent($localizedElement);
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
                    // TODO: this should be caught in validation
                    throw new Exception('Invalid slug: '.$originalSlug);
                }

                // Go ahead and re-do search index keywords to grab things like "title" in
                // a multi-site installs.
                if ($isNewElement) {
                    Craft::$app->getSearch()->indexElementAttributes($localizedElement);
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
                            ['elementId' => $element->id],
                            ['not', ['siteId' => $supportedSiteIds]]
                        ])
                    ->execute();

                if ($element::hasContent()) {
                    Craft::$app->getDb()->createCommand()
                        ->delete(
                            $element->getContentTable(),
                            [
                                'and',
                                ['elementId' => $element->id],
                                ['not', ['siteId' => $supportedSiteIds]]
                            ])
                        ->execute();
                }
            }

            $element->afterSave($isNewElement);

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Delete any caches involving this element. (Even do this for new elements, since they
        // might pop up in a cached criteria.)
        Craft::$app->getTemplateCaches()->deleteCachesByElement($element);

        // Fire an 'afterSaveElement' event
        $this->trigger(self::EVENT_AFTER_SAVE_ELEMENT, new ElementEvent([
            'element' => $element,
            'isNew' => $isNewElement,
        ]));

        return true;
    }

    /**
     * Updates an element’s slug and URI, along with any descendants.
     *
     * @param ElementInterface $element           The element to update.
     * @param bool             $updateOtherSites  Whether the element’s other sites should also be updated.
     * @param bool             $updateDescendants Whether the element’s descendants should also be updated.
     * @param bool             $asTask            Whether the element’s slug and URI should be updated via a background task.
     *
     * @return void
     */
    public function updateElementSlugAndUri(ElementInterface $element, bool $updateOtherSites = true, bool $updateDescendants = true, bool $asTask = false)
    {
        /** @var Element $element */
        if ($asTask) {
            Craft::$app->getTasks()->queueTask([
                'type' => UpdateElementSlugsAndUris::class,
                'elementId' => $element->id,
                'elementType' => get_class($element),
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
     * @param bool             $updateOtherSites Whether the element’s other sites should also be updated.
     * @param bool             $asTask           Whether the descendants’ slugs and URIs should be updated via a background task.
     *
     * @return void
     */
    public function updateDescendantSlugsAndUris(ElementInterface $element, bool $updateOtherSites = true, bool $asTask = false)
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

            if (!empty($childIds)) {
                Craft::$app->getTasks()->queueTask([
                    'type' => UpdateElementSlugsAndUris::class,
                    'elementId' => $childIds,
                    'elementType' => get_class($element),
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
     * @param int $mergedElementId     The ID of the element that is going away.
     * @param int $prevailingElementId The ID of the element that is sticking around.
     *
     * @return bool Whether the elements were merged successfully.
     * @throws \Exception if reasons
     */
    public function mergeElementsByIds(int $mergedElementId, int $prevailingElementId): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Update any relations that point to the merged element
            $relations = (new Query())
                ->select(['id', 'fieldId', 'sourceId', 'sourceSiteId'])
                ->from(['{{%relations}}'])
                ->where(['targetId' => $mergedElementId])
                ->all();

            foreach ($relations as $relation) {
                // Make sure the persisting element isn't already selected in the same field
                $persistingElementIsRelatedToo = (new Query())
                    ->from(['{{%relations}}'])
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
                ->from(['{{%structureelements}}'])
                ->where(['elementId' => $mergedElementId])
                ->all();

            foreach ($structureElements as $structureElement) {
                // Make sure the persisting element isn't already a part of that structure
                $persistingElementIsInStructureToo = (new Query())
                    ->from(['{{%structureElements}}'])
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
            /** @var ElementInterface|null $elementType */
            $elementType = $this->getElementTypeById($prevailingElementId);

            if ($elementType !== null && ($refHandle = $elementType::refHandle()) !== null) {
                $refTagPrefix = "{{$refHandle}:";

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
     * Deletes an element by its ID.
     *
     * @param int         $elementId    The element’s ID
     * @param string|null $elementType  The element class.
     * @param int|null    $siteId       The site to fetch the element in.
     *                                  Defaults to the current site.
     *
     * @return bool Whether the element was deleted successfully
     * @throws \Exception
     */
    public function deleteElementById(int $elementId, string $elementType = null, int $siteId = null): bool
    {
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
                ->from('{{%elements_i18n}}')
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

        return $this->deleteElement($element);
    }

    /**
     * Deletes an element.
     *
     * @param ElementInterface $element The element to be deleted
     *
     * @return bool Whether the element was deleted successfully
     * @throws \Exception
     */
    public function deleteElement(ElementInterface $element): bool
    {
        /** @var Element $element */
        // Fire a 'beforeDeleteElement' event
        $this->trigger(self::EVENT_BEFORE_DELETE_ELEMENT, new ElementEvent([
            'element' => $element,
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            if (!$element->beforeDelete()) {
                $transaction->rollBack();

                return false;
            }

            // First delete any structure nodes with this element, so NestedSetBehavior can do its thing.
            /** @var StructureElementRecord[] $records */
            $records = StructureElementRecord::findAll([
                'elementId' => $element->id
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

            // Delete the caches before they drop their elementId relations (passing `false` because there's no chance
            // this element is suddenly going to show up in a new query)
            Craft::$app->getTemplateCaches()->deleteCachesByElementId($element->id, false);

            // Delete the elements table rows, which will cascade across all other InnoDB tables
            Craft::$app->getDb()->createCommand()
                ->delete('{{%elements}}', ['id' => $element->id])
                ->execute();

            // The searchindex table is probably MyISAM, though
            Craft::$app->getDb()->createCommand()
                ->delete('{{%searchindex}}', ['elementId' => $element->id])
                ->execute();

            $element->afterDelete();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }


        // Fire an 'afterDeleteElement' event
        $this->trigger(self::EVENT_AFTER_DELETE_ELEMENT, new ElementEvent([
            'element' => $element,
        ]));

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

    // Element Actions
    // -------------------------------------------------------------------------

    /**
     * Creates an element action with a given config.
     *
     * @param mixed $config The element action’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @return ElementActionInterface The element action
     */
    public function createAction($config): ElementActionInterface
    {
        /** @var ElementAction $action */
        $action = ComponentHelper::createComponent($config, ElementActionInterface::class);

        return $action;
    }

    // Misc
    // -------------------------------------------------------------------------

    /**
     * Returns an element class by its handle.
     *
     * @param string $refHandle The element class handle
     *
     * @return string|null The element class, or null if it could not be found
     */
    public function getElementTypeByRefHandle(string $refHandle)
    {
        foreach ($this->getAllElementTypes() as $class) {
            /** @var string|ElementInterface $class */
            if (
                ($elementRefHandle = $class::refHandle()) !== null &&
                strcasecmp($elementRefHandle, $refHandle) === 0
            ) {
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
    public function parseRefs(string $str): string
    {
        if (StringHelper::contains($str, '{')) {
            global $refTagsByElementHandle;
            $refTagsByElementHandle = [];

            $str = preg_replace_callback('/\{(\w+)\:([^\:\}]+)(?:\:([^\:\}]+))?\}/',
                function($matches) {
                    global $refTagsByElementHandle;

                    if (strpos($matches[1], '_') === false) {
                        $elementTypeHandle = ucfirst($matches[1]);
                    } else {
                        $elementTypeHandle = preg_replace_callback('/^\w|_\w/', function($matches) {
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

            if (!empty($refTagsByElementHandle)) {
                $search = [];
                $replace = [];

                $things = ['id', 'ref'];

                foreach ($refTagsByElementHandle as $elementTypeHandle => $refTags) {
                    $elementType = $this->getElementTypeByRefHandle($elementTypeHandle);

                    if ($elementType === null) {
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
                            $refTagsByThing = $thing === 'id' ? $refTagsById : $refTagsByRef;

                            if (!empty($refTagsByThing)) {
                                /** @var Element|string $elementType */
                                $elementQuery = $elementType::find();
                                $elementQuery->status(null);
                                $elementQuery->limit(null);
                                $elementQuery->$thing(array_keys($refTagsByThing));
                                $elements = $elementQuery->all();

                                $elementsByThing = [];

                                foreach ($elements as $element) {
                                    $elementsByThing[$element->$thing] = $element;
                                }

                                foreach ($refTagsByThing as $thingVal => $thingRefTags) {
                                    if (isset($elementsByThing[$thingVal])) {
                                        $element = $elementsByThing[$thingVal];
                                    } else {
                                        $element = false;
                                    }

                                    foreach ($thingRefTags as $refTag) {
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
                                                    Craft::error('An exception was thrown when parsing the ref tag "'.$refTag['matches'][0]."\":\n".$e->getMessage(), __METHOD__);

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
     * @param int $id     The element’s ID
     * @param int $siteId The element’s site ID
     *
     * @return ElementInterface|null The placeholder element if one exists, or null.
     * @see setPlaceholderElement()
     */
    public function getPlaceholderElement(int $id, int $siteId)
    {
        if (isset($this->_placeholderElements[$id][$siteId])) {
            return $this->_placeholderElements[$id][$siteId];
        }

        return null;
    }

    /**
     * Eager-loads additional elements onto a given set of elements.
     *
     * @param string             $elementType The root element type class
     * @param ElementInterface[] $elements    The root element models that should be updated with the eager-loaded elements
     * @param string|array       $with        Dot-delimited paths of the elements that should be eager-loaded into the root elements
     *
     * @return void
     */
    public function eagerLoadElements(string $elementType, array $elements, $with)
    {
        // Bail if there aren't even any elements
        if (empty($elements)) {
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
        $elementTypesByPath = ['__root__' => $elementType];

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
                    $map = $sourceElementType::eagerLoadingMap($elementsByPath[$sourcePath], $segment);

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

                            $targetElementIdsBySourceIds[$mapping['source']][] = $mapping['target'];
                        }

                        // Get the target elements
                        /** @var Element $targetElementType */
                        $targetElementType = $map['elementType'];
                        /** @var ElementQuery $query */
                        $query = $targetElementType::find();
                        Craft::configure($query, array_merge(
                        // Default to no order and limit, but allow the element type/path criteria to override
                            ['orderBy' => null, 'limit' => null],
                            $map['criteria'] ?? [],
                            $pathCriterias[$targetPath] ?? []
                        ));
                        $query->id = $uniqueTargetElementIds;
                        /** @var Element[] $targetElements */
                        $targetElements = $query->all();

                        if (!empty($targetElements)) {
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
                                    if (in_array($targetElement->id, $targetElementIdsBySourceIds[$sourceElementId], false)) {
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
