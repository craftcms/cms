<?php
namespace craft\gql\types;

use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\helpers\StringHelper;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class MatrixBlockType
 */
class MatrixBlockType extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [MatrixBlockInterface::getType()];
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var MatrixBlockElement $source */
        $fieldName = $resolveInfo->fieldName;

        if (StringHelper::substr($fieldName, 0, 5) === 'field') {
            $field = $source->getField();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 5));

            return $field->$property ?? null;
        }

        if (StringHelper::substr($fieldName, 0, 9) === 'ownerSite') {
            $ownerSite = $source->getSite();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 9));

            return $ownerSite->$property ?? null;
        }

        if (StringHelper::substr($fieldName, 0, 5) === 'owner') {
            $owner = $source->getOwner();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 5));

            return $owner->$property ?? null;
        }

        if (StringHelper::substr($fieldName, 0, 4) === 'type') {
            $entryType = $source->getType();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 4));

            return $entryType->$property ?? null;
        }

        return $source->$fieldName;
    }

}
