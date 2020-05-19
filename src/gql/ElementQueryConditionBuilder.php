<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\FieldInterface;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\fields\Assets as AssetField;
use craft\fields\BaseRelationField;
use craft\fields\Users as UserField;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\helpers\StringHelper;
use craft\services\Gql;
use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\WrappingType;

/**
 * Class ElementQueryConditionBuilder.
 *
 * This class is used to analyze a GraphQL query tree based on its ResolveInfo object and generate an array of
 * [method => parameters] to be called on the element query to ensure eager-loading of everything applicable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class ElementQueryConditionBuilder
{
    /**
     * @var ResolveInfo
     */
    private $_resolveInfo;
    private $_fragments;
    private $_eagerLoadableFieldsByContext = [];
    private $_transformableAssetProperties = ['url', 'width', 'height'];
    private $_eagerLoadableProperties = ['children', 'localized', 'parent'];


    public function __construct(ResolveInfo $resolveInfo)
    {
        $this->_resolveInfo = $resolveInfo;
        $this->_fragments = $resolveInfo->fragments;

        // Cache all eager-loadable fields by context
        $allFields = Craft::$app->getFields()->getAllFields(false);
        foreach ($allFields as $field) {
            if ($field instanceof EagerLoadingFieldInterface) {
                $this->_eagerLoadableFieldsByContext[$field->context][$field->handle] = $field;
            }
        }
    }

    /**
     * Extract the query conditions based on the resolve information passed in the constructor.
     * Returns an array of [methodName => parameters] to be called on the element query.
     *
     * @param FieldInterface $startingParentField the starting parent field for the extraction, if any
     * @return array
     */
    public function extractQueryConditions(FieldInterface $startingParentField = null)
    {
        $startingNode = $this->_resolveInfo->fieldNodes[0];
        $extractedConditions = [];

        if ($startingNode === null) {
            return [];
        }

        // Load up all eager loading rules.
        $eagerLoadingRules = $this->_traverseAndExtractRules($startingNode, '', $startingParentField ? $startingParentField->context : 'global', $startingParentField);

        $relationCountFields = [];

        // Figure out which routes should be loaded using `withCount`
        foreach ($eagerLoadingRules as $element => $parameters) {
            if (StringHelper::endsWith($element, '@' . Gql::GRAPHQL_COUNT_FIELD)) {
                if (isset($parameters['field'])) {
                    $relationCountFields[$parameters['field']] = true;
                }
            }
        }

        foreach ($eagerLoadingRules as $element => $parameters) {
            if (StringHelper::endsWith($element, '@' . Gql::GRAPHQL_COUNT_FIELD)) {
                continue;
            }

            if (!empty($relationCountFields[$element])) {
                $parameters['count'] = true;
            }

            if ($element == 'withTransforms') {
                $extractedConditions['withTransforms'] = $parameters;
                continue;
            }

            if (empty($parameters)) {
                $eagerLoadConditions[] = $element;
            } else {
                $eagerLoadConditions[] = [$element, $parameters];
            }
        }

        if (!empty($eagerLoadConditions)) {
            $extractedConditions['with'] = $eagerLoadConditions;
        }

        return $extractedConditions;
    }

    /**
     * Extract arguments from an array of argument nodes, substituting variables wit the real values.
     *
     * @param ArgumentNode[] $argumentNodes
     * @return array
     */
    private function _extractArguments($argumentNodes)
    {
        $arguments = [];

        foreach ($argumentNodes as $argumentNode) {
            $arguments[$argumentNode->name->value] = $this->_extractArgumentValue($argumentNode);
        }

        return $arguments;
    }

    /**
     * Extract the value from an argument node, even if it's an array or a GraphQL variable.
     *
     * @param Node $argumentNode
     * @param string $nodeName
     */
    private function _extractArgumentValue(Node $argumentNode, $nodeName = null)
    {
        if (isset($argumentNode->value->values)) {
            $extractedValue = [];
            foreach ($argumentNode->value->values as $value) {
                $extractedValue[] = $this->_extractArgumentValue($value, $argumentNode->name->value);
            }
        } else {
            $nodeName = $nodeName ?? $argumentNode->name->value;
            $extractedValue = $argumentNode->kind === 'Variable' ? $this->_resolveInfo->variableValues[$nodeName] : ($argumentNode->value->value ?? ($argumentNode->value ?? null));
        }

        return $extractedValue;
    }

    /**
     * Extract arguments from the node's directives, if any.
     *
     * @param Node $node
     * @return array
     */
    private function _extractTransformDirectiveArguments(Node $node)
    {
        $arguments = [];
        $directives = $node->directives ?? [];

        foreach ($directives as $directive) {
            if ($directive->name->value === 'transform') {
                $arguments = $this->_extractArguments($directive->arguments ?? []);
                unset($arguments['immediately']);
                break;
            }
        }
        return $arguments;
    }

    /**
     * Prepare a list of transform arguments for return.
     *
     * @param $arguments
     * @return array
     */
    private function _prepareTransformArguments($arguments)
    {
        if (empty($arguments)) {
            return [];
        }

        // `handle` arguments are just strings on their own.
        if (!empty($arguments['handle'])) {
            $arguments += [$arguments['handle']];
            unset($arguments['handle']);
        } else {
            // If there's no handle, then it's a key => value pair that we need to encapsulate.
            $arguments = [$arguments];
        }

        return $arguments;
    }

    /**
     * Return true if the tree parsed is an asset query tree.
     *
     * @return bool
     */
    private function _isInsideAssetQuery()
    {
        if ($this->_resolveInfo->returnType instanceof WrappingType) {
            return $this->_resolveInfo->returnType->getWrappedType()->name === AssetInterface::getName();
        }

        return $this->_resolveInfo->returnType->name === AssetInterface::getName();
    }

    /**
     * Traverse child nodes of a GraphQL query formed as an AST.
     *
     * This method traverses all the child descendant nodes recursively for a GraphQL query AST node,
     * keeping track of where in the tree it currently resides to correctly build the `with` clause
     * for the resulting element query.
     *
     * @param Node $parentNode the parent node being traversed.
     * @param string $prefix the current eager loading prefix to use
     * @param string $context the context in which to search fields
     * @param FieldInterface $parentField the current parent field, that we are in.
     * @return array
     */
    private function _traverseAndExtractRules(Node $parentNode, $prefix = '', $context = 'global', FieldInterface $parentField = null): array
    {
        $eagerLoadNodes = [];
        $subNodes = $parentNode->selectionSet->selections ?? [];

        // If this is an Asset query
        $rootOfAssetQuery = $parentField === null && $this->_isInsideAssetQuery();

        if ($rootOfAssetQuery) {
            // That has transform directive defined
            // We should eager-load transforms using the directive's arguments
            $eagerLoadNodes['withTransforms'] = $this->_prepareTransformArguments($this->_extractTransformDirectiveArguments($parentNode));
        }

        // For each subnode that is a direct descendant
        foreach ($subNodes as $subNode) {
            $nodeName = $subNode->name->value ?? null;

            // If that's a GraphQL field
            if ($subNode instanceof FieldNode) {
                $craftContentField = $this->_eagerLoadableFieldsByContext[$context][$nodeName] ?? null;

                $transformableAssetProperty = ($rootOfAssetQuery || $parentField) && in_array($nodeName, $this->_transformableAssetProperties, true);
                $isAssetField = $craftContentField instanceof AssetField;
                $isUserPhoto = $nodeName === 'photo' && ($parentField === null || $parentField instanceof UserField);

                // That is a Craft field that can be eager-loaded or is the special `children` property
                $possibleTransforms = $transformableAssetProperty || $isAssetField;
                $otherEagerLoadableNode = $nodeName === Gql::GRAPHQL_COUNT_FIELD || in_array($nodeName, $this->_eagerLoadableProperties, true);

                if ($possibleTransforms || $craftContentField || $otherEagerLoadableNode || $isUserPhoto) {
                    // Any arguments?
                    $arguments = $this->_extractArguments($subNode->arguments ?? []);

                    $transformEagerLoadArguments = [];

                    // If it's a place where we can have transforms defined, grab the possible values from directive as well
                    if ($isAssetField) {
                        $transformEagerLoadArguments = $this->_extractTransformDirectiveArguments($subNode);
                        $transformArgumentInjectionPoint = $prefix . $nodeName;
                    }

                    if ($transformableAssetProperty) {
                        $transformEagerLoadArguments = array_merge_recursive($this->_extractTransformDirectiveArguments($subNode), $arguments);
                        $transformArgumentInjectionPoint = StringHelper::removeRight($prefix, '.');

                        // Also, these can't have any arguments.
                        $arguments = [];
                    }

                    // If we've caught any eager-loadable transforms, massage the data.
                    if (!empty($transformEagerLoadArguments)) {
                        $transformEagerLoadArguments = $this->_prepareTransformArguments($transformEagerLoadArguments);

                        if (empty($transformArgumentInjectionPoint)) {
                            $nodeArguments = &$eagerLoadNodes;
                        } else {
                            if (empty($eagerLoadNodes[$transformArgumentInjectionPoint])) {
                                $eagerLoadNodes[$transformArgumentInjectionPoint] = [];
                            }

                            $nodeArguments = &$eagerLoadNodes[$transformArgumentInjectionPoint];
                        }

                        if (empty($nodeArguments['withTransforms'])) {
                            $nodeArguments['withTransforms'] = [];
                        }

                        $nodeArguments['withTransforms'] = array_merge_recursive($nodeArguments['withTransforms'], $transformEagerLoadArguments);
                    }


                    // If this a custom Craft content field
                    if ($craftContentField) {
                        /** @var EagerLoadingFieldInterface $craftContentField */
                        $additionalArguments = $craftContentField->getEagerLoadingGqlConditions();

                        // Load additional requirements enforced by schema, enforcing permissions to see content
                        if ($additionalArguments === false) {
                            // If `false` was returned, make sure nothing is returned by setting an always-false constraint.
                            $arguments = ['id' => 0];
                        } else {
                            // Loop through what schema allows for this content type
                            foreach ($additionalArguments as $argumentName => $argumentValue) {
                                // If they also want to filter by field that is enforced by schema
                                if (isset($arguments[$argumentName])) {
                                    if (!is_array($arguments[$argumentName])) {
                                        $arguments[$argumentName] = [$arguments[$argumentName]];
                                    }

                                    // See what remains after we enforce the scope by schema.
                                    $allowed = array_intersect($arguments[$argumentName], $argumentValue);

                                    // If they wanted to filter by values that were not allowed by schema, make it impossible
                                    if (empty($allowed)) {
                                        $arguments = ['id' => 0];
                                        break;
                                    }

                                    // Otherwise, allow the overlapping things.
                                    $arguments[$argumentName] = $allowed;
                                } else {
                                    // Otherwise, just add their filters to the list.
                                    $arguments[$argumentName] = $argumentValue;
                                }
                            }
                        }
                    }

                    // If they're angling for the count field, alias it so each count field gets their own eager-load arguments.
                    if ($nodeName == Gql::GRAPHQL_COUNT_FIELD) {
                        if (!empty($subNode->alias) && !empty($subNode->alias->value)) {
                            $nodeName = $subNode->alias->value . '@' . $nodeName;
                        } else {
                            // Just re-use the node name, then.
                            $nodeName .= '@' . $nodeName;
                        }
                    }

                    // Add this to the eager loading list.
                    if (!$transformableAssetProperty) {
                        $eagerLoadNodes[$prefix . $nodeName] = array_key_exists($prefix . $nodeName, $eagerLoadNodes) ? array_merge_recursive($eagerLoadNodes[$prefix . $nodeName], $arguments) : $arguments;
                    }

                    // If it has any more selections, build the prefix further and proceed in a recursive manner
                    if (!empty($subNode->selectionSet)) {
                        $traversePrefix = $prefix . ($craftContentField ? $craftContentField->handle : 'children');

                        if ($craftContentField) {
                            // Relational fields should reset context to global.
                            if ($craftContentField instanceof BaseRelationField) {
                                $traverseContext = 'global';
                            } else {
                                $traverseContext = $craftContentField->context;
                            }
                        } else {
                            $traverseContext = $context;
                        }

                        $eagerLoadNodes = array_merge_recursive($eagerLoadNodes, $this->_traverseAndExtractRules($subNode, $traversePrefix . '.', $traverseContext, $craftContentField));
                    }
                }
                // If not, see if it's an inline fragment
            } else if ($subNode instanceof InlineFragmentNode) {
                $nodeName = $subNode->typeCondition->name->value;

                // If we are inside a field that supports different subtypes, it should implement the appropriate interface
                if ($parentField instanceof GqlInlineFragmentFieldInterface) {
                    // Get the Craft entity that correlates to the fragment
                    // Build the prefix, load the context and proceed in a recursive manner
                    $gqlFragmentEntity = $parentField->getGqlFragmentEntityByName($nodeName);
                    $eagerLoadNodes = array_merge_recursive($eagerLoadNodes, $this->_traverseAndExtractRules($subNode, $prefix . $gqlFragmentEntity->getEagerLoadingPrefix() . ':', $gqlFragmentEntity->getFieldContext(), $parentField));
                    // If we are not, just expand the fragment and traverse it as if on the same level in the query tree
                } else {
                    $eagerLoadNodes = array_merge_recursive($eagerLoadNodes, $this->_traverseAndExtractRules($subNode, $prefix, $context, $parentField));
                }
                // Finally, if this is a named fragment, expand it and traverse it as if on the same level in the query tree
            } else if ($subNode instanceof FragmentSpreadNode) {
                $fragmentDefinition = $this->_fragments[$nodeName];
                $eagerLoadNodes = array_merge_recursive($eagerLoadNodes, $this->_traverseAndExtractRules($fragmentDefinition, $prefix, $context, $parentField));
            }
        }

        return $eagerLoadNodes;
    }
}
