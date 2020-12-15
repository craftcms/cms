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
use craft\base\Element;
use craft\base\FieldInterface;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\elements\db\EagerLoadPlan;
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
use GraphQL\Language\AST\FragmentDefinitionNode;
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
        $this->_resolveInfo = $config['resolveInfo'] ?? null;
        unset($config['resolveInfo']);

        if ($this->_resolveInfo) {
            $this->_fragments = $this->_resolveInfo->fragments;
        }

        parent::__construct($config);

        // Cache all eager-loadable fields by context
        $allFields = Craft::$app->getFields()->getAllFields(false);

        foreach ($allFields as $field) {
            if ($field instanceof EagerLoadingFieldInterface) {
                $this->_eagerLoadableFieldsByContext[$field->context][$field->handle] = $field;
            }
        }
    }

    /**
     * Set the current ResolveInfo object.
     *
     * @param ResolveInfo $resolveInfo
     */
    public function setResolveInfo(ResolveInfo $resolveInfo)
    {
        $this->_resolveInfo = $resolveInfo;
        $this->_fragments = $this->_resolveInfo->fragments;
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

        if ($startingNode === null) {
            return [];
        }

        $rootPlan = new EagerLoadPlan();

        // Load up all eager loading rules.
        $extractedConditions = [
            'with' => $this->_traversAndBuildPlans($startingNode, $startingParentField ? $startingParentField->context : 'global', $startingParentField, null, $rootPlan)
        ];

        if (!empty($rootPlan->criteria['withTransforms'])) {
            $extractedConditions['withTransforms'] = $rootPlan->criteria['withTransforms'];
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
     * @param string $context the context in which to search fields
     * @param FieldInterface $parentField the current parent field, that we are in.
     * @param Node|null $wrappingFragment the wrapping fragment node, if any
     * @param EagerLoadPlan $parentPlan The parent eager-loading plan
     * @return array
     */
    private function _traversAndBuildPlans(Node $parentNode, $context = 'global', FieldInterface $parentField = null, Node $wrappingFragment = null, EagerLoadPlan $parentPlan): array
    {
        $subNodes = $parentNode->selectionSet->selections ?? [];
        $plans = [];

        $rootOfAssetQuery = $parentField === null && $this->_isInsideAssetQuery();

        if ($rootOfAssetQuery) {
            // If this is a root asset query that has transform directive defined
            // We should eager-load transforms using the directive's arguments
            $transformArguments = $this->_prepareTransformArguments($this->_extractTransformDirectiveArguments($parentNode));
            if ($transformArguments) {
                $parentPlan->criteria['withTransforms'] = $transformArguments;
            }
        }

        $countedHandles = [];

        // For each subnode that is a direct descendant
        foreach ($subNodes as $subNode) {
            $nodeName = $subNode->name->value ?? null;

            // If that's a GraphQL field
            if ($subNode instanceof FieldNode) {
                $craftContentField = $this->_eagerLoadableFieldsByContext[$context][$nodeName] ?? null;

                $transformableAssetProperty = ($rootOfAssetQuery || $parentField instanceof AssetField) && in_array($nodeName, $this->_transformableAssetProperties, true);
                $isAssetField = $craftContentField instanceof AssetField;
                $isSpecialField = $this->_isAdditionalEagerLoadableNode($nodeName, $parentField);
                $canBeAliased = !$isSpecialField || $this->_canSpecialFieldBeAliased($nodeName);

                $possibleTransforms = $transformableAssetProperty || $isAssetField;
                $otherEagerLoadableNode = $nodeName === Gql::GRAPHQL_COUNT_FIELD;

                // That is a Craft field that can be eager-loaded or is a special eager-loadable field
                if ($possibleTransforms || $craftContentField || $otherEagerLoadableNode || $isSpecialField) {
                    $plan = new EagerLoadPlan();

                    // Any arguments?
                    $arguments = $this->_extractArguments($subNode->arguments ?? []);

                    $transformEagerLoadArguments = [];


                    // If it's a place where we can have transforms defined, grab the possible values from directive as well
                    if ($isAssetField) {
                        $transformEagerLoadArguments = $this->_extractTransformDirectiveArguments($subNode);
                    }

                    if ($transformableAssetProperty) {
                        $transformEagerLoadArguments = array_merge_recursive($this->_extractTransformDirectiveArguments($subNode), $arguments);

                        // Also, these can't have any arguments.
                        $arguments = [];
                    }

                    // If we've found any eager-loadable transforms, massage the data.
                    if (!empty($transformEagerLoadArguments)) {
                        $transformEagerLoadArguments = $this->_prepareTransformArguments($transformEagerLoadArguments);
                        // If the property is transformable, then merge into the _parent_ plan.
                        if ($transformableAssetProperty) {
                            $parentPlan->criteria['withTransforms'] = array_merge_recursive($parentPlan->criteria['withTransforms'] ?? [], $transformEagerLoadArguments);
                        } else {
                            $plan->criteria['withTransforms'] = array_merge_recursive($plan->criteria['withTransforms'] ?? [], $transformEagerLoadArguments);
                        }
                    }

                    // If this a custom Craft content field
                    if ($craftContentField) {
                        /** @var EagerLoadingFieldInterface $craftContentField */
                        $additionalArguments = $craftContentField->getEagerLoadingGqlConditions();

                        // Load additional requirements enforced by schema, enforcing permissions to see content
                        if ($additionalArguments === false) {
                            // If `false` was returned, make sure nothing is returned by setting a constraint that always fails.
                            $arguments = ['id' => ['and', 1, 2]];
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
                        $countedHandles[] = $arguments['field'];
                        continue;
                    }

                    if (!$transformableAssetProperty) {
                        $plan->handle = $nodeName;
                        $plan->alias = $alias ?: $nodeName;
                    }

                    // Add this to the eager loading list.
                    if (!$transformableAssetProperty) {
                        /** @var InlineFragmentNode|FragmentDefinitionNode $wrappingFragment */
                        if ($wrappingFragment) {
                            // TODO: In Craft 4, get rid of all closures
                            $plan->when = function(Element $element) use ($wrappingFragment) {
                                return $element->getGqlTypeName() === $wrappingFragment->typeCondition->name->value;
                            };
                        }
                        $plan->criteria = array_merge_recursive($plan->criteria, $arguments);
                    }

                    // If it has any more selections, build the plans recursively
                    if (!empty($subNode->selectionSet)) {
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

                        $plan->nested = $this->_traversAndBuildPlans($subNode, $traverseContext, $nodeName === self::LOCALIZED_NODENAME ? $parentField : $craftContentField, $wrappingFragment, $plan);
                    }
                }
                // If not, see if it's a fragment
            } else if ($subNode instanceof InlineFragmentNode || $subNode instanceof FragmentSpreadNode) {
                $plan = new EagerLoadPlan();

                // For named fragments, replace the node with the actual fragment.
                if ($subNode instanceof FragmentSpreadNode) {
                    $subNode = $this->_fragments[$nodeName];
                }

                $wrappingFragment = $subNode;

                $nodeName = $subNode->typeCondition->name->value;

                // If we are inside a field that supports different subtypes, it should implement the appropriate interface
                if ($parentField instanceof GqlInlineFragmentFieldInterface) {
                    // Get the Craft entity that correlates to the fragment
                    // Build the prefix, load the context and proceed in a recursive manner
                    try {
                        $gqlFragmentEntity = $parentField->getGqlFragmentEntityByName($nodeName);
                        $plan->nested = $this->_traversAndBuildPlans($subNode, $gqlFragmentEntity->getFieldContext(), $parentField, $wrappingFragment, $plan);

                        // Correct the handles and, maybe, aliases.
                        foreach ($plan->nested as $nestedPlan) {
                            $newHandle = StringHelper::removeLeft($gqlFragmentEntity->getEagerLoadingPrefix() . ':' . $nestedPlan->handle, ':');
                            if ($nestedPlan->handle === $nestedPlan->alias) {
                                $nestedPlan->alias = $newHandle;
                            }
                            $nestedPlan->handle = $newHandle;
                        }
                        // This is to be expected, depending on whether the fragment is targeted towards the field itself instead of its subtypes.
                    } catch (InvalidArgumentException $exception) {
                        $plan->nested = $this->_traversAndBuildPlans($subNode, $context, $parentField, $wrappingFragment, $plan);
                    }
                    // If we are not, just expand the fragment and traverse it as if on the same level in the query tree
                } else {
                    $plan->nested = $this->_traversAndBuildPlans($subNode, $context, $parentField, $wrappingFragment, $plan);
                }
            }

            if (isset($plan)) {
                if (!empty($plan->handle)) {
                    $plans[] = $plan;
                } else if (!empty($plan->nested)) {
                    // Unpack plans generated by parsing fragments.
                    foreach ($plan->nested as $nestedPlan) {
                        $plans[] = $nestedPlan;
                    }
                }
                unset($plan);
            }
        }

        if (!empty($countedHandles)) {
            // For each required count
            foreach ($countedHandles as $countedHandle) {
                $foundPlan = false;

                // Check if we can just flag an existing plan to load the count as well
                foreach ($plans as $plan) {
                    if ($plan->handle === $countedHandle) {
                        $plan->count = true;
                        $foundPlan = true;
                    }
                }

                // If not, create a new plan.
                if (!$foundPlan) {
                    $plans[] = new EagerLoadPlan([
                        'handle' => $countedHandle,
                        'alias' => $countedHandle,
                        'count' => true,
                    ]);
                }
            }
        }

        return array_values($plans);
    }

    /**
     * @param string $nodeName
     * @param null $parentField
     * @return bool
     */
    public function canNodeBeAliased(string $nodeName, $parentField = null)
    {
        return !$this->_isAdditionalEagerLoadableNode($nodeName, $parentField) || $this->_canSpecialFieldBeAliased($nodeName);
    }
}
