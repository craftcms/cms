<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use ArrayObject;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Assets as AssetField;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Entries as EntryField;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Users as UserField;
use CraftCms\Cms\Gql\Contracts\GqlInlineFragmentFieldInterface;
use CraftCms\Cms\Gql\Events\RegisterGqlEagerLoadableFields;
use CraftCms\Cms\Gql\Gql as GqlService;
use CraftCms\Cms\Gql\Interfaces\Elements\Asset as AssetInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ObjectFieldNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\WrappingType;
use InvalidArgumentException;

class ElementQueryConditionBuilder extends Component
{
    public const string LOCALIZED_NODENAME = 'localized';

    private ?ResolveInfo $_resolveInfo;

    private ArgumentManager $_argumentManager;

    private array $_fragments;

    private array $_eagerLoadableFieldsByContext = [];

    private array $_transformableAssetProperties = ['url', 'width', 'height'];

    private array $_additionalEagerLoadableNodes;

    public function __construct($config = [])
    {
        $this->_resolveInfo = Arr::pull($config, 'resolveInfo');

        if ($this->_resolveInfo) {
            $this->_fragments = $this->_resolveInfo->fragments;
        }

        parent::__construct($config);

        // Cache all eager-loadable fields by context
        $allFields = Fields::getAllFields(false);

        foreach ($allFields as $field) {
            if ($field instanceof EagerLoadingFieldInterface) {
                /** @var EagerLoadingFieldInterface&Field $field */
                $this->_eagerLoadableFieldsByContext[$field->context][$field->handle] = $field;
            }
        }
    }

    public function setResolveInfo(ResolveInfo $resolveInfo): void
    {
        $this->_resolveInfo = $resolveInfo;
        $this->_fragments = $this->_resolveInfo->fragments;
    }

    public function setArgumentManager(ArgumentManager $argumentManager): void
    {
        $this->_argumentManager = $argumentManager;
    }

    /**
     * Extract the query conditions based on the resolve information passed in the constructor.
     * Returns an array of [methodName => parameters] to be called on the element query.
     *
     * @param  FieldInterface|null  $startingParentField  the starting parent field for the extraction, if any
     */
    public function extractQueryConditions(?FieldInterface $startingParentField = null): array
    {
        /** @var ArrayObject $fieldNodes */
        $fieldNodes = $this->_resolveInfo->fieldNodes;

        if ($fieldNodes->count() === 0 || empty($fieldNodes[0])) {
            return [];
        }

        $startingNode = $fieldNodes[0];

        $rootPlan = new EagerLoadPlan;

        // Load up all eager loading rules.
        $extractedConditions = [
            'with' => $this->_traverseAndBuildPlans($startingNode, $rootPlan, $startingParentField, null, $startingParentField ? $startingParentField->context : 'global'),
        ];

        if (! empty($rootPlan->criteria['withTransforms'])) {
            $extractedConditions['withTransforms'] = $rootPlan->criteria['withTransforms'];
        }

        return $extractedConditions;
    }

    /**
     * @param  ArgumentNode[]|NodeList  $argumentNodes
     */
    private function _extractArguments(NodeList|array $argumentNodes): array
    {
        $arguments = [];

        foreach ($argumentNodes as $argumentNode) {
            $arguments[$argumentNode->name->value] = $this->_extractArgumentValue($argumentNode);
        }

        return $arguments;
    }

    private function _extractArgumentValue(Node $argumentNode): mixed
    {
        // Deal with a raw object value.
        if ($argumentNode->kind === 'ObjectValue') {
            /** @var ObjectValueNode $argumentNode */
            $extractedValue = [];
            foreach ($argumentNode->fields as $fieldNode) {
                $extractedValue[$fieldNode->name->value] = $this->_extractArgumentValue($fieldNode);
            }

            return $extractedValue;
        }

        if (in_array($argumentNode->kind, ['Argument', 'Variable', 'ListValue', 'ObjectField'], true)) {
            /** @var ArgumentNode|VariableNode|ListValueNode|ObjectFieldNode $argumentNode */
            $argumentNodeValue = $argumentNode->value;

            switch ($argumentNodeValue->kind) {
                case 'Variable':
                    return $this->_resolveInfo->variableValues[$argumentNodeValue->name->value];
                case 'ListValue':
                    $extractedValue = [];
                    foreach ($argumentNodeValue->values as $value) {
                        $extractedValue[] = $this->_extractArgumentValue($value);
                    }

                    return $extractedValue;
                case 'ObjectValue':
                    $extractedValue = [];
                    foreach ($argumentNodeValue->fields as $fieldNode) {
                        $extractedValue[$fieldNode->name->value] = $this->_extractArgumentValue($fieldNode);
                    }

                    return $extractedValue;
                case 'NullValue':
                    return null;
                default:
                    return $argumentNodeValue->value;
            }
        }

        $value = $argumentNode->value ?? null;

        return $argumentNode->kind === 'IntValue' ? (int) $value : $value;
    }

