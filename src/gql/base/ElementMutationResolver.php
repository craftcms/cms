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
use craft\errors\GqlException;
use craft\events\MutationPopulateElementEvent;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;

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
    public const CONTENT_FIELD_KEY = '_contentFields';

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
     *     // Add the timestamp to the element’s title
     *     $event->arguments['title'] = ($event->arguments['title'] ?? '') . '[' . DateTimeHelper::currentTimeStamp() . ']';
     * });
     * ```
     */
    public const EVENT_BEFORE_POPULATE_ELEMENT = 'beforeMutationPopulateElement';

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
    public const EVENT_AFTER_POPULATE_ELEMENT = 'afterMutationPopulateElement';

    /**
     * A list of attributes that are unchangeable by mutations.
     *
     * @var string[]
     */
    protected array $immutableAttributes = ['id', 'uid'];

    /**
     * @var Type[] Argument type definitions by name.
     */
    protected array $argumentTypeDefsByName = [];

    /**
     * Populate the element with submitted data.
     *
     * @template T of ElementInterface
     * @param T $element
     * @param array $arguments
     * @param ResolveInfo|null $resolveInfo
     * @return T
     * @throws GqlException if data not found.
     */
    protected function populateElementWithData(ElementInterface $element, array $arguments, ?ResolveInfo $resolveInfo = null): ElementInterface
    {
        $normalized = false;

        if ($resolveInfo) {
            $arguments = $this->recursivelyNormalizeArgumentValues($resolveInfo, $arguments);
            $normalized = true;
        }

        $contentFields = $this->getResolutionData(self::CONTENT_FIELD_KEY) ?? [];

        foreach ($this->immutableAttributes as $attribute) {
            unset($arguments[$attribute]);
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_POPULATE_ELEMENT)) {
            $event = new MutationPopulateElementEvent([
                'arguments' => $arguments,
                'element' => $element,
            ]);

            $this->trigger(self::EVENT_BEFORE_POPULATE_ELEMENT, $event);

            $arguments = $event->arguments;
            $element = $event->element;
        }

        foreach ($arguments as $argument => $value) {
            if (isset($contentFields[$argument])) {
                if (!$normalized) {
                    $value = $this->normalizeValue($argument, $value);
                }
                $element->setFieldValue($argument, $value);
            } elseif ($element->canSetProperty($argument)) {
                $element->{$argument} = $value;
            }
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_POPULATE_ELEMENT)) {
            $event = new MutationPopulateElementEvent([
                'arguments' => $arguments,
                'element' => $element,
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
     * @return ElementInterface
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

            foreach ($element->getFirstErrors() as $errorMessage) {
                $validationErrors[] = $errorMessage;
            }

            throw new UserError(implode("\n", $validationErrors));
        }

        return $element;
    }

    /**
     * Normalize argument values in a recursive manner
     *
     * @param ResolveInfo $resolveInfo
     * @param array $mutationArguments
     * @return array
     */
    protected function recursivelyNormalizeArgumentValues(ResolveInfo $resolveInfo, array $mutationArguments): array
    {
        return $this->_traverseAndNormalizeArguments($resolveInfo->fieldDefinition->args ?? [], $mutationArguments);
    }

    /**
     * Traverse an argument list recursively and normalize the values.
     *
     * @param array $argumentDefinitions
     * @param array $mutationArguments
     * @return array
     */
    private function _traverseAndNormalizeArguments(array $argumentDefinitions, array $mutationArguments): array
    {
        $normalized = [];

        // Keep track of known argument names and the corresponding input types.
        /** @var FieldArgument $argumentDefinition */
        foreach ($argumentDefinitions as $argumentDefinition) {
            $typeDef = $argumentDefinition->getType();

            if ($typeDef instanceof WrappingType) {
                $typeDef = $typeDef->getWrappedType(true);
            }

            $this->argumentTypeDefsByName[$argumentDefinition->name] = $typeDef;
        }

        // Now look at the actual provided arguments
        foreach ($mutationArguments as $argumentName => $value) {
            if (is_numeric($argumentName)) {
                // If this just an array of values, iterate over those elements
                $normalized[$argumentName] = $this->_traverseAndNormalizeArguments($argumentDefinitions, $value);
            } else {
                // Find the relevant type def
                $argumentTypeDef = $this->argumentTypeDefsByName[$argumentName];

                // If it's an input object, traverse that
                if ($argumentTypeDef instanceof InputObjectType) {
                    if (!empty($argumentTypeDef->getFields())) {
                        $value = $this->_traverseAndNormalizeArguments($argumentTypeDef->getFields(), $value);
                    }
                }

                // Use the normalizer, if it exists
                $normalizer = $argumentTypeDef->config['normalizeValue'] ?? null;
                if ($normalizer && is_callable($normalizer)) {
                    $normalized[$argumentName] = $normalizer($value);
                } else {
                    $normalized[$argumentName] = $value;
                }
            }
        }

        return $normalized;
    }
}
