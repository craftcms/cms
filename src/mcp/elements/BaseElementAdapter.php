<?php

declare(strict_types=1);

namespace craft\mcp\elements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\mcp\Authorization;
use craft\mcp\ElementLifecycle;
use craft\mcp\ElementQueryCriteria;
use craft\mcp\serializers\ElementSerializer;
use Illuminate\Support\Arr;
use Mcp\Exception\ToolCallException;

/**
 * Default `elements.*` behavior for an element type.
 *
 * A subclass must define {@see handle()}, {@see elementType()}, and {@see updateAttributesSchema()}. It can opt into
 * creation by defining {@see createAttributesSchema()}, and override any other method to add type-specific rules.
 *
 * @since 5.12.0
 */
abstract class BaseElementAdapter implements ElementAdapter
{
    public function __construct(
        protected readonly ElementSerializer $elementSerializer,
        protected readonly ElementLifecycle $lifecycle,
    ) {
    }

    /**
     * Returns the JSON Schema for attributes `elements.update` and `elements.validate` accept.
     *
     * Only declared properties are accepted.
     *
     * @return array<string, mixed>
     */
    abstract protected function updateAttributesSchema(): array;

    /**
     * Returns the JSON Schema for attributes `elements.create` accepts, or `null` if it cannot create this type.
     *
     * @return array<string, mixed>|null
     */
    protected function createAttributesSchema(): ?array
    {
        return null;
    }

    /**
     * Returns type-specific criteria properties, added to Craft's common element query criteria.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function criteriaProperties(): array
    {
        return [];
    }

    /**
     * Returns the JSON Schema for the `context` that selects a new element's field layout, or `null` if the type has
     * one field layout.
     *
     * @return array<string, mixed>|null
     */
    protected function fieldSchemaContextSchema(): ?array
    {
        return null;
    }

    /** @return list<string> */
    protected function duplicateModes(): array
    {
        return [];
    }

    /**
     * Returns guidance for MCP clients about this type.
     *
     * @return list<string>
     */
    protected function notes(): array
    {
        return [];
    }

    public function schema(): array
    {
        $elementType = static::elementType();

        return array_filter([
            'type' => static::handle(),
            'name' => $elementType::pluralDisplayName(),
            'elementType' => $elementType,
            'criteria' => [
                'type' => 'object',
                'properties' => [
                    ...ElementQueryCriteria::SchemaProperties,
                    ...$this->criteriaProperties(),
                ],
                'additionalProperties' => true,
            ],
            'createAttributes' => $this->createAttributesSchema(),
            'updateAttributes' => $this->updateAttributesSchema(),
            'fieldSchemaContext' => $this->fieldSchemaContextSchema(),
            'duplicateModes' => $this->duplicateModes(),
            'notes' => $this->notes(),
        ], static fn(mixed $value): bool => $value !== null && $value !== []);
    }

    public function listQuery(User $actor): ElementQueryInterface
    {
        return static::elementType()::find()->orderBy('elements.id');
    }

    public function find(?int $id, ?string $uid, ?int $siteId): ?ElementInterface
    {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $query = $this->findQuery();

        Craft::configure($query, Arr::whereNotNull([
            'id' => $id,
            'uid' => $uid,
            'siteId' => $siteId,
        ]));

        return $query->one();
    }

    public function canView(User $actor, ElementInterface $element): bool
    {
        return Authorization::allows($actor, 'view', $element);
    }

    public function canDelete(User $actor, ElementInterface $element): bool
    {
        return Authorization::allows($actor, 'delete', $element);
    }

    public function serialize(ElementInterface $element, ?array $fields = null, bool $summary = false): array
    {
        return $this->elementSerializer->serialize($element, fields: $fields);
    }

    public function fieldSchemaElement(?ElementInterface $element, array $context, User $actor): ElementInterface
    {
        $element ??= $this->newElement($context, $actor);
        $this->authorizeSave($actor, $element);

        return $element;
    }

    public function create(array $attributes, array $fields, User $actor): array
    {
        if ($this->createAttributesSchema() === null) {
            throw new ToolCallException(sprintf('Creating %s is not supported.', static::elementType()::pluralLowerDisplayName()));
        }

        $element = $this->newElement([], $actor);

        $this->populate($element, $attributes, $fields, $actor);
        $this->authorizeSave($actor, $element);

        return $this->save($element, $actor);
    }

    public function update(ElementInterface $element, array $attributes, array $fields, User $actor): array
    {
        $this->authorizeSave($actor, $element);
        $this->populate($element, $attributes, $fields, $actor);
        $this->authorizeSave($actor, $element);

        return $this->save($element, $actor);
    }

    public function prepareValidation(ElementInterface $element, array $attributes, array $fields, User $actor): void
    {
        $this->authorizeSave($actor, $element);
        $this->populate($element, $attributes, $fields, $actor);
        $this->authorizeSave($actor, $element);
    }

    public function duplicate(ElementInterface $element, ?string $mode): ElementInterface
    {
        throw new ToolCallException(sprintf('Duplicating %s is not supported.', static::elementType()::pluralLowerDisplayName()));
    }

    /**
     * Returns the query `find()` loads an element with.
     */
    protected function findQuery(): ElementQueryInterface
    {
        return static::elementType()::find();
    }

    /**
     * Returns a new, unsaved element.
     *
     * @param  array<string, mixed>  $context  Values matching the `fieldSchemaContext` schema, or `[]` for `elements.create`.
     */
    protected function newElement(array $context, User $actor): ElementInterface
    {
        $element = new (static::elementType());
        Craft::configure($element, $context);

        return $element;
    }

    /**
     * Applies validated attributes and custom field values to an element.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     */
    protected function populate(ElementInterface $element, array $attributes, array $fields, User $actor): void
    {
        Craft::configure($element, $attributes);
        $element->setFieldValues($fields);
    }

    protected function authorizeSave(User $actor, ElementInterface $element): void
    {
        if (!Authorization::allows($actor, 'save', $element)) {
            throw new ToolCallException(sprintf('You are not authorized to save this %s.', $element::lowerDisplayName()));
        }
    }

    /** @return array{element: array<string, mixed>}&array<string, mixed> */
    protected function save(ElementInterface $element, User $actor): array
    {
        return ['element' => $this->serialize($this->saveElement($element, $actor))];
    }

    /**
     * Saves an element as the acting user and returns the saved element, which may be a new draft.
     */
    protected function saveElement(ElementInterface $element, User $actor): ElementInterface
    {
        $element->setScenario(\craft\base\Element::SCENARIO_LIVE);

        if (!Craft::$app->getElements()->saveElement($element)) {
            throw new ToolCallException(
                implode("\n", $element->getErrorSummary(true)) ?: sprintf('%s could not be saved.', $element::displayName()),
            );
        }

        return $element;
    }
}