    private function _isAdditionalEagerLoadableNode(string $nodeName, mixed $parentField): bool
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

    private function _canSpecialFieldBeAliased(string $nodeName): bool
    {
        $nodeList = $this->_getKnownSpecialEagerLoadNodes();

        if (isset($nodeList[$nodeName])) {
            return $nodeList[$nodeName]['canBeAliased'] ?? true;
        }

        return false;
    }

    private function _getKnownSpecialEagerLoadNodes(): array
    {
        if (! isset($this->_additionalEagerLoadableNodes)) {
            $list = [
                'photo' => [UserField::class, 'canBeAliased' => false],
                'addresses' => [UserField::class, 'canBeAliased' => true],
                'author' => [EntryField::class, 'canBeAliased' => false],
                'uploader' => [AssetField::class, 'canBeAliased' => false],
                'parent' => [BaseRelationField::class, 'canBeAliased' => false],
                'ancestors' => [BaseRelationField::class, 'canBeAliased' => false],
                'children' => [BaseRelationField::class, 'canBeAliased' => true],
                'descendants' => [BaseRelationField::class, 'canBeAliased' => false],
                'currentRevision' => [BaseRelationField::class, 'canBeAliased' => false],
                'draftCreator' => [BaseRelationField::class, 'canBeAliased' => false],
                'drafts' => [BaseRelationField::class, 'canBeAliased' => false],
                'revisions' => [BaseRelationField::class, 'canBeAliased' => false],
                'revisionCreator' => [BaseRelationField::class, 'canBeAliased' => false],
                self::LOCALIZED_NODENAME => [EntryField::class],
            ];

            event($event = new RegisterGqlEagerLoadableFields(fieldList: $list));

            $this->_additionalEagerLoadableNodes = $event->fieldList;
        }

        return $this->_additionalEagerLoadableNodes;
    }

    private function _extractTransformDirectiveArguments(Node $node): array
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

    private function _prepareTransformArguments(array $arguments): array
    {
        if (empty($arguments)) {
            return [];
        }

        return [GqlHelper::prepareTransformArguments($arguments)];
    }

    private function _isInsideAssetQuery(): bool
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
     * @param  Node  $parentNode  the parent node being traversed.
     * @param  EagerLoadPlan  $parentPlan  The parent eager-loading plan
     * @param  FieldInterface|null  $parentField  the current parent field, that we are in.
     * @param  Node|null  $wrappingFragment  the wrapping fragment node, if any
     * @param  string  $context  the context in which to search fields
     */
    private function _traverseAndBuildPlans(Node $parentNode, EagerLoadPlan $parentPlan, ?FieldInterface $parentField = null, ?Node $wrappingFragment = null, string $context = 'global'): array
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
                /** @var FieldInterface|null $craftContentField */
                $craftContentField = $this->_eagerLoadableFieldsByContext[$context][$nodeName] ?? null;

                $transformableAssetProperty = ($rootOfAssetQuery || $parentField instanceof AssetField) && in_array($nodeName, $this->_transformableAssetProperties, true);
                $isAssetField = $craftContentField instanceof AssetField;
                $isSpecialField = $this->_isAdditionalEagerLoadableNode((string) $nodeName, $parentField);
                $canBeAliased = ! $isSpecialField || $this->_canSpecialFieldBeAliased((string) $nodeName);

                $possibleTransforms = $transformableAssetProperty || $isAssetField;
                $otherEagerLoadableNode = $nodeName === GqlService::GRAPHQL_COUNT_FIELD;

