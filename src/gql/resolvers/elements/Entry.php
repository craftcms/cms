<?php
namespace craft\gql\resolvers\elements;

use craft\elements\Entry as EntryElement;
use craft\helpers\StringHelper;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Entry
 */
class Entry extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        // If this is the begining of a resolver chain, start fresh
        if ($source === null) {
            $query = EntryElement::find();
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
            'section',
            'type',
            'authorGroup',
            'sectionId',
            'typeId',
            'authorId',
            'authorGroupId',
            'typeId',
        ]);
    }
}
