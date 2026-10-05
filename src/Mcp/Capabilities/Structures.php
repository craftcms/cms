<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Mcp\ElementQueryFactory;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\Structure\Structures as StructureService;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class Structures
{
    private const array Operations = ['start', 'end', 'before', 'after'];

    public function __construct(
        private Elements $elements,
        private ElementQueryFactory $elementQueries,
        private McpActor $actor,
        private StructureService $structures,
    ) {}

    /**
     * @param  string  $elementType  Registered element type reference handle or class name.
     * @param  string  $operation  Move to the start or end of the root, or before or after a target element.
     * @param  int|null  $structureId  Structure ID. It may be inferred when the element belongs to exactly one structure.
     * @param  int|null  $elementId  Element ID.
     * @param  string|null  $elementUid  Element UID.
     * @param  int|null  $targetElementId  Target element ID for before or after operations.
     * @param  string|null  $targetElementUid  Target element UID for before or after operations.
     * @param  int|null  $siteId  Site ID used to load the elements.
     * @return array{moved: true, operation: string, structureId: int, element: array<string, mixed>}
     */
    #[McpTool(
        name: 'structures.move-element',
        description: 'Moves an element within a Craft CMS structure.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function moveElement(
        string $elementType,
        #[Schema(enum: self::Operations)]
        string $operation,
        ?int $structureId = null,
        ?int $elementId = null,
        #[Schema(format: 'uuid')]
        ?string $elementUid = null,
        ?int $targetElementId = null,
        #[Schema(format: 'uuid')]
        ?string $targetElementUid = null,
        ?int $siteId = null,
        Mode $mode = Mode::Auto,
    ): array {
        $type = $this->elementQueries->resolve($elementType);
        $element = $this->element($type, $elementId, $elementUid, $siteId);

        if (! $element) {
            throw new ToolCallException('Element not found.');
        }

        $requiresTarget = in_array($operation, ['before', 'after'], true);
        $target = $requiresTarget
            ? $this->element($type, $targetElementId, $targetElementUid, $siteId)
            : null;

        if ($requiresTarget && ! $target) {
            throw new ToolCallException('Target element not found.');
        }

        $structureId ??= $this->inferStructureId($target ?? $element);

        if ($structureId === null) {
            throw new ToolCallException('Structure ID could not be inferred. Provide structureId.');
        }

        $this->authorize($structureId, $element, $target);

        $moved = match ($operation) {
            'start' => $this->structures->prependToRoot($structureId, $element, $mode),
            'end' => $this->structures->appendToRoot($structureId, $element, $mode),
            'before' => $this->structures->moveBefore($structureId, $element, $target, $mode),
            'after' => $this->structures->moveAfter($structureId, $element, $target, $mode),
            default => throw new ToolCallException('Unsupported structure operation.'),
        };

        if (! $moved) {
            throw new ToolCallException('Element could not be moved in the structure.');
        }

        return [
            'moved' => true,
            'operation' => $operation,
            'structureId' => $structureId,
            'element' => Arr::whereNotNull([
                'type' => $element::class,
                ...$element->toArray(),
            ]),
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $type
     */
    private function element(
        string $type,
        ?int $id,
        ?string $uid,
        ?int $siteId,
    ): ?ElementInterface {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            return null;
        }

        return $id !== null
            ? $this->elements->getElementById($id, $type, $siteId)
            : $this->elements->getElementByUid($uid, $type, $siteId);
    }

    private function inferStructureId(ElementInterface $element): ?int
    {
        $structureIds = StructureElement::query()
            ->where('elementId', $element->getId())
            ->distinct()
            ->pluck('structureId');

        return $structureIds->count() === 1 ? (int) $structureIds->first() : null;
    }

    private function authorize(
        int $structureId,
        ElementInterface $element,
        ?ElementInterface $target,
    ): void {
        $structure = $this->structures->getStructureById($structureId);

        if (! $structure || Gate::forUser($this->actor->user())->denies('edit', $structure)) {
            throw new ToolCallException('You are not authorized to edit this structure.');
        }

        if (! $this->belongsToStructure($structureId, $element)) {
            throw new ToolCallException('Element is not in the requested structure.');
        }

        if ($target && ! $this->belongsToStructure($structureId, $target)) {
            throw new ToolCallException('Target element is not in the requested structure.');
        }

        if ($target && Gate::forUser($this->actor->user())->denies('view', $target)) {
            throw new ToolCallException(sprintf('You are not authorized to view this %s.', $target::lowerDisplayName()));
        }
    }

    private function belongsToStructure(int $structureId, ElementInterface $element): bool
    {
        return StructureElement::query()
            ->where('structureId', $structureId)
            ->where('elementId', $element->getId())
            ->exists();
    }
}
