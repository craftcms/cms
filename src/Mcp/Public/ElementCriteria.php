<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Public;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Arr;
use LogicException;
use Mcp\Exception\ToolCallException;

/**
 * @since 6.0.0
 */
readonly class ElementCriteria
{
    public function __construct(
        private Access $access,
        private ElementQueryCriteria $elementQueryCriteria,
    ) {}

    /**
     * @return array{
     *     available: list<array{name: string, type: string, description: string}>,
     *     publicConstraints: array<string, string>,
     * }
     */
    public function for(ElementType $type): array
    {
        $native = $this->native($type);
        $available = [
            ...$native,
            ...$this->customFields($type, [
                ...array_column($native, 'name'),
                ...($type->is(User::class) ? array_keys(ElementQueryCriteria::UserSchemaProperties) : []),
            ]),
        ];

        return [
            'available' => $available,
            'publicConstraints' => [
                ...$this->publicConstraints($available),
                'orderBy' => 'Comma-separated public field names, optionally followed by asc or desc. Allowed fields: '.implode(', ', array_keys($this->sortFields($type))).'. An ID tie-breaker is always applied.',
                'relatedTo' => 'A nonempty list of up to 100 public element IDs in the selected sites. Other native relation expressions are not supported.',
                'with' => 'At most five paths, three levels deep. Each target query enforces public content and state restrictions and returns at most 100 targets.',
            ],
        ];
    }

    /** @param array<string, mixed> $criteria */
    public function assertSupported(ElementType $type, array $criteria): void
    {
        $available = $this->for($type)['available'];
        $supported = array_flip(array_column($available, 'name'));

        foreach (array_keys($criteria) as $name) {
            if (is_string($name) && array_key_exists($name, $supported)) {
                continue;
            }

            throw new ToolCallException(
                "Unsupported public query criterion [$name] for element type [$type->name]. Use craft-context-get for supported criteria.",
            );
        }
    }

    /** @return list<array{name: string, type: string, description: string}> */
    private function native(ElementType $type): array
    {
        $properties = [
            ...Arr::only(ElementQueryCriteria::SchemaProperties, [
                'id',
                'uid',
                'site',
                'siteId',
                'search',
                'status',
                'archived',
                'limit',
                'offset',
                'orderBy',
            ]),
            'orderBy' => [
                'type' => 'string',
                'description' => 'Comma-separated public sort field names with optional asc or desc. See publicConstraints.orderBy for allowed fields.',
            ],
            'relatedTo' => [
                'type' => 'array',
                'items' => ['type' => 'integer', 'minimum' => 1],
                'minItems' => 1,
                'maxItems' => Access::MaxLimit,
                'description' => 'Related public element IDs. Every ID must be queryable in the selected public sites.',
            ],
            'with' => [
                ...ElementQueryCriteria::SchemaProperties['with'],
                'maxItems' => 5,
                'description' => 'Eager-loading paths. At most five paths, three levels deep. Targets must be public and are capped at 100 per target query.',
            ],
            'drafts' => ['type' => 'boolean', 'description' => 'Whether drafts may be returned.'],
            'revisions' => ['type' => 'boolean', 'description' => 'Whether revisions may be returned.'],
        ];

        $properties = match (true) {
            $type->is(Entry::class) => [
                ...$properties,
                ...Arr::only(ElementQueryCriteria::EntrySchemaProperties, ['section', 'sectionId', 'slug']),
                'field' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Nested entry field handle or handles.'],
                'fieldId' => [...ElementQueryCriteria::IntegerOrIntegersSchema, 'description' => 'Nested entry field ID or IDs.'],
                'title' => ['type' => 'string', 'description' => 'Entry title criteria.'],
            ],
            $type->is(Asset::class) => [
                ...$properties,
                ...ElementQueryCriteria::AssetSchemaProperties,
            ],
            $type->is(User::class) => [
                ...$properties,
                ...Arr::except(ElementQueryCriteria::UserSchemaProperties, ['admin', 'lastLoginDate']),
            ],
            default => $properties,
        };

        return $this->elementQueryCriteria->describe($properties);
    }

    /** @return array<string, string> Public sort field names mapped to native query columns. */
    public function sortFields(ElementType $type): array
    {
        $fields = [
            'id' => 'elements.id',
            'uid' => 'elements.uid',
            'dateCreated' => 'elements.dateCreated',
            'dateUpdated' => 'elements.dateUpdated',
        ];

        if ($type->class::hasTitles()) {
            $fields['title'] = 'elements_sites.title';
            $fields['slug'] = 'elements_sites.slug';
        }

        return match (true) {
            $type->is(Entry::class) => [...$fields, 'postDate' => 'entries.postDate', 'expiryDate' => 'entries.expiryDate'],
            $type->is(Asset::class) => [...$fields, 'filename' => 'assets.filename', 'kind' => 'assets.kind'],
            $type->is(User::class) => [
                ...$fields,
                'username' => 'users.username',
                'email' => 'users.email',
                'firstName' => 'users.firstName',
                'lastName' => 'users.lastName',
                'fullName' => 'users.fullName',
            ],
            default => $fields,
        };
    }

    /**
     * @param  list<string>  $reserved
     * @return list<array{name: string, type: string, description: string}>
     */
    private function customFields(ElementType $type, array $reserved): array
    {
        $criteria = [];
        $reserved = array_flip($reserved);

        foreach ($this->fieldLayouts($type) as $layout) {
            foreach ($layout->getCustomFields() as $field) {
                $handle = $field->handle;

                if ($handle === null || $handle === '') {
                    throw new LogicException("Public query field [$field->uid] does not have a handle.");
                }

                if (! array_key_exists($handle, $reserved)) {
                    $criteria[$handle] = [
                        'description' => "Custom field criteria for '$field->name'.",
                    ];
                }
            }
        }

        return $this->elementQueryCriteria->describe($criteria);
    }

    /** @return list<FieldLayout> */
    private function fieldLayouts(ElementType $type): array
    {
        if ($type->is(Entry::class)) {
            $layouts = [];

            foreach ($this->access->models('sections') as $section) {
                if ($section instanceof Section) {
                    foreach ($section->getEntryTypes() as $entryType) {
                        $layout = $entryType->getFieldLayout();
                        $layouts[spl_object_id($layout)] = $layout;
                    }
                }
            }

            foreach ($this->access->models('nestedEntryFields') as $field) {
                if ($field instanceof ElementContainerFieldInterface) {
                    foreach ($field->getFieldLayoutProviders() as $provider) {
                        $layout = $provider->getFieldLayout();
                        $layouts[spl_object_id($layout)] = $layout;
                    }
                }
            }

            return array_values($layouts);
        }

        if ($type->is(Asset::class)) {
            return collect($this->access->models('volumes'))
                ->filter(static fn (object $volume): bool => $volume instanceof Volume)
                ->map(static fn (Volume $volume): FieldLayout => $volume->getFieldLayout())
                ->values()
                ->all();
        }

        return $type->query()->getFieldLayouts();
    }

    /**
     * @param  list<array{name: string, type: string, description: string}>  $available
     * @return array<string, string>
     */
    private function publicConstraints(array $available): array
    {
        $constraints = [
            'site' => 'Intersected with the public site allowlist. Defaults to all public sites.',
            'siteId' => 'Intersected with the public site allowlist. Defaults to all public sites.',
            'section' => 'Entries are constrained to public sections and public nested-entry fields.',
            'sectionId' => 'Entries are constrained to public sections and public nested-entry fields.',
            'field' => 'Entries are constrained to public sections and public nested-entry fields.',
            'fieldId' => 'Entries are constrained to public sections and public nested-entry fields.',
            'volume' => 'Assets are constrained to public volumes.',
            'volumeId' => 'Assets are constrained to public volumes.',
            'group' => 'Users are constrained to public user groups unless the user element type is explicitly public.',
            'groupId' => 'Users are constrained to public user groups unless the user element type is explicitly public.',
            'status' => 'Overridden to the public status unless inactive elements are enabled.',
            'archived' => 'Overridden to false unless inactive elements are enabled.',
            'drafts' => 'Overridden to false unless drafts are enabled.',
            'revisions' => 'Overridden to false unless revisions are enabled.',
            'limit' => sprintf('Clamped between 1 and %d. Defaults to %d.', Access::MaxLimit, Access::DefaultLimit),
            'offset' => 'Clamped to 0 or greater.',
        ];

        return array_intersect_key($constraints, array_flip(array_column($available, 'name')));
    }
}
