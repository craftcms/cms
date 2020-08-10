<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

use Craft;
use craft\base\Component;
use craft\base\EagerLoadingFieldInterface;
use craft\base\FieldInterface;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\events\RegisterGqlEagerLoadableFields;
use craft\fields\Assets as AssetField;
use craft\fields\BaseRelationField;
use craft\fields\Categories as CategoryField;
use craft\fields\Entries as EntryField;
use craft\fields\Users as UserField;
use craft\gql\base\ElementResolver;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\StringHelper;
use craft\services\Gql;
use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\WrappingType;
use yii\base\InvalidArgumentException;

/**
 * Class ElementQueryConditionBuilder.
 *
 * This class is used to analyze a GraphQL query tree based on its ResolveInfo object and generate an array of
 * [method => parameters] to be called on the element query to ensure eager-loading of everything applicable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class ElementQueryConditionBuilder extends Component
{

    /**
     * @event RegisterGqlEagerLoadableFields The event that is triggered when registering additional eager-loading nodes.
     *
     * Plugins get a chance to add their own eager-loadable fields.
     *
     * ---
     * ```php
     * use craft\events\RegisterGqlEagerLoadableFields;
     * use craft\gql\ElementQueryConditionBuilder;
     * use yii\base\Event;
     *
     * Event::on(ElementQueryConditionBuilder::class, ElementQueryConditionBuilder::EVENT_REGISTER_GQL_EAGERLOADABLE_FIELDS, function(RegisterGqlEagerLoadableFields $event) {
     *     // Add my fields
     *     $event->fieldList['myEagerLoadableField'] = ['*'];
     * });
     * ```
     *
     * @since 3.5.0
     */
    const EVENT_REGISTER_GQL_EAGERLOADABLE_FIELDS = 'registerGqlEagerLoadableFields';

    const LOCALIZED_NODENAME = 'localized';

    /**
     * @var ResolveInfo
     */
    private $_resolveInfo;
    private $_fragments;
    private $_eagerLoadableFieldsByContext = [];
    private $_transformableAssetProperties = ['url', 'width', 'height'];
    private $_additionalEagerLoadableNodes = null;


    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        $this->_resolveInfo = $config['resolveInfo'];
        unset($config['resolveInfo']);

        parent::__construct($config);

        $this->_fragments = $this->_resolveInfo->fragments;

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
            if (isset($parameters['field']) && StringHelper::endsWith($element, '@' . Gql::GRAPHQL_COUNT_FIELD)) {
                $relationCountFields[$parameters['field']] = true;
            }
        }

        // Parse everything else
        foreach ($eagerLoadingRules as $element => $parameters) {
            // Don't need these anymore
            if (StringHelper::endsWith($element, '@' . Gql::GRAPHQL_COUNT_FIELD)) {
                continue;
            }

            // If this element was flagged for `withCount`, add it to parameters
            if (!empty($relationCountFields[$element])) {
                $parameters['count'] = true;
            }

            // `withTransforms` get loaded using the `withTransforms` method.
            if ($element === 'withTransforms') {
                $extractedConditions['withTransforms'] = $parameters;
                continue;
            }

            // Just dump it all in where it belongs.
            if (empty($parameters)) {
                $extractedConditions['with'][] = $element;
            } else {
                $extractedConditions['with'][] = [$element, $parameters];
            }
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
     */
    private function _extractArgumentValue(Node $argumentNode)
    {
        $argumentNodeValue = $argumentNode->value;

        if (isset($argumentNodeValue->values)) {
            $extractedValue = [];
            foreach ($argumentNodeValue->values as $value) {
                $extractedValue[] = $this->_extractArgumentValue($value);
            }
        } else {
            if (in_array($argumentNode->kind, ['Argument', 'Variable'], true)) {
                $extractedValue = $argumentNodeValue->kind === 'Variable' ? $this->_resolveInfo->variableValues[$argumentNodeValue->name->value] : $argumentNodeValue->value;
            } else {
                $extractedValue = $argumentNodeValue;
            }
        }

        return $extractedValue;
    }

    /**
     * Figure out whether a node in the parentfield is a special eager-loadable field.
     *
     * @param string $nodeName
     * @param $parentField
     *
     * @return bool
     */
    private function _isAdditionalEagerLoadableNode(string $nodeName, $parentField): bool
    {
        $nodeList = $this->_getKnownSpecialEagerLoadNodes();

        if (isset($nodeList[$nodeName])) {
            // Top level - anything goes
            if ($parentField === null) {
                return true;
            }

            foreach ($nodeList[$nodeName] as $key => $value) {
                if ($key === '*' || $value === '*') {
                    return true;
                }

                if (is_string($value) && is_a($parentField, $value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return true if the special field can be aliased when eager-loading.
     *
     * @param string $nodeName
     * @return bool
     */
    private function _canSpecialFieldBeAliased(string $nodeName): bool
    {
        $nodeList = $this->_getKnownSpecialEagerLoadNodes();

        if (isset($nodeList[$nodeName])) {
            if (!isset($nodeList[$nodeName]['canBeAliased']) || $nodeList[$nodeName]['canBeAliased']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a list of all the known special eager load nodes and their allowed locations.
     *
     * @return array
     */
    private function _getKnownSpecialEagerLoadNodes(): array
    {
        if ($this->_additionalEagerLoadableNodes === null) {
            $list = [
                'photo' => [UserField::class, 'canBeAliased' => false],
                'author' => [EntryField::class, 'canBeAliased' => false],
                'uploader' => [AssetField::class, 'canBeAliased' => false],
                'parent' => [BaseRelationField::class, 'canBeAliased' => false],
                'children' => [BaseRelationField::class, 'canBeAliased' => false],
                'currentRevision' => [BaseRelationField::class, 'canBeAliased' => false],
                'draftCreator' => [BaseRelationField::class, 'canBeAliased' => false],
                'revisionCreator' => [BaseRelationField::class, 'canBeAliased' => false],
                self::LOCALIZED_NODENAME => [CategoryField::class, EntryField::class],
            ];

            if ($this->hasEventHandlers(self::EVENT_REGISTER_GQL_EAGERLOADABLE_FIELDS)) {
                $event = new RegisterGqlEagerLoadableFields([
                    'fieldList' => $list
                ]);
                $this->trigger(self::EVENT_REGISTER_GQL_EAGERLOADABLE_FIELDS, $event);

                $list = $event->fieldList;
            }

            $this->_additionalEagerLoadableNodes = $list;
        }

        return (array)$this->_additionalEagerLoadableNodes;
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

        $arguments = [GqlHelper::prepareTransformArguments($arguments)];

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
                $isSpecialField = $this->_isAdditionalEagerLoadableNode($nodeName, $parentField);
                $canBeAliased = $isSpecialField && $this->_canSpecialFieldBeAliased($nodeName);

                // That is a Craft field that can be eager-loaded or is the special `children` property
                $possibleTransforms = $transformableAssetProperty || $isAssetField;
                $otherEagerLoadableNode = $nodeName === Gql::GRAPHQL_COUNT_FIELD;

                if ($possibleTransforms || $craftContentField || $otherEagerLoadableNode || $isSpecialField) {
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

                        // For relational fields, prepare the arguments.
                        if ($craftContentField instanceof BaseRelationField) {
                            $arguments = ElementResolver::prepareArguments($arguments);
                        }
                    }

                    // See if the field was aliased in the query
                    $alias = ($canBeAliased && !(empty($subNode->alias)) && !empty($subNode->alias->value)) ? $subNode->alias->value : null;

                    // If they're angling for the count field, alias it so each count field gets their own eager-load arguments.
                    if ($nodeName === Gql::GRAPHQL_COUNT_FIELD) {
                        if ($alias) {
                            $nodeName = $alias . '@' . Gql::GRAPHQL_COUNT_FIELD;
                        } else {
                            // Just re-use the node name, then.
                            $nodeName .= '@' . Gql::GRAPHQL_COUNT_FIELD;
                        }
                    }

                    $nodeKey = $alias ? $nodeName . ' as ' . $alias : $nodeName;

                    // Add this to the eager loading list.
                    if (!$transformableAssetProperty) {
                        $eagerLoadNodes[$prefix . $nodeKey] = array_key_exists($prefix . $nodeKey, $eagerLoadNodes) ? array_merge_recursive($eagerLoadNodes[$prefix . $nodeKey], $arguments) : $arguments;
                    }

                    // If it has any more selections, build the prefix further and proceed in a recursive manner
                    if (!empty($subNode->selectionSet)) {
                        if ($alias) {
                            $traversePrefix = $prefix . $alias;
                        } else {
                            $traversePrefix = $prefix . ($craftContentField ? $craftContentField->handle : $nodeName);
                        }

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

                        $eagerLoadNodes = array_merge_recursive($eagerLoadNodes, $this->_traverseAndExtractRules($subNode, $traversePrefix . '.', $traverseContext, $nodeName === self::LOCALIZED_NODENAME ? $parentField : $craftContentField));
                    }
                }
                // If not, see if it's a fragment
            } else if ($subNode instanceof InlineFragmentNode || $subNode instanceof FragmentSpreadNode) {
                // For named fragments, replace the node with the actual fragment.
                if ($subNode instanceof FragmentSpreadNode) {
                    $subNode = $this->_fragments[$nodeName];
                }

                $nodeName = $subNode->typeCondition->name->value;

                // If we are inside a field that supports different subtypes, it should implement the appropriate interface
                if ($parentField instanceof GqlInlineFragmentFieldInterface) {
                    // Get the Craft entity that correlates to the fragment
                    // Build the prefix, load the context and proceed in a recursive manner
                    try {
                        $gqlFragmentEntity = $parentField->getGqlFragmentEntityByName($nodeName);
                        $eagerLoadNodes = array_merge_recursive($eagerLoadNodes, $this->_traverseAndExtractRules($subNode, $prefix . $gqlFragmentEntity->getEagerLoadingPrefix() . ':', $gqlFragmentEntity->getFieldContext(), $parentField));
                        // This is to be expected, depending on whether the fragment is targeted towards the field itself instead of its subtypes.
                    } catch (InvalidArgumentException $exception) {
                        $eagerLoadNodes = array_merge_recursive($eagerLoadNodes, $this->_traverseAndExtractRules($subNode, $prefix, $context, $parentField));
                    }
                    // If we are not, just expand the fragment and traverse it as if on the same level in the query tree
                } else {
                    $eagerLoadNodes = array_merge_recursive($eagerLoadNodes, $this->_traverseAndExtractRules($subNode, $prefix, $context, $parentField));
                }
            }
        }

        return $eagerLoadNodes;
    }
}
