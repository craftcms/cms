<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Gql\Events\AfterPopulateElement;
use CraftCms\Cms\Gql\Events\BeforePopulateElement;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use Illuminate\Support\Facades\Cache;
use yii\web\ServerErrorHttpException;

abstract class ElementMutationResolver extends MutationResolver
{
    /**
     * Constant used to reference content fields in resolution data storage.
     */
    public const string CONTENT_FIELD_KEY = '_contentFields';

    /**
     * @var string[]
     */
    protected array $immutableAttributes = ['id', 'uid'];

    /**
     * @var Type[] Argument type definitions by name.
     */
    protected array $argumentTypeDefsByName = [];

    /**
     * @template T of ElementInterface
     *
     * @param  T  $element
     * @return T
     *
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

        // Fire a 'beforeMutationPopulateElement' event
        event($event = new BeforePopulateElement(
            resolverClass: static::class,
            arguments: $arguments,
            element: $element,
        ));
        $arguments = $event->arguments;
        $element = $event->element;

        foreach ($arguments as $argument => $value) {
            if (isset($contentFields[$argument])) {
                if (! $normalized) {
                    $value = $this->normalizeValue($argument, $value);
                }
                $element->setFieldValue($argument, $value);
            } elseif ($element->canSetProperty($argument)) {
                $element->{$argument} = $value;
            }
        }

        // Fire an 'afterMutationPopulateElement' event
        event($event = new AfterPopulateElement(
            resolverClass: static::class,
            arguments: $arguments,
            element: $element,
        ));

        return $event->element;
    }

    /**
     * @throws UserError if validation errors.
     */
    protected function saveElement(ElementInterface $element): ElementInterface
    {
        /** @var Element $element */
        if ($element->enabled && $element->inScenarios(Element::SCENARIO_DEFAULT)) {
            $element->setScenario(Element::SCENARIO_LIVE);
        }

        $isNotNew = $element->id;
        if ($isNotNew) {
            $mutex = Cache::lock("element:$element->id", 15);
            if (! $mutex->get()) {
                throw new ServerErrorHttpException('Could not acquire a lock to save the element.');
            }
        }

        try {
            Craft::$app->getElements()->saveElement($element);
        } finally {
            if ($isNotNew) {
                $mutex->release();
            }
        }

        if ($element->hasErrors()) {
            $validationErrors = [];

            foreach ($element->getFirstErrors() as $errorMessage) {
                $validationErrors[] = $errorMessage;
            }

            throw new UserError(implode("\n", $validationErrors));
        }

        return $element;
    }

    protected function recursivelyNormalizeArgumentValues(ResolveInfo $resolveInfo, array $mutationArguments): array
    {
        return $this->_traverseAndNormalizeArguments($resolveInfo->fieldDefinition->args ?? [], $mutationArguments);
    }

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
                    if (! empty($argumentTypeDef->getFields())) {
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
