<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class MatrixBlock
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class MatrixBlock extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            MatrixBlockInterface::getType(),
            ElementInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var MatrixBlockElement $source */
        $fieldName = $resolveInfo->fieldName;

        if ($fieldName === 'typeHandle') {
            return $source->getType()->handle;
        }

        return $source->$fieldName;
    }

}
