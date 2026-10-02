<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\ElementIndexSourceSettings;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * @since 6.0.0
 */
class EmbeddedNestedElementScope
{
    private const array ELEMENT_INDEX_SCOPE_CRITERIA = [
        'fieldId',
        'ownerId',
        'primaryOwnerId',
        'siteId',
        'drafts',
        'revisions',
        'trashed',
        'canonicalsOnly',
        'savedDraftsOnly',
    ];

    private bool $ownerAuthorized = false;

    /** @var ElementQueryInterface|ElementCollection<array-key, ElementInterface>|null */
    private ElementQueryInterface|ElementCollection|null $elements = null;

    private ?FieldInterface $field = null;

    private bool $fieldResolved = false;

    /** @var array<string, mixed>|null */
    private ?array $indexConfig = null;

    private ?ElementIndexSourceSettings $indexSource = null;

    public function __construct(private readonly Request $request, private ?ElementInterface $owner = null) {}

    /** @return array<string, list<string|object>> */
    private static function rules(): array
    {
        return [
            'ownerElementType' => ['required', 'string', new ElementTypeRule],
            'ownerId' => ['required', 'integer'],
            'ownerSiteId' => ['required', 'integer'],
            'attribute' => ['required', 'string'],
        ];
    }

    public function owner(): ElementInterface
    {
        $owner = $this->resolveOwner();

        if ($this->ownerAuthorized) {
            return $owner;
        }

        abort_if(ElementHelper::isRevision($owner), 400, 'Revision owners cannot be modified');

        $this->authorizeNestedElements($owner);
        $this->ownerAuthorized = true;

        return $owner;
    }

    /** @return array<string, mixed> */
    private function indexCriteria(): array
    {
        $owner = $this->resolveOwner();
        Gate::authorize('view', $owner);
        $elements = $this->resolveElements($owner);

        $criteria = [
            'siteId' => $owner->siteId,
            'status' => null,
            'drafts' => null,
            'canonicalsOnly' => true,
            'savedDraftsOnly' => true,
            'trashed' => false,
        ];

        if ($elements instanceof ElementQueryInterface) {
            foreach (['fieldId', 'ownerId', 'primaryOwnerId'] as $param) {
                if (property_exists($elements, $param) && $elements->$param !== null) {
                    $criteria[$param] = $elements->$param;
                }
            }
        } else {
            $field = $this->field($owner);
            $criteria['fieldId'] = $field !== null ? $field->id : false;
            $criteria['ownerId'] = $owner->id;

            foreach ($elements as $element) {
                abort_unless(
                    $element instanceof NestedElementInterface && $element->getOwnerId() === $owner->id,
                    400,
                    'Nested element collection is not scoped to the requested owner',
                );
            }
        }

        if ($owner->getIsRevision()) {
            $criteria = [...$criteria, 'revisions' => null, 'trashed' => null, 'drafts' => false];
        }

        return $criteria;
    }

