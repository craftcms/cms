<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Elements;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Gql\Arguments\ElementArguments;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Support\Facades\Gql;
use Override;

/**
 * @since 6.0.0
 */
class Entry extends ElementArguments
{
    #[Override]
    protected static function elementType(): string
    {
        return EntryElement::class;
    }

    #[Override]
    public static function getContentArguments(): array
    {
        return Gql::getOrSetContentArguments(EntryElement::class, function (): array {
            /** @var EntryType[] $entryTypes */
            $entryTypes = [];

            foreach (GqlHelper::getSchemaContainedSections() as $section) {
                foreach ($section->getEntryTypes() as $entryType) {
                    $entryTypes[$entryType->id] = $entryType;
                }
            }

            foreach (GqlHelper::getSchemaContainedNestedEntryFields() as $field) {
                foreach ($field->getFieldLayoutProviders() as $entryType) {
                    /** @var EntryType $entryType */
                    $entryTypes[$entryType->id] = $entryType;
                }
            }

            $arguments = [];
            foreach ($entryTypes as $entryType) {
                $arguments += Gql::getFieldLayoutArguments($entryType->getFieldLayout());
            }

            return $arguments;
        });
    }
}
