<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\elements\Entry as EntryElement;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Entry extends Element
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            EntryInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var EntryElement $source */
        $fieldName = $resolveInfo->fieldName;

        switch ($fieldName) {
            case 'sectionId':
                return $source->sectionId;
            case 'typeId':
                return $source->typeId;
            case 'sectionHandle':
                return $source->getSection()->handle;
            case 'typeHandle':
                return $source->getType()->handle;
            case 'draftName':
            case 'draftNotes':
                return $source->getIsDraft() ? $source->{$fieldName} : null;
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
