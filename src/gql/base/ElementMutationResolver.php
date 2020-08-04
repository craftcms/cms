<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\Entry as EntryElement;
use craft\errors\GqlException;
use craft\events\MutationPopulateElementEvent;
use GraphQL\Error\UserError;

/**
 * Class MutationResolver
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class ElementMutationResolver extends MutationResolver
{
    /**
     * Constant used to reference content fields in resolution data storage.
     */
    const CONTENT_FIELD_KEY = '_contentFields';

    /**
     * @event MutationPopulateElementEvent The event that is triggered before populating an element when resolving a mutation
     *
     * Plugins get a chance to modify the arguments used to populate an element as well as the element itself before it gets populated with data.
     *
     * ---
     * ```php
     * use craft\events\MutationPopulateElementEvent;
     * use craft\gql\resolvers\mutations\Asset as AssetMutationResolver;
     * use craft\helpers\DateTimeHelper;
     * use yii\base\Event;
     *
     * Event::on(AssetMutationResolver::class, AssetMutationResolver::EVENT_BEFORE_POPULATE_ELEMENT, function(MutationPopulateElementEvent $event) {
     *     // Add the timestamp to the element's title
     *     $event->arguments['title'] = ($event->arguments['title'] ?? '') . '[' . DateTimeHelper::currentTimeStamp() . ']';
     * });
     * ```
     */
    const EVENT_BEFORE_POPULATE_ELEMENT = 'beforeMutationPopulateElement';

    /**
     * @event MutationPopulateElementEvent The event that is triggered after populating an element when resolving a mutation
     *
     * Plugins get a chance to modify the element before it gets saved to the database.
     *
     * ---
     * ```php
     * use craft\events\MutationPopulateElementEvent;
     * use craft\gql\resolvers\mutations\Asset as AssetMutationResolver;
     * use yii\base\Event;
     *
     * Event::on(AssetMutationResolver::class, AssetMutationResolver::EVENT_AFTER_POPULATE_ELEMENT, function(MutationPopulateElementEvent $event) {
     *     // Always set the focal point to top left corner for new files just because it's funny.
     *     if (empty($event->element->id)) {
     *         $event->element->focalpoint = ['x' => 0, 'y' => 0];
     *     }
     * });
     * ```
     */
    const EVENT_AFTER_POPULATE_ELEMENT = 'afterMutationPopulateElement';

    /**
     * A list of attributes that are unchangeable by mutations.
     *
     * @var string[]
     */
    protected $immutableAttributes = ['id', 'uid'];

    /**
     * Populate the element with submitted data.
     *
     * @param Element $element
     * @param array $arguments
     * @return EntryElement
     * @throws GqlException if data not found.
     */
    protected function populateElementWithData(Element $element, array $arguments): Element
    {
        $contentFields = $this->getResolutionData(self::CONTENT_FIELD_KEY) ?? [];

        foreach ($this->immutableAttributes as $attribute) {
            unset($arguments[$attribute]);
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_POPULATE_ELEMENT)) {
            $event = new MutationPopulateElementEvent([
                'arguments' => $arguments,
                'element' => $element
            ]);

            $this->trigger(self::EVENT_BEFORE_POPULATE_ELEMENT, $event);

            $arguments = $event->arguments;
            $element = $event->element;
        }

        foreach ($arguments as $argument => $value) {
            if (isset($contentFields[$argument])) {
                $value = $this->normalizeValue($argument, $value);
                $element->setFieldValue($argument, $value);
            } else {
                if (property_exists($element, $argument)) {
                    $element->{$argument} = $value;
                }
            }
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_POPULATE_ELEMENT)) {
            $event = new MutationPopulateElementEvent([
                'arguments' => $arguments,
                'element' => $element
            ]);

            $this->trigger(self::EVENT_AFTER_POPULATE_ELEMENT, $event);
            $element = $event->element;
        }

        return $element;
    }

    /**
     * Save an element.
     *
     * @param ElementInterface $element
     * @throws UserError if validation errors.
     */
    protected function saveElement(ElementInterface $element): ElementInterface
    {
        /** @var Element $element */
        if ($element->enabled && $element->getScenario() == Element::SCENARIO_DEFAULT) {
            $element->setScenario(Element::SCENARIO_LIVE);
        }

        Craft::$app->getElements()->saveElement($element);

        if ($element->hasErrors()) {
            $validationErrors = [];

            foreach ($element->getFirstErrors() as $attribute => $errorMessage) {
                $validationErrors[] = $errorMessage;
            }

            throw new UserError(implode("\n", $validationErrors));
        }

        return $element;
    }
}
