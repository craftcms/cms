<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\base\Element as CraftElement;
use craft\gql\base\ObjectType;
use craft\gql\interfaces\Element as ElementInterface;
use craft\services\Gql;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Element extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        if (!array_key_exists('interfaces', $config)) {
            $config['interfaces'] = [];
        }

        $config['interfaces'] = array_merge([ElementInterface::getType()], $config['interfaces']);

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var CraftElement $source */
        $fieldName = $resolveInfo->fieldName;

        if ($fieldName == Gql::GRAPHQL_COUNT_FIELD && !empty($arguments['field'])) {
            return $source->getEagerLoadedElementCount($arguments['field']);
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
