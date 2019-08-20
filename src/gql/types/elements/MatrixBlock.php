<?php
namespace craft\gql\types\elements;

use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\types\BaseType;
use craft\helpers\StringHelper;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class MatrixBlock
 */
class MatrixBlock extends BaseType
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

        if (StringHelper::substr($fieldName, 0, 5) === 'field' && $fieldName !== 'fieldId') {
            $field = $source->getField();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 5));

            return $field->$property;
        }

        // Not very clean or nice in any other aspect. Get structure elements in ASAP.
        if (StringHelper::substr($fieldName, 0, 5) === 'owner') {
            $owner = $source->getOwner();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 5));

            if (StringHelper::length($property) > 0) {
                return $owner->$property;
            }

            return $owner;
        }

        if (StringHelper::substr($fieldName, 0, 4) === 'type') {
            $entryType = $source->getType();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 4));

            return $entryType->$property;
        }

        return $source->$fieldName;
    }

}