                // That is a Craft field that can be eager-loaded or is a special eager-loadable field
                if ($possibleTransforms || $craftContentField || $otherEagerLoadableNode || $isSpecialField) {
                    $plan = new EagerLoadPlan;

                    // Any arguments?
                    $arguments = $this->_extractArguments($subNode->arguments);

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
                    if (! empty($transformEagerLoadArguments)) {
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
                        if ($additionalArguments === null) {
                            // If `false` was returned, make sure nothing is returned by setting a constraint that always fails.
                            $arguments = ['id' => ['and', 1, 2]];
                        } else {
                            // Loop through what schema allows for this content type
                            foreach ($additionalArguments as $argumentName => $argumentValue) {
                                // If they also want to filter by field that is enforced by schema
                                if (isset($arguments[$argumentName])) {
                                    if (! is_array($arguments[$argumentName])) {
                                        $arguments[$argumentName] = [$arguments[$argumentName]];
                                    }

                                    // See what remains after we enforce the scope by schema.
                                    $allowed = array_intersect($arguments[$argumentName], $argumentValue);

                                    // If they wanted to filter by values that were not allowed by schema, make it impossible
                                    if (empty($allowed)) {
                                        $arguments = ['id' => ['and', 1, 2]];
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
                        if ($craftContentField instanceof EagerLoadingFieldInterface) {
                            $arguments = $this->_argumentManager->prepareArguments($arguments);
                        }
                    }

                    // See if the field was aliased in the query
                    $alias = ($canBeAliased && ! (empty($subNode->alias)) && ! empty($subNode->alias->value)) ? $subNode->alias->value : null;

                    // If they're angling for the count field, alias it so each count field gets their own eager-load arguments.
                    if ($nodeName === GqlService::GRAPHQL_COUNT_FIELD) {
                        $countedHandles[] = $arguments['field'];

                        continue;
                    }

                    // Add this to the eager loading list.
                    if (! $transformableAssetProperty) {
                        $plan->handle = $nodeName;
                        $plan->alias = $alias ?: $nodeName;
                        /** @var InlineFragmentNode|FragmentDefinitionNode|null $wrappingFragment */
                        if ($wrappingFragment) {
                            $plan->when = function (Element $element) use ($wrappingFragment) {
                                $typeName = $wrappingFragment->typeCondition->name->value;
                                if (preg_match('/^(\w+)Interface$/', $typeName, $match)) {
                                    return str_ends_with($element->getGqlTypeName(), "_{$match[1]}");
                                }

                                return $element->getGqlTypeName() === $typeName;
                            };
                        }
                        $plan->criteria = array_merge_recursive($plan->criteria, $this->_argumentManager->prepareArguments($arguments));
                    }

                    // If it has any more selections, build the plans recursively
                    if (! empty($subNode->selectionSet)) {
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

                        $plan->nested = $this->_traverseAndBuildPlans($subNode, $plan, $nodeName === self::LOCALIZED_NODENAME ? $parentField : $craftContentField, $wrappingFragment, $traverseContext);
                    }
                }
                // If not, see if it's a fragment
            } elseif ($subNode instanceof InlineFragmentNode || $subNode instanceof FragmentSpreadNode) {
                $plan = new EagerLoadPlan;

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
                        $plan->nested = $this->_traverseAndBuildPlans($subNode, $plan, $parentField, $wrappingFragment, $gqlFragmentEntity->getFieldContext());

                        // Correct the handles and, maybe, aliases.
                        foreach ($plan->nested as $nestedPlan) {
                            $newHandle = Str::chopStart($gqlFragmentEntity->getEagerLoadingPrefix().':'.$nestedPlan->handle, ':');
                            if ($nestedPlan->handle === $nestedPlan->alias) {
                                $nestedPlan->alias = $newHandle;
                            }
                            $nestedPlan->handle = $newHandle;
                        }
                        // This is to be expected, depending on whether the fragment is targeted towards the field itself instead of its subtypes.
                    } catch (InvalidArgumentException) {
                        $plan->nested = $this->_traverseAndBuildPlans($subNode, $plan, $parentField, $wrappingFragment, $context);
                    }
                    // If we are not, just expand the fragment and traverse it as if on the same level in the query tree
                } else {
                    $plan->nested = $this->_traverseAndBuildPlans($subNode, $plan, $parentField, $wrappingFragment, $context);
                }
            }

            if (isset($plan)) {
                if (! empty($plan->handle)) {
                    $plans[] = $plan;
                } elseif (! empty($plan->nested)) {
                    // Unpack plans generated by parsing fragments.
                    foreach ($plan->nested as $nestedPlan) {
                        $plans[] = $nestedPlan;
                    }
                }
                unset($plan);
            }
        }

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
            if (! $foundPlan) {
                $plans[] = new EagerLoadPlan(
                    handle: $countedHandle,
                    alias: $countedHandle,
                    count: true,
                );
            }
        }

        return $plans;
    }

    public function canNodeBeAliased(string $nodeName, $parentField = null): bool
    {
        if (! $this->_isAdditionalEagerLoadableNode($nodeName, $parentField)) {
            return true;
        }

        return $this->_canSpecialFieldBeAliased($nodeName);
    }
}
