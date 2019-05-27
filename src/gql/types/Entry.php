<?php
namespace craft\gql\types;

use craft\elements\Entry as EntryElement;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\helpers\StringHelper;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class EntryType
 */
class Entry extends Element
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [EntryInterface::getType()];
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var EntryElement $source */
        $fieldName = $resolveInfo->fieldName;

        if (StringHelper::substr($fieldName, 0, 7) === 'section') {
            $section = $source->getSection();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 7));

            return $section->$property ?? null;
        }

        if (StringHelper::substr($fieldName, 0, 4) === 'type') {
            $entryType = $source->getType();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 4));

            return $entryType->$property ?? null;
        }

        return $source->$fieldName;
    }
}
