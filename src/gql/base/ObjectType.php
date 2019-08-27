<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\errors\GqlException;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ObjectType as GqlObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class ObjectType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class ObjectType extends GqlObjectType
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
     * Resolve a value with the directives that apply to it.
     *
     * @param mixed $source The parent data source to use for resolving this field
     * @param array $arguments arguments for resolving this field.
     * @param mixed $context The context shared between all resolvers
     * @param ResolveInfo $resolveInfo The resolve information
     *
     * @return mixed $result
     * @throws GqlException if an error occurs
     */
    public function resolveWithDirectives($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        try {
            $value = $this->resolve($source, $arguments, $context, $resolveInfo);

            if (isset($resolveInfo->fieldNodes[0]->directives)) {
                foreach ($resolveInfo->fieldNodes[0]->directives as $directive) {
                    /** @var Directive $directiveEntity */
                    $directiveEntity = GqlEntityRegistry::getEntity($directive->name->value);
                    $arguments = [];

                    if (isset($directive->arguments[0])) {
                        foreach ($directive->arguments as $argument) {
                            $arguments[$argument->name->value] = $argument->value->value;
                        }
                    }

                    $value = $directiveEntity::apply($source, $value, $arguments, $resolveInfo);
                }
            }
        } catch (\Throwable $exception) {
            throw new GqlException($exception->getMessage(), 0, $exception);
        }

        return $value;
    }

    // Protected methods
    // =========================================================================

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
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        return $source->{$resolveInfo->fieldName};
    }

}
