<?php
namespace craft\gql\types;

use craft\gql\directives\BaseDirective;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class ElementType
 */
abstract class Element extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['resolveField'] = [$this, 'resolveWithDirectives'];
        parent::__construct($config);
    }

    /**
     * Resolve a field value with arguments, context and resolve information.
     *
     * @param mixed $source The parent data source to use for resolving this field
     * @param array $arguments arguments for resolving this field.
     * @param mixed $context The context shared between all resolvers
     * @param ResolveInfo $resolveInfo The resolve information
     *
     * @return mixed $result
     */
    abstract protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo);

    /**
     * Resolve a value with the directives that apply to it.
     *
     * TODO: once we allow querying for structure as well, this will move up to the base type.
     *
     * @param mixed $source The parent data source to use for resolving this field
     * @param array $arguments arguments for resolving this field.
     * @param mixed $context The context shared between all resolvers
     * @param ResolveInfo $resolveInfo The resolve information
     *
     * @return mixed $result
     */
    public function resolveWithDirectives($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        $value = $this->resolve($source, $arguments, $context, $resolveInfo);

        if (isset($resolveInfo->fieldNodes[0])) {
            foreach ($resolveInfo->fieldNodes[0]->directives as $directive) {
                /** @var BaseDirective $directiveEntity */
                $directiveEntity = GqlEntityRegistry::getEntity($directive->name->value);
                $arguments = [];

                if (isset($directive->arguments[0])) {
                    foreach ($directive->arguments as $argument) {
                        $arguments[$argument->name->value] = $argument->value->value;
                    }
                }

                $value = $directiveEntity::applyDirective($source, $value, $arguments);
            }
        }

        return $value;
    }
}
