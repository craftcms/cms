<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Elements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Elements\Entry as EntryInterface;
use CraftCms\Cms\Gql\Resolvers\Elements\Entry as EntryResolver;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Gate;
use Override;

/**
 * @since 6.0.0
 */
class Entry extends Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            EntryInterface::getType(),
        ];

        parent::__construct($config);
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var EntryElement $source */
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
            'draftCreator' => $source->getIsDraft() ? $source->getDraftCreator() : null,
            'revisionCreator' => $source->getIsRevision() ? $source->getRevisionCreator() : null,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };

        // Related entries that are eager-loaded via the entry’s own eager-loading map don’t go through the entry
        // resolver, so limit them to what an authenticated user can view here
        if (
            in_array($fieldName, ['parent', 'ancestors', 'children', 'descendants', 'currentRevision', 'drafts', 'revisions'], true) &&
            ($user = GqlHelper::getSchemaUser())
        ) {
            $gate = Gate::forUser($user);

            if ($value instanceof ElementInterface) {
                return $gate->allows('view', $value) ? $value : null;
            }

            if (is_iterable($value)) {
                $viewable = [];

                foreach ($value as $element) {
                    if ($gate->allows('view', $element)) {
                        $viewable[] = $element;
                    }
                }

                return $viewable;
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected static function elementResolverClass(): ?string
    {
        return EntryResolver::class;
    }
}
