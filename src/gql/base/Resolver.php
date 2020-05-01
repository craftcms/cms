<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Field;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\fields\BaseRelationField;
use craft\helpers\StringHelper;
use craft\services\Gql;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Resolver
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class Resolver
{
    /**
     * @var array Cache fields by context.
     */
    protected static $eagerLoadableFieldsByContext;

    /**
     * Resolve a field to its value.
     *
     * @param mixed $source The parent data source to use for resolving this field
     * @param array $arguments arguments for resolving this field.
     * @param mixed $context The context shared between all resolvers
     * @param ResolveInfo $resolveInfo The resolve information
     */
    abstract public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo);

    /**
     * Returns a list of all the arguments that can be accepted as arrays.
     *
     * @return array
     */
    public static function getArrayableArguments(): array
    {
        return [];
    }

    /**
     * Prepare arguments for use, converting to array where applicable.
     *
     * @param array $arguments
     * @return array
     */
    public static function prepareArguments(array $arguments): array
    {
        $arrayable = static::getArrayableArguments();

        foreach ($arguments as $key => &$value) {
            if (in_array($key, $arrayable, true) && !empty($value) && !is_array($value)) {
                $array = StringHelper::split($value);

                if (count($array) > 1) {
                    $value = $array;
                }
            } else if (is_array($value) && count($value) === 1 && isset($value[0]) && $value[0] === '*') {
                // Normalize ['*'] to '*'
                $value = '*';
            }
        }

        return $arguments;
    }

    /**
     * Extract eager load conditions for a given resolve information. Preferrably at the very top of the query.
     *
     * @param Node $parentNode
     * @return array
     */
    protected static function extractEagerLoadCondition(ResolveInfo $resolveInfo)
    {
        $startingNode = $resolveInfo->fieldNodes[0];
        $fragments = $resolveInfo->fragments;

        if ($startingNode === null) {
            return [];
        }

        $allFields = Craft::$app->getFields()->getAllFields(false);
        $eagerLoadableFieldsByContext = [];

        /** @var Field $field */
        foreach ($allFields as $field) {
            if ($field instanceof EagerLoadingFieldInterface) {
                $eagerLoadableFieldsByContext[$field->context][$field->handle] = $field;
            }
        }

        /**
         * Traverse child nodes of a GraphQL query formed as AST.
         *
         * This method traverses all the child descendant nodes recursively for a GraphQL query AST node,
         * keeping track of where in the tree it currently resides to correctly build the `with` clause
         * for the resulting element query.
         *
         * @param Node $parentNode the parent node being traversed.
         * @param string $prefix the current eager loading prefix to use
         * @param string $context the context in which to search fields
         * @param null $parentField the current parent field, that we are in.
         * @return array
         */
        $traverseNodes = function(Node $parentNode, $prefix = '', $context = 'global', $parentField = null) use (&$traverseNodes, $fragments, $eagerLoadableFieldsByContext, $resolveInfo) {
            $eagerLoadNodes = [];
            $subNodes = $parentNode->selectionSet->selections ?? [];

            // For each subnode that is a direct descendant
            foreach ($subNodes as $subNode) {
                $nodeName = $subNode->name->value ?? null;

                // If that's a GraphQL field
                if ($subNode instanceof FieldNode) {
                    $field = $eagerLoadableFieldsByContext[$context][$nodeName] ?? null;

                    // That is a Craft field that can be eager-loaded or is the special `children` property
                    if ($field || $nodeName === 'children' || $nodeName === Gql::GRAPHQL_COUNT_FIELD) {
                        $arguments = [];

                        // Any arguments?
                        $argumentNodes = $subNode->arguments ?? [];

                        foreach ($argumentNodes as $argumentNode) {
                            if (isset($argumentNode->value->values)) {
                                $values = [];

                                foreach ($argumentNode->value->values as $value) {
                                    $values[] = $value->value;
                                }

                                $arguments[$argumentNode->name->value] = $values;
                            } else {
                                $arguments[$argumentNode->name->value] = $argumentNode->value->kind === 'Variable' ? $resolveInfo->variableValues[$argumentNode->value->name->value] : ($argumentNode->value->value ?? null);
                            }
                        }

                        if ($field) {
                            /** @var EagerLoadingFieldInterface $field */
                            $additionalArguments = $field->getEagerLoadingGqlConditions();

                            // Load additional requirements enforced by schema
                            if ($additionalArguments === false) {
                                // If `false` was returned, make sure nothing is returned.
                                $arguments = ['id' => 0];
                            } else {
                                foreach ($additionalArguments as $argumentName => $argumentValue) {
                                    if (isset($arguments[$argumentName])) {
                                        // If a filter was used that was not an array, make it an array
                                        if (!is_array($arguments[$argumentName])) {
                                            $arguments[$argumentName] = [$arguments[$argumentName]];
                                        }

                                        // See what remains after we enforce the scope by schema
                                        $allowed = array_intersect($arguments[$argumentName], $argumentValue);

                                        // If that cleared out all that they wanted, make it an impossible condition
                                        if (empty($allowed)) {
                                            $arguments = ['id' => 0];

                                            break;
                                        } else {
                                            $arguments[$argumentName] = $allowed;
                                        }
                                    } else {
                                        $arguments[$argumentName] = $argumentValue;
                                    }
                                }
                            }
                        }

                        if ($nodeName == Gql::GRAPHQL_COUNT_FIELD) {
                            if (!empty($subNode->alias) && !empty($subNode->alias->value)) {
                                $nodeName = $subNode->alias->value . '@' . $nodeName;
                            } else {
                                // Just re-use the node name, then.
                                $nodeName .= '@' . $nodeName;
                            }
                        }

                        // Add it all to the list
                        if (array_key_exists($prefix . $nodeName, $eagerLoadNodes)) {
                            $eagerLoadNodes[$prefix . $nodeName] = array_merge_recursive($eagerLoadNodes[$prefix . $nodeName], $arguments);
                        } else {
                            $eagerLoadNodes[$prefix . $nodeName] = $arguments;
                        }

                        // If it has any more selections, build the prefix further and proceed in a recursive manner
                        if (!empty($subNode->selectionSet)) {
                            $traversePrefix = $prefix . ($field ? $field->handle : 'children');

                            if ($field) {
                                // Relational fields should reset context to global.
                                if ($field instanceof BaseRelationField) {
                                    $traverseContext = 'global';
                                } else {
                                    $traverseContext = $field->context;
                                }
                            } else {
                                $traverseContext = $context;
                            }

                            $eagerLoadNodes += $traverseNodes($subNode, $traversePrefix . '.', $traverseContext, $field);
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
                        $eagerLoadNodes += $traverseNodes($subNode, $prefix . $gqlFragmentEntity->getEagerLoadingPrefix() . ':', $gqlFragmentEntity->getFieldContext(), $parentField);
                        // If we are not, just expand the fragment and traverse it as if on the same level in the query tree
                    } else {
                        $eagerLoadNodes += $traverseNodes($subNode, $prefix, $context, $parentField);
                    }
                    // Finally, if this is a named fragment, expand it and traverse it as if on the same level in the query tree
                } else if ($subNode instanceof FragmentSpreadNode) {
                    $fragmentDefinition = $fragments[$nodeName];
                    $eagerLoadNodes += $traverseNodes($fragmentDefinition, $prefix, $context, $parentField);
                }
            }

            return $eagerLoadNodes;
        };

        return $traverseNodes($startingNode);
    }
}
