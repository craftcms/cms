<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use Craft;
use craft\base\ElementInterface;
use craft\behaviors\DraftBehavior;
use craft\elements\Entry as EntryElement;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\Gql;
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
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var EntryElement|DraftBehavior $source */
        $fieldName = $resolveInfo->fieldName;

        $value = match ($fieldName) {
            'sectionId' => $source->sectionId,
            'fieldId' => $source->fieldId,
            'ownerId' => $source->ownerId,
            'sortOrder' => $source->sortOrder,
            'typeId' => $source->getTypeId(),
            'section' => $source->getSection(),
            'type' => $source->getType(),
            'sectionHandle' => $source->getSection()?->handle,
            'fieldHandle' => $source->getField()?->handle,
            'typeHandle' => $source->getType()->handle,
            'draftName', 'draftNotes' => $source->getIsDraft() ? $source->{$fieldName} : null,
            'draftCreator' => $source->getIsDraft() ? $source->getCreator() : null,
            'revisionCreator' => $source->getIsRevision() ? $source->getCreator() : null,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };

        // Related entries that are eager-loaded via the entry's own eager-loading map don't go through the entry
        // resolver, so limit them to what an authenticated user can view here
        if (
            in_array($fieldName, ['parent', 'ancestors', 'children', 'descendants', 'currentRevision', 'drafts', 'revisions'], true) &&
            ($user = Gql::getSchemaUser())
        ) {
            $elementsService = Craft::$app->getElements();
            if ($value instanceof ElementInterface) {
                return $elementsService->canView($value, $user) ? $value : null;
            }
            if (is_iterable($value)) {
                $viewable = [];
                foreach ($value as $element) {
                    if ($elementsService->canView($element, $user)) {
                        $viewable[] = $element;
                    }
                }
                return $viewable;
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    protected static function elementResolverClass(): ?string
    {
        return EntryResolver::class;
    }
}
