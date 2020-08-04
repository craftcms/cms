<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\errors\GqlException;
use craft\helpers\Gql as GqlHelper;
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
            $value = GqlHelper::applyDirectives($source, $resolveInfo, $value);
        } catch (\Throwable $exception) {
            throw new GqlException($exception->getMessage(), 0, $exception);
        }

        return $value;
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
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        $fieldName = is_array($resolveInfo->path) ? array_slice($resolveInfo->path, -1)[0] : $resolveInfo->fieldName;
        $isAlias = $fieldName !== $resolveInfo->fieldName;

        if ($isAlias && !($source instanceof ElementInterface && $source->getEagerLoadedElements($fieldName))) {
            $fieldName = $resolveInfo->fieldName;
        }

        $result = $source->$fieldName;

        if ($result instanceof ElementQueryInterface) {
            return $result->all();
        }

        return $result;
    }
}
