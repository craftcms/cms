<?php
namespace craft\gql\resolvers\elements;

use craft\elements\MatrixBlock as MatrixBlockElement;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class MatrixBlock
 */
class MatrixBlock extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        // If this is the begining of a resolver chain, start fresh
        if ($source === null) {
            $query = MatrixBlockElement::find();
        // If not, get the prepared element query
        } else {
            $fieldName = $resolveInfo->fieldName;
            $query = $source->$fieldName;
        }

        $arguments = self::prepareArguments($arguments);

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        return $query->all();
    }

    /**
     * @inheritdoc
     */
    public static function getArrayableArguments(): array
    {
        return array_merge(parent::getArrayableArguments(), [
            'type',
            'typeId',
            'ownerId',
            'fieldId',
            'ownerSiteId',
        ]);
    }
}
