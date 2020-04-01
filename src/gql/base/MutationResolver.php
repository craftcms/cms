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
    private $_resolutionData = [];
    
    protected $valueNormalizers = [];

    /**
     * Construct a mutation resolver and store the resolution data.
     *
     * @param array $data
     */
    public function __construct(array $data = [], array $valueNormalizers = [])
    {
        $this->_resolutionData = $data;
        $this->valueNormalizers = $valueNormalizers;
    }

    /**
     * Return stored resolution data.
     *
     * @param string $key
     * @return mixed
     * @throws GqlException
     */
    protected function _getData(string $key)
    {
        if (!isset($this->_resolutionData[$key])) {
            throw new GqlException('Stored resolution data by key “' . $key . '” not found!');
        }

        return $this->_resolutionData[$key];
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
        $contentFieldHandles = $this->_getData('contentFieldHandles');

        foreach ($arguments as $argument => $value) {
            if (isset($contentFieldHandles[$argument])) {
                if (array_key_exists($argument, $this->valueNormalizers)) {
                    $normalizer = $this->valueNormalizers[$argument];

                    if (is_callable($normalizer)) {
                        $value = $normalizer($value);
                    }
                }

                $element->setFieldValue($argument, $value);
            } else {
                $element->{$argument} = $value;
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

        if ($element->enabled) {
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
