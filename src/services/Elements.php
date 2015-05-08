<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Element;
use craft\app\db\Query;
use craft\app\base\ElementActionInterface;
use craft\app\elements\Asset;
use craft\app\elements\Category;
use craft\app\base\ElementInterface;
use craft\app\elements\Entry;
use craft\app\elements\GlobalSet;
use craft\app\elements\MatrixBlock;
use craft\app\elements\Tag;
use craft\app\elements\User;
use craft\app\errors\Exception;
use craft\app\events\DeleteElementsEvent;
use craft\app\events\ElementEvent;
use craft\app\events\MergeElementsEvent;
use craft\app\helpers\ComponentHelper;
use craft\app\helpers\ElementHelper;
use craft\app\helpers\StringHelper;
use craft\app\records\Element as ElementRecord;
use craft\app\records\ElementLocale as ElementLocaleRecord;
use craft\app\records\StructureElement as StructureElementRecord;
use craft\app\tasks\FindAndReplace;
use craft\app\tasks\UpdateElementSlugsAndUris;
use yii\base\Component;

/**
 * The Elements service provides APIs for managing elements.
 *
 * An instance of the Elements service is globally accessible in Craft via [[Application::elements `Craft::$app->getElements()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Elements extends Component
{
	// Constants
	// =========================================================================

	/**
	 * @var string The element interface name
	 */
	const ELEMENT_INTERFACE = 'craft\app\base\ElementInterface';

	/**
	 * @var string The element action interface name
	 */
	const ACTION_INTERFACE = 'craft\app\base\ElementActionInterface';

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
     * You may set [[ElementEvent::performAction]] to `false` to prevent the element from getting saved.
     */
    const EVENT_BEFORE_SAVE_ELEMENT = 'beforeSaveElement';

	/**
     * @event ElementEvent The event that is triggered after an element is saved.
     */
    const EVENT_AFTER_SAVE_ELEMENT = 'afterSaveElement';

	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	private $_placeholderElements;

	// Public Methods
	// =========================================================================

	// Finding Elements
	// -------------------------------------------------------------------------

	/**
	 * Returns an element by its ID.
	 *
	 * If no element type is provided, the method will first have to run a DB query to determine what type of element
	 * the $elementId is, so you should definitely pass it if it’s known.
	 *
	 * The element’s status will not be a factor when usisng this method.
	 *
	 * @param int    $elementId   The element’s ID.
	 * @param null   $elementType The element class.
	 * @param string $localeId    The locale to fetch the element in.
	 *                            Defaults to [[\craft\app\web\Application::language `Craft::$app->language`]].
	 *
	 * @return ElementInterface|Element|null The matching element, or `null`.
	 */
	public function getElementById($elementId, $elementType = null, $localeId = null)
	{
		if (!$elementId)
		{
			return null;
		}

		if (!$elementType)
		{
			$elementType = $this->getElementTypeById($elementId);

			if (!$elementType)
			{
				return null;
			}
		}

		return $elementType::find()
		    ->id($elementId)
		    ->locale($localeId)
		    ->status(null)
		    ->localeEnabled(false)
			->one();
	}

	/**
	 * Returns an element by its URI.
	 *
	 * @param string      $uri         The element’s URI.
	 * @param string|null $localeId    The locale to look for the URI in, and to return the element in.
	 *                                 Defaults to [[\craft\app\web\Application::language `Craft::$app->language`]].
	 * @param bool        $enabledOnly Whether to only look for an enabled element. Defaults to `false`.
	 *
	 * @return ElementInterface|Element|null The matching element, or `null`.
	 */
	public function getElementByUri($uri, $localeId = null, $enabledOnly = false)
	{
		if ($uri === '')
		{
			$uri = '__home__';
		}

		if (!$localeId)
		{
			$localeId = Craft::$app->language;
		}

		// First get the element ID and type

		$conditions = ['and',
			'elements_i18n.uri = :uri',
			'elements_i18n.locale = :locale'
		];

		$params = [
			':uri'    => $uri,
			':locale' => $localeId
		];

		if ($enabledOnly)
		{
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

		if ($result)
		{
			// Return the actual element
			return $this->getElementById($result['id'], $result['type'], $localeId);
		}
	}

	/**
	 * Returns the class(es) of an element with a given ID(s).
	 *
	 * If a single ID is passed in (an int), then a single element class will be returned (a string), or `null` if
	 * no element exists by that ID.
	 *
	 * If an array is passed in, then an array will be returned.
	 *
	 * @param int|array $elementId An element’s ID, or an array of elements’ IDs.
	 *
	 * @return ElementInterface|ElementInterface[]|Element|Element[]|null The element class(es).
	 */
	public function getElementTypeById($elementId)
	{
		if (is_array($elementId))
		{
			return (new Query())
				->select('type')
				->distinct(true)
				->from('{{%elements}}')
				->where(['in', 'id', $elementId])
				->column();
		}
		else
		{
			return (new Query())
				->select('type')
				->from('{{%elements}}')
				->where(['id' => $elementId])
				->scalar();
		}
	}

	/**
	 * Returns an element’s URI for a given locale.
	 *
	 * @param int    $elementId The element’s ID.
	 * @param string $localeId  The locale to search for the element’s URI in.
	 *
	 * @return string|null The element’s URI, or `null`.
	 */
	public function getElementUriForLocale($elementId, $localeId)
	{
		return (new Query())
			->select('uri')
			->from('{{%elements_i18n}}')
			->where(['elementId' => $elementId, 'locale' => $localeId])
			->scalar();
	}

	/**
	 * Returns the locales that a given element is enabled in.
	 *
	 * @param int $elementId The element’s ID.
	 *
	 * @return array The locales that the element is enabled in. If the element could not be found, an empty array
	 *               will be returned.
	 */
	public function getEnabledLocalesForElement($elementId)
	{
		return (new Query())
			->select('locale')
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
	 * @param ElementInterface|ElementInterface $element         The element that is being saved
	 * @param bool|null                         $validateContent Whether the element's content should be validated. If left 'null', it
	 *                                                           will depend on whether the element is enabled or not.
	 *
	 * @throws Exception|\Exception
	 * @return bool
	 */
	public function saveElement(ElementInterface $element, $validateContent = null)
	{
		// Make sure the element is cool with this
		// (Needs to happen before validation, so field types have a chance to prepare their POST values)
		if (!$element->beforeSave())
		{
			return false;
		}

		$isNewElement = !$element->id;

		// Validate the content first
		if ($element::hasContent())
		{
			if ($validateContent === null)
			{
				$validateContent = (bool) $element->enabled;
			}

			if ($validateContent && !Craft::$app->getContent()->validateContent($element))
			{
				$element->addErrors($element->getContent()->getErrors());
				return false;
			}
			else
			{
				// Make sure there's a title
				if ($element::hasTitles())
				{
					$fields = ['title'];
					$content = $element->getContent();
					$content->setRequiredFields($fields);

					if (!$content->validate($fields) && $content->hasErrors('title'))
					{
						// Just set *something* on it
						if ($isNewElement)
						{
							$content->title = 'New '.$element::classHandle();
						}
						else
						{
							$content->title = $element::classHandle().' '.$element->id;
						}
					}
				}
			}
		}

		// Get the element record
		if (!$isNewElement)
		{
			$elementRecord = ElementRecord::findOne([
				'id'   => $element->id,
				'type' => $element::className()
			]);

			if (!$elementRecord)
			{
				throw new Exception(Craft::t('app', 'No element exists with the ID “{id}”.', ['id' => $element->id]));
			}
		}
		else
		{
			$elementRecord = new ElementRecord();
			$elementRecord->type = $element::className();
		}

		// Set the attributes
		$elementRecord->enabled = (bool) $element->enabled;
		$elementRecord->archived = (bool) $element->archived;

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			// Fire a 'beforeSaveElement' event
			$event = new ElementEvent([
				'element' => $element
			]);

			$this->trigger(static::EVENT_BEFORE_SAVE_ELEMENT, $event);

			// Is the event giving us the go-ahead?
			if ($event->performAction)
			{
				// Save the element record first
				$success = $elementRecord->save(false);

				if ($success)
				{
					if ($isNewElement)
					{
						// Save the element id on the element model, in case {id} is in the URL format
						$element->id = $elementRecord->id;

						if ($element::hasContent())
						{
							$element->getContent()->elementId = $element->id;
						}
					}

					// Save the content
					if ($element::hasContent())
					{
						Craft::$app->getContent()->saveContent($element, false, (bool)$element->id);
					}

					// Update the search index
					Craft::$app->getSearch()->indexElementAttributes($element);

					// Update the locale records and content

					// We're saving all of the element's locales here to ensure that they all exist and to update the URI in
					// the event that the URL format includes some value that just changed

					$localeRecords = [];

					if (!$isNewElement)
					{
						$existingLocaleRecords = ElementLocaleRecord::findAll([
							'elementId' => $element->id
						]);

						foreach ($existingLocaleRecords as $record)
						{
							$localeRecords[$record->locale] = $record;
						}
					}

					$mainLocaleId = $element->locale;

					$locales = $element->getLocales();
					$localeIds = [];

					if (!$locales)
					{
						throw new Exception('All elements must have at least one locale associated with them.');
					}

					foreach ($locales as $localeId => $localeInfo)
					{
						if (is_numeric($localeId) && is_string($localeInfo))
						{
							$localeId = $localeInfo;
							$localeInfo = [];
						}

						$localeIds[] = $localeId;

						if (!isset($localeInfo['enabledByDefault']))
						{
							$localeInfo['enabledByDefault'] = true;
						}

						if (isset($localeRecords[$localeId]))
						{
							$localeRecord = $localeRecords[$localeId];
						}
						else
						{
							$localeRecord = new ElementLocaleRecord();

							$localeRecord->elementId = $element->id;
							$localeRecord->locale = $localeId;
							$localeRecord->enabled = $localeInfo['enabledByDefault'];
						}

						// Is this the main locale?
						$isMainLocale = ($localeId == $mainLocaleId);

						if ($isMainLocale)
						{
							$localizedElement = $element;
						}
						else
						{
							// Copy the element for this locale
							$localizedElement = $element->copy();
							$localizedElement->locale = $localeId;

							if ($localeRecord->id)
							{
								// Keep the original slug
								$localizedElement->slug = $localeRecord->slug;
							}
							else
							{
								// Default to the main locale's slug
								$localizedElement->slug = $element->slug;
							}
						}

						if ($element::hasContent())
						{
							if (!$isMainLocale)
							{
								$content = null;

								if (!$isNewElement)
								{
									// Do we already have a content row for this locale?
									$content = Craft::$app->getContent()->getContent($localizedElement);
								}

								if (!$content)
								{
									$content = Craft::$app->getContent()->createContent($localizedElement);
									$content->setAttributes($element->getContent()->getAttributes());
									$content->id = null;
									$content->locale = $localeId;
								}

								$localizedElement->setContent($content);
							}

							if (!$localizedElement->getContent()->id)
							{
								Craft::$app->getContent()->saveContent($localizedElement, false, false);
							}
						}

						// Capture the original slug, in case it's entirely composed of invalid characters
						$originalSlug = $localizedElement->slug;

						// Clean up the slug
						ElementHelper::setValidSlug($localizedElement);

						// If the slug was entirely composed of invalid characters, it will be blank now.
						if ($originalSlug && !$localizedElement->slug)
						{
							$localizedElement->slug = $originalSlug;
							$element->addError('slug', Craft::t('app', '{attribute} is invalid.', ['attribute' => Craft::t('app', 'Slug')]));

							// Don't bother with any of the other locales
							$success = false;
							break;
						}

						ElementHelper::setUniqueUri($localizedElement);

						$localeRecord->slug = $localizedElement->slug;
						$localeRecord->uri = $localizedElement->uri;

						if ($isMainLocale)
						{
							$localeRecord->enabled = (bool)$element->localeEnabled;
						}

						$success = $localeRecord->save();

						if (!$success)
						{
							// Pass any validation errors on to the element
							$element->addErrors($localeRecord->getErrors());

							// Don't bother with any of the other locales
							break;
						}
					}

					if ($success)
					{
						if (!$isNewElement)
						{
							// Delete the rows that don't need to be there anymore

							Craft::$app->getDb()->createCommand()->delete('{{%elements_i18n}}', ['and',
								'elementId = :elementId',
								['not in', 'locale', $localeIds]
							], [
								':elementId' => $element->id
							])->execute();

							if ($element::hasContent())
							{
								Craft::$app->getDb()->createCommand()->delete($element->getContentTable(), ['and',
									'elementId = :elementId',
									['not in', 'locale', $localeIds]
								], [
									':elementId' => $element->id
								])->execute();
							}
						}

						// Tell the element it was just saved
						$element->afterSave();

						// Finally, delete any caches involving this element. (Even do this for new elements, since they
						// might pop up in a cached criteria.)
						Craft::$app->getTemplateCache()->deleteCachesByElement($element);
					}
				}
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we saved the user, in case something changed
			// in onBeforeSaveElement
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			// Fire an 'afterSaveElement' event
			$this->trigger(static::EVENT_AFTER_SAVE_ELEMENT, new ElementEvent([
				'element' => $element
			]));
		}
		else
		{
			if ($isNewElement)
			{
				$element->id = null;

				if ($element::hasContent())
				{
					$element->getContent()->id = null;
					$element->getContent()->elementId = null;
				}
			}
		}

		return $success;
	}

	/**
	 * Updates an element’s slug and URI, along with any descendants.
	 *
	 * @param ElementInterface|Element $element            The element to update.
	 * @param boolean                  $updateOtherLocales Whether the element’s other locales should also be updated.
	 * @param boolean                  $updateDescendants  Whether the element’s descendants should also be updated.
	 * @param boolean                  $asTask             Whether the element’s slug and URI should be updated via a background task.
	 *
	 * @return null
	 */
	public function updateElementSlugAndUri(ElementInterface $element, $updateOtherLocales = true, $updateDescendants = true, $asTask = false)
	{
		if ($asTask)
		{
			Craft::$app->getTasks()->queueTask([
				'type'               => UpdateElementSlugsAndUris::className(),
				'elementId'          => $element->id,
				'elementType'        => $element::className(),
				'locale'             => $element->locale,
				'updateOtherLocales' => $updateOtherLocales,
				'updateDescendants'  => $updateDescendants,
			]);

			return;
		}

		ElementHelper::setUniqueUri($element);

		Craft::$app->getDb()->createCommand()->update('{{%elements_i18n}}', [
			'slug' => $element->slug,
			'uri'  => $element->uri
		], [
			'elementId' => $element->id,
			'locale'    => $element->locale
		])->execute();

		// Delete any caches involving this element
		Craft::$app->getTemplateCache()->deleteCachesByElement($element);

		if ($updateOtherLocales)
		{
			$this->updateElementSlugAndUriInOtherLocales($element);
		}

		if ($updateDescendants)
		{
			$this->updateDescendantSlugsAndUris($element, $updateOtherLocales);
		}
	}

	/**
	 * Updates an element’s slug and URI, for any locales besides the given one.
	 *
	 * @param ElementInterface|Element $element The element to update.
	 *
	 * @return null
	 */
	public function updateElementSlugAndUriInOtherLocales(ElementInterface $element)
	{
		foreach (Craft::$app->getI18n()->getSiteLocaleIds() as $localeId)
		{
			if ($localeId == $element->locale)
			{
				continue;
			}

			$elementInOtherLocale = $element::find()
				->id($element->id)
				->locale($localeId)
				->one();

			if ($elementInOtherLocale)
			{
				$this->updateElementSlugAndUri($elementInOtherLocale, false, false);
			}
		}
	}

	/**
	 * Updates an element’s descendants’ slugs and URIs.
	 *
	 * @param ElementInterface|Element $element            The element whose descendants should be updated.
	 * @param bool                     $updateOtherLocales Whether the element’s other locales should also be updated.
	 * @param bool                     $asTask             Whether the descendants’ slugs and URIs should be updated via a background task.
	 *
	 * @return null
	 */
	public function updateDescendantSlugsAndUris(ElementInterface $element, $updateOtherLocales = true, $asTask = false)
	{
		$query = $element::find()
		    ->descendantOf($element)
		    ->descendantDist(1)
		    ->status(null)
		    ->localeEnabled(null)
			->locale($element->locale);

		if ($asTask)
		{
			$childIds = $query->ids();

			if ($childIds)
			{
				Craft::$app->getTasks()->queueTask([
					'type'               => UpdateElementSlugsAndUris::className(),
					'elementId'          => $childIds,
					'elementType'        => $element::className(),
					'locale'             => $element->locale,
					'updateOtherLocales' => $updateOtherLocales,
					'updateDescendants'  => true,
				]);
			}
		}
		else
		{
			$children = $query->all();

			foreach ($children as $child)
			{
				$this->updateElementSlugAndUri($child, $updateOtherLocales, true, false);
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
	 * @throws \Exception
	 * @return bool Whether the elements were merged successfully.
	 */
	public function mergeElementsByIds($mergedElementId, $prevailingElementId)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// Update any relations that point to the merged element
			$relations = (new Query())
				->select(['id', 'fieldId', 'sourceId', 'sourceLocale'])
				->from('{{%relations}}')
				->where(['targetId' => $mergedElementId])
				->all();

			foreach ($relations as $relation)
			{
				// Make sure the persisting element isn't already selected in the same field
				$persistingElementIsRelatedToo = (new Query())
					->from('{{%relations}}')
					->where([
						'fieldId'      => $relation['fieldId'],
						'sourceId'     => $relation['sourceId'],
						'sourceLocale' => $relation['sourceLocale'],
						'targetId'     => $prevailingElementId
					])
					->exists();

				if (!$persistingElementIsRelatedToo)
				{
					Craft::$app->getDb()->createCommand()->update('{{%relations}}', [
						'targetId' => $prevailingElementId
					], [
						'id' => $relation['id']
					])->execute();
				}
			}

			// Update any structures that the merged element is in
			$structureElements = (new Query())
				->select(['id', 'structureId'])
				->from('{{%structureelements}}')
				->where(['elementId' => $mergedElementId])
				->all();

			foreach ($structureElements as $structureElement)
			{
				// Make sure the persisting element isn't already a part of that structure
				$persistingElementIsInStructureToo = (new Query())
					->from('{{%structureElements}}')
					->where([
						'structureId' => $structureElement['structureId'],
						'elementId' => $prevailingElementId
					])
					->exists();

				if (!$persistingElementIsInStructureToo)
				{
					Craft::$app->getDb()->createCommand()->update('{{%relations}}', [
						'elementId' => $prevailingElementId
					], [
						'id' => $structureElement['id']
					])->execute();
				}
			}

			// Update any reference tags
			$elementType = $this->getElementTypeById($prevailingElementId);

			if ($elementType && ($elementTypeHandle = $elementType::classHandle()))
			{
				$refTagPrefix = "{{$elementTypeHandle}:";

				Craft::$app->getTasks()->queueTask([
					'type'        => FindAndReplace::className(),
					'description' => Craft::t('app', 'Updating element references'),
					'find'        => $refTagPrefix.$mergedElementId.':',
					'replace'     => $refTagPrefix.$prevailingElementId.':',
				]);

				Craft::$app->getTasks()->queueTask([
					'type'        => FindAndReplace::className(),
					'description' => Craft::t('app', 'Updating element references'),
					'find'        => $refTagPrefix.$mergedElementId.'}',
					'replace'     => $refTagPrefix.$prevailingElementId.'}',
				]);
			}

			// Fire an 'afterMergeElements' event
			$this->trigger(static::EVENT_AFTER_MERGE_ELEMENTS, new MergeElementsEvent([
				'mergedElementId'     => $mergedElementId,
				'prevailingElementId' => $prevailingElementId
			]));

			// Now delete the merged element
			$success = $this->deleteElementById($mergedElementId);

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return $success;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Deletes an element(s) by its ID(s).
	 *
	 * @param int|array $elementIds The element’s ID, or an array of elements’ IDs.
	 *
	 * @throws \Exception
	 * @return bool Whether the element(s) were deleted successfully.
	 */
	public function deleteElementById($elementIds)
	{
		if (!$elementIds)
		{
			return false;
		}

		if (!is_array($elementIds))
		{
			$elementIds = [$elementIds];
		}

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			// Fire a 'beforeDeleteElements' event
			$this->trigger(static::EVENT_BEFORE_DELETE_ELEMENTS, new DeleteElementsEvent([
				'elementIds' => $elementIds
			]));

			// First delete any structure nodes with these elements, so NestedSetBehavior can do its thing. We need to
			// go one-by-one in case one of theme deletes the record of another in the process.
			foreach ($elementIds as $elementId)
			{
				$records = StructureElementRecord::findAll([
					'elementId' => $elementId
				]);

				foreach ($records as $record)
				{
					// If this element still has any children, move them up before the one getting deleted.
					$children = $record->children()->findAll();

					foreach ($children as $child)
					{
						$child->insertBefore($record);
					}

					// Delete this element's node
					$record->deleteWithChildren();
				}
			}

			// Delete the caches before they drop their elementId relations (passing `false` because there's no chance
			// this element is suddenly going to show up in a new query)
			Craft::$app->getTemplateCache()->deleteCachesByElementId($elementIds, false);

			// Now delete the rows in the elements table
			if (count($elementIds) == 1)
			{
				$condition = ['id' => $elementIds[0]];
				$matrixBlockCondition = ['ownerId' => $elementIds[0]];
				$searchIndexCondition = ['elementId' => $elementIds[0]];
			}
			else
			{
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

			if ($matrixBlockIds)
			{
				Craft::$app->getMatrix()->deleteBlockById($matrixBlockIds);
			}

			// Delete the elements table rows, which will cascade across all other InnoDB tables
			$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%elements}}', $condition)->execute();

			// The searchindex table is MyISAM, though
			Craft::$app->getDb()->createCommand()->delete('{{%searchindex}}', $searchIndexCondition)->execute();

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

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
			Asset::className(),
			Category::className(),
			Entry::className(),
			GlobalSet::className(),
			MatrixBlock::className(),
			Tag::className(),
			User::className(),
		];
	}

	// Element Actions
	// -------------------------------------------------------------------------

	/**
	 * Creates an element action with a given config.
	 *
	 * @param mixed $config The element action’s class name, or its config, with a `type` value and optionally a `settings` value
	 * @return ElementActionInterface|ElementAction The element action
	 */
	public function createAction($config)
	{
		return ComponentHelper::createComponent($config, self::ACTION_INTERFACE);
	}

	// Misc
	// -------------------------------------------------------------------------

	/**
	 * Returns an element class by its handle.
	 *
	 * @param string $handle The element class handle
	 * @return ElementInterface|null The element class, or null if it could not be found
	 */
	public function getElementTypeByHandle($handle)
	{
		foreach ($this->getAllElementTypes() as $class)
		{
			if (strcasecmp($class::classHandle(), $handle) === 0)
			{
				return $class;
			}
		}
	}

	/**
	 * Parses a string for element [reference tags](http://buildwithcraft.com/docs/reference-tags).
	 *
	 * @param string $str The string to parse.
	 *
	 * @return string The parsed string.
	 */
	public function parseRefs($str)
	{
		if (StringHelper::contains($str, '{'))
		{
			global $refTagsByElementHandle;
			$refTagsByElementHandle = [];

			$str = preg_replace_callback('/\{(\w+)\:([^\:\}]+)(?:\:([^\:\}]+))?\}/', function($matches)
			{
				global $refTagsByElementHandle;

				$elementTypeHandle = ucfirst($matches[1]);
				$token = '{'.StringHelper::randomString(9).'}';

				$refTagsByElementHandle[$elementTypeHandle][] = ['token' => $token, 'matches' => $matches];

				return $token;
			}, $str);

			if ($refTagsByElementHandle)
			{
				$search = [];
				$replace = [];

				$things = ['id', 'ref'];

				foreach ($refTagsByElementHandle as $elementTypeHandle => $refTags)
				{
					$elementType = $this->getElementTypeByHandle($elementTypeHandle);

					if (!$elementType)
					{
						// Just put the ref tags back the way they were
						foreach ($refTags as $refTag)
						{
							$search[] = $refTag['token'];
							$replace[] = $refTag['matches'][0];
						}
					}
					else
					{
						$refTagsById  = [];
						$refTagsByRef = [];

						foreach ($refTags as $refTag)
						{
							// Searching by ID?
							if (is_numeric($refTag['matches'][2]))
							{
								$refTagsById[$refTag['matches'][2]][] = $refTag;
							}
							else
							{
								$refTagsByRef[$refTag['matches'][2]][] = $refTag;
							}
						}

						// Things are about to get silly...
						foreach ($things as $thing)
						{
							$refTagsByThing = ${'refTagsBy'.ucfirst($thing)};

							if ($refTagsByThing)
							{
								$elements = $elementType::find()
								    ->status(null)
								    ->$thing(array_keys($refTagsByThing))
									->all();

								$elementsByThing = [];

								foreach ($elements as $element)
								{
									$elementsByThing[$element->$thing] = $element;
								}

								foreach ($refTagsByThing as $thingVal => $refTags)
								{
									if (isset($elementsByThing[$thingVal]))
									{
										$element = $elementsByThing[$thingVal];
									}
									else
									{
										$element = false;
									}

									foreach($refTags as $refTag)
									{
										$search[] = $refTag['token'];

										if ($element)
										{
											if (!empty($refTag['matches'][3]) && isset($element->{$refTag['matches'][3]}))
											{
												$value = (string) $element->{$refTag['matches'][3]};
												$replace[] = $this->parseRefs($value);
											}
											else
											{
												// Default to the URL
												$replace[] = $element->getUrl();
											}
										}
										else
										{
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
	 * matching ID and locale.
	 *
	 * This is used by Live Preview and Sharing features.
	 *
	 * @param ElementInterface|Element $element The element currently being edited by Live Preview.
	 * @see getPlaceholderElement()
	 */
	public function setPlaceholderElement(ElementInterface $element)
	{
		// Won't be able to do anything with this if it doesn't have an ID or locale
		if (!$element->id || !$element->locale)
		{
			return;
		}

		$this->_placeholderElements[$element->id][$element->locale] = $element;
	}

	/**
	 * Returns a placeholder element by its ID and locale.
	 *
	 * @param integer $id The element’s ID
	 * @param string  $locale The element’s locale
	 * @return ElementInterface|Element|null The placeholder element if one exists, or null.
	 * @see setPlaceholderElement()
	 */
	public function getPlaceholderElement($id, $locale)
	{
		if (isset($this->_placeholderElements[$id][$locale]))
		{
			return $this->_placeholderElements[$id][$locale];
		}
		else
		{
			return null;
		}
	}
}