    /** @return array<string, mixed> */
    private function indexConfig(): array
    {
        if ($this->indexConfig !== null) {
            return $this->indexConfig;
        }

        $owner = $this->resolveOwner();
        $field = $this->field($owner);
        $static = $owner->getIsRevision() || ! $this->isAuthorized('manageNestedElements', $owner);

        $config = $this->request->validate([
            'allowedViewModes' => ['sometimes', 'array'],
            'allowedViewModes.*' => ['string'],
            'showHeaderColumn' => ['sometimes', 'boolean'],
            'defaultTableColumns' => ['sometimes', 'array'],
            'defaultTableColumns.*' => ['string'],
            'per_page' => ['sometimes', 'integer', 'min:1'],
            'sortable' => ['sometimes', 'boolean'],
            'canPaste' => ['sometimes', 'boolean'],
            'static' => ['sometimes', 'boolean'],
            'fieldLayouts' => ['sometimes', 'array'],
            'fieldLayouts.*' => ['array'],
        ]);

        return $this->indexConfig = [
            ...NestedElementManager::defaultIndexConfig($owner, $field, $this->attribute()),
            ...Arr::except($config, ['per_page', 'fieldLayouts']),
            'pageSize' => $this->request->integer('per_page', 50),
            'fieldLayouts' => array_map(FieldLayout::createFromConfig(...), $config['fieldLayouts'] ?? []),
            'static' => $static || ($config['static'] ?? false),
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>|null  $config
     */
    public function indexSource(string $elementType, ?array $config = null): ElementIndexSourceSettings
    {
        if ($this->indexSource !== null) {
            return $this->indexSource;
        }

        $config ??= $this->indexConfig();
        $config['prevalidate'] = ($config['prevalidate'] ?? false) || $this->request->boolean('prevalidate');

        return $this->indexSource = ElementIndexSourceSettings::forNestedOwner(
            elementType: $elementType,
            owner: $this->resolveOwner(),
            attribute: $this->attribute(),
            criteria: $this->indexCriteria(),
            config: $config,
        );
    }

    public function canReorder(): bool
    {
        return $this->isAuthorized('reorderNestedElements', $this->resolveOwner());
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    public function filterCriteria(array $criteria): array
    {
        return Arr::except($criteria, self::ELEMENT_INDEX_SCOPE_CRITERIA);
    }

    /**
     * @param  list<int|string>  $elementIds
     * @return Collection<int, NestedElementInterface>
     */
    public function selectedElements(array $elementIds, bool $authorizeSave = true): Collection
    {
        $ids = array_values(array_unique(array_map(intval(...), $elementIds)));
        abort_if($ids === [], 400, 'Invalid elementIds param');

        $elements = $this->elements();
        if ($elements instanceof ElementQueryInterface) {
            $selectedElements = (clone $elements)
                ->id($ids)
                ->status(null)
                ->drafts(null)
                ->provisionalDrafts(null)
                ->get();
        } else {
            $selectedElements = $elements->filter(
                fn (ElementInterface $element): bool => in_array($element->id, $ids, true),
            )->values();
        }

        abort_if($selectedElements->count() !== count($ids), 400, 'One or more elements do not belong to this owner and attribute');

        foreach ($selectedElements as $selectedElement) {
            abort_unless($selectedElement instanceof NestedElementInterface, 400, 'Invalid nested element');
            Gate::authorize('view', $selectedElement);
            if ($authorizeSave) {
                Gate::authorize('save', $selectedElement);
            }
        }

        /** @var Collection<int, NestedElementInterface> $selectedElements */
        return $selectedElements;
    }

    /** @return ElementQueryInterface|ElementCollection<array-key, ElementInterface> */
    public function elements(): ElementQueryInterface|ElementCollection
    {
        return $this->resolveElements($this->owner());
    }

    /** @return ElementQueryInterface|ElementCollection<array-key, ElementInterface> */
    private function resolveElements(ElementInterface $owner): ElementQueryInterface|ElementCollection
    {
        if ($this->elements) {
            return $this->elements;
        }

        $elements = $owner->{$this->attribute()};

        abort_if(
            ! $elements instanceof ElementQueryInterface &&
            ! $elements instanceof ElementCollection,
            400,
            'Invalid attribute param',
        );

        if ($elements instanceof ElementQueryInterface) {
            $this->assertNestedElementQueryScope($elements, $owner);
        }

        return $this->elements = $elements;
    }

    private function assertNestedElementQueryScope(ElementQueryInterface $query, ElementInterface $owner): void
    {
        abort_unless($query instanceof NestedElementQueryInterface, 400, 'Invalid nested element query');

        $queryParams = get_object_vars($query);
        $ownerIds = [];

        foreach ([$queryParams['ownerId'] ?? null, $queryParams['primaryOwnerId'] ?? null] as $value) {
            if ($value === null) {
                continue;
            }

            $ownerIds[] = $this->exactScopeId($value, 'Invalid nested element owner scope');
        }

        $ownerIds = array_values(array_unique($ownerIds));

        abort_unless($ownerIds === [$owner->id], 400, 'Nested element query is not scoped to the requested owner');

        if (str_starts_with($this->attribute(), 'field:')) {
            $field = $this->field($owner);
            abort_if($field?->id === null, 400, 'Invalid nested field attribute');

            $fieldId = $this->exactScopeId($queryParams['fieldId'] ?? null, 'Invalid nested element field scope');
            abort_unless($fieldId === $field->id, 400, 'Nested element query is not scoped to the requested field');
        }
    }

    private function exactScopeId(mixed $value, string $message): int
    {
        if (is_array($value)) {
            abort_unless(array_is_list($value) && count($value) === 1, 400, $message);
            $value = $value[0];
        }

        abort_unless(is_int($value) || (is_string($value) && ctype_digit($value)), 400, $message);

        $id = (int) $value;
        abort_unless($id > 0, 400, $message);

        return $id;
    }

    private function attribute(): string
    {
        return (string) $this->request->input('attribute');
    }

    private function field(ElementInterface $owner): ?FieldInterface
    {
        if ($this->fieldResolved) {
            return $this->field;
        }

        $this->fieldResolved = true;
        $attribute = $this->attribute();

        if (! str_starts_with($attribute, 'field:')) {
            return null;
        }

        return $this->field = $owner->getFieldLayout()?->getFieldByHandle(substr($attribute, strlen('field:')));
    }

    private function resolveOwner(): ElementInterface
    {
        if ($this->owner) {
            return $this->owner;
        }

        $this->request->validate(self::rules());

        /** @var class-string<ElementInterface> $ownerElementType */
        $ownerElementType = $this->request->input('ownerElementType');
        $owner = Elements::getElementById(
            $this->request->integer('ownerId'),
            $ownerElementType,
            $this->request->integer('ownerSiteId'),
        );

        abort_if(is_null($owner), 400, 'Invalid owner params');

        return $this->owner = $owner;
    }

    private function authorizeNestedElements(ElementInterface $owner): void
    {
        if ($this->isAuthorized('manageNestedElements', $owner)) {
            return;
        }

        abort(403, 'User is not authorized to perform this action');
    }

    private function isAuthorized(string $action, ElementInterface $owner): bool
    {
        $attribute = $this->attribute();

        if (SessionAuth::checkAuthorization(sprintf('%s::%s::%s', $action, $owner->id, $attribute))) {
            return true;
        }

        return $owner->id !== $owner->getCanonicalId()
            && SessionAuth::checkAuthorization(sprintf('%s::%s::%s', $action, $owner->getCanonicalId(), $attribute));
    }
}
