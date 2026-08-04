<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Foundation\Http\FormRequest;

class NestedElementsRequest extends FormRequest
{
    private ?ElementInterface $owner = null;

    /** @var ElementQueryInterface|ElementCollection<array-key, ElementInterface>|null */
    private ElementQueryInterface|ElementCollection|null $nestedElements = null;

    /** @var int[]|null */
    private ?array $elementIds = null;

    private ?int $offset = null;

    private ?NestedElementInterface $nestedElement = null;

    /** @return array<string, list<string|object>> */
    public function rules(): array
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
        if ($this->owner) {
            return $this->owner;
        }

        /** @var class-string<ElementInterface> $ownerElementType */
        $ownerElementType = $this->input('ownerElementType');
        $owner = Elements::getElementById(
            $this->integer('ownerId'),
            $ownerElementType,
            $this->integer('ownerSiteId'),
        );

        abort_if(is_null($owner), 400, 'Invalid owner params');

        $this->authorizeNestedElements($owner);

        return $this->owner = $owner;
    }

    /** @return ElementQueryInterface|ElementCollection<array-key, ElementInterface> */
    public function nestedElements(): ElementQueryInterface|ElementCollection
    {
        if ($this->nestedElements) {
            return $this->nestedElements;
        }

        $nestedElements = $this->owner()->{$this->attribute()};

        abort_if(
            ! $nestedElements instanceof ElementQueryInterface &&
            ! $nestedElements instanceof ElementCollection,
            400,
            'Invalid attribute param',
        );

        return $this->nestedElements = $nestedElements;
    }

    /** @return list<int> */
    public function elementIds(): array
    {
        $this->validateReorder();

        abort_if(is_null($this->elementIds), 400, 'Invalid elementIds param');

        return $this->elementIds;
    }

    /**
     * Ensures the user is authorized to reorder the nested elements, in addition to
     * (not instead of) the general `manageNestedElements` authorization checked by
     * {@see owner()} — a field's nested elements can be manageable without being sortable.
     */
    public function authorizeReorder(): void
    {
        $owner = $this->owner();
        $attribute = $this->attribute();

        if (SessionAuth::checkAuthorization(sprintf('reorderNestedElements::%s::%s', $owner->id, $attribute))) {
            return;
        }

        if (
            $owner->id !== $owner->getCanonicalId() &&
            SessionAuth::checkAuthorization(sprintf('reorderNestedElements::%s::%s', $owner->getCanonicalId(), $attribute))
        ) {
            return;
        }

        abort(403, 'User is not authorized to perform this action');
    }

    public function offset(): int
    {
        $this->validateReorder();

        abort_if(is_null($this->offset), 400, 'Invalid offset param');

        return $this->offset;
    }

    public function nestedElement(): NestedElementInterface
    {
        if ($this->nestedElement) {
            return $this->nestedElement;
        }

        $this->validate([
            'elementId' => ['required', 'integer'],
        ]);

        $elementId = $this->integer('elementId');

        $nestedElements = $this->nestedElements();

        if ($nestedElements instanceof ElementQueryInterface) {
            $element = $nestedElements
                ->id($elementId)
                ->status(null)
                ->drafts(null)
                ->provisionalDrafts(null)
                ->one();
        } else {
            $element = $nestedElements->first(
                fn (ElementInterface $element) => (
                    $element->id === $elementId ||
                    $element->getCanonicalId() === $elementId
                )
            );
        }

        abort_if(! $element instanceof NestedElementInterface, 400, 'Invalid elementId param');

        return $this->nestedElement = $element;
    }

    private function attribute(): string
    {
        return (string) $this->input('attribute');
    }

    private function authorizeNestedElements(ElementInterface $owner): void
    {
        $attribute = $this->attribute();

        if (SessionAuth::checkAuthorization(sprintf('manageNestedElements::%s::%s', $owner->id, $attribute))) {
            return;
        }

        if (
            $owner->id !== $owner->getCanonicalId() &&
            SessionAuth::checkAuthorization(sprintf('manageNestedElements::%s::%s', $owner->getCanonicalId(), $attribute))
        ) {
            return;
        }

        abort(403, 'User is not authorized to perform this action');
    }

    private function validateReorder(): void
    {
        if (! is_null($this->elementIds) && ! is_null($this->offset)) {
            return;
        }

        $this->validate([
            'elementIds' => ['required', 'array'],
            'offset' => ['required', 'integer'],
        ]);

        $this->elementIds = array_map(fn ($id) => (int) $id, $this->array('elementIds'));
        $this->offset = $this->integer('offset');
    }
}
