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
use craft\helpers\Gql;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class MutationResolver
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class MutationResolver
{
    /**
     * @var array Data that might be useful during mutation resolution.
     */
    private $_resolutionData;

    /**
     * @var callable[] Value normalizers stored by argument name
     */
    private $_valueNormalizers;

    /**
     * Construct a mutation resolver and store the resolution data as well as normalizers, if any provided.
     *
     * @param array $data Resolver data
     * @param array $valueNormalizers Data normalizers
     */
    public function __construct(array $data = [], array $valueNormalizers = [])
    {
        $this->_resolutionData = $data;
        $this->_valueNormalizers = $valueNormalizers;
    }

    /**
     * Set a piece of data to be used by the resolver when resolving.
     *
     * @param string $key
     * @param $value
     */
    public function setResolutionData(string $key, $value)
    {
        $this->_resolutionData[$key] = $value;
    }

    /**
     * Set a data normalizer for an argument to use for data normalization during resolving.
     *
     * @param string $argument
     * @param callable $normalizer
     */
    public function setValueNormalizer(string $argument, callable $normalizer = null)
    {
        if ($normalizer === null) {
            unset($this->_valueNormalizers[$argument]);
        } else {
            $this->_valueNormalizers[$argument] = $normalizer;
        }
    }

    /**
     * Return stored resolution data.
     *
     * @param string $key
     * @return mixed
     */
    public function getResolutionData(string $key)
    {
        return $this->_resolutionData[$key] ?? null;
    }

    /**
     * Normalize a value according to stored normalizers.
     *
     * @param string $argument
     * @param mixed $value
     * @return mixed
     */
    protected function normalizeValue(string $argument, $value)
    {
        if (array_key_exists($argument, $this->_valueNormalizers)) {
            $normalizer = $this->_valueNormalizers[$argument];

            $value = $normalizer($value);
        }

        return $value;
    }

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
        /** @var array $contentFieldHandles */
        $contentFieldHandles = $this->getResolutionData('contentFieldHandles');

        foreach ($arguments as $argument => $value) {
            if (isset($contentFieldHandles[$argument])) {
                $value = $this->normalizeValue($argument, $value);
                $element->setFieldValue($argument, $value);
            } else {
                if (property_exists($element, $argument)) {
                    $element->{$argument} = $value;
                }
            }
        }

        return $element;
    }

    /**
     * Resolve a mutation field by source, arguments, context and resolution information.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     * @throws \Throwable if reasons.
     */
    abstract public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo);

    /**
     * Check if schema can perform the action on a scope and throw an Exception if not.
     *
     * @param string $scope
     * @param string $action
     *
     * @throws \Exception if reasons
     */
    protected function requireSchemaAction(string $scope, string $action)
    {
        if (!Gql::canSchema($scope, $action)) {
            throw new Error('Unable to perform the action.');
        }
    }

    /**
     * Save an element.
     *
     * @param ElementInterface $element
     * @throws UserError if validation errors.
     */
    protected function saveElement(ElementInterface $element)
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
    }
}
