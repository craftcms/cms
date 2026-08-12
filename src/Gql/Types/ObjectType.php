<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\GqlHelper;
use GraphQL\Type\Definition\ObjectType as GqlObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Throwable;

abstract class ObjectType extends GqlObjectType
{
    public function __construct(array $config)
    {
        $config['resolveField'] = $this->resolveWithDirectives(...);
        parent::__construct($config);
    }

    /**
     * @param  mixed  $source  The parent data source to use for resolving this field
     * @param  array<string, mixed>  $arguments  arguments for resolving this field.
     * @param  mixed  $context  The context shared between all resolvers
     * @param  ResolveInfo  $resolveInfo  The resolve information
     * @return mixed $result
     *
     * @throws GqlException if an error occurs
     */
    public function resolveWithDirectives(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        try {
            $value = $this->resolve($source, $arguments, $context, $resolveInfo);
            $value = GqlHelper::applyDirectives($source, $resolveInfo, $value);
        } catch (Throwable $exception) {
            throw new GqlException($exception->getMessage(), 0, $exception);
        }

        return $value;
    }

    /**
     * @param  mixed  $source  The parent data source to use for resolving this field
     * @param  array<string, mixed>  $arguments  arguments for resolving this field.
     * @param  mixed  $context  The context shared between all resolvers
     * @param  ResolveInfo  $resolveInfo  The resolve information
     * @return mixed $result
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = GqlHelper::getFieldNameWithAlias($resolveInfo, $source, $context);

        $result = null;

        if (is_object($source)) {
            $result = $source->$fieldName;
        } elseif (is_array($source)) {
            $result = $source[$fieldName] ?? null;
        }

        return $result instanceof ElementQueryInterface ? $result->all() : $result;
    }
}
